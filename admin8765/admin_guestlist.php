<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   REQUIRE ADMIN LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   TOKEN FROM URL
========================================================= */

$eventToken = trim(
    $_GET["token"] ?? ""
);


/* =========================================================
   EVENT LINK
========================================================= */

$eventLink = "";

if ($eventToken !== "") {

    $eventLink =
        "http://localhost/eventsolutions/invite/view_invitation.php?token="
        . urlencode($eventToken);
}


/* =========================================================
   AJAX REQUESTS
========================================================= */

if (isset($_GET["ajax"])) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );


    /* =====================================================
       GET EVENT GUEST LIST BY TOKEN
    ===================================================== */

    if ($_GET["ajax"] === "guest_list") {

        $token = trim(
            $_GET["token"] ?? ""
        );

        $search = trim(
            $_GET["search"] ?? ""
        );

        $status = trim(
            $_GET["status"] ?? ""
        );


        if ($token === "") {

            echo json_encode([
                "success" => false,
                "message" => "No event token was provided."
            ]);

            exit;
        }


        /* =================================================
           FIRST FIND THE BOOKING BELONGING TO THIS TOKEN
        ================================================= */

        $bookingStmt = $conn->prepare("

            SELECT

                b.id AS booking_id,

                COALESCE(
                    NULLIF(b.event_name, ''),
                    'Untitled Event'
                ) AS event_name,

                b.event_date,

                b.start_time,

                b.phone,

                b.guest_count,

                b.invitation_token,

                ep.package_name,

                et.name AS event_type,

                v.venue_name

            FROM bookings b

            LEFT JOIN event_packages ep
                ON ep.id = b.package_id

            LEFT JOIN event_types et
                ON et.id = ep.event_type_id

            LEFT JOIN venues v
                ON v.id = b.venue_id

            WHERE b.invitation_token = ?

            LIMIT 1

        ");


        if (!$bookingStmt) {

            echo json_encode([
                "success" => false,
                "message" => $conn->error
            ]);

            exit;
        }


        $bookingStmt->bind_param(
            "s",
            $token
        );

        $bookingStmt->execute();

        $bookingResult =
            $bookingStmt->get_result();

        $event =
            $bookingResult->fetch_assoc();

        $bookingStmt->close();


        /* =================================================
           TOKEN NOT FOUND
        ================================================= */

        if (!$event) {

            echo json_encode([
                "success" => false,
                "message" =>
                "Invalid event token. The event could not be found."
            ]);

            exit;
        }


        $bookingId =
            (int)$event["booking_id"];


        /* =================================================
           LOAD ONLY GUESTS FOR THIS BOOKING
        ================================================= */

        $sql = "

            SELECT

                i.id AS invitation_id,

                i.booking_id,

                i.guest_name,

                i.guest_email,

                i.status AS guest_status,

                i.confirmed_at,

                i.created_at,

                COALESCE(
                    NULLIF(b.event_name, ''),
                    'Untitled Event'
                ) AS event_name,

                b.event_date,

                b.start_time,

                b.phone,

                b.guest_count,

                ep.package_name,

                et.name AS event_type,

                v.venue_name

            FROM invitations i

            INNER JOIN bookings b
                ON b.id = i.booking_id

            LEFT JOIN event_packages ep
                ON ep.id = b.package_id

            LEFT JOIN event_types et
                ON et.id = ep.event_type_id

            LEFT JOIN venues v
                ON v.id = b.venue_id

            WHERE i.booking_id = ?

        ";


        $params = [
            $bookingId
        ];

        $types = "i";


        /* =================================================
           SEARCH
        ================================================= */

        if ($search !== "") {

            $sql .= "

                AND (

                    i.guest_name LIKE ?

                    OR i.guest_email LIKE ?

                    OR b.phone LIKE ?

                    OR b.event_name LIKE ?


                    OR ep.package_name LIKE ?

                    OR v.venue_name LIKE ?

                )

            ";


            $searchValue =
                "%" . $search . "%";


            $params[] =
                $searchValue;

            $params[] =
                $searchValue;

            $params[] =
                $searchValue;

            $params[] =
                $searchValue;

            $params[] =
                $searchValue;

            $params[] =
                $searchValue;

            $params[] =
                $searchValue;


            $types .= "sssssss";
        }


        /* =================================================
           STATUS FILTER
        ================================================= */

        if ($status !== "") {

            $sql .= "
                AND i.status = ?
            ";

            $params[] =
                $status;

            $types .= "s";
        }


        /* =================================================
           LATEST CONFIRMED GUEST FIRST
        ================================================= */

        $sql .= "

            ORDER BY

                CASE

                    WHEN i.status = 'confirmed'
                    THEN 0

                    WHEN i.status = 'pending'
                    THEN 1

                    ELSE 2

                END ASC,

                i.confirmed_at DESC,

                i.created_at DESC,

                i.id DESC

        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => $conn->error
            ]);

            exit;
        }


        $stmt->bind_param(
            $types,
            ...$params
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        $guests = [];


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $guests[] = [

                "invitation_id" =>
                (int)$row["invitation_id"],

                "booking_id" =>
                (int)$row["booking_id"],

                "guest_name" =>
                $row["guest_name"],

                "guest_email" =>
                $row["guest_email"],

                "status" =>
                $row["guest_status"]
                    ?: "pending",

                "confirmed_at" =>
                $row["confirmed_at"],

                "event_name" =>
                $row["event_name"],

                "event_date" =>
                $row["event_date"],

                "start_time" =>
                $row["start_time"],

                "guest_count" =>
                (int)$row["guest_count"],

                "package_name" =>
                $row["package_name"],

                "event_type" =>
                $row["event_type"],

                "venue_name" =>
                $row["venue_name"],

                "is_wedding" =>
                false
            ];
        }


        $stmt->close();


        /* =================================================
           EVENT-SPECIFIC COUNTS
        ================================================= */

        $countStmt =
            $conn->prepare("

                SELECT

                    COUNT(*) AS total,

                    SUM(
                        CASE
                            WHEN status = 'confirmed'
                            THEN 1
                            ELSE 0
                        END
                    ) AS confirmed,

                    SUM(
                        CASE
                            WHEN
                                status = 'pending'
                                OR status IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS pending,

                    SUM(
                        CASE
                            WHEN status = 'declined'
                            THEN 1
                            ELSE 0
                        END
                    ) AS declined

                FROM invitations

                WHERE booking_id = ?

            ");


        if (!$countStmt) {

            echo json_encode([
                "success" => false,
                "message" => $conn->error
            ]);

            exit;
        }


        $countStmt->bind_param(
            "i",
            $bookingId
        );

        $countStmt->execute();

        $countResult =
            $countStmt->get_result();

        $counts =
            $countResult->fetch_assoc();

        $countStmt->close();


        /* =================================================
           RETURN EVENT DATA AND STATISTICS
        ================================================= */

        echo json_encode([

            "success" => true,

            "token" =>
            $token,

            "event" => [

                "booking_id" =>
                $bookingId,

                "event_name" =>
                $event["event_name"],

                "event_date" =>
                $event["event_date"],

                "start_time" =>
                $event["start_time"],

                "phone" =>
                $event["phone"],

                "guest_count" =>
                (int)(
                    $event["guest_count"]
                    ?? 0
                ),

                "package_name" =>
                $event["package_name"],

                "event_type" =>
                $event["event_type"],

                "venue_name" =>
                $event["venue_name"]
            ],

            "stats" => [

                /*
                 * Guest Number comes directly from
                 * bookings.guest_count.
                 */

                "total" =>
                (int)(
                    $event["guest_count"]
                    ?? 0
                ),

                "confirmed" =>
                (int)(
                    $counts["confirmed"]
                    ?? 0
                ),

                "pending" =>
                (int)(
                    $counts["pending"]
                    ?? 0
                ),

                "declined" =>
                (int)(
                    $counts["declined"]
                    ?? 0
                )
            ],

            "guests" =>
            $guests
        ]);

        exit;
    }


    /* =====================================================
       CONFIRM GUEST BY TOKEN
    ===================================================== */

    if ($_GET["ajax"] === "confirm_guest") {

        $token =
            trim(
                $_POST["token"] ?? ""
            );


        if ($token === "") {

            echo json_encode([

                "success" => false,

                "status" =>
                "invalid",

                "message" =>
                "QR token is empty."

            ]);

            exit;
        }


        try {

            $conn->begin_transaction();


            /* =================================================
               LOAD THE GUEST BELONGING TO THIS EXACT TOKEN
            ================================================= */

            $stmt =
                $conn->prepare("

                    SELECT

                        i.id AS invitation_id,

                        i.booking_id,

                        i.guest_name,

                        i.guest_email,

                        i.status AS guest_status,

                        i.confirmed_at,

                        COALESCE(
                    NULLIF(b.event_name, ''),
                    'Untitled Event'
                ) AS event_name,

                        b.event_date,

                        b.start_time,

                        b.phone,

                        b.guest_count,

                        ep.package_name,

                        et.name AS event_type,

                        v.venue_name

                    FROM invitations i

                    INNER JOIN bookings b
                        ON b.id = i.booking_id

                    LEFT JOIN event_packages ep
                        ON ep.id = b.package_id

                    LEFT JOIN event_types et
                        ON et.id = ep.event_type_id

                    LEFT JOIN venues v
                        ON v.id = b.venue_id

                    WHERE i.invitation_token = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $stmt->bind_param(
                "s",
                $token
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $guest =
                $result->fetch_assoc();

            $stmt->close();


            /* =================================================
               INVALID TOKEN
            ================================================= */

            if (!$guest) {

                $conn->rollback();

                echo json_encode([

                    "success" => false,

                    "status" =>
                    "invalid",

                    "message" =>
                    "Invalid QR code. No guest is associated with this token."

                ]);

                exit;
            }


            /* =================================================
               ATOMIC CONFIRMATION
            ================================================= */

            $stmt =
                $conn->prepare("

                    UPDATE invitations

                    SET

                        status = 'confirmed',

                        confirmed_at =
                            CURRENT_TIMESTAMP

                    WHERE invitation_token = ?

                    AND (

                        status IS NULL

                        OR status <> 'confirmed'

                    )

                ");


            if (!$stmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $stmt->bind_param(
                "s",
                $token
            );

            $stmt->execute();

            $affectedRows =
                $stmt->affected_rows;

            $stmt->close();


            /* =================================================
               FIRST SUCCESSFUL CONFIRMATION
            ================================================= */

            if ($affectedRows === 1) {

                $conn->commit();


                $guest["guest_status"] =
                    "confirmed";

                $guest["confirmed_at"] =
                    date(
                        "Y-m-d H:i:s"
                    );


                echo json_encode([

                    "success" => true,

                    "status" =>
                    "confirmed",

                    "message" =>
                    "Guest successfully confirmed.",

                    "token" =>
                    $token,

                    "guest" =>
                    $guest

                ]);

                exit;
            }


            /* =================================================
               ALREADY CONFIRMED
            ================================================= */

            $conn->commit();


            echo json_encode([

                "success" => true,

                "status" =>
                "already_confirmed",

                "message" =>
                "This guest has already been confirmed.",

                "token" =>
                $token,

                "guest" =>
                $guest

            ]);

            exit;
        } catch (Throwable $e) {

            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }


            echo json_encode([

                "success" => false,

                "status" =>
                "error",

                "message" =>
                $e->getMessage()

            ]);

            exit;
        }
    }


    echo json_encode([

        "success" => false,

        "message" =>
        "Invalid AJAX request."

    ]);

    exit;
}


/* =========================================================
   OPTIONAL TOKEN CREATION ENDPOINT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "create_token"
) {

    $bookingId =
        (int)(
            $_POST["booking_id"]
            ?? 0
        );


    if ($bookingId > 0) {

        try {

            /* =================================================
               FIND BOOKING
            ================================================= */

            $stmt =
                $conn->prepare("

                    SELECT

                        id

                    FROM bookings

                    WHERE id = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $stmt->bind_param(
                "i",
                $bookingId
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $booking =
                $result->fetch_assoc();

            $stmt->close();


            if ($booking) {

                $token =
                    bin2hex(
                        random_bytes(24)
                    );


                $conn->begin_transaction();


                /* =================================================
                   UPDATE MAIN BOOKING TOKEN
                ================================================= */

                $stmt =
                    $conn->prepare("

                        UPDATE bookings

                        SET

                            invitation_token = ?

                        WHERE id = ?

                    ");


                if (!$stmt) {

                    throw new Exception(
                        $conn->error
                    );
                }


                $stmt->bind_param(
                    "si",
                    $token,
                    $bookingId
                );

                $stmt->execute();

                $stmt->close();
                $conn->commit();


                header(
                    "Location: admin_guestlist.php"
                        . "?token_created=1&token="
                        . urlencode($token)
                );

                exit;
            }
        } catch (Throwable $e) {

            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }


            header(
                "Location: admin_guestlist.php"
                    . "?token_failed=1"
            );

            exit;
        }
    }


    header(
        "Location: admin_guestlist.php"
            . "?token_failed=1"
    );

    exit;
}


/* =========================================================
   PAGE SETTINGS
========================================================= */

$pageTitle =
    "Guest List";

$pageSection =
    "Administrator";

$pageHeading =
    "Guest List";


/* =========================================================
   INITIAL EVENT DATA
========================================================= */

$initialGuests = [];

$initialEvent = null;

$initialStats = [

    "total" =>
    0,

    "confirmed" =>
    0,

    "pending" =>
    0,

    "declined" =>
    0
];


/* =========================================================
   ONLY LOAD EVENT WHEN TOKEN EXISTS
========================================================= */

if ($eventToken !== "") {


    /* =====================================================
       FIND EVENT BY TOKEN
    ===================================================== */

    $stmt =
        $conn->prepare("

            SELECT

                b.id AS booking_id,

                COALESCE(
                    NULLIF(b.event_name, ''),
                    'Untitled Event'
                ) AS event_name,

                b.event_date,

                b.start_time,

                b.phone,

                b.guest_count,

                b.invitation_token,

                ep.package_name,

                et.name AS event_type,

                v.venue_name

            FROM bookings b

            LEFT JOIN event_packages ep
                ON ep.id = b.package_id

            LEFT JOIN event_types et
                ON et.id = ep.event_type_id

            LEFT JOIN venues v
                ON v.id = b.venue_id

            WHERE b.invitation_token = ?

            LIMIT 1

        ");


    if ($stmt) {

        $stmt->bind_param(
            "s",
            $eventToken
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $initialEvent =
            $result->fetch_assoc();

        $stmt->close();
    }


    /* =====================================================
       LOAD ONLY THIS EVENT'S GUESTS
    ===================================================== */

    if ($initialEvent) {

        $bookingId =
            (int)$initialEvent["booking_id"];


        $guestSql = "

            SELECT

                i.id AS invitation_id,

                i.booking_id,

                i.guest_name,

                i.guest_email,

                i.status AS guest_status,

                i.confirmed_at,

                i.created_at,

                COALESCE(
                    NULLIF(b.event_name, ''),
                    'Untitled Event'
                ) AS event_name,

                b.event_date,

                b.start_time,

                b.phone,

                b.guest_count,

                ep.package_name,

                et.name AS event_type,

                v.venue_name

            FROM invitations i

            INNER JOIN bookings b
                ON b.id = i.booking_id

            LEFT JOIN event_packages ep
                ON ep.id = b.package_id

            LEFT JOIN event_types et
                ON et.id = ep.event_type_id

            LEFT JOIN venues v
                ON v.id = b.venue_id

            WHERE i.booking_id = ?

            ORDER BY

                CASE

                    WHEN i.status = 'confirmed'
                    THEN 0

                    WHEN i.status = 'pending'
                    THEN 1

                    ELSE 2

                END ASC,

                i.confirmed_at DESC,

                i.created_at DESC,

                i.id DESC

        ";


        $stmt =
            $conn->prepare(
                $guestSql
            );


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $bookingId
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            while (
                $row =
                $result->fetch_assoc()
            ) {

                $initialGuests[] = [

                    "invitation_id" =>
                    (int)$row["invitation_id"],

                    "booking_id" =>
                    (int)$row["booking_id"],

                    "guest_name" =>
                    $row["guest_name"],

                    "guest_email" =>
                    $row["guest_email"],

                    "status" =>
                    $row["guest_status"]
                        ?: "pending",

                    "confirmed_at" =>
                    $row["confirmed_at"],

                    "event_name" =>
                    $row["event_name"],

                    "event_date" =>
                    $row["event_date"],

                    "start_time" =>
                    $row["start_time"],

                    "guest_count" =>
                    (int)$row["guest_count"],

                    "package_name" =>
                    $row["package_name"],

                    "event_type" =>
                    $row["event_type"],

                    "venue_name" =>
                    $row["venue_name"]
                ];
            }


            $stmt->close();
        }


        /* =================================================
           EVENT-SPECIFIC STATISTICS
        ================================================= */

        $statsStmt =
            $conn->prepare("

                SELECT

                    COUNT(*) AS total,

                    SUM(
                        CASE
                            WHEN status = 'confirmed'
                            THEN 1
                            ELSE 0
                        END
                    ) AS confirmed,

                    SUM(
                        CASE
                            WHEN
                                status = 'pending'
                                OR status IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS pending,

                    SUM(
                        CASE
                            WHEN status = 'declined'
                            THEN 1
                            ELSE 0
                        END
                    ) AS declined

                FROM invitations

                WHERE booking_id = ?

            ");


        if ($statsStmt) {

            $statsStmt->bind_param(
                "i",
                $bookingId
            );

            $statsStmt->execute();

            $statsResult =
                $statsStmt->get_result();

            $statsRow =
                $statsResult->fetch_assoc();

            $statsStmt->close();


            if ($statsRow) {

                /*
                 * Guest Number comes directly from
                 * bookings.guest_count.
                 */

                $initialStats["total"] =
                    (int)(
                        $initialEvent["guest_count"]
                        ?? 0
                    );

                $initialStats["confirmed"] =
                    (int)$statsRow["confirmed"];

                $initialStats["pending"] =
                    (int)$statsRow["pending"];

                $initialStats["declined"] =
                    (int)$statsRow["declined"];
            }
        }
    }
}


/* =========================================================
   PAGE CONTENT
========================================================= */

ob_start();

?>

<link
    rel="stylesheet"
    href="admin.css/admin_guestlist.css">


<div class="admin-guest-list">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="guest-list-header">

        <div class="guest-list-heading">

            <span class="guest-list-eyebrow">
                Guest Management
            </span>

            <h1>
                Guest List
            </h1>

            <?php if ($initialEvent): ?>

                <p>

                    Guest attendance for

                    <strong>

                        <?= e(
                            $initialEvent["event_name"]
                        ); ?>

                    </strong>

                </p>

            <?php else: ?>

                <p>
                    Scan an event QR code to load its guest list.
                </p>

            <?php endif; ?>

        </div>


        <div class="guest-header-actions">

            <a
                href="admin_camera.php"
                class="guest-scan-page-btn">

                <i class="fa-solid fa-qrcode"></i>

                <span>
                    Scan QR Code
                </span>

            </a>

        </div>

    </div>


    <!-- =====================================================
         TOKEN MESSAGE
    ====================================================== -->

    <?php if (
        isset($_GET["token_created"])
    ): ?>

        <div class="guest-alert success">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Event QR token generated successfully.
            </span>

        </div>

    <?php endif; ?>


    <?php if (
        isset($_GET["token_failed"])
    ): ?>

        <div class="guest-alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                Unable to generate event QR token.
            </span>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         NO TOKEN
    ====================================================== -->

    <?php if (
        $eventToken === ""
    ): ?>

        <div class="guest-no-event">

            <div class="guest-no-event-icon">

                <i class="fa-solid fa-qrcode"></i>

            </div>

            <h2>
                No Event Selected
            </h2>

            <p>

                Scan an event QR code first.

                The guest list will then display only the

                guests registered for that specific event.

            </p>

            <a
                href="admin_camera.php"
                class="guest-open-camera-btn">

                <i class="fa-solid fa-camera"></i>

                Open QR Scanner

            </a>

        </div>


    <?php elseif (
        !$initialEvent
    ): ?>

        <div
            class="guest-no-event error-state">

            <div
                class="guest-no-event-icon">

                <i
                    class="fa-solid fa-circle-exclamation"></i>

            </div>

            <h2>
                Event Not Found
            </h2>

            <p>

                The QR token is invalid or the event no longer exists.

            </p>

            <a
                href="admin_camera.php"
                class="guest-open-camera-btn">

                <i class="fa-solid fa-qrcode"></i>

                Scan Another QR

            </a>

        </div>


    <?php else: ?>


        <!-- =================================================
             EVENT INFORMATION
        ================================================== -->

        <div class="guest-event-card">

            <div class="guest-event-main">

                <div class="guest-event-icon">

                    <i
                        class="fa-solid fa-calendar-check"></i>

                </div>

                <div>

                    <span class="guest-event-label">
                        Current Event
                    </span>

                    <h2>

                        <?= e(
                            $initialEvent["event_name"]
                        ); ?>

                    </h2>

                    <p>

                        <?php if (
                            !empty($initialEvent["event_type"])
                        ): ?>

                            <?= e(
                                $initialEvent["event_type"]
                            ); ?>

                        <?php endif; ?>


                        <?php if (
                            !empty($initialEvent["package_name"])
                        ): ?>

                            •
                            <?= e(
                                $initialEvent["package_name"]
                            ); ?>

                        <?php endif; ?>

                    </p>

                </div>

            </div>


            <div class="guest-event-details">

                <div>

                    <i
                        class="fa-solid fa-calendar-days"></i>

                    <span>

                        <?= e(
                            $initialEvent["event_date"]
                        ); ?>

                    </span>

                </div>


                <div>

                    <i
                        class="fa-solid fa-clock"></i>

                    <span>

                        <?= e(
                            $initialEvent["start_time"]
                                ?: "—"
                        ); ?>

                    </span>

                </div>


                <div>

                    <i
                        class="fa-solid fa-location-dot"></i>

                    <span>

                        <?= e(
                            $initialEvent["venue_name"]
                                ?: "—"
                        ); ?>

                    </span>

                </div>

            </div>


            <!-- =================================================
                 EVENT LINK
            ================================================== -->

            <?php if (
                $eventLink !== ""
            ): ?>

                <div class="guest-event-link">

                    <div class="guest-event-link-info">

                        <i
                            class="fa-solid fa-link"></i>

                        <div>

                            <span>
                                Event Link
                            </span>

                            <input
                                type="text"
                                id="eventLink"
                                value="<?= e($eventLink); ?>"
                                readonly>

                        </div>

                    </div>


                    <div class="guest-event-link-actions">

                        <a
                            href="<?= e($eventLink); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="guest-event-open-link">

                            <i
                                class="fa-solid fa-arrow-up-right-from-square"></i>

                            Open Event

                        </a>


                        <button
                            type="button"
                            class="guest-event-copy-link"
                            id="copyEventLink"
                            data-link="<?= e($eventLink); ?>">

                            <i
                                class="fa-solid fa-copy"></i>

                            Copy Link

                        </button>

                    </div>

                </div>

            <?php endif; ?>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="guest-stats-grid">


            <div class="guest-stat-card">

                <div class="guest-stat-icon total">

                    <i
                        class="fa-solid fa-users"></i>

                </div>

                <div class="guest-stat-content">

                    <span>
                        Guest Number
                    </span>

                    <strong id="totalGuests">

                        <?= $initialStats["total"]; ?>

                    </strong>

                </div>

            </div>


            <div class="guest-stat-card">

                <div
                    class="guest-stat-icon confirmed">

                    <i
                        class="fa-solid fa-user-check"></i>

                </div>

                <div class="guest-stat-content">

                    <span>
                        Confirmed Attendance
                    </span>

                    <strong id="confirmedGuests">

                        <?= $initialStats["confirmed"]; ?>

                    </strong>

                </div>

            </div>


            <div class="guest-stat-card">

                <div
                    class="guest-stat-icon pending">

                    <i
                        class="fa-solid fa-user-clock"></i>

                </div>

                <div class="guest-stat-content">

                    <span>
                        Pending
                    </span>

                    <strong id="pendingGuests">

                        <?= $initialStats["pending"]; ?>

                    </strong>

                </div>

            </div>


            <div class="guest-stat-card">

                <div
                    class="guest-stat-icon declined">

                    <i
                        class="fa-solid fa-user-xmark"></i>

                </div>

                <div class="guest-stat-content">

                    <span>
                        Declined
                    </span>

                    <strong id="declinedGuests">

                        <?= $initialStats["declined"]; ?>

                    </strong>

                </div>

            </div>


        </div>


        <!-- =================================================
             FILTERS
        ================================================== -->

        <div class="guest-filter-card">

            <div class="guest-filter-left">

                <div class="guest-search">

                    <i
                        class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="guestSearch"
                        placeholder="Search guest or phone..."
                        autocomplete="off">

                </div>


                <div class="guest-status-filter">

                    <select
                        id="guestStatus">

                        <option value="">
                            All Status
                        </option>

                        <option value="confirmed">
                            Confirmed
                        </option>

                        <option value="pending">
                            Pending
                        </option>

                        <option value="declined">
                            Declined
                        </option>

                    </select>

                </div>

            </div>


            <button
                type="button"
                id="refreshGuestList"
                class="guest-refresh-btn">

                <i
                    class="fa-solid fa-rotate"></i>

                Refresh

            </button>

        </div>


        <!-- =================================================
             GUEST TABLE
        ================================================== -->

        <div class="guest-table-card">

            <div class="guest-table-header">

                <div>

                    <h2>
                        Event Guests
                    </h2>

                    <p>
                        Showing only guests registered for this event.
                    </p>

                </div>


                <span
                    class="guest-count-label"
                    id="guestCountLabel">

                    <?= count(
                        $initialGuests
                    ); ?>

                    guests

                </span>

            </div>


            <div class="guest-table-wrapper">

                <table class="guest-table">

                    <thead>

                        <tr>

                            <th>
                                Guest
                            </th>

                            <th>
                                Event
                            </th>

                            <th>
                                Event Date
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Venue
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Confirmed
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="guestTableBody">

                        <?php if (
                            empty($initialGuests)
                        ): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="guest-empty">

                                    <i
                                        class="fa-solid fa-users-slash"></i>

                                    <strong>
                                        No guests found
                                    </strong>

                                    <span>
                                        No guest records have been registered for this event.
                                    </span>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $initialGuests
                                as $guest
                            ): ?>

                                <?php

                                $guestStatus =
                                    strtolower(
                                        $guest["status"]
                                            ?? "pending"
                                    );

                                ?>


                                <tr>

                                    <td>

                                        <div
                                            class="guest-name-cell">

                                            <div
                                                class="guest-avatar">

                                                <i
                                                    class="fa-solid fa-user"></i>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= e(
                                                        $guest["guest_name"]
                                                    ); ?>

                                                </strong>


                                                <?php if (
                                                    !empty($guest["guest_email"])
                                                ): ?>

                                                    <small>

                                                        <?= e(
                                                            $guest["guest_email"]
                                                        ); ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <div
                                            class="guest-event-cell">

                                            <strong>

                                                <?= e(
                                                    $guest["event_name"]
                                                ); ?>

                                            </strong>


                                            <?php if (
                                                !empty($guest["package_name"])
                                            ): ?>

                                                <small>

                                                    <?= e(
                                                        $guest["package_name"]
                                                    ); ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= e(
                                            $guest["event_date"]
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $guest["phone"]
                                                ?: "—"
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $guest["venue_name"]
                                                ?: "—"
                                        ); ?>

                                    </td>


                                    <td>

                                        <span
                                            class="guest-status <?= e(
                                                                    $guestStatus
                                                                ); ?>">


                                            <?php if (
                                                $guestStatus
                                                === "confirmed"
                                            ): ?>

                                                <i
                                                    class="fa-solid fa-circle-check"></i>

                                                Confirmed


                                            <?php elseif (
                                                $guestStatus
                                                === "declined"
                                            ): ?>

                                                <i
                                                    class="fa-solid fa-circle-xmark"></i>

                                                Declined


                                            <?php else: ?>

                                                <i
                                                    class="fa-solid fa-clock"></i>

                                                Pending

                                            <?php endif; ?>


                                        </span>

                                    </td>


                                    <td>

                                        <?= !empty($guest["confirmed_at"])

                                            ? e(
                                                $guest["confirmed_at"]
                                            )

                                            : "—"; ?>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    <?php endif; ?>


</div>


<script>
    /* =========================================================
   EVENT LINK COPY
========================================================= */

    document.addEventListener(
        "DOMContentLoaded",
        function() {

            const copyEventLink =
                document.getElementById(
                    "copyEventLink"
                );

            const eventLinkInput =
                document.getElementById(
                    "eventLink"
                );


            if (
                copyEventLink &&
                eventLinkInput
            ) {

                copyEventLink.addEventListener(
                    "click",
                    async function() {

                        const link =
                            copyEventLink.dataset.link ||
                            eventLinkInput.value;


                        try {

                            await navigator.clipboard.writeText(
                                link
                            );


                            const originalHTML =
                                copyEventLink.innerHTML;


                            copyEventLink.innerHTML =

                                '<i class="fa-solid fa-check"></i> ' +
                                'Copied';


                            setTimeout(
                                function() {

                                    copyEventLink.innerHTML =
                                        originalHTML;

                                },
                                1800
                            );


                        } catch (error) {

                            eventLinkInput.select();

                            eventLinkInput.setSelectionRange(
                                0,
                                eventLinkInput.value.length
                            );


                            document.execCommand(
                                "copy"
                            );


                            const originalHTML =
                                copyEventLink.innerHTML;


                            copyEventLink.innerHTML =

                                '<i class="fa-solid fa-check"></i> ' +
                                'Copied';


                            setTimeout(
                                function() {

                                    copyEventLink.innerHTML =
                                        originalHTML;

                                },
                                1800
                            );

                        }

                    }
                );

            }

        }
    );
</script>


<script>
    window.GUEST_LIST_CONFIG = {

        ajaxUrl: "admin_guestlist.php",

        token:

            <?= json_encode(
                $eventToken,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            ); ?>,

        initialGuests:

            <?= json_encode(
                $initialGuests,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            ); ?>,

        initialEvent:

            <?= json_encode(
                $initialEvent,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            ); ?>,

        initialStats:

            <?= json_encode(
                $initialStats,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            ); ?>,

        pollingInterval: 3000

    };
</script>


<script
    src="admin.js/admin_guestlist.js"></script>


<?php

$pageContent =
    ob_get_clean();


require_once __DIR__
    . "/admin_include/admin_header.php";

?>
