<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (
    isset($_SESSION["role"]) &&
    strtolower((string) $_SESSION["role"]) !== "admin"
) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   PAGE SETTINGS
========================================================= */

$pageTitle = "Host Bookings";
$pageSection = "Administrator";
$pageHeading = "Host Bookings";
$message = "";
$messageType = "";


/* =========================================================
   STATUS OPTIONS
========================================================= */

$statusOptions = [
    "pending",
    "confirmed",
    "completed",
    "cancelled"
];


/* =========================================================
   UPDATE BOOKING STATUS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "update_status") {

        $bookingId = isset($_POST["booking_id"])
            ? (int) $_POST["booking_id"]
            : 0;

        $newStatus = trim($_POST["status"] ?? "");

        if ($bookingId <= 0) {
            header("Location: admin_hostbooking.php?error=invalid_booking");
            exit;
        }

        if (!in_array($newStatus, $statusOptions, true)) {
            header("Location: admin_hostbooking.php?error=invalid_status");
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE host_bookings
            SET
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $newStatus,
                $bookingId
            );

            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: admin_hostbooking.php?success=status_updated"
                );

                exit;

            } else {

                $stmt->close();

                header(
                    "Location: admin_hostbooking.php?error=update_failed"
                );

                exit;
            }

        } else {

            header(
                "Location: admin_hostbooking.php?error=prepare_failed"
            );

            exit;
        }
    }
}


/* =========================================================
   SUCCESS / ERROR MESSAGE
========================================================= */

if (isset($_GET["success"])) {

    switch ($_GET["success"]) {

        case "status_updated":

            $message = "Host booking status updated successfully.";
            $messageType = "success";

            break;
    }
}


if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "invalid_booking":

            $message = "Invalid booking selected.";
            $messageType = "error";

            break;

        case "invalid_status":

            $message = "Invalid booking status.";
            $messageType = "error";

            break;

        case "update_failed":

            $message = "Unable to update the booking status.";
            $messageType = "error";

            break;

        case "prepare_failed":

            $message = "Unable to prepare the database request.";
            $messageType = "error";

            break;
    }
}


/* =========================================================
   FILTERS
========================================================= */

$search = trim($_GET["search"] ?? "");
$statusFilter = trim($_GET["status"] ?? "");
$eventDate = trim($_GET["event_date"] ?? "");


/* =========================================================
   STATISTICS
========================================================= */

$totalBookings = 0;
$pendingBookings = 0;
$confirmedBookings = 0;
$completedBookings = 0;
$cancelledBookings = 0;


/* =========================================================
   TOTAL
========================================================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM host_bookings
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalBookings = (int) ($row["total"] ?? 0);
}


/* =========================================================
   PENDING
========================================================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM host_bookings
    WHERE status = 'pending'
");

if ($result) {

    $row = $result->fetch_assoc();

    $pendingBookings = (int) ($row["total"] ?? 0);
}


/* =========================================================
   CONFIRMED
========================================================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM host_bookings
    WHERE status = 'confirmed'
");

if ($result) {

    $row = $result->fetch_assoc();

    $confirmedBookings = (int) ($row["total"] ?? 0);
}


/* =========================================================
   COMPLETED
========================================================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM host_bookings
    WHERE status = 'completed'
");

if ($result) {

    $row = $result->fetch_assoc();

    $completedBookings = (int) ($row["total"] ?? 0);
}


/* =========================================================
   CANCELLED
========================================================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM host_bookings
    WHERE status = 'cancelled'
");

if ($result) {

    $row = $result->fetch_assoc();

    $cancelledBookings = (int) ($row["total"] ?? 0);
}


/* =========================================================
   BUILD BOOKING QUERY
========================================================= */

$where = [];
$params = [];
$types = "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $where[] = "
        (
            hb.event_name LIKE ?
            OR hb.phone LIKE ?
            OR hb.address LIKE ?
            OR h.host_name LIKE ?
            OR CAST(hb.host_package_id AS CHAR) LIKE ?
            OR CAST(hb.id AS CHAR) LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssssss";
}


/* =========================================================
   STATUS FILTER
========================================================= */

if (
    $statusFilter !== "" &&
    in_array($statusFilter, $statusOptions, true)
) {

    $where[] = "hb.status = ?";

    $params[] = $statusFilter;

    $types .= "s";
}


/* =========================================================
   EVENT DATE FILTER
========================================================= */

