<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   REQUIRE LOGIN
========================================================= */
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

/* =========================================================
   REQUIRE FILES
========================================================= */
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

$emailConfig = [];
$emailConfigPath = __DIR__ . "/config/email.php";

if (file_exists($emailConfigPath)) {
    $loadedEmailConfig = require $emailConfigPath;
    if (is_array($loadedEmailConfig)) {
        $emailConfig = $loadedEmailConfig;
    }
}

/* =========================================================
   HELPERS
========================================================= */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function formatDate($value): string
{
    $timestamp = strtotime((string)$value);
    return $timestamp ? date("F j, Y", $timestamp) : (string)$value;
}

function formatTime($value): string
{
    $timestamp = strtotime((string)$value);
    return $timestamp ? date("g:i A", $timestamp) : (string)$value;
}

/* =========================================================
   USER INFORMATION
========================================================= */
$userId = (int)$_SESSION["user_id"];
$userName = $_SESSION["fullname"] ?? "";
$userEmail = $_SESSION["email"] ?? "";
$userAddress = $_SESSION["address"] ?? "";

$userStmt = $conn->prepare("
    SELECT fullname, email, address
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($userStmt) {
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $currentUser = $userResult->fetch_assoc();
    $userStmt->close();

    if ($currentUser) {
        $userName = $currentUser["fullname"] ?? $userName;
        $userEmail = $currentUser["email"] ?? $userEmail;
        $userAddress = $currentUser["address"] ?? "";
    }
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    die("Your account does not have a valid email address. Please update your profile.");
}

/* =========================================================
   GET PACKAGE ID
========================================================= */
if (!isset($_GET["package_id"]) || !is_numeric($_GET["package_id"])) {
    header("Location: host.php");
    exit;
}

$packageId = (int)$_GET["package_id"];

if ($packageId <= 0) {
    header("Location: host.php");
    exit;
}

/* =========================================================
   GET HOST PACKAGE
========================================================= */
$packageStmt = $conn->prepare("
    SELECT
        hp.id,
        hp.host_id,
        hp.package_name,
        hp.price,
        hp.inclusions,
        h.host_name,
        h.host_type,
        h.description,
        h.image
    FROM host_packages hp
    INNER JOIN hosts h ON hp.host_id = h.id
    WHERE hp.id = ?
    LIMIT 1
");

if (!$packageStmt) {
    die("Unable to load host package: " . e($conn->error));
}

$packageStmt->bind_param("i", $packageId);
$packageStmt->execute();
$packageResult = $packageStmt->get_result();
$hostPackage = $packageResult->fetch_assoc();
$packageStmt->close();

if (!$hostPackage) {
    header("Location: host.php");
    exit;
}

/* =========================================================
   HOST INFORMATION
========================================================= */
$hostId = (int)$hostPackage["host_id"];
$hostName = $hostPackage["host_name"] ?? "Host";
$hostType = $hostPackage["host_type"] ?? "Host";
$packageName = $hostPackage["package_name"] ?? "Host Package";
$packagePrice = $hostPackage["price"] ?? "";
$hostDescription = $hostPackage["description"] ?? "";

$hostImage = trim((string)($hostPackage["image"] ?? ""));

if ($hostImage === "") {
    $hostImage = "images/logo.png";
} elseif (
    !preg_match('/^(https?:)?\/\//i', $hostImage) &&
    strpos($hostImage, "/") !== 0 &&
    strpos($hostImage, "images/") !== 0 &&
    strpos($hostImage, "images\\") !== 0
) {
    $hostImage = "images/" . ltrim($hostImage, "/\\");
}

/* =========================================================
   PACKAGE INCLUSIONS
========================================================= */
$inclusions = [];

if (!empty($hostPackage["inclusions"])) {
    $decoded = json_decode($hostPackage["inclusions"], true);

    if (is_array($decoded)) {
        $inclusions = $decoded;
    } else {
        $parts = preg_split("/\r\n|\r|\n|,/", $hostPackage["inclusions"]);
        if (is_array($parts)) {
            $inclusions = array_filter(array_map("trim", $parts), static function ($item) {
                return $item !== "";
            });
        }
    }
}

/* =========================================================
   GET DATES ALREADY BOOKED BY THIS HOST
========================================================= */
$hostBookedDates = [];

$hostBookedStmt = $conn->prepare("
    SELECT DISTINCT event_date
    FROM host_bookings
    WHERE host_id = ?
      AND event_date >= CURDATE()
      AND status IN ('pending', 'confirmed')
    ORDER BY event_date ASC
");

if ($hostBookedStmt) {
    $hostBookedStmt->bind_param("i", $hostId);
    $hostBookedStmt->execute();
    $hostBookedResult = $hostBookedStmt->get_result();

    while ($row = $hostBookedResult->fetch_assoc()) {
        if (!empty($row["event_date"])) {
            $hostBookedDates[] = $row["event_date"];
        }
    }

    $hostBookedStmt->close();
}

/* =========================================================
   GET EXISTING CONFIRMED CUSTOMER EVENTS
   Informational only. These do NOT block host booking.
========================================================= */
$existingEventDates = [];

$existingEventsStmt = $conn->prepare("
    SELECT DISTINCT event_date
    FROM bookings
    WHERE event_date >= CURDATE()
      AND status = 'confirmed'

    UNION

    SELECT DISTINCT event_date
    FROM booking_wedding
    WHERE event_date >= CURDATE()
      AND status = 'confirmed'

    ORDER BY event_date ASC
");

if ($existingEventsStmt) {
    $existingEventsStmt->execute();
    $existingEventsResult = $existingEventsStmt->get_result();

    while ($row = $existingEventsResult->fetch_assoc()) {
        if (!empty($row["event_date"])) {
            $existingEventDates[] = $row["event_date"];
        }
    }

    $existingEventsStmt->close();
}

$hostBookedDates = array_values(array_unique($hostBookedDates));
sort($hostBookedDates);

$existingEventDates = array_values(array_unique($existingEventDates));
sort($existingEventDates);

/* =========================================================
   FORM VARIABLES
========================================================= */
$phone = "";
$eventDate = "";
$startTime = "";
$specialRequests = "";

$errors = [];
$success = false;
$bookingId = 0;

/* =========================================================
   SUBMIT BOOKING
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone = trim($_POST["phone"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $startTime = trim($_POST["start_time"] ?? "");
    $specialRequests = trim($_POST["special_requests"] ?? "");

    /* =====================================================
       VALIDATE PHONE
    ====================================================== */
    $cleanPhone = preg_replace("/[\s\-\(\)]/", "", $phone);

    if ($phone === "") {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match("/^(09\d{9}|\+639\d{9})$/", $cleanPhone)) {
        $errors[] = "Please enter a valid Philippine phone number.";
    }

    /* =====================================================
       VALIDATE ADDRESS
    ====================================================== */
    if (trim($userAddress) === "") {
        $errors[] = "Please add your address in your profile before booking.";
    }

    /* =====================================================
       VALIDATE EVENT DATE
    ====================================================== */
    $dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);
    $dateErrors = DateTime::getLastErrors();

    $dateHasErrors = is_array($dateErrors)
        ? ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)
        : false;

    if ($eventDate === "") {
        $errors[] = "Event date is required.";
    } elseif (!$dateObject || $dateHasErrors || $dateObject->format("Y-m-d") !== $eventDate) {
        $errors[] = "Please select a valid event date.";
    } elseif ($dateObject < new DateTime("today")) {
        $errors[] = "Event date cannot be in the past.";
    }

    /* =====================================================
       VALIDATE START TIME
    ====================================================== */
    $timeObject = DateTime::createFromFormat("!H:i", $startTime);
    $timeErrors = DateTime::getLastErrors();

    $timeHasErrors = is_array($timeErrors)
        ? ($timeErrors["warning_count"] > 0 || $timeErrors["error_count"] > 0)
        : false;

    if ($startTime === "") {
        $errors[] = "Start time is required.";
    } elseif (!$timeObject || $timeHasErrors || $timeObject->format("H:i") !== $startTime) {
        $errors[] = "Please select a valid start time.";
    }

    /* =====================================================
       VALIDATE SPECIAL REQUESTS
    ====================================================== */
    if (strlen($specialRequests) > 2000) {
        $errors[] = "Special requests cannot exceed 2000 characters.";
    }

    /* =====================================================
       CHECK HOST AVAILABILITY
    ====================================================== */
    if (empty($errors)) {
        $availabilityStmt = $conn->prepare("
            SELECT id
            FROM host_bookings
            WHERE host_id = ?
              AND event_date = ?
              AND status IN ('pending', 'confirmed')
            LIMIT 1
        ");

        if (!$availabilityStmt) {
            $errors[] = "Unable to check host availability.";
        } else {
            $availabilityStmt->bind_param("is", $hostId, $eventDate);
            $availabilityStmt->execute();
            $availabilityResult = $availabilityStmt->get_result();

            if ($availabilityResult->num_rows > 0) {
                $errors[] = "This host is already booked on the selected date. Please select another date.";
            }

            $availabilityStmt->close();
        }
    }

    /* =====================================================
       SAVE HOST BOOKING
       event_id is NULL because this page manually selects
       the date and time instead of selecting an event.
    ====================================================== */
    if (empty($errors)) {

        $eventName = "Host Booking - " . $hostName;

        $insertStmt = $conn->prepare("
            INSERT INTO host_bookings (
                user_id,
                event_id,
                host_id,
                host_package_id,
                event_name,
                event_date,
                start_time,
                address,
                phone,
                special_requests,
                status
            )
            VALUES (
                ?,
                NULL,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'pending'
            )
        ");

        if (!$insertStmt) {
            $errors[] = "Unable to prepare host booking request: " . $conn->error;
        } else {

            $insertStmt->bind_param(
                "iiissssss",
                $userId,
                $hostId,
                $packageId,
                $eventName,
                $eventDate,
                $startTime,
                $userAddress,
                $phone,
                $specialRequests
            );

            if ($insertStmt->execute()) {

                $bookingId = (int)$conn->insert_id;

                sendHostBookingEmails(
                    $emailConfig,
                    $bookingId,
                    $userName,
                    $userEmail,
                    $phone,
                    $userAddress,
                    $hostName,
                    $hostType,
                    $packageName,
                    $packagePrice,
                    $eventDate,
                    $startTime,
                    $specialRequests
                );

                $insertStmt->close();

                header(
                    "Location: book_host.php?package_id=" .
                    $packageId .
                    "&success=1&booking_id=" .
                    $bookingId
                );
                exit;

            } else {
                $errors[] = "Unable to submit your host booking. Please try again.";
            }

            $insertStmt->close();
        }
    }
}

/* =========================================================
   SUCCESS MESSAGE
========================================================= */
if (isset($_GET["success"]) && $_GET["success"] === "1") {
    $success = true;
    $bookingId = isset($_GET["booking_id"])
        ? (int)$_GET["booking_id"]
        : 0;
}

/* =========================================================
   SEND HOST BOOKING EMAILS
========================================================= */
function sendHostBookingEmails(
    array $emailConfig,
    int $bookingId,
    string $customerName,
    string $customerEmail,
    string $phone,
    string $address,
    string $hostName,
    string $hostType,
    string $packageName,
    $packagePrice,
    string $eventDate,
    string $startTime,
    string $specialRequests
): bool {

    try {

        $smtpHost = $emailConfig["smtp_host"] ?? "smtp.gmail.com";
        $smtpPort = (int)($emailConfig["smtp_port"] ?? 587);
        $smtpUsername = $emailConfig["smtp_username"] ?? "";
        $smtpPassword = $emailConfig["smtp_password"] ?? "";
        $fromEmail = $emailConfig["from_email"] ?? $smtpUsername;
        $fromName = $emailConfig["from_name"] ?? "Event Solutions by S.H.E.";
        $adminEmail = $emailConfig["admin_email"] ?? "";
        $adminName = $emailConfig["admin_name"] ?? "Administrator";

        if ($smtpUsername === "" || $smtpPassword === "" || $fromEmail === "") {
            error_log("Host booking email error: email configuration is incomplete.");
            return false;
        }

        /* =====================================================
           ADMIN EMAIL
        ====================================================== */
        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {

            $adminMail = new PHPMailer(true);
            $adminMail->isSMTP();
            $adminMail->Host = $smtpHost;
            $adminMail->SMTPAuth = true;
            $adminMail->Username = $smtpUsername;
            $adminMail->Password = $smtpPassword;
            $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $adminMail->Port = $smtpPort;
            $adminMail->CharSet = "UTF-8";

            $adminMail->setFrom($fromEmail, $fromName);
            $adminMail->addAddress($adminEmail, $adminName);

            if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $adminMail->addReplyTo($customerEmail, $customerName);
            }

            $adminMail->isHTML(true);
            $adminMail->Subject = "New Host Booking Request #" . $bookingId;

            $adminMail->Body = '
                <div style="font-family:Arial,Helvetica,sans-serif;max-width:700px;margin:0 auto;border:1px solid #e5ddd2;background:#ffffff;">
                    <div style="background:#1c1c1c;padding:30px;text-align:center;">
                        <h1 style="margin:0;color:#d4a373;">Event Solutions by S.H.E.</h1>
                        <p style="color:#f7efe7;margin:8px 0 0;">Host Booking</p>
                    </div>

                    <div style="padding:30px;color:#333333;">
                        <h2>New Host Booking Request</h2>
                        <p>A new host booking request has been submitted.</p>

                        <div style="background:#faf7f2;padding:20px;border-left:4px solid #d4a373;">
                            <p><strong>Booking Number:</strong> #' . $bookingId . '</p>
                            <p><strong>Customer:</strong> ' . e($customerName) . '</p>
                            <p><strong>Email:</strong> ' . e($customerEmail) . '</p>
                            <p><strong>Phone:</strong> ' . e($phone) . '</p>
                            <p><strong>Address:</strong> ' . e($address) . '</p>
                            <p><strong>Host:</strong> ' . e($hostName) . '</p>
                            <p><strong>Host Type:</strong> ' . e($hostType) . '</p>
                            <p><strong>Package:</strong> ' . e($packageName) . '</p>
                            <p><strong>Price:</strong> ' . e($packagePrice) . '</p>
                            <p><strong>Event Date:</strong> ' . e(formatDate($eventDate)) . '</p>
                            <p><strong>Start Time:</strong> ' . e(formatTime($startTime)) . '</p>
                            <p><strong>Special Requests:</strong> ' .
                                nl2br(e($specialRequests !== "" ? $specialRequests : "None")) .
                            '</p>
                        </div>

                        <div style="margin-top:20px;padding:18px;background:#fff4e8;">
                            <strong>Status:</strong>
                            <span style="color:#b7791f;">Pending Review</span>
                        </div>
                    </div>

                    <div style="background:#1c1c1c;padding:20px;text-align:center;color:#aaa;font-size:12px;">
                        Event Solutions by S.H.E.<br>
                        Creating memorable experiences.
                    </div>
                </div>
            ';

            $adminMail->AltBody =
                "New Host Booking Request #" . $bookingId . "\n\n" .
                "Customer: " . $customerName . "\n" .
                "Email: " . $customerEmail . "\n" .
                "Phone: " . $phone . "\n" .
                "Address: " . $address . "\n\n" .
                "Host: " . $hostName . "\n" .
                "Host Type: " . $hostType . "\n" .
                "Package: " . $packageName . "\n" .
                "Price: " . $packagePrice . "\n" .
                "Event Date: " . formatDate($eventDate) . "\n" .
                "Start Time: " . formatTime($startTime) . "\n\n" .
                "Special Requests: " . ($specialRequests !== "" ? $specialRequests : "None") . "\n\n" .
                "Status: Pending Review";

            $adminMail->send();
        }

        /* =====================================================
           CUSTOMER EMAIL
        ====================================================== */
        $customerMail = new PHPMailer(true);
        $customerMail->isSMTP();
        $customerMail->Host = $smtpHost;
        $customerMail->SMTPAuth = true;
        $customerMail->Username = $smtpUsername;
        $customerMail->Password = $smtpPassword;
        $customerMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $customerMail->Port = $smtpPort;
        $customerMail->CharSet = "UTF-8";

        $customerMail->setFrom($fromEmail, $fromName);
        $customerMail->addAddress($customerEmail, $customerName);

        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $customerMail->addReplyTo($adminEmail, $adminName);
        }

        $customerMail->isHTML(true);
        $customerMail->Subject = "Host Booking Submitted - #" . $bookingId;

        $customerMail->Body = '
            <div style="font-family:Arial,Helvetica,sans-serif;max-width:700px;margin:0 auto;border:1px solid #e5ddd2;background:#ffffff;">
                <div style="background:#1c1c1c;padding:30px;text-align:center;">
                    <h1 style="margin:0;color:#d4a373;">Event Solutions by S.H.E.</h1>
                    <p style="color:#f7efe7;margin:8px 0 0;">Host Booking</p>
                </div>

                <div style="padding:30px;color:#333333;">
                    <h2>Host Booking Submitted Successfully!</h2>
                    <p>Hello ' . e($customerName) . ',</p>

                    <p>
                        Thank you for choosing Event Solutions by S.H.E.
                        Your host booking request has been received.
                    </p>

                    <div style="background:#faf7f2;padding:20px;border-left:4px solid #d4a373;">
                        <p><strong>Booking Number:</strong> #' . $bookingId . '</p>
                        <p><strong>Host:</strong> ' . e($hostName) . '</p>
                        <p><strong>Host Type:</strong> ' . e($hostType) . '</p>
                        <p><strong>Package:</strong> ' . e($packageName) . '</p>
                        <p><strong>Price:</strong> ' . e($packagePrice) . '</p>
                        <p><strong>Event Date:</strong> ' . e(formatDate($eventDate)) . '</p>
                        <p><strong>Start Time:</strong> ' . e(formatTime($startTime)) . '</p>
                    </div>

                    <div style="margin-top:20px;padding:18px;background:#fff4e8;">
                        <strong>Current Status:</strong>
                        <span style="color:#b7791f;">Pending Review</span>
                        <p style="margin-bottom:0;">
                            Our team will review your request and contact you
                            once your booking has been confirmed.
                        </p>
                    </div>

                    <p>
                        Thank you for choosing
                        <strong>Event Solutions by S.H.E.</strong>
                    </p>
                </div>

                <div style="background:#1c1c1c;padding:20px;text-align:center;color:#aaa;font-size:12px;">
                    Event Solutions by S.H.E.<br>
                    Creating memorable experiences.
                </div>
            </div>
        ';

        $customerMail->AltBody =
            "Hello " . $customerName . ",\n\n" .
            "Your host booking request has been submitted successfully.\n\n" .
            "Booking Number: #" . $bookingId . "\n" .
            "Host: " . $hostName . "\n" .
            "Host Type: " . $hostType . "\n" .
            "Package: " . $packageName . "\n" .
            "Price: " . $packagePrice . "\n" .
            "Event Date: " . formatDate($eventDate) . "\n" .
            "Start Time: " . formatTime($startTime) . "\n\n" .
            "Status: Pending Review\n\n" .
            "Our team will review your request and contact you once your booking has been confirmed.\n\n" .
            "Thank you for choosing Event Solutions by S.H.E.";

        $customerMail->send();

        return true;

    } catch (Exception $e) {
        error_log("Host booking email error: " . $e->getMessage());
        return false;
    }
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
        Book Host |
        <?= e($hostName) ?> |
        Event Solutions by S.H.E.
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/book_host.css">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
    >
</head>

<body>

<?php include "includes/header.php"; ?>

<main class="booking-page">

    <section class="booking-hero">
        <div class="booking-hero-decoration"></div>

        <div class="booking-hero-content">
            <span class="booking-label">
                EVENT SOLUTIONS BY S.H.E
            </span>

            <h1>
                Book Your
                <span>Host</span>
            </h1>

            <p>
                Choose your preferred event date and start time,
                then send us your host booking request.
            </p>
        </div>
    </section>

    <section class="booking-section">
        <div class="booking-container">

            <aside class="booking-summary">

                <div class="summary-card">

                    <div class="summary-card-content">

                        <span class="package-brand">
                            EVENT SOLUTIONS BY S.H.E.
                        </span>

                        <h2><?= e($hostName) ?></h2>

                        <span class="summary-event-type">
                            <?= e($hostType) ?>
                        </span>

                        <div class="package-divider"></div>

                        <span class="summary-small-label">
                            SELECTED PACKAGE
                        </span>

                        <h3><?= e($packageName) ?></h3>

                        <?php if ($packagePrice !== ""): ?>
                            <div class="host-price">
                                
                                <?= e('₱ ' .$packagePrice) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hostDescription !== ""): ?>
                            <p class="host-description">
                                <?= e($hostDescription) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($inclusions)): ?>
                            <div class="summary-inclusions">
                                <h3>Package Includes</h3>

                                <ul>
                                    <?php foreach ($inclusions as $inclusion): ?>
                                        <li>
                                            <span class="check-icon">✓</span>
                                            <span><?= e(trim((string)$inclusion)) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="summary-note">
                            <span class="note-icon">✓</span>

                            <p>
                                Your host booking will be reviewed
                                before it is confirmed.
                            </p>
                        </div>

                    </div>

                    <div class="summary-card-image">
                        <img
                            src="<?= e($hostImage) ?>"
                            alt="<?= e($hostName) ?>"
                            onerror="
                                this.style.display='none';
                                this.parentElement.classList.add('image-failed');
                            "
                        >

                        <div class="package-image-overlay"></div>
                    </div>

                </div>

                <div class="booking-help-card">
                    <span>NEED HELP?</span>

                    <h3>
                        Have questions about this host?
                    </h3>

                    <p>
                        Our team is ready to help you
                        plan the perfect event.
                    </p>

                    <a href="contact.php">
                        Contact Us
                        <span>→</span>
                    </a>
                </div>

            </aside>

            <div class="booking-form-wrapper">

                <div class="form-header">
                    <span class="form-step">
                        STEP 01
                    </span>

                    <h2>
                        Host Booking Details
                    </h2>

                    <p>
                        Enter your information and
                        choose your preferred schedule.
                    </p>
                </div>

                <?php if ($success): ?>

                    <div class="booking-success">
                        <div class="success-icon">✓</div>

                        <div>
                            <h3>
                                Host Booking Submitted!
                            </h3>

                            <p>
                                Your request has been submitted successfully.
                                Your booking number is
                                <strong>#<?= $bookingId ?></strong>.
                            </p>

                            <div class="success-actions">
                                <a
                                    class="success-primary"
                                    href="my-event.php"
                                >
                                    View My Events →
                                </a>

                                <a
                                    class="success-secondary"
                                    href="host.php"
                                >
                                    Browse Hosts
                                </a>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if (!empty($errors)): ?>

                    <div class="booking-errors">
                        <strong>
                            Please correct the following:
                        </strong>

                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                <?php endif; ?>

                <?php if (!$success): ?>

                    <form
                        method="POST"
                        action="book_host.php?package_id=<?= $packageId ?>"
                        id="hostBookingForm"
                        novalidate
                    >

                        <div class="form-section">

                            <div class="form-section-heading">
                                <span class="section-number">01</span>

                                <div>
                                    <h3>Your Information</h3>
                                    <p>How can we contact you?</p>
                                </div>
                            </div>

                            <div class="form-grid">

                                <div class="form-group full-width">
                                    <label for="fullName">
                                        Full Name
                                    </label>

                                    <div class="input-wrapper">
                                        

                                        <input
                                            type="text"
                                            id="fullName"
                                            value="<?= e($userName) ?>"
                                            readonly
                                        >
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">
                                        Email Address
                                    </label>

                                    <div class="input-wrapper">
                    

                                        <input
                                            type="email"
                                            id="email"
                                            value="<?= e($userEmail) ?>"
                                            readonly
                                        >
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="phone">
                                        Phone Number
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">
                                    
                                        <input
                                            type="tel"
                                            id="phone"
                                            name="phone"
                                            value="<?= e($phone) ?>"
                                            placeholder="09XXXXXXXXX"
                                            maxlength="14"
                                            required
                                        >
                                    </div>

                                    <small class="field-error"></small>
                                </div>

                                <div class="form-group full-width">
                                    <label for="address">
                                        Address
                                    </label>

                                    <div class="input-wrapper">

                                        <input
                                            type="text"
                                            id="address"
                                            value="<?= e($userAddress) ?>"
                                            readonly
                                        >
                                    </div>

                                    <?php if (trim($userAddress) === ""): ?>
                                        <small class="field-hint">
                                            Please add your address
                                            in your profile before booking.
                                        </small>
                                    <?php endif; ?>

                                </div>

                            </div>
                        </div>

                        <div class="form-section">

                            <div class="form-section-heading">
                                <span class="section-number">02</span>

                                <div>
                                    <h3>Selected Host</h3>
                                    <p>Your selected host and package.</p>
                                </div>
                            </div>

                            <div class="selected-host-card">

                                <div class="selected-host-avatar">
                                    <img
                                        src="<?= e($hostImage) ?>"
                                        alt="<?= e($hostName) ?>"
                                        onerror="this.src='images/logo.png';"
                                    >
                                </div>

                                <div class="selected-host-details">
                                    <span><?= e($hostType) ?></span>

                                    <h3><?= e($hostName) ?></h3>

                                    <p><?= e($packageName) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">

                            <div class="form-section-heading">
                                <span class="section-number">03</span>

                                <div>
                                    <h3>Booking Schedule</h3>
                                    <p>
                                        Manually select your event
                                        date and start time.
                                    </p>
                                </div>
                            </div>

                            <div class="calendar-notice">
                                <span class="material-symbols-outlined">
                                    event
                                </span>

                                <div>
                                    <strong>Calendar Availability</strong>

                                    <p>
                                        Gold dates are already booked
                                        for this host.
                                    </p>
                                </div>
                            </div>

                            <div class="form-grid">

                                <div class="form-group">
                                    <label for="eventDate">
                                        Event Date
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">
                                        

                                        <input
                                            type="text"
                                            id="eventDate"
                                            name="event_date"
                                            value="<?= e($eventDate) ?>"
                                            placeholder="Select event date"
                                            autocomplete="off"
                                            required
                                        >
                                    </div>

                                    <small class="field-error"></small>
                                </div>

                                <div class="form-group">
                                    <label for="startTime">
                                        Start Time
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">
                                        

                                        <input
                                            type="time"
                                            id="startTime"
                                            name="start_time"
                                            value="<?= e($startTime) ?>"
                                            required
                                        >
                                    </div>

                                    <small class="field-error"></small>
                                </div>

                            </div>

                            <div class="calendar-legend">

                                <span>
                                    <i class="legend-available"></i>
                                    Available
                                </span>

                                <span>
                                    <i class="legend-booked"></i>
                                    Host booked
                                </span>

                                <span>
                                    <i class="legend-event"></i>
                                    Existing event
                                </span>

                            </div>

                            <div class="calendar-selected-info" id="calendarSelectedInfo">
                                Select a date to view availability.
                            </div>

                        </div>

                        <div class="form-section">

                            <div class="form-section-heading">
                                <span class="section-number">04</span>

                                <div>
                                    <h3>Additional Information</h3>
                                    <p>
                                        Tell us anything else
                                        we should know.
                                    </p>
                                </div>
                            </div>

                            <div class="form-group full-width">

                                <label for="specialRequests">
                                    Special Requests
                                    <span class="optional">Optional</span>
                                </label>

                                <textarea
                                    id="specialRequests"
                                    name="special_requests"
                                    maxlength="2000"
                                    rows="6"
                                    placeholder="Tell us about the program, theme, special arrangements, or other requests..."
                                ><?= e($specialRequests) ?></textarea>

                                <div class="textarea-footer">
                                    <small>
                                        Our team will review
                                        your requests.
                                    </small>

                                    <span id="characterCount">
                                        0 / 2000
                                    </span>
                                </div>

                            </div>
                        </div>

                        <div class="booking-agreement">

                            <label class="checkbox-label">

                                <input
                                    type="checkbox"
                                    id="bookingAgreement"
                                    required
                                >

                                <span class="custom-checkbox"></span>

                                <span class="agreement-text">
                                    I confirm that the information
                                    provided above is correct and
                                    I understand that this is a
                                    booking request and not yet
                                    a confirmed reservation.
                                </span>

                            </label>

                            <small
                                id="agreementError"
                                class="field-error"
                            ></small>

                        </div>

                        <div class="form-submit">

                            <button
                                type="submit"
                                class="submit-booking"
                                id="submitBooking"
                            >
                                <span class="button-text">
                                    Submit Host Booking
                                </span>

                                <span class="button-arrow">
                                    →
                                </span>

                                <span class="button-loading">
                                    Processing...
                                </span>
                            </button>

                            <p>
                                You will receive confirmation
                                after our team reviews your request.
                            </p>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

        </div>
    </section>

</main>

<!-- =========================================================
     BOOKING SUBMIT LOADING POPUP
========================================================= -->

<div
    class="booking-loading-overlay"
    id="bookingLoadingOverlay"
    aria-hidden="true"
>
    <div
        class="booking-loading-popup"
        role="status"
        aria-live="polite"
    >

        <div class="booking-loading-spinner"></div>

        <h3>
            Submitting Your Booking
        </h3>

        <p>
            Please wait while we submit your host booking request.
            This may take a few moments.
        </p>

        <div class="booking-loading-progress">
            <span></span>
        </div>

        <p class="booking-loading-status">
            Processing host booking...
        </p>

    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
window.hostBookedDates = <?= json_encode(
    $hostBookedDates,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) ?>;

window.eventBookedDates = <?= json_encode(
    $existingEventDates,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) ?>;

window.hostBookingToday = <?= json_encode(date("Y-m-d")) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="js/header.js"></script>
<script src="js/book_host.js"></script>

</body>
</html>
