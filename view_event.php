<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];


/* =========================================================
   GET BOOKING ID
========================================================= */

$bookingId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($bookingId <= 0) {
    header("Location: my-event.php");
    exit;
}


/* =========================================================
   GET EVENT DETAILS
========================================================= */

$eventStmt = $conn->prepare("
    SELECT
        b.id,
        b.user_id,
        b.event_name,
        b.event_date,
        b.start_time,
        b.end_time,
        b.guest_count,
        b.phone,
        b.special_requests,
        b.status,
        b.created_at,
        b.updated_at,
        b.package_id,
        b.venue_id,
        p.package_name,
        p.inclusions,
        p.image AS package_image,
        v.venue_name
    FROM bookings b
    LEFT JOIN event_packages p
        ON b.package_id = p.id
    LEFT JOIN venues v
        ON b.venue_id = v.id
    WHERE b.id = ?
    AND b.user_id = ?
    LIMIT 1
");

if (!$eventStmt) {
    die(
        "Unable to prepare event query: " .
        htmlspecialchars($conn->error)
    );
}

$eventStmt->bind_param(
    "ii",
    $bookingId,
    $userId
);

if (!$eventStmt->execute()) {
    die(
        "Unable to execute event query: " .
        htmlspecialchars($eventStmt->error)
    );
}

$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();


/* =========================================================
   EVENT NOT FOUND
========================================================= */

if (!$event) {
    header("Location: my-event.php");
    exit;
}


/* =========================================================
   FORMAT FUNCTIONS
========================================================= */

function viewEventDate($date)
{
    if (empty($date)) {
        return "—";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date("F d, Y", $timestamp);
}


function viewEventTime($time)
{
    if (empty($time)) {
        return "—";
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return htmlspecialchars($time);
    }

    return date("h:i A", $timestamp);
}


function viewStatusClass($status)
{
    $status = strtolower(
        trim((string) $status)
    );

    switch ($status) {

        case "confirmed":
            return "confirmed";

        case "pending":
            return "pending";

        case "cancelled":
        case "canceled":
            return "cancelled";

        case "completed":
            return "completed";

        default:
            return "unknown";
    }
}


function viewStatusLabel($status)
{
    $status = trim(
        (string) $status
    );

    if ($status === "") {
        return "Unknown";
    }

    return ucfirst(
        strtolower($status)
    );
}


/* =========================================================
   EVENT VALUES
========================================================= */

$status = strtolower(
    trim(
        (string) ($event["status"] ?? "")
    )
);

$statusClass = viewStatusClass(
    $event["status"]
);

$statusLabel = viewStatusLabel(
    $event["status"]
);


/* =========================================================
   PACKAGE INCLUSIONS
========================================================= */

$inclusions = [];

if (!empty($event["inclusions"])) {

    $inclusions = preg_split(
        "/\r\n|\r|\n/",
        $event["inclusions"]
    );
}

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
        View Event | Event Solutions by S.H.E.
    </title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

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
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         VIEW EVENT CSS ONLY
    ====================================================== -->



    <!-- GOOGLE MATERIAL SYMBOLS -->
    <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
    rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="css/view_event.css"
    >

</head>


<body>

<main class="view-event-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="view-event-hero">

        <div class="view-event-container">
            <div class="hero-label">

                <span class="label-line"></span>

                EVENT DETAILS

                <span class="label-line"></span>

            </div>


            <h1>

                <?= htmlspecialchars(
                    $event["event_name"]
                    ?? "Your Event"
                ) ?>

            </h1>


            <p>

                Booking
                #<?= (int) $event["id"] ?>

            </p>

        </div>

    </section>



    <!-- =====================================================
         EVENT CONTENT
    ====================================================== -->

    <section class="view-event-content">

        <div class="view-event-container">


            <!-- =================================================
                 STATUS CARD
            ================================================== -->

            <div class="event-status-card">

                <div class="status-card-left">

                    <div class="status-icon">

                        <?php if ($statusClass === "confirmed"): ?>

                            <span class="material-symbols-outlined">
                                check_circle
                            </span>

                        <?php elseif ($statusClass === "pending"): ?>

                            <span class="material-symbols-outlined">
                                schedule
                            </span>

                        <?php elseif ($statusClass === "cancelled"): ?>

                            <span class="material-symbols-outlined">
                                cancel
                            </span>

                        <?php elseif ($statusClass === "completed"): ?>

                            <span class="material-symbols-outlined">
                                task_alt
                            </span>

                        <?php else: ?>

                            <span class="material-symbols-outlined">
                                help
                            </span>

                        <?php endif; ?>

                    </div>


                    <div>

                        <span class="status-small-label">
                            Booking Status
                        </span>

                        <strong>
                            <?= htmlspecialchars($statusLabel) ?>
                        </strong>

                    </div>

                </div>


                <div
                    class="status-badge <?= htmlspecialchars($statusClass) ?>"
                >
                    <?= htmlspecialchars($statusLabel) ?>
                </div>

            </div>



            <!-- =================================================
                 MAIN GRID
            ================================================== -->

            <div class="event-details-grid">


                <!-- =================================================
                     EVENT INFORMATION
                ================================================== -->

                <section class="event-card">

                    <div class="card-header">

                        <div class="card-header-icon">

                            <span class="material-symbols-outlined">
                                event
                            </span>

                        </div>


                        <div>

                            <h2>
                                Event Information
                            </h2>

                            <p>
                                Details about your scheduled event
                            </p>

                        </div>

                    </div>


                    <div class="details-list">


                        <!-- DATE -->

                        <div class="detail-item">

                            <div class="detail-icon">

                                <span class="material-symbols-outlined">
                                    calendar_month
                                </span>

                            </div>


                            <div class="detail-content">

                                <span>
                                    Event Date
                                </span>

                                <strong>

                                    <?= viewEventDate(
                                        $event["event_date"]
                                    ) ?>

                                </strong>

                            </div>

                        </div>



                        <!-- TIME -->

                        <div class="detail-item">

                            <div class="detail-icon">

                                <span class="material-symbols-outlined">
                                    schedule
                                </span>

                            </div>


                            <div class="detail-content">

                                <span>
                                    Event Time
                                </span>

                                <strong>

                                    <?= viewEventTime(
                                        $event["start_time"]
                                    ) ?>


                                    <?php if (!empty($event["end_time"])): ?>

                                        <span class="time-separator">
                                            —
                                        </span>

                                        <?= viewEventTime(
                                            $event["end_time"]
                                        ) ?>

                                    <?php endif; ?>

                                </strong>

                            </div>

                        </div>



                        <!-- GUESTS -->

                        <div class="detail-item">

                            <div class="detail-icon">

                                <span class="material-symbols-outlined">
                                    group
                                </span>

                            </div>


                            <div class="detail-content">

                                <span>
                                    Number of Guests
                                </span>

                                <strong>

                                    <?= (int) $event["guest_count"] ?>

                                    <?= (
                                        (int) $event["guest_count"] === 1
                                    )
                                        ? "Guest"
                                        : "Guests"
                                    ?>

                                </strong>

                            </div>

                        </div>



                        <!-- PHONE -->

                        <div class="detail-item">

                            <div class="detail-icon">

                                <span class="material-symbols-outlined">
                                    phone
                                </span>

                            </div>


                            <div class="detail-content">

                                <span>
                                    Contact Number
                                </span>

                                <strong>

                                    <?= !empty($event["phone"])
                                        ? htmlspecialchars(
                                            $event["phone"]
                                        )
                                        : "—"
                                    ?>

                                </strong>

                            </div>

                        </div>

                    </div>

                </section>



                <!-- =================================================
                     VENUE & PACKAGE
                ================================================== -->

                <section class="event-card">

                    <div class="card-header">

                        <div class="card-header-icon">

                            <span class="material-symbols-outlined">
                                location_on
                            </span>

                        </div>


                        <div>

                            <h2>
                                Venue & Package
                            </h2>

                            <p>
                                Your selected event arrangements
                            </p>

                        </div>

                    </div>


                    <div class="selection-list">


                        <!-- VENUE -->

                        <div class="selection-item">

                            <div class="selection-icon">

                                <span class="material-symbols-outlined">
                                    location_on
                                </span>

                            </div>


                            <div class="selection-content">

                                <span>
                                    Venue
                                </span>


                                <?php if (!empty($event["venue_name"])): ?>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $event["venue_name"]
                                        ) ?>

                                    </strong>

                                <?php else: ?>

                                    <strong class="not-available">
                                        No venue selected
                                    </strong>

                                <?php endif; ?>

                            </div>

                        </div>



                        <!-- PACKAGE -->

                        <div class="selection-item">

                            <div class="selection-icon">

                                <span class="material-symbols-outlined">
                                    inventory_2
                                </span>

                            </div>


                            <div class="selection-content">

                                <span>
                                    Event Package
                                </span>


                                <?php if (!empty($event["package_name"])): ?>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $event["package_name"]
                                        ) ?>

                                    </strong>

                                <?php else: ?>

                                    <strong class="not-available">
                                        No package selected
                                    </strong>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </section>



                <!-- =================================================
                     SPECIAL REQUESTS
                ================================================== -->

                <section class="event-card full-width">

                    <div class="card-header">

                        <div class="card-header-icon">

                            <span class="material-symbols-outlined">
                                notes
                            </span>

                        </div>


                        <div>

                            <h2>
                                Special Requests
                            </h2>

                            <p>
                                Additional information provided with your booking
                            </p>

                        </div>

                    </div>


                    <?php if (!empty($event["special_requests"])): ?>

                        <div class="special-request-box">

                            <span class="material-symbols-outlined">
                                format_quote
                            </span>


                            <p>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $event["special_requests"]
                                    )
                                ) ?>

                            </p>

                        </div>

                    <?php else: ?>

                        <div class="no-special-request">

                            <span class="material-symbols-outlined">
                                info
                            </span>

                            <p>
                                No special requests were added to this booking.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>



                <!-- =================================================
                     PACKAGE INCLUSIONS
                ================================================== -->

                <?php if (!empty($inclusions)): ?>

                    <section class="event-card full-width">

                        <div class="card-header">

                            <div class="card-header-icon">

                                <span class="material-symbols-outlined">
                                    checklist
                                </span>

                            </div>


                            <div>

                                <h2>
                                    Package Inclusions
                                </h2>

                                <p>
                                    Services included in your selected package
                                </p>

                            </div>

                        </div>


                        <div class="inclusions-grid">

                            <?php foreach ($inclusions as $inclusion): ?>

                                <?php

                                $inclusion = trim($inclusion);

                                if ($inclusion === "") {
                                    continue;
                                }

                                ?>

                                <div class="inclusion-item">

                                    <span class="material-symbols-outlined">
                                        check_circle
                                    </span>

                                    <span>

                                        <?= htmlspecialchars(
                                            $inclusion
                                        ) ?>

                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>



                <!-- =================================================
                     BOOKING INFORMATION
                ================================================== -->

                <section class="event-card full-width">

                    <div class="card-header">

                        <div class="card-header-icon">

                            <span class="material-symbols-outlined">
                                history
                            </span>

                        </div>


                        <div>

                            <h2>
                                Booking Information
                            </h2>

                            <p>
                                Information about your booking request
                            </p>

                        </div>

                    </div>


                    <div class="booking-meta">


                        <div>

                            <span>
                                Booking Number
                            </span>

                            <strong>
                                #<?= (int) $event["id"] ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Submitted
                            </span>

                            <strong>

                                <?= !empty($event["created_at"])
                                    ? date(
                                        "M d, Y h:i A",
                                        strtotime(
                                            $event["created_at"]
                                        )
                                    )
                                    : "—"
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                Last Updated
                            </span>

                            <strong>

                                <?= !empty($event["updated_at"])
                                    ? date(
                                        "M d, Y h:i A",
                                        strtotime(
                                            $event["updated_at"]
                                        )
                                    )
                                    : "—"
                                ?>

                            </strong>

                        </div>

                    </div>

                </section>

            </div>



            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="event-page-actions">

                <a
                    href="my-event.php"
                    class="secondary-action"
                >

                    <span class="material-symbols-outlined">
                        arrow_back
                    </span>

                    Back to My Events

                </a>


                <?php if ($status === "confirmed"): ?>

                    <a
                        href="create_invitation.php?booking_id=<?= (int) $event["id"] ?>"
                        class="primary-action"
                    >

                        <span class="material-symbols-outlined">
                            link
                        </span>

                        Create Invitation

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<script src="js/view_event.js"></script>

</body>

</html>