if ($eventDate !== "") {

    $where[] = "hb.event_date = ?";

    $params[] = $eventDate;

    $types .= "s";
}


/* =========================================================
   WHERE SQL
========================================================= */

$whereSql = "";

if (!empty($where)) {

    $whereSql = "WHERE " . implode(" AND ", $where);
}


/* =========================================================
   GET BOOKINGS
========================================================= */

$bookings = [];

$sql = "
    SELECT
        hb.id,
        hb.user_id,
        hb.host_id,
        hb.host_package_id,
        hb.event_id,
        hb.event_name,
        hb.event_date,
        hb.start_time,
        hb.phone,
        hb.address,
        hb.special_requests,
        hb.status,
        hb.created_at,
        hb.updated_at,

        h.host_name,
        h.host_type,
        h.image AS host_image

    FROM host_bookings hb

    LEFT JOIN hosts h
        ON h.id = hb.host_id

    $whereSql

    ORDER BY
        hb.event_date ASC,
        hb.start_time ASC,
        hb.id DESC
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $bookings[] = $row;
        }
    }

    $stmt->close();
}


/* =========================================================
   IMAGE HELPER
========================================================= */

function getHostBookingImageUrl($image)
{
    if (empty($image)) {
        return "";
    }

    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }

    return "../" . ltrim($image, "/");
}


/* =========================================================
   STATUS HELPER
========================================================= */

function getHostBookingStatusLabel($status)
{
    return ucfirst(strtolower((string) $status));
}


/* =========================================================
   DATE FORMAT
========================================================= */

function formatHostBookingDate($date)
{
    if (empty($date)) {
        return "—";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars(
            $date,
            ENT_QUOTES,
            "UTF-8"
        );
    }

    return date("M d, Y", $timestamp);
}


/* =========================================================
   TIME FORMAT
========================================================= */

function formatHostBookingTime($time)
{
    if (empty($time)) {
        return "—";
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return htmlspecialchars(
            $time,
            ENT_QUOTES,
            "UTF-8"
        );
    }

    return date("h:i A", $timestamp);
}


ob_start();

?>

<link
    rel="stylesheet"
    href="admin.css/admin_hostbooking.css"
>

