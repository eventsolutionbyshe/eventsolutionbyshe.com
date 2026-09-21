<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !$conn) {
    die("Database connection failed.");
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| REQUEST DATA
|--------------------------------------------------------------------------
*/

$bookingId =
    isset($_GET['booking_id'])
        ? (int) $_GET['booking_id']
        : 0;

$bookingType =
    isset($_GET['booking_type'])
        ? strtolower(trim($_GET['booking_type']))
        : 'regular';

if (
    $bookingType !== 'wedding' &&
    $bookingType !== 'regular'
) {
    $bookingType = 'regular';
}


if ($bookingId <= 0) {
    die("Invalid booking.");
}


/*
|--------------------------------------------------------------------------
| EVENT DATA
|--------------------------------------------------------------------------
*/

$event = null;


/*
|--------------------------------------------------------------------------
| REGULAR BOOKING
|--------------------------------------------------------------------------
*/

if ($bookingType === 'regular') {

    $stmt = $conn->prepare("
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

    if (!$stmt) {
        die("Failed to prepare booking query.");
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


/*
|--------------------------------------------------------------------------
| WEDDING BOOKING
|--------------------------------------------------------------------------
*/

} else {

    $stmt = $conn->prepare("
        SELECT
            bw.id,
            bw.bride_name,
            bw.groom_name,
            bw.event_date,
            bw.start_time,
            bw.guest_count,
            bw.church,
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

    if (!$stmt) {
        die("Failed to prepare wedding query.");
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


    if ($event) {

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
}


/*
|--------------------------------------------------------------------------
| EVENT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$event) {
    die("Event not found or you do not have permission to access it.");
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status =
    strtolower(
        trim(
            $event['status'] ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| ONLY CONFIRMED
|--------------------------------------------------------------------------
*/

if ($status !== 'confirmed') {

    $errorMessage =
        "Only confirmed events can create invitations.";

} else {

    $errorMessage = '';
}


/*
|--------------------------------------------------------------------------
| EVENT VALUES
|--------------------------------------------------------------------------
*/

$eventName =
    $event['event_name'] ??
    'Event';

$eventDate =
    $event['event_date'] ??
    '';

$startTime =
    $event['start_time'] ??
    '';

$venueName =
    $event['venue_name'] ??
    'Not specified';

$packageName =
    $event['package_name'] ??
    'Not specified';

$eventTypeName =
    $event['event_type_name'] ??
    'Event';

$currentGuestCount =
    max(
        1,
        (int) (
            $event['guest_count'] ??
            1
        )
    );

$invitationToken =
    trim(
        $event['invitation_token'] ??
        ''
    );


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

$success = false;
$invitationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF
     */
    $postedCsrf =
        $_POST['csrf_token'] ??
        '';

    if (
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $errorMessage =
            "Security verification failed. Please refresh the page.";

    } elseif ($status !== 'confirmed') {

        $errorMessage =
            "Only confirmed events can create invitations.";

    } else {

        /*
         * Guest capacity
         */
        $guestCount =
            isset($_POST['guest_count'])
                ? (int) $_POST['guest_count']
                : 0;


        if ($guestCount < 1) {

            $errorMessage =
                "Number of guests must be at least 1.";

        } elseif ($guestCount > 10000) {

            $errorMessage =
                "Number of guests is too large.";

        } else {

            /*
             * Update existing guest_count.
             *
             * We use the existing booking guest_count
             * as the invitation capacity.
             */

            if ($bookingType === 'regular') {

                $updateStmt =
                    $conn->prepare("
                        UPDATE bookings
                        SET
                            guest_count = ?
                        WHERE
                            id = ?
                            AND user_id = ?
                    ");

            } else {

                $updateStmt =
                    $conn->prepare("
                        UPDATE booking_wedding
                        SET
                            guest_count = ?
                        WHERE
                            id = ?
                            AND user_id = ?
                    ");
            }


            if (!$updateStmt) {

                $errorMessage =
                    "Failed to update guest count.";

            } else {

                $updateStmt->bind_param(
                    "iii",
                    $guestCount,
                    $bookingId,
                    $userId
                );

                if (!$updateStmt->execute()) {

                    $errorMessage =
                        "Failed to save guest count.";

                    $updateStmt->close();

                } else {

                    $updateStmt->close();


                    /*
                     * Generate event invitation token
                     * if it doesn't exist.
                     */

                    if ($invitationToken === '') {

                        $newToken = '';

                        do {

                            $newToken =
                                bin2hex(
                                    random_bytes(32)
                                );


                            if ($bookingType === 'regular') {

                                $checkStmt =
                                    $conn->prepare("
                                        SELECT id
                                        FROM bookings
                                        WHERE invitation_token = ?
                                        LIMIT 1
                                    ");

                            } else {

                                $checkStmt =
                                    $conn->prepare("
                                        SELECT id
                                        FROM booking_wedding
                                        WHERE invitation_token = ?
                                        LIMIT 1
                                    ");
                            }


                            $exists =
                                false;


                            if ($checkStmt) {

                                $checkStmt->bind_param(
                                    "s",
                                    $newToken
                                );

                                $checkStmt->execute();

                                $checkResult =
                                    $checkStmt->get_result();

                                $exists =
                                    $checkResult->num_rows > 0;

                                $checkStmt->close();
                            }

                        } while ($exists);


                        /*
                         * Save token.
                         */

                        if ($bookingType === 'regular') {

                            $tokenStmt =
                                $conn->prepare("
                                    UPDATE bookings
                                    SET invitation_token = ?
                                    WHERE
                                        id = ?
                                        AND user_id = ?
                                ");

                        } else {

                            $tokenStmt =
                                $conn->prepare("
                                    UPDATE booking_wedding
                                    SET invitation_token = ?
                                    WHERE
                                        id = ?
                                        AND user_id = ?
                                ");
                        }


                        if (!$tokenStmt) {

                            $errorMessage =
                                "Failed to create invitation token.";

                        } else {

                            $tokenStmt->bind_param(
                                "sii",
                                $newToken,
                                $bookingId,
                                $userId
                            );

                            if (!$tokenStmt->execute()) {

                                $errorMessage =
                                    "Failed to save invitation token.";

                            } else {

                                $invitationToken =
                                    $newToken;
                            }

                            $tokenStmt->close();
                        }
                    }


                    /*
                     * Build invitation link.
                     */

                    if (
                        $errorMessage === '' &&
                        $invitationToken !== ''
                    ) {

                        /*
                         * Determine project root.
                         *
                         * This file is inside /invite/.
                         */

                        $scheme =
                            (
                                !empty($_SERVER['HTTPS']) &&
                                $_SERVER['HTTPS'] !== 'off'
                            )
                                ? 'https'
                                : 'http';

                        $rootPath =
                            dirname(
                                dirname(
                                    $_SERVER['SCRIPT_NAME']
                                )
                            );

                        $rootPath =
                            str_replace(
                                '\\',
                                '/',
                                $rootPath
                            );

                        $rootPath =
                            rtrim(
                                $rootPath,
                                '/'
                            );


                        $invitationLink =
                            $scheme .
                            '://' .
                            $_SERVER['HTTP_HOST'] .
                            $rootPath .
                            '/invite/view_invitation.php?token=' .
                            rawurlencode(
                                $invitationToken
                            );


                        /*
                         * Refresh displayed capacity.
                         */

                        $currentGuestCount =
                            $guestCount;

                        $event['guest_count'] =
                            $guestCount;

                        $success = true;
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| IF TOKEN ALREADY EXISTS
|--------------------------------------------------------------------------
|
| The event has already had an invitation created.
|
*/

if (
    !$success &&
    $invitationToken !== ''
) {

    $scheme =
        (
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        )
            ? 'https'
            : 'http';

    $rootPath =
        dirname(
            dirname(
                $_SERVER['SCRIPT_NAME']
            )
        );

    $rootPath =
        str_replace(
            '\\',
            '/',
            $rootPath
        );

    $rootPath =
        rtrim(
            $rootPath,
            '/'
        );

    $invitationLink =
        $scheme .
        '://' .
        $_SERVER['HTTP_HOST'] .
        $rootPath .
        '/invite/view_invitation.php?token=' .
        rawurlencode(
            $invitationToken
        );
}


/*
|--------------------------------------------------------------------------
| FORMAT DISPLAY DATE/TIME
|--------------------------------------------------------------------------
*/

$displayDate =
    !empty($eventDate)
        ? date(
            'F d, Y',
            strtotime($eventDate)
        )
        : 'Not specified';

$displayTime =
    !empty($startTime)
        ? date(
            'h:i A',
            strtotime($startTime)
        )
        : 'Not specified';

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
        Create Invitation
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

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family:
                "Poppins",
                sans-serif;

            color: #333;

            background:
                linear-gradient(
                    180deg,
                    #fffdfb,
                    #fcfaf7
                );
        }

        .page {
            padding: 22px;
        }

        .event-summary {
            padding: 20px;

            background:
                linear-gradient(
                    135deg,
                    #fffaf5,
                    #f8eee5
                );

            border:
                1px solid
                rgba(185, 148, 69, 0.18);

            border-radius: 10px;

            margin-bottom: 18px;
        }

        .eyebrow {
            color: #9d7a32;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 2px;
        }

        .event-summary h1 {
            margin: 5px 0 15px;

            color: #181818;

            font-size: 23px;

            line-height: 1.3;
        }

        .event-info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 10px;
        }

        .info-item {
            padding: 11px;

            background:
                rgba(255, 255, 255, 0.78);

            border:
                1px solid
                rgba(185, 148, 69, 0.10);

            border-radius: 7px;
        }

        .info-label {
            display: block;

            margin-bottom: 3px;

            color: #8b8177;

            font-size: 9px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }

        .info-value {
            color: #242424;

            font-size: 11px;

            font-weight: 600;
        }

        .builder-card {
            padding: 20px;

            background: white;

            border:
                1px solid
                rgba(185, 148, 69, 0.18);

            border-radius: 10px;
        }

        .builder-card h2 {
            margin: 0 0 5px;

            color: #181818;

            font-size: 17px;
        }

        .builder-card p {
            margin: 0 0 18px;

            color: #777;

            font-size: 11px;

            line-height: 1.7;
        }

        .guest-control {
            display: flex;

            align-items: center;

            gap: 10px;
        }

        .guest-control button {
            width: 40px;
            height: 40px;

            border:
                1px solid
                #ded3c7;

            border-radius: 7px;

            background: #fff;

            color: #181818;

            font-size: 20px;

            cursor: pointer;
        }

        .guest-control button:hover {
            border-color: #b99445;

            background: #fffaf4;
        }

        .guest-control input {
            width: 90px;
            height: 40px;

            text-align: center;

            border:
                1px solid
                #ded3c7;

            border-radius: 7px;

            outline: none;

            color: #181818;

            font-size: 14px;

            font-weight: 600;
        }

        .guest-control input:focus {
            border-color: #b99445;

            box-shadow:
                0 0 0 3px
                rgba(185, 148, 69, 0.10);
        }

        .generate-button {
            width: 100%;

            min-height: 44px;

            margin-top: 18px;

            border: 0;

            border-radius: 7px;

            background:
                linear-gradient(
                    135deg,
                    #b99445,
                    #9d7a32
                );

            color: white;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }

        .generate-button:hover {
            opacity: 0.94;
        }

        .alert {
            padding: 11px 13px;

            margin-bottom: 16px;

            border-radius: 7px;

            font-size: 11px;

            line-height: 1.6;
        }

        .alert-error {
            color: #9c4141;

            background: #fff0f0;

            border:
                1px solid
                #f1d0d0;
        }

        .alert-success {
            color: #367653;

            background: #eef8f1;

            border:
                1px solid
                #cfe7d6;
        }

        .generated-link-card {
            margin-top: 18px;

            padding: 16px;

            background: #fcf8f2;

            border:
                1px solid
                rgba(185, 148, 69, 0.22);

            border-radius: 8px;
        }

        .generated-link-card h3 {
            margin: 0 0 5px;

            color: #181818;

            font-size: 14px;
        }

        .generated-link-card p {
            margin: 0 0 12px;

            color: #777;

            font-size: 10px;
        }

        .link-row {
            display: flex;

            gap: 7px;
        }

        .link-input {
            min-width: 0;

            flex: 1;

            height: 40px;

            padding: 0 10px;

            border:
                1px solid
                #ddd2c6;

            border-radius: 6px;

            background: white;

            color: #555;

            font-size: 10px;
        }

        .copy-button {
            height: 40px;

            padding: 0 13px;

            border: 0;

            border-radius: 6px;

            background: #181818;

            color: white;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            cursor: pointer;

            font-size: 10px;

            font-weight: 600;
        }

        .share-buttons {
            display: flex;

            flex-wrap: wrap;

            gap: 7px;

            margin-top: 10px;
        }

        .share-button {
            min-height: 35px;

            padding: 0 11px;

            border:
                1px solid
                #dfd5ca;

            border-radius: 6px;

            background: white;

            color: #333;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            cursor: pointer;

            font-size: 10px;

            font-weight: 600;
        }

        .share-button:hover {
            border-color: #b99445;

            background: #fffaf4;
        }

        .guest-list-link {
            margin-top: 12px;

            display: inline-flex;

            align-items: center;

            gap: 5px;

            color: #9d7a32;

            text-decoration: none;

            font-size: 10px;

            font-weight: 600;
        }

        .guest-list-link:hover {
            text-decoration: underline;
        }

        .material-symbols-outlined {
            font-size: 16px;
        }

        @media (max-width: 600px) {

            .page {
                padding: 14px;
            }

            .event-info-grid {
                grid-template-columns: 1fr;
            }

            .link-row {
                flex-direction: column;
            }

            .copy-button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="page">


    <!-- =====================================================
         ERROR
    ====================================================== -->

    <?php if (!empty($errorMessage)): ?>

        <div class="alert alert-error">

            <?= htmlspecialchars($errorMessage) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUCCESS
    ====================================================== -->

    <?php if ($success): ?>

        <div class="alert alert-success">

            Your invitation link has been generated successfully.
            You can copy or share it below.

        </div>

    <?php endif; ?>


    <!-- =====================================================
         EVENT INFORMATION
    ====================================================== -->

    <div class="event-summary">

        <span class="eyebrow">
            EVENT INFORMATION
        </span>

        <h1>
            <?= htmlspecialchars($eventName) ?>
        </h1>


        <div class="event-info-grid">

            <div class="info-item">

                <span class="info-label">
                    Event Type
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($eventTypeName) ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Date
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($displayDate) ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Time
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($displayTime) ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Venue
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($venueName) ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Package
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($packageName) ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Guest Capacity
                </span>

                <span class="info-value">
                    <?= number_format($currentGuestCount) ?>
                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         BUILDER
    ====================================================== -->

    <div class="builder-card">

        <h2>
            Number of Guests
        </h2>

        <p>
            Set the maximum number of guests who can register
            through this invitation link.
        </p>


        <?php if ($status === 'confirmed'): ?>

            <form
                method="POST"
                action=""
                id="invitationForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken) ?>"
                >


                <div class="guest-control">

                    <button
                        type="button"
                        id="decreaseGuests"
                        aria-label="Decrease guests"
                    >
                        −
                    </button>


                    <input
                        type="number"
                        name="guest_count"
                        id="guestCount"
                        value="<?= $currentGuestCount ?>"
                        min="1"
                        max="10000"
                        required
                    >


                    <button
                        type="button"
                        id="increaseGuests"
                        aria-label="Increase guests"
                    >
                        +
                    </button>

                </div>


                <button
                    type="submit"
                    class="generate-button"
                >

                    <span class="material-symbols-outlined">
                        link
                    </span>

                    Generate Link

                </button>

            </form>

        <?php endif; ?>


        <!-- =================================================
             GENERATED LINK
        ================================================== -->

        <?php if (!empty($invitationLink)): ?>

            <div class="generated-link-card">

                <h3>
                    Invitation Link
                </h3>

                <p>
                    Share this link with your guests.
                    The invitation remains available while
                    your event is active.
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


                <!-- SHARE -->

                <div class="share-buttons">

                    <button
                        type="button"
                        class="share-button"
                        id="shareButton"
                    >

                        <span class="material-symbols-outlined">
                            share
                        </span>

                        Share

                    </button>


                    <!-- GMAIL -->

                    <a
                        href="#"
                        class="share-button"
                        id="gmailButton"
                        target="_blank"
                        rel="noopener"
                    >

                        <span class="material-symbols-outlined">
                            mail
                        </span>

                        Gmail

                    </a>


                    <!-- WHATSAPP -->

                    <a
                        href="#"
                        class="share-button"
                        id="whatsappButton"
                        target="_blank"
                        rel="noopener"
                    >

                        WhatsApp

                    </a>


                    <!-- FACEBOOK -->

                    <a
                        href="#"
                        class="share-button"
                        id="facebookButton"
                        target="_blank"
                        rel="noopener"
                    >

                        Facebook

                    </a>

                </div>


                <a
                    href="../guest_list.php?booking_id=<?= $bookingId ?>&booking_type=<?= urlencode($bookingType) ?>"
                    class="guest-list-link"
                    target="_parent"
                >

                    <span class="material-symbols-outlined">
                        groups
                    </span>

                    Open Guest List

                </a>

            </div>

        <?php endif; ?>

    </div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const guestInput =
            document.getElementById(
                "guestCount"
            );

        const decreaseButton =
            document.getElementById(
                "decreaseGuests"
            );

        const increaseButton =
            document.getElementById(
                "increaseGuests"
            );


        if (guestInput) {

            function normalizeGuestValue() {

                let value =
                    parseInt(
                        guestInput.value,
                        10
                    );

                if (
                    Number.isNaN(value) ||
                    value < 1
                ) {
                    value = 1;
                }

                if (value > 10000) {
                    value = 10000;
                }

                guestInput.value =
                    value;
            }


            if (decreaseButton) {

                decreaseButton.addEventListener(
                    "click",
                    function () {

                        let value =
                            parseInt(
                                guestInput.value,
                                10
                            ) || 1;

                        value--;

                        if (value < 1) {
                            value = 1;
                        }

                        guestInput.value =
                            value;
                    }
                );
            }


            if (increaseButton) {

                increaseButton.addEventListener(
                    "click",
                    function () {

                        let value =
                            parseInt(
                                guestInput.value,
                                10
                            ) || 1;

                        value++;

                        if (value > 10000) {
                            value = 10000;
                        }

                        guestInput.value =
                            value;
                    }
                );
            }


            guestInput.addEventListener(
                "change",
                normalizeGuestValue
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INVITATION LINK
        |--------------------------------------------------------------------------
        */

        const linkInput =
            document.getElementById(
                "invitationLink"
            );

        const copyButton =
            document.getElementById(
                "copyLinkButton"
            );

        const shareButton =
            document.getElementById(
                "shareButton"
            );

        const gmailButton =
            document.getElementById(
                "gmailButton"
            );

        const whatsappButton =
            document.getElementById(
                "whatsappButton"
            );

        const facebookButton =
            document.getElementById(
                "facebookButton"
            );


        if (linkInput) {

            const invitationLink =
                linkInput.value;


            /*
             * COPY
             */

            if (copyButton) {

                copyButton.addEventListener(
                    "click",
                    async function () {

                        try {

                            await navigator.clipboard.writeText(
                                invitationLink
                            );

                            copyButton.innerHTML = `
                                <span class="material-symbols-outlined">
                                    check
                                </span>
                                Copied
                            `;

                            setTimeout(
                                function () {

                                    copyButton.innerHTML = `
                                        <span class="material-symbols-outlined">
                                            content_copy
                                        </span>
                                        Copy
                                    `;

                                },
                                1800
                            );

                        } catch (error) {

                            linkInput.select();

                            document.execCommand(
                                "copy"
                            );

                        }

                    }
                );
            }


            /*
             * WEB SHARE
             */

            if (shareButton) {

                shareButton.addEventListener(
                    "click",
                    async function () {

                        if (
                            navigator.share
                        ) {

                            try {

                                await navigator.share(
                                    {
                                        title:
                                            "Event Invitation",

                                        text:
                                            "You are invited to my event.",

                                        url:
                                            invitationLink
                                    }
                                );

                            } catch (error) {

                                /*
                                 * User cancelled sharing.
                                 */

                            }

                        } else {

                            try {

                                await navigator.clipboard.writeText(
                                    invitationLink
                                );

                                alert(
                                    "Invitation link copied. You can paste it into Messenger or another app."
                                );

                            } catch (error) {

                                alert(
                                    invitationLink
                                );

                            }

                        }
                    }
                );
            }


            /*
             * GMAIL
             */

            if (gmailButton) {

                const subject =
                    encodeURIComponent(
                        "You're Invited!"
                    );

                const body =
                    encodeURIComponent(
                        "You are invited to an event.\n\n" +
                        "Please open the invitation link below:\n\n" +
                        invitationLink
                    );

                gmailButton.href =
                    "https://mail.google.com/mail/" +
                    "?view=cm" +
                    "&fs=1" +
                    "&su=" +
                    subject +
                    "&body=" +
                    body;
            }


            /*
             * WHATSAPP
             */

            if (whatsappButton) {

                whatsappButton.href =
                    "https://wa.me/?text=" +
                    encodeURIComponent(
                        "You're invited! View the event invitation here:\n\n" +
                        invitationLink
                    );
            }


            /*
             * FACEBOOK
             */

            if (facebookButton) {

                facebookButton.href =
                    "https://www.facebook.com/sharer/sharer.php?u=" +
                    encodeURIComponent(
                        invitationLink
                    );
            }

        }


        /*
        |--------------------------------------------------------------------------
        | NOTIFY PARENT
        |--------------------------------------------------------------------------
        */

        <?php if ($success): ?>

        try {

            window.parent.postMessage(
                {
                    type:
                        "invitation-created",

                    eventId:
                        <?= (int) $bookingId ?>,

                    bookingType:
                        <?= json_encode($bookingType) ?>,

                    invitationLink:
                        <?= json_encode($invitationLink) ?>
                },
                "*"
            );

        } catch (error) {

            console.error(
                "Unable to notify parent window.",
                error
            );

        }

        <?php endif; ?>

    }
);

</script>

</body>
</html>