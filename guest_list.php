<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        "Location: auth/login.php"
    );

    exit;
}

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !$conn) {

    die("Database connection failed.");

}


/*
|--------------------------------------------------------------------------
| REQUEST
|--------------------------------------------------------------------------
*/

$bookingId =
    isset($_GET['booking_id'])
        ? (int) $_GET['booking_id']
        : 0;

$bookingType =
    isset($_GET['booking_type'])
        ? strtolower(
            trim(
                $_GET['booking_type']
            )
        )
        : 'regular';


if (
    $bookingType !== 'regular' &&
    $bookingType !== 'wedding'
) {

    $bookingType =
        'regular';

}


if ($bookingId <= 0) {

    die("Invalid booking.");

}


/*
|--------------------------------------------------------------------------
| INVITATION TABLE
|--------------------------------------------------------------------------
|
| Regular:
|   bookings       -> invitations
|
| Wedding:
|   booking_wedding -> invitation_wedding
|
|--------------------------------------------------------------------------
*/

$invitationTable =
    ($bookingType === 'wedding')
        ? 'invitation_wedding'
        : 'invitations';


/*
|--------------------------------------------------------------------------
| AJAX FETCH
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'fetch_guests'
) {

    header(
        "Content-Type: application/json; charset=utf-8"
    );


    /*
     * EVENT OWNERSHIP
     */

    $event = null;


    if ($bookingType === 'regular') {

        $stmt =
            $conn->prepare("

                SELECT

                    b.id,

                    b.event_name,

                    b.event_date,

                    b.start_time,

                    b.guest_count,

                    b.status,

                    b.invitation_token,

                    p.package_name,

                    v.venue_name,

                    et.name AS event_type_name

                FROM bookings b

                LEFT JOIN event_packages p

                    ON b.package_id = p.id

                LEFT JOIN venues v

                    ON b.venue_id = v.id

                LEFT JOIN event_types et

                    ON p.event_type_id = et.id

                WHERE

                    b.id = ?

                    AND b.user_id = ?

                LIMIT 1

            ");

    } else {

        $stmt =
            $conn->prepare("

                SELECT

                    bw.id,

                    bw.bride_name,

                    bw.groom_name,

                    bw.event_date,

                    bw.start_time,

                    bw.guest_count,

                    bw.status,

                    bw.invitation_token,

                    p.package_name,

                    v.venue_name

                FROM booking_wedding bw

                LEFT JOIN event_packages p

                    ON bw.package_id = p.id

                LEFT JOIN venues v

                    ON bw.venue_id = v.id

                WHERE

                    bw.id = ?

                    AND bw.user_id = ?

                LIMIT 1

            ");

    }


    if (!$stmt) {

        echo json_encode([

            "success" => false,

            "message" => "Failed to prepare event query."

        ]);

        exit;

    }


    $stmt->bind_param(

        "ii",

        $bookingId,

        $userId

    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $event =
        $result->fetch_assoc();


    $stmt->close();


    if (!$event) {

        echo json_encode([

            "success" => false,

            "message" => "Event not found."

        ]);

        exit;

    }


    /*
     * WEDDING NAME
     */

    if ($bookingType === 'wedding') {

        $event['event_name'] =
            trim(
                $event['bride_name'] .
                ' & ' .
                $event['groom_name'] .
                ' Wedding'
            );

        $event['event_type_name'] =
            'Wedding';

    }


    /*
     * GUEST CAPACITY
     */

    $capacity =
        max(
            1,
            (int) (
                $event['guest_count'] ??
                1
            )
        );


    /*
     * FETCH GUESTS
     *
     * Regular:
     * invitations
     *
     * Wedding:
     * invitation_wedding
     */

    $guestStmt =
        $conn->prepare("

            SELECT

                id,

                guest_name,

                guest_email,

                qrcode_image,

                status,

                confirmed_at,

                created_at

            FROM {$invitationTable}

            WHERE

                booking_id = ?

                AND guest_email IS NOT NULL

                AND guest_email <> ''

            ORDER BY id DESC

        ");


    $guests = [];


    if ($guestStmt) {

        $guestStmt->bind_param(

            "i",

            $bookingId

        );


        $guestStmt->execute();


        $guestResult =
            $guestStmt->get_result();


        while (

            $guest =
                $guestResult->fetch_assoc()

        ) {

            $guests[] = [

                "id" =>
                    (int) $guest['id'],

                "guest_name" =>
                    $guest['guest_name'],

                "guest_email" =>
                    $guest['guest_email'],

                "qrcode_image" =>
                    $guest['qrcode_image'],

                "status" =>
                    strtolower(
                        trim(
                            $guest['status'] ??
                            'pending'
                        )
                    ),

                "confirmed_at" =>
                    $guest['confirmed_at'],

                "created_at" =>
                    $guest['created_at']

            ];

        }


        $guestStmt->close();

    }


    /*
     * CONFIRMED COUNT
     *
     * Regular:
     * invitations
     *
     * Wedding:
     * invitation_wedding
     */

    $confirmedStmt =
        $conn->prepare("

            SELECT COUNT(*) AS total

            FROM {$invitationTable}

            WHERE

                booking_id = ?

                AND status = 'confirmed'

        ");


    $confirmedCount = 0;


    if ($confirmedStmt) {

        $confirmedStmt->bind_param(

            "i",

            $bookingId

        );


        $confirmedStmt->execute();


        $confirmedResult =
            $confirmedStmt->get_result();


        $confirmedRow =
            $confirmedResult->fetch_assoc();


        $confirmedCount =
            (int) (
                $confirmedRow['total'] ??
                0
            );


        $confirmedStmt->close();

    }


    /*
     * REMAINING
     */

    $remaining =
        max(
            0,
            $capacity -
            $confirmedCount
        );


    /*
     * RESPONSE
     */

    echo json_encode(

        [

            "success" => true,

            "event" => [

                "id" =>
                    (int) $event['id'],

                "event_name" =>
                    $event['event_name'] ??
                    'Event',

                "event_date" =>
                    $event['event_date'] ??
                    '',

                "start_time" =>
                    $event['start_time'] ??
                    '',

                "guest_count" =>
                    $capacity,

                "venue_name" =>
                    $event['venue_name'] ??
                    'Not specified',

                "package_name" =>
                    $event['package_name'] ??
                    'Not specified',

                "event_type_name" =>
                    $event['event_type_name'] ??
                    'Event',

                "status" =>
                    $event['status'] ??
                    '',

                "invitation_token" =>
                    $event['invitation_token'] ??
                    ''

            ],

            "capacity" =>
                $capacity,

            "confirmed" =>
                $confirmedCount,

            "remaining" =>
                $remaining,

            "guests" =>
                $guests

        ],

        JSON_UNESCAPED_SLASHES

    );

    exit;

}


/*
|--------------------------------------------------------------------------
| GET EVENT
|--------------------------------------------------------------------------
*/

$event = null;


if ($bookingType === 'regular') {

    $stmt =
        $conn->prepare("

            SELECT

                b.id,

                b.event_name,

                b.event_date,

                b.start_time,

                b.guest_count,

                b.status,

                b.invitation_token,

                p.package_name,

                v.venue_name,

                et.name AS event_type_name

            FROM bookings b

            LEFT JOIN event_packages p

                ON b.package_id = p.id

            LEFT JOIN venues v

                ON b.venue_id = v.id

            LEFT JOIN event_types et

                ON p.event_type_id = et.id

            WHERE

                b.id = ?

                AND b.user_id = ?

            LIMIT 1

        ");

} else {

    $stmt =
        $conn->prepare("

            SELECT

                bw.id,

                bw.bride_name,

                bw.groom_name,

                bw.event_date,

                bw.start_time,

                bw.guest_count,

                bw.status,

                bw.invitation_token,

                p.package_name,

                v.venue_name

            FROM booking_wedding bw

            LEFT JOIN event_packages p

                ON bw.package_id = p.id

            LEFT JOIN venues v

                ON bw.venue_id = v.id

            WHERE

                bw.id = ?

                AND bw.user_id = ?

            LIMIT 1

        ");

}


if (!$stmt) {

    die("Failed to prepare event query.");

}


$stmt->bind_param(

    "ii",

    $bookingId,

    $userId

);


$stmt->execute();


$result =
    $stmt->get_result();


$event =
    $result->fetch_assoc();


$stmt->close();


if (!$event) {

    die(
        "Event not found or you do not have permission to view it."
    );

}


/*
|--------------------------------------------------------------------------
| WEDDING
|--------------------------------------------------------------------------
*/

if ($bookingType === 'wedding') {

    $event['event_name'] =
        trim(
            $event['bride_name'] .
            ' & ' .
            $event['groom_name'] .
            ' Wedding'
        );

    $event['event_type_name'] =
        'Wedding';

}


/*
|--------------------------------------------------------------------------
| EVENT STATUS
|--------------------------------------------------------------------------
*/

$status =
    strtolower(
        trim(
            $event['status'] ??
            ''
        )
    );


/*
|--------------------------------------------------------------------------
| INVITATION TOKEN
|--------------------------------------------------------------------------
*/

$invitationToken =
    trim(
        $event['invitation_token'] ??
        ''
    );


if ($invitationToken === '') {

    die("

        <div style='
            font-family:Poppins,Arial,sans-serif;
            padding:60px;
            text-align:center;
        '>

            <h2>Invitation Not Created</h2>

            <p>
                Please create an invitation for this event first.
            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| BUILD LINK
|--------------------------------------------------------------------------
*/

$scheme =
    (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    )
        ? 'https'
        : 'http';


$basePath =
    rtrim(
        str_replace(
            '\\',
            '/',
            dirname(
                $_SERVER['SCRIPT_NAME']
            )
        ),
        '/'
    );


$invitationLink =
    $scheme .
    '://' .
    $_SERVER['HTTP_HOST'] .
    $basePath .
    '/invite/view_invitation.php?token=' .
    rawurlencode(
        $invitationToken
    );


/*
|--------------------------------------------------------------------------
| CAPACITY
|--------------------------------------------------------------------------
*/

$capacity =
    max(
        1,
        (int) (
            $event['guest_count'] ??
            1
        )
    );


/*
|--------------------------------------------------------------------------
| INITIAL GUEST DATA
|--------------------------------------------------------------------------
*/

$guestStmt =
    $conn->prepare("

        SELECT

            id,

            guest_name,

            guest_email,

            qrcode_image,

            status,

            confirmed_at,

            created_at

        FROM {$invitationTable}

        WHERE

            booking_id = ?

            AND guest_email IS NOT NULL

            AND guest_email <> ''

        ORDER BY id DESC

    ");


$guests = [];


if ($guestStmt) {

    $guestStmt->bind_param(

        "i",

        $bookingId

    );


    $guestStmt->execute();


    $guestResult =
        $guestStmt->get_result();


    while (

        $guest =
            $guestResult->fetch_assoc()

    ) {

        $guests[] =
            $guest;

    }


    $guestStmt->close();

}


/*
|--------------------------------------------------------------------------
| INITIAL COUNT
|--------------------------------------------------------------------------
*/

$confirmedStmt =
    $conn->prepare("

        SELECT COUNT(*) AS total

        FROM {$invitationTable}

        WHERE

            booking_id = ?

            AND status = 'confirmed'

    ");


$confirmedCount = 0;


if ($confirmedStmt) {

    $confirmedStmt->bind_param(

        "i",

        $bookingId

    );


    $confirmedStmt->execute();


    $confirmedResult =
        $confirmedStmt->get_result();


    $confirmedRow =
        $confirmedResult->fetch_assoc();


    $confirmedCount =
        (int) (
            $confirmedRow['total'] ??
            0
        );


    $confirmedStmt->close();

}


$remaining =
    max(
        0,
        $capacity -
        $confirmedCount
    );


/*
|--------------------------------------------------------------------------
| DATE/TIME
|--------------------------------------------------------------------------
*/

$eventName =
    $event['event_name'] ??
    'Event';


$displayDate =
    !empty($event['event_date'])
        ? date(
            'F d, Y',
            strtotime(
                $event['event_date']
            )
        )
        : 'Not specified';


$displayTime =
    !empty($event['start_time'])
        ? date(
            'h:i A',
            strtotime(
                $event['start_time']
            )
        )
        : 'Not specified';


$venueName =
    $event['venue_name'] ??
    'Not specified';


$packageName =
    $event['package_name'] ??
    'Not specified';


$eventTypeName =
    $event['event_type_name'] ??
    'Event';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Guest List -
        <?= htmlspecialchars($eventName) ?>
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
        rel="stylesheet"
    >

    <link
    rel="stylesheet"
    href="css/header.css"
>

<link
    rel="stylesheet"
    href="css/footer.css"
>

<link
    rel="stylesheet"
    href="css/guestlist.css"
>

</head>

<body>

    <?php include "includes/header.php"; ?>
    
<div class="page">

    <!-- =====================================================
         EVENT
    ====================================================== -->

    <div class="event-card">

        <div class="event-card-top">

            <div>

                <span class="eyebrow">
                    EVENT
                </span>

                <h2>
                    <?= htmlspecialchars($eventName) ?>
                </h2>

                <div class="event-type">
                    <?= htmlspecialchars($eventTypeName) ?>
                </div>

            </div>

        </div>


        <div class="event-info-grid">

            <div class="info-box">

                <span>
                    Date
                </span>

                <strong>
                    <?= htmlspecialchars($displayDate) ?>
                </strong>

            </div>


            <div class="info-box">

                <span>
                    Time
                </span>

                <strong>
                    <?= htmlspecialchars($displayTime) ?>
                </strong>

            </div>


            <div class="info-box">

                <span>
                    Venue
                </span>

                <strong>
                    <?= htmlspecialchars($venueName) ?>
                </strong>

            </div>


            <div class="info-box">

                <span>
                    Package
                </span>

                <strong>
                    <?= htmlspecialchars($packageName) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================================
         INVITATION LINK
    ====================================================== -->

    <div class="link-card">

        <h3>
            Invitation Link
        </h3>

        <p>
            Share this link with guests so they can register.
        </p>


        <div class="link-row">

            <input
                type="text"
                class="link-input"
                id="invitationLink"
                value="<?= htmlspecialchars($invitationLink) ?>"
                readonly
            >


            <button
                type="button"
                class="copy-button"
                id="copyLinkButton"
            >

                <span class="material-symbols-outlined">
                    content_copy
                </span>

                Copy

            </button>

        </div>


        <button
            type="button"
            class="share-button"
            id="shareButton"
        >

            <span class="material-symbols-outlined">
                share
            </span>

            Share Invitation

        </button>

    </div>


    <!-- =====================================================
         STATS
    ====================================================== -->

    <div class="stats">

        <div class="stat-card">

            <span class="stat-label">
                Total Guest Capacity
            </span>

            <div
                class="stat-number"
                id="capacityNumber"
            >
                <?= number_format($capacity) ?>
            </div>

        </div>


        <div class="stat-card confirmed">

            <span class="stat-label">
                Confirmed / Registered
            </span>

            <div
                class="stat-number"
                id="confirmedNumber"
            >
                <?= number_format($confirmedCount) ?>
            </div>

        </div>


        <div class="stat-card remaining">

            <span class="stat-label">
                Remaining
            </span>

            <div
                class="stat-number"
                id="remainingNumber"
            >
                <?= number_format($remaining) ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         GUEST TABLE
    ====================================================== -->

    <div class="guest-card">

        <div class="guest-card-header">

            <h2>
                Registered Guests
            </h2>

            <div class="live-status">

                <span class="live-dot"></span>

                Live Updates

            </div>

        </div>


        <div
            class="table-wrapper"
            id="guestTableWrapper"
        >

            <?php if (!empty($guests)): ?>

                <table
                    class="guest-table"
                    id="guestTable"
                >

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Guest
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Confirmed At
                            </th>

                            <th>
                                QR
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="guestTableBody"
                    >

                        <?php
                        $number = 1;
                        ?>


                        <?php foreach ($guests as $guest): ?>

                            <?php

                            $guestStatus =
                                strtolower(
                                    trim(
                                        $guest['status'] ??
                                        'pending'
                                    )
                                );


                            $confirmedAt =
                                $guest['confirmed_at'] ??
                                '';

                            ?>


                            <tr
                                data-guest-id="<?= (int) $guest['id'] ?>"
                            >

                                <td>

                                    <span class="guest-number">
                                        <?= $number++ ?>
                                    </span>

                                </td>


                                <td>

                                    <div class="guest-name">

                                        <?= htmlspecialchars(
                                            $guest['guest_name']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <div class="guest-email">

                                        <?= htmlspecialchars(
                                            $guest['guest_email']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="status status-<?= htmlspecialchars($guestStatus) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $guestStatus
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= !empty($confirmedAt)

                                        ? htmlspecialchars(

                                            date(

                                                'M d, Y h:i A',

                                                strtotime(
                                                    $confirmedAt
                                                )

                                            )

                                        )

                                        : '—'

                                    ?>

                                </td>


                                <td>

                                    <?php if (!empty($guest['qrcode_image'])): ?>

                                        <a
                                            href="<?= htmlspecialchars($guest['qrcode_image']) ?>"
                                            class="qr-button"
                                            target="_blank"
                                            rel="noopener"
                                            title="View QR code"
                                        >

                                            <span class="material-symbols-outlined">
                                                qr_code_2
                                            </span>

                                        </a>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>


            <?php else: ?>


                <div
                    class="empty-guests"
                    id="emptyGuests"
                >

                    <span class="material-symbols-outlined">
                        groups
                    </span>

                    No guests have registered yet.

                </div>


                <table
                    class="guest-table"
                    id="guestTable"
                    style="display:none;"
                >

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Guest</th>

                            <th>Email</th>

                            <th>Status</th>

                            <th>Confirmed At</th>

                            <th>QR</th>

                        </tr>

                    </thead>


                    <tbody
                        id="guestTableBody"
                    ></tbody>

                </table>


            <?php endif; ?>

        </div>

    </div>

</div>


<script src="js/guestlist.js"defer></script>
<script src="js/header.js"></script>
<?php include "includes/footer.php"; ?>

</body>

</html>