<div class="admin-host-bookings">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="host-bookings-header">

        <div class="host-bookings-header-left">

            <div class="host-bookings-title-icon">
                <i class="fa-solid fa-microphone-lines"></i>
            </div>

            <div>

                <span class="host-bookings-page-label">
                    <?= htmlspecialchars(
                        $pageSection,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $pageHeading,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </h2>

                <p>
                    Manage host and singing package bookings.
                </p>

            </div>

        </div>


        <div class="host-bookings-header-actions">

            <button
                type="button"
                class="btn-host-bookings-report"
                id="btnHostBookingsReport"
            >

                <i class="fa-solid fa-print"></i>

                <span>
                    Print Report
                </span>

            </button>

        </div>

    </div>


    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (
        $message !== "" &&
        $messageType === "error"
    ): ?>

        <div
            class="host-bookings-alert error"
            role="alert"
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUCCESS TOAST
    ====================================================== -->

    <?php if (
        $message !== "" &&
        $messageType === "success"
    ): ?>

        <div
            class="host-bookings-toast"
            id="hostBookingsToast"
            role="status"
            aria-live="polite"
        >

            <div class="host-bookings-toast-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>


            <div class="host-bookings-toast-content">

                <strong>
                    Success
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </span>

            </div>


            <button
                type="button"
                class="host-bookings-toast-close"
                id="hostBookingsToastClose"
                aria-label="Close notification"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>


            <div class="host-bookings-toast-progress"></div>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="host-booking-stat-grid">

        <div class="host-booking-stat-card">

            <div class="host-booking-stat-icon total">

                <i class="fa-solid fa-calendar-check"></i>

            </div>

            <div class="host-booking-stat-content">

                <span>
                    Total Bookings
                </span>

                <strong>
                    <?= number_format($totalBookings); ?>
                </strong>

            </div>

        </div>


        <div class="host-booking-stat-card">

            <div class="host-booking-stat-icon pending">

                <i class="fa-solid fa-clock"></i>

            </div>

            <div class="host-booking-stat-content">

                <span>
                    Pending
                </span>

                <strong>
                    <?= number_format($pendingBookings); ?>
                </strong>

            </div>

        </div>


        <div class="host-booking-stat-card">

            <div class="host-booking-stat-icon confirmed">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div class="host-booking-stat-content">

                <span>
                    Confirmed
                </span>

                <strong>
                    <?= number_format($confirmedBookings); ?>
                </strong>

            </div>

        </div>


        <div class="host-booking-stat-card">

            <div class="host-booking-stat-icon completed">

                <i class="fa-solid fa-flag-checkered"></i>

            </div>

            <div class="host-booking-stat-content">

                <span>
                    Completed
                </span>

                <strong>
                    <?= number_format($completedBookings); ?>
                </strong>

            </div>

        </div>


        <div class="host-booking-stat-card">

            <div class="host-booking-stat-icon cancelled">

                <i class="fa-solid fa-ban"></i>

            </div>

            <div class="host-booking-stat-content">

                <span>
                    Cancelled
                </span>

                <strong>
                    <?= number_format($cancelledBookings); ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================================
         BOOKINGS PANEL
    ====================================================== -->

    <div class="host-bookings-panel">

        <div class="host-bookings-panel-header">

            <div>

                <span class="host-bookings-panel-label">
                    HOST BOOKINGS
                </span>

                <strong>
                    <?= number_format(count($bookings)); ?>
                    displayed
                </strong>

            </div>

        </div>


        <!-- =================================================
             FILTER BAR
        ================================================== -->

        <form
            method="GET"
            action="admin_hostbooking.php"
            class="host-bookings-filter-bar"
            id="hostBookingsFilterForm"
        >

            <div class="host-bookings-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    placeholder="Search booking, event, host, phone..."
                    autocomplete="off"
                >

            </div>


            <div class="host-bookings-filter">

                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <?php foreach ($statusOptions as $status): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $status,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            <?= $statusFilter === $status
                                ? "selected"
                                : ""; ?>
                        >

                            <?= htmlspecialchars(
                                getHostBookingStatusLabel($status),
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="host-bookings-date-filter">

                <input
                    type="date"
                    name="event_date"
                    value="<?= htmlspecialchars(
                        $eventDate,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    aria-label="Filter by event date"
                >

            </div>


            <button
                type="submit"
                class="btn-host-bookings-search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <span>
                    Search
                </span>

            </button>


            <a
                href="admin_hostbooking.php"
                class="btn-host-bookings-reset"
            >

                <i class="fa-solid fa-rotate-left"></i>

                <span>
                    Reset
                </span>

            </a>

        </form>


        <!-- =================================================
             REPORT
        ================================================== -->

        <div
            class="host-bookings-report"
            id="hostBookingsReport"
        >

            <div class="host-bookings-report-header">

                <div>

                    <h3>
                        Host Booking Report
                    </h3>

                    <p>
                        List of host and singing package reservations
                    </p>

                </div>

                <span class="host-bookings-report-date">
                    <?= date("F d, Y h:i A"); ?>
                </span>

            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="host-bookings-table-wrapper">

                <table class="host-bookings-table">

                    <thead>

                        <tr>

                            <th>
                                Booking
                            </th>

                            <th>
                                Host
                            </th>

                            <th>
                                Event
                            </th>

                            <th>
                                Schedule
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Package
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="no-print">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($bookings)): ?>

                        <?php foreach ($bookings as $booking): ?>

                            <?php

                            $bookingId = (int) $booking["id"];


                            $hostName = trim(
                                (string) (
                                    $booking["host_name"] ?? ""
                                )
                            );

                            if ($hostName === "") {
                                $hostName = "Unknown Host";
                            }


                            $hostType = trim(
                                (string) (
                                    $booking["host_type"] ?? ""
                                )
                            );


                            $hostImage = getHostBookingImageUrl(
                                $booking["host_image"] ?? ""
                            );


                            $eventName = trim(
                                (string) (
                                    $booking["event_name"] ?? ""
                                )
                            );

                            if ($eventName === "") {
                                $eventName = "Unnamed Event";
                            }


                            $status = strtolower(
                                trim(
                                    (string) (
                                        $booking["status"] ?? "pending"
                                    )
                                )
                            );

                            ?>

                            <tr>

                                <!-- BOOKING -->

                                <td>

                                    <div class="host-booking-id">

                                        <span class="host-booking-id-label">
                                            BOOKING
                                        </span>

                                        <strong>
                                            #<?= $bookingId; ?>
                                        </strong>

                                        <?php if (
                                            !empty($booking["event_id"])
                                        ): ?>

                                            <small>
                                                Event #
                                                <?= (int) $booking["event_id"]; ?>
                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- HOST -->

                                <td>

                                    <div class="host-booking-host">

                                        <div class="host-booking-avatar">

                                            <?php if ($hostImage !== ""): ?>

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $hostImage,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $hostName,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>"
                                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                                >

                                                <span
                                                    class="host-booking-avatar-placeholder"
                                                    style="display:none;"
                                                >

                                                    <i class="fa-solid fa-user"></i>

                                                </span>

                                            <?php else: ?>

                                                <span class="host-booking-avatar-placeholder">

                                                    <i class="fa-solid fa-user"></i>

                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="host-booking-host-info">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $hostName,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>

                                            </strong>


                                            <?php if ($hostType !== ""): ?>

                                                <span>

                                                    <?= htmlspecialchars(
                                                        $hostType,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>

                                                </span>

                                            <?php endif; ?>


                                            <small>

                                                Host #
                                                <?= (int) $booking["host_id"]; ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- EVENT -->

                                <td>

                                    <div class="host-booking-event">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $eventName,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </strong>


                                        <?php if (
                                            !empty($booking["user_id"])
                                        ): ?>

                                            <small>

                                                Customer #
                                                <?= (int) $booking["user_id"]; ?>

                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- SCHEDULE -->

                                <td>

                                    <div class="host-booking-schedule">

                                        <strong>

                                            <?= formatHostBookingDate(
                                                $booking["event_date"]
                                            ); ?>

                                        </strong>

                                        <span>

                                            <i class="fa-regular fa-clock"></i>

                                            <?= formatHostBookingTime(
                                                $booking["start_time"]
                                            ); ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- CONTACT -->

                                <td>

                                    <div class="host-booking-contact">

                                        <?php if (
                                            !empty($booking["phone"])
                                        ): ?>

                                            <span>

                                                <i class="fa-solid fa-phone"></i>

                                                <?= htmlspecialchars(
                                                    $booking["phone"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="muted">
                                                No phone
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- ADDRESS -->

                                <td>

                                    <div class="host-booking-address">

                                        <?php if (
                                            !empty($booking["address"])
                                        ): ?>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $booking["address"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                )
                                            ); ?>

                                        <?php else: ?>

                                            <span class="muted">
                                                No address
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- PACKAGE -->

                                <td>

                                    <div class="host-booking-package">

                                        <span class="host-package-badge">

                                            <i class="fa-solid fa-microphone"></i>

                                            Package #
                                            <?= (int) $booking["host_package_id"]; ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="host-booking-status <?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                    >

                                        <?php if ($status === "pending"): ?>

                                            <i class="fa-solid fa-clock"></i>

                                        <?php elseif ($status === "confirmed"): ?>

                                            <i class="fa-solid fa-circle-check"></i>

                                        <?php elseif ($status === "completed"): ?>

                                            <i class="fa-solid fa-flag-checkered"></i>

                                        <?php elseif ($status === "cancelled"): ?>

                                            <i class="fa-solid fa-ban"></i>

                                        <?php else: ?>

                                            <i class="fa-solid fa-circle"></i>

                                        <?php endif; ?>


                                        <span>

                                            <?= htmlspecialchars(
                                                getHostBookingStatusLabel($status),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </span>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td class="no-print">

                                    <button
                                        type="button"
                                        class="btn-host-booking-view"

                                        data-booking-id="<?= $bookingId; ?>"

                                        data-event-id="<?= (int) (
                                            $booking["event_id"] ?? 0
                                        ); ?>"

                                        data-user-id="<?= (int) (
                                            $booking["user_id"] ?? 0
                                        ); ?>"

                                        data-host-id="<?= (int) (
                                            $booking["host_id"] ?? 0
                                        ); ?>"

                                        data-host-package-id="<?= (int) (
                                            $booking["host_package_id"] ?? 0
                                        ); ?>"

                                        data-event-name="<?= htmlspecialchars(
                                            $eventName,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-event-date="<?= htmlspecialchars(
                                            formatHostBookingDate(
                                                $booking["event_date"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-start-time="<?= htmlspecialchars(
                                            formatHostBookingTime(
                                                $booking["start_time"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-phone="<?= htmlspecialchars(
                                            $booking["phone"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-address="<?= htmlspecialchars(
                                            $booking["address"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-special-requests="<?= htmlspecialchars(
                                            $booking["special_requests"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-host-name="<?= htmlspecialchars(
                                            $hostName,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-host-type="<?= htmlspecialchars(
                                            $hostType,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-status="<?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-created-at="<?= htmlspecialchars(
                                            $booking["created_at"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"

                                        data-updated-at="<?= htmlspecialchars(
                                            $booking["updated_at"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                        <span>
                                            Status
                                        </span>

                                    </button>

                                </td>

                            </tr>


                            <!-- SPECIAL REQUEST -->

                            <?php if (
                                !empty(
                                    trim(
                                        (string) (
                                            $booking["special_requests"] ?? ""
                                        )
                                    )
                                )
                            ): ?>

                                <tr class="special-request-row">

                                    <td colspan="9">

                                        <div class="host-booking-special-request">

                                            <div class="special-request-icon">

                                                <i class="fa-solid fa-message"></i>

                                            </div>


                                            <div>

                                                <strong>
                                                    Special Request
                                                </strong>

                                                <span>

                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $booking["special_requests"],
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        )
                                                    ); ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            <?php endif; ?>


                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                class="no-table-data"
                            >

                                <div class="host-bookings-empty">

                                    <div class="host-bookings-empty-icon">

                                        <i class="fa-solid fa-calendar-xmark"></i>

                                    </div>


                                    <h3>
                                        No Host Bookings Found
                                    </h3>


                                    <p>
                                        There are no host bookings matching your current filters.
                                    </p>


                                    <?php if (
                                        $search !== "" ||
                                        $statusFilter !== "" ||
                                        $eventDate !== ""
                                    ): ?>

                                        <a
                                            href="admin_hostbooking.php"
                                            class="host-bookings-empty-button"
                                        >

                                            <i class="fa-solid fa-rotate-left"></i>

                                            Reset Filters

                                        </a>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     STATUS MODAL
========================================================= -->

<div
    class="host-booking-status-modal"
    id="hostBookingStatusModal"
    aria-hidden="true"
>

    <div
        class="host-booking-status-overlay"
        data-close-host-booking-status
    ></div>


    <div
        class="host-booking-status-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="hostBookingStatusTitle"
    >

        <button
            type="button"
            class="host-booking-status-close"
            id="hostBookingStatusClose"
            aria-label="Close"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div
            class="host-booking-status-icon"
            id="hostBookingStatusIcon"
        >

            <i class="fa-solid fa-calendar-check"></i>

        </div>


        <span class="host-booking-status-label">
            BOOKING STATUS
        </span>


        <h3 id="hostBookingStatusTitle">
            Update Booking Status
        </h3>


        <p id="hostBookingStatusMessage">
            Select the new status for this host booking.
        </p>


        <!-- =================================================
             STATUS OPTIONS
        ================================================== -->

        <div class="host-booking-status-options">

            <button
                type="button"
                class="host-booking-status-option pending"
                data-status-option="pending"
            >

                <i class="fa-solid fa-clock"></i>

                <span>
                    Pending
                </span>

            </button>


            <button
                type="button"
                class="host-booking-status-option confirmed"
                data-status-option="confirmed"
            >

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    Confirmed
                </span>

            </button>


            <button
                type="button"
                class="host-booking-status-option completed"
                data-status-option="completed"
            >

                <i class="fa-solid fa-flag-checkered"></i>

                <span>
                    Completed
                </span>

            </button>


            <button
                type="button"
                class="host-booking-status-option cancelled"
                data-status-option="cancelled"
            >

                <i class="fa-solid fa-ban"></i>

                <span>
                    Cancelled
                </span>

            </button>

        </div>


        <!-- =================================================
             STATUS FORM
        ================================================== -->

        <form
            method="POST"
            action="admin_hostbooking.php"
            id="hostBookingStatusForm"
        >

            <input
                type="hidden"
                name="action"
                value="update_status"
            >


            <input
                type="hidden"
                name="booking_id"
                id="hostBookingStatusBookingId"
                value=""
            >


            <input
                type="hidden"
                name="status"
                id="hostBookingSelectedStatus"
                value=""
            >


            <div class="host-booking-status-actions">

                <button
                    type="button"
                    class="host-booking-status-cancel"
                    id="hostBookingStatusCancel"
                >

                    <i class="fa-solid fa-xmark"></i>

                    <span>
                        Cancel
                    </span>

                </button>


                <button
                    type="submit"
                    class="host-booking-status-confirm"
                    id="hostBookingStatusConfirm"
                    disabled
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        Update Status
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<script src="admin.js/admin_hostbooking.js"></script>


<?php

$pageContent = ob_get_clean();

require_once __DIR__ . "/admin_include/admin_header.php";

?>