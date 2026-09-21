<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    empty($_SESSION["user_id"])
) {
    header("Location: auth/login.php");
    exit;
}


/* =========================================================
   REQUIRE FILES
========================================================= */

require_once "config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

$emailConfig = require __DIR__ . "/config/email.php";


/* =========================================================
   USER INFORMATION
========================================================= */

$userId = (int) $_SESSION["user_id"];

$userName = $_SESSION["fullname"] ?? "";
$userEmail = $_SESSION["email"] ?? "";
$userAddress = $_SESSION["address"] ?? "";


/* =========================================================
   FORM VARIABLES
========================================================= */

$celebrantName = "";
$eventName = "";
$eventDate = "";
$startTime = "";
$venueId = "";
$specialRequests = "";

$guestCount = 0;

$errors = [];
$success = false;
$bookingId = 0;


/* =========================================================
   GET CURRENT USER INFORMATION
========================================================= */

$userStmt = $conn->prepare("
    SELECT
        fullname,
        email,
        address
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($userStmt) {

    $userStmt->bind_param(
        "i",
        $userId
    );

    $userStmt->execute();

    $userResult =
        $userStmt->get_result();

    $currentUser =
        $userResult->fetch_assoc();

    $userStmt->close();

    if ($currentUser) {

        $userName =
            $currentUser["fullname"];

        $userEmail =
            $currentUser["email"];

        $userAddress =
            $currentUser["address"] ?? "";
    }
}


/* =========================================================
   VALIDATE CUSTOMER EMAIL
========================================================= */

if (
    !filter_var(
        $userEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    die("Your account does not have a valid email address. Please update your profile.");
}


/* =========================================================
   GET PACKAGE ID
========================================================= */

if (
    !isset($_GET["package_id"]) ||
    !is_numeric($_GET["package_id"])
) {

    header("Location: events.php");
    exit;
}

$packageId =
    (int) $_GET["package_id"];


/* =========================================================
   GET PACKAGE INFORMATION
========================================================= */

$packageStmt = $conn->prepare("
    SELECT
        p.id,
        p.package_name,
        p.inclusions,
        p.image,
        et.name AS event_type
    FROM event_packages p
    LEFT JOIN event_types et
        ON p.event_type_id = et.id
    WHERE p.id = ?
    LIMIT 1
");

if (!$packageStmt) {

    die("Unable to prepare package query: "
        . $conn->error);
}

$packageStmt->bind_param(
    "i",
    $packageId
);

$packageStmt->execute();

$packageResult =
    $packageStmt->get_result();

$package =
    $packageResult->fetch_assoc();

$packageStmt->close();


/* =========================================================
   PACKAGE NOT FOUND
========================================================= */

if (!$package) {

    header("Location: events.php");
    exit;
}


/* =========================================================
   DYNAMIC EVENT TYPE
========================================================= */

$eventType =
    trim(
        $package["event_type"] ?? ""
    );

if ($eventType === "") {
    $eventType = "Event";
}


/* =========================================================
   DYNAMIC EVENT LABELS
========================================================= */

$eventTypeLower =
    strtolower($eventType);


/*
 * Examples:
 *
 * Birthday  -> Birthday Celebrant
 * Debut     -> Debut Celebrant
 * Graduation -> Graduation Celebrant
 */

$celebrantLabel =
    $eventType . " Celebrant";


$bookingTitle =
    "Book Your " . $eventType;


$bookingButtonText =
    "Submit " . $eventType . " Booking";


$eventDescription =
    "Tell us about the "
    . strtolower($eventType)
    . " celebration you are planning.";


$bookingLoadingMessage =
    "Please wait while we process your "
    . strtolower($eventType)
    . " booking.";


/* =========================================================
   PACKAGE INFORMATION
========================================================= */

$packageName =
    $package["package_name"] ?? "";

$packageImage =
    trim(
        (string) ($package["image"] ?? "")
    );

if ($packageImage === "") {

    $packageImage =
        "images/logo.png";
} elseif (
    !preg_match(
        '/^(https?:)?\/\//i',
        $packageImage
    ) &&
    strpos(
        $packageImage,
        "/"
    ) !== 0
) {

    if (
        strpos(
            $packageImage,
            "images/"
        ) !== 0 &&
        strpos(
            $packageImage,
            "images\\"
        ) !== 0
    ) {

        $packageImage =
            "images/"
            . $packageImage;
    }
}


$inclusions = [];

if (!empty($package["inclusions"])) {

    $decoded =
        json_decode(
            $package["inclusions"],
            true
        );

    if (is_array($decoded)) {

        $inclusions =
            $decoded;
    } else {

        $inclusions =
            array_filter(
                preg_split(
                    "/\r\n|\r|\n|,/",
                    $package["inclusions"]
                )
            );
    }
}


/* =========================================================
   GET VENUES
========================================================= */

$venues = [];

$venueStmt = $conn->prepare("
    SELECT
        id,
        venue_name,
        location
    FROM venues
    WHERE status = 'available'
    ORDER BY venue_name ASC
");

if ($venueStmt) {

    $venueStmt->execute();

    $venueResult =
        $venueStmt->get_result();

    while (
        $venue =
        $venueResult->fetch_assoc()
    ) {

        $venues[] =
            $venue;
    }

    $venueStmt->close();
}


/* =========================================================
   GET BOOKED DATES
========================================================= */

$bookedDates = [];


/* ---------------------------------------------------------
   NORMAL BOOKINGS
--------------------------------------------------------- */

$bookedStmt = $conn->prepare("
    SELECT DISTINCT
        event_date
    FROM bookings
    WHERE event_date >= CURDATE()
    AND status = 'confirmed'
    ORDER BY event_date ASC
");

if ($bookedStmt) {

    $bookedStmt->execute();

    $bookedResult =
        $bookedStmt->get_result();

    while (
        $row =
        $bookedResult->fetch_assoc()
    ) {

        if (!empty($row["event_date"])) {

            $bookedDates[] =
                $row["event_date"];
        }
    }

    $bookedStmt->close();
}


/* ---------------------------------------------------------
   WEDDING BOOKINGS
 *
 * Kept only for date conflict protection.
 * Wedding-specific fields/labels are NOT used here.
--------------------------------------------------------- */

$weddingBookedStmt = $conn->prepare("
    SELECT DISTINCT
        event_date
    FROM booking_wedding
    WHERE event_date >= CURDATE()
    AND status = 'confirmed'
    ORDER BY event_date ASC
");

if ($weddingBookedStmt) {

    $weddingBookedStmt->execute();

    $weddingBookedResult =
        $weddingBookedStmt->get_result();

    while (
        $row =
        $weddingBookedResult->fetch_assoc()
    ) {

        if (!empty($row["event_date"])) {

            $bookedDates[] =
                $row["event_date"];
        }
    }

    $weddingBookedStmt->close();
}


/* =========================================================
   REMOVE DUPLICATES / SORT
========================================================= */

$bookedDates =
    array_values(
        array_unique(
            $bookedDates
        )
    );

sort($bookedDates);


/* =========================================================
   JSON FOR JAVASCRIPT
========================================================= */

$bookedDatesJson =
    json_encode(
        $bookedDates,
        JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
    );

if ($bookedDatesJson === false) {

    $bookedDatesJson = "[]";
}


/* =========================================================
   SUBMIT BOOKING
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    /* =====================================================
       GET POST VALUES
    ====================================================== */

    $celebrantName =
        trim(
            $_POST["celebrant_name"] ?? ""
        );

    $eventName =
        trim(
            $_POST["event_name"] ?? ""
        );

    $eventDate =
        trim(
            $_POST["event_date"] ?? ""
        );

    $startTime =
        trim(
            $_POST["start_time"] ?? ""
        );

    $venueId =
        isset($_POST["venue_id"])
        ? (int) $_POST["venue_id"]
        : 0;

    $specialRequests =
        trim(
            $_POST["special_requests"] ?? ""
        );

    $guestCount = 0;


    /* =====================================================
       GENERATE EVENT NAME
    ====================================================== */

    if (!empty($celebrantName)) {

        $eventName =
            $celebrantName
            . "'s "
            . $eventType;
    } else {

        $eventName =
            trim(
                $_POST["event_name"] ?? ""
            );
    }


    /* =====================================================
       VALIDATE CELEBRANT NAME
    ====================================================== */

    if (
        empty($celebrantName)
    ) {

        $errors[] =
            $celebrantLabel
            . " name is required.";
    }


    if (
        strlen($celebrantName) > 150
    ) {

        $errors[] =
            $celebrantLabel
            . " name is too long.";
    }


    /* =====================================================
       VALIDATE EVENT NAME
    ====================================================== */

    if (
        empty($eventName)
    ) {

        $errors[] =
            "Event name is required.";
    }


    /* =====================================================
       VALIDATE DATE
    ====================================================== */

    if (
        empty($eventDate)
    ) {

        $errors[] =
            "Event date is required.";
    } else {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $eventDate
            );

        $dateValid =
            $dateObject &&
            $dateObject->format("Y-m-d")
            === $eventDate;

        if (!$dateValid) {

            $errors[] =
                "Please select a valid event date.";
        } else {

            $today =
                new DateTime(
                    date("Y-m-d")
                );

            if (
                $dateObject < $today
            ) {

                $errors[] =
                    "Event date cannot be in the past.";
            }
        }
    }


    /* =====================================================
       VALIDATE START TIME
    ====================================================== */

    if (
        empty($startTime)
    ) {

        $errors[] =
            "Start time is required.";
    } else {

        $timeObject =
            DateTime::createFromFormat(
                "H:i",
                $startTime
            );

        if (
            !$timeObject ||
            $timeObject->format("H:i")
            !== $startTime
        ) {

            $errors[] =
                "Please select a valid start time.";
        }
    }


    /* =====================================================
       VALIDATE PHONE
    ====================================================== */

    $phone =
        trim(
            $_POST["phone"] ?? ""
        );

    if (
        empty($phone)
    ) {

        $errors[] =
            "Phone number is required.";
    } else {

        $phoneClean =
            preg_replace(
                "/[\s\-\(\)]/",
                "",
                $phone
            );

        if (
            !preg_match(
                "/^(09\d{9}|\+639\d{9})$/",
                $phoneClean
            )
        ) {

            $errors[] =
                "Please enter a valid Philippine phone number.";
        }
    }


    /* =====================================================
       VALIDATE ADDRESS
    ====================================================== */

    if (
        empty(trim($userAddress))
    ) {

        $errors[] =
            "Please add your address in your profile before booking.";
    }


    /* =====================================================
       VALIDATE VENUE
    ====================================================== */

    if (
        $venueId <= 0
    ) {

        $errors[] =
            "Please select a venue.";
    } else {

        $venueCheck =
            $conn->prepare("
                SELECT
                    id,
                    venue_name,
                    location
                FROM venues
                WHERE id = ?
                LIMIT 1
            ");

        if (!$venueCheck) {

            $errors[] =
                "Unable to verify the selected venue.";
        } else {

            $venueCheck->bind_param(
                "i",
                $venueId
            );

            $venueCheck->execute();

            $venueResult =
                $venueCheck->get_result();

            if (
                $venueResult->num_rows === 0
            ) {

                $errors[] =
                    "The selected venue could not be found.";
            }

            $venueCheck->close();
        }
    }


    /* =====================================================
       VALIDATE SPECIAL REQUESTS
    ====================================================== */

    if (
        strlen($specialRequests) > 2000
    ) {

        $errors[] =
            "Special requests cannot exceed 2000 characters.";
    }


    /* =====================================================
       CHECK DATE CONFLICT - NORMAL BOOKINGS
     *
     * Only CONFIRMED bookings block the selected date.
     *
     * Start time is NOT checked.
     * Venue is NOT checked.
     *
     * Pending bookings DO NOT block the date.
    ====================================================== */

    if (
        empty($errors) &&
        !empty($eventDate)
    ) {

        $conflictStmt =
            $conn->prepare("
                SELECT
                    id
                FROM bookings
                WHERE event_date = ?
                AND status = 'confirmed'
                LIMIT 1
            ");

        if (!$conflictStmt) {

            $errors[] =
                "Unable to check date availability.";
        } else {

            $conflictStmt->bind_param(
                "s",
                $eventDate
            );

            $conflictStmt->execute();

            $conflictResult =
                $conflictStmt->get_result();

            if (
                $conflictResult->num_rows > 0
            ) {

                $errors[] =
                    "This date is already booked. Please select a new date.";
            }

            $conflictStmt->close();
        }
    }


    /* =====================================================
       CHECK DATE CONFLICT - WEDDING BOOKINGS
     *
     * Only CONFIRMED wedding bookings block the selected
     * date.
     *
     * Start time is NOT checked.
     * Venue is NOT checked.
     *
     * Pending wedding bookings DO NOT block the date.
    ====================================================== */

    if (
        empty($errors) &&
        !empty($eventDate)
    ) {

        $weddingConflictStmt =
            $conn->prepare("
                SELECT
                    id
                FROM booking_wedding
                WHERE event_date = ?
                AND status = 'confirmed'
                LIMIT 1
            ");

        if (!$weddingConflictStmt) {

            $errors[] =
                "Unable to check date availability.";
        } else {

            $weddingConflictStmt->bind_param(
                "s",
                $eventDate
            );

            $weddingConflictStmt->execute();

            $weddingConflictResult =
                $weddingConflictStmt->get_result();

            if (
                $weddingConflictResult->num_rows > 0
            ) {

                $errors[] =
                    "This date is already booked. Please select a new date.";
            }

            $weddingConflictStmt->close();
        }
    }


    /* =====================================================
       SAVE BOOKING
    ====================================================== */

    if (
        empty($errors)
    ) {

        $insertStmt =
            $conn->prepare("
                INSERT INTO bookings (
                    user_id,
                    event_name,
                    package_id,
                    venue_id,
                    event_date,
                    start_time,
                    guest_count,
                    phone,
                    special_requests,
                    status
                )
                VALUES (
                    ?,
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

            $errors[] =
                "Unable to prepare booking request.";
        } else {

            $insertStmt->bind_param(
                "isiississ",
                $userId,
                $eventName,
                $packageId,
                $venueId,
                $eventDate,
                $startTime,
                $guestCount,
                $phone,
                $specialRequests
            );


            /* =============================================
               EXECUTE
            ============================================== */

            if (
                $insertStmt->execute()
            ) {

                $bookingId =
                    $conn->insert_id;


                /* =========================================
                   GET VENUE INFORMATION
                ========================================== */

                $venueName = "";
                $venueLocation = "";
                $venueImage = "";

                $emailVenueStmt =
                    $conn->prepare("
                        SELECT
                            venue_name,
                            location
                        FROM venues
                        WHERE id = ?
                        LIMIT 1
                    ");

                if ($emailVenueStmt) {

                    $emailVenueStmt->bind_param(
                        "i",
                        $venueId
                    );

                    $emailVenueStmt->execute();

                    $emailVenueResult =
                        $emailVenueStmt->get_result();

                    $emailVenue =
                        $emailVenueResult->fetch_assoc();

                    $emailVenueStmt->close();

                    if ($emailVenue) {

                        $venueName =
                            $emailVenue["venue_name"] ?? "";

                        $venueLocation =
                            $emailVenue["location"] ?? "";
                    }
                }


                /* =========================================
                   GET VENUE IMAGE
                ========================================== */

                $venueImageColumnCheck =
                    $conn->query("
                        SHOW COLUMNS
                        FROM venues
                        LIKE 'image'
                    ");

                if (
                    $venueImageColumnCheck &&
                    $venueImageColumnCheck->num_rows > 0
                ) {

                    $venueImageStmt =
                        $conn->prepare("
                            SELECT
                                image
                            FROM venues
                            WHERE id = ?
                            LIMIT 1
                        ");

                    if ($venueImageStmt) {

                        $venueImageStmt->bind_param(
                            "i",
                            $venueId
                        );

                        $venueImageStmt->execute();

                        $venueImageResult =
                            $venueImageStmt->get_result();

                        $venueImageData =
                            $venueImageResult->fetch_assoc();

                        $venueImageStmt->close();

                        if ($venueImageData) {

                            $venueImage =
                                trim(
                                    (string) (
                                        $venueImageData["image"] ?? ""
                                    )
                                );
                        }
                    }
                }

                if ($venueImage === "") {

                    $venueImage =
                        "images/logo.png";
                }


                /* =========================================
                   SEND EMAILS
                ========================================== */

                sendEventBookingEmails(
                    $emailConfig,
                    $bookingId,
                    $userName,
                    $userEmail,
                    $eventName,
                    $celebrantName,
                    $eventDate,
                    $startTime,
                    $phone,
                    $userAddress,
                    $venueId,
                    $venueName,
                    $venueLocation,
                    $packageId,
                    $packageName,
                    $eventType,
                    $celebrantLabel,
                    $specialRequests,
                    $packageImage,
                    $venueImage
                );


                /* =========================================
                   REDIRECT
                ========================================== */

                header(
                    "Location: book_event.php?package_id="
                        . $packageId
                        . "&success=1&booking_id="
                        . $bookingId
                );

                exit;
            } else {

                $errors[] =
                    "Unable to submit your booking. Please try again.";
            }

            $insertStmt->close();
        }
    }
}


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

if (
    isset($_GET["success"]) &&
    $_GET["success"] === "1"
) {

    $success = true;

    $bookingId =
        isset($_GET["booking_id"])
        ? (int) $_GET["booking_id"]
        : 0;
}


/* =========================================================
   EVENT PUBLIC IMAGE URL FUNCTION
========================================================= */

function buildEventPublicImageUrl(
    $imagePath,
    $baseUrl
) {

    $imagePath =
        trim(
            (string) $imagePath
        );


    if ($imagePath === "") {

        return
            rtrim(
                $baseUrl,
                "/"
            )
            . "/images/logo.png";
    }


    $imagePath =
        str_replace(
            "\\",
            "/",
            $imagePath
        );


    if (
        preg_match(
            '/^https?:\/\//i',
            $imagePath
        )
    ) {

        return $imagePath;
    }


    if (
        strpos(
            $imagePath,
            "//"
        ) === 0
    ) {

        return
            "https:"
            . $imagePath;
    }


    $imagePath =
        ltrim(
            $imagePath,
            "/"
        );


    $projectFolder =
        "eventsolutions";


    if (
        stripos(
            $imagePath,
            $projectFolder . "/"
        ) === 0
    ) {

        $imagePath =
            substr(
                $imagePath,
                strlen(
                    $projectFolder
                ) + 1
            );
    }


    if (
        strpos(
            $imagePath,
            "images/"
        ) !== 0
    ) {

        $imagePath =
            "images/"
            . $imagePath;
    }


    return
        rtrim(
            $baseUrl,
            "/"
        )
        . "/"
        . ltrim(
            $imagePath,
            "/"
        );
}


/* =========================================================
   EMAIL FUNCTION
========================================================= */

function sendEventBookingEmails(
    $emailConfig,
    $bookingId,
    $customerName,
    $customerEmail,
    $eventName,
    $celebrantName,
    $eventDate,
    $startTime,
    $phone,
    $userAddress,
    $venueId,
    $venueName,
    $venueLocation,
    $packageId,
    $packageName,
    $eventType,
    $celebrantLabel,
    $specialRequests,
    $packageImage,
    $venueImage
) {

    try {

        /* =================================================
           FORMAT DATE AND TIME
        ================================================== */

        $formattedDate =
            date(
                "F j, Y",
                strtotime($eventDate)
            );

        $formattedStartTime =
            date(
                "g:i A",
                strtotime($startTime)
            );


        /* =================================================
           WEBSITE BASE URL
        ================================================== */

        $baseUrl =
            "https://eventsolution.page.gd";


        /* =================================================
           PACKAGE / VENUE URLS
        ================================================== */

        $packageUrl =
            $baseUrl
            . "/view_package.php?id="
            . (int) $packageId;


        $venueUrl =
            $baseUrl
            . "/view_venue.php?id="
            . (int) $venueId;


        /* =================================================
           PACKAGE IMAGE URL
        ================================================== */

        $packageImageUrl =
            buildEventPublicImageUrl(
                $packageImage,
                $baseUrl
            );


        /* =================================================
           VENUE IMAGE URL
        ================================================== */

        $venueImageUrl =
            buildEventPublicImageUrl(
                $venueImage,
                $baseUrl
            );


        /* =================================================
           SAFE HTML VALUES
        ================================================== */

        $safeCustomerName =
            htmlspecialchars(
                $customerName,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeCustomerEmail =
            htmlspecialchars(
                $customerEmail,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeEventName =
            htmlspecialchars(
                $eventName,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeCelebrantName =
            htmlspecialchars(
                $celebrantName,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeCelebrantLabel =
            htmlspecialchars(
                $celebrantLabel,
                ENT_QUOTES,
                "UTF-8"
            );

        $safePhone =
            htmlspecialchars(
                $phone !== ""
                    ? $phone
                    : "Not provided",
                ENT_QUOTES,
                "UTF-8"
            );

        $safeAddress =
            htmlspecialchars(
                $userAddress !== ""
                    ? $userAddress
                    : "Not provided",
                ENT_QUOTES,
                "UTF-8"
            );

        $safeVenueName =
            htmlspecialchars(
                $venueName,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeVenueLocation =
            htmlspecialchars(
                $venueLocation !== ""
                    ? $venueLocation
                    : "Not provided",
                ENT_QUOTES,
                "UTF-8"
            );

        $safePackageName =
            htmlspecialchars(
                $packageName,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeEventType =
            htmlspecialchars(
                $eventType,
                ENT_QUOTES,
                "UTF-8"
            );

        $safePackageUrl =
            htmlspecialchars(
                $packageUrl,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeVenueUrl =
            htmlspecialchars(
                $venueUrl,
                ENT_QUOTES,
                "UTF-8"
            );

        $safePackageImageUrl =
            htmlspecialchars(
                $packageImageUrl,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeVenueImageUrl =
            htmlspecialchars(
                $venueImageUrl,
                ENT_QUOTES,
                "UTF-8"
            );

        $safeSpecialRequests =
            nl2br(
                htmlspecialchars(
                    $specialRequests !== ""
                        ? $specialRequests
                        : "None",
                    ENT_QUOTES,
                    "UTF-8"
                )
            );


        /* =================================================
           ADMIN EMAIL
        ================================================== */

        $adminMail =
            new PHPMailer(true);

        $adminMail->isSMTP();

        $adminMail->Host =
            $emailConfig["smtp_host"];

        $adminMail->SMTPAuth =
            true;

        $adminMail->Username =
            $emailConfig["smtp_username"];

        $adminMail->Password =
            $emailConfig["smtp_password"];

        $adminMail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $adminMail->Port =
            $emailConfig["smtp_port"];

        $adminMail->CharSet =
            "UTF-8";


        /*
         * Prevent PHPMailer from adding
         * the default X-Mailer header.
         */

        $adminMail->XMailer =
            "";


        $adminMail->setFrom(
            $emailConfig["from_email"],
            $emailConfig["from_name"]
        );


        $adminMail->addAddress(
            $emailConfig["admin_email"],
            $emailConfig["admin_name"]
        );


        $adminMail->addReplyTo(
            $customerEmail,
            $customerName
        );


        $adminMail->isHTML(true);


        $adminMail->Subject =
            "New "
            . $eventType
            . " Booking Request #"
            . $bookingId;


        /* =================================================
           ADMIN BODY
        ================================================== */

        $adminMail->Body = '

        <div style="
            font-family:Arial,Helvetica,sans-serif;
            max-width:700px;
            margin:0 auto;
            background:#ffffff;
            border:1px solid #e5ddd2;
        ">

            <div style="
                background:#1c1c1c;
                padding:30px;
                text-align:center;
            ">

                <h1 style="
                    margin:0;
                    color:#d4a373;
                    font-size:26px;
                ">
                    Event Solutions by S.H.E.
                </h1>

                <p style="
                    margin:8px 0 0;
                    color:#f7efe7;
                    font-size:14px;
                ">
                    New ' . $safeEventType . ' Booking Request
                </p>

            </div>


            <div style="
                padding:30px;
                color:#333333;
            ">

                <h2 style="
                    margin-top:0;
                    color:#222222;
                ">
                    ' . $safeEventType . ' Booking Request #'
            . $bookingId . '
                </h2>

                <p>
                    A new ' . $safeEventType . '
                    booking request has been submitted
                    through the Event Solutions by S.H.E.
                    website.
                </p>


                <div style="
                    margin:25px 0;
                    padding:20px;
                    background:#faf7f2;
                    border-left:4px solid #d4a373;
                ">

                    <h3 style="
                        margin-top:0;
                        color:#222222;
                    ">
                        Customer Information
                    </h3>

                    <p>
                        <strong>Name:</strong>
                        ' . $safeCustomerName . '
                    </p>

                    <p>
                        <strong>Email:</strong>
                        ' . $safeCustomerEmail . '
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        ' . $safePhone . '
                    </p>

                    <p>
                        <strong>Address:</strong>
                        ' . $safeAddress . '
                    </p>

                </div>


                <div style="
                    margin:25px 0;
                    padding:20px;
                    background:#faf7f2;
                    border-left:4px solid #d4a373;
                ">

                    <h3 style="
                        margin-top:0;
                        color:#222222;
                    ">
                        ' . $safeEventType . ' Information
                    </h3>


                    <p>
                        <strong>' . $safeCelebrantLabel . ':</strong>
                        ' . $safeCelebrantName . '
                    </p>


                    <p>
                        <strong>Event Name:</strong>
                        ' . $safeEventName . '
                    </p>


                    <!-- PACKAGE -->

                    <div style="
                        width:100%;
                        margin:15px 0 20px;
                        padding:15px;
                        box-sizing:border-box;
                        border:1px solid #e5ddd2;
                        border-radius:10px;
                        background:#ffffff;
                    ">

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="width:100%;"
                        >

                            <tr>

                                <td
                                    valign="middle"
                                    style="
                                        width:65%;
                                        padding:5px 10px 5px 0;
                                        vertical-align:middle;
                                    "
                                >

                                    <p style="
                                        margin:0 0 10px;
                                        font-size:15px;
                                        line-height:1.5;
                                    ">

                                        <strong>
                                            Package:
                                        </strong>

                                        <br>

                                        ' . $safePackageName . '

                                    </p>


                                    <a
                                        href="' . $safePackageUrl . '"
                                        target="_blank"
                                        style="
                                            display:inline-block;
                                            padding:8px 14px;
                                            border-radius:6px;
                                            background:#b99445;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-size:13px;
                                            font-weight:bold;
                                        "
                                    >
                                        View Package Details →
                                    </a>

                                </td>


                                <td
                                    valign="middle"
                                    align="right"
                                    style="
                                        width:35%;
                                        padding:5px 0 5px 10px;
                                        text-align:right;
                                        vertical-align:middle;
                                    "
                                >

                                    <a
                                        href="' . $safePackageUrl . '"
                                        target="_blank"
                                        style="
                                            text-decoration:none;
                                            display:inline-block;
                                        "
                                    >

                                        <img
                                            src="' . $safePackageImageUrl . '"
                                            alt="' . $safePackageName . '"
                                            width="140"
                                            style="
                                                display:block;
                                                width:140px;
                                                max-width:100%;
                                                height:95px;
                                                object-fit:cover;
                                                border-radius:8px;
                                                border:1px solid #e5ddd2;
                                            "
                                        >

                                    </a>

                                </td>

                            </tr>

                        </table>

                    </div>


                    <p>
                        <strong>Event Type:</strong>
                        ' . $safeEventType . '
                    </p>


                    <p>
                        <strong>Date:</strong>
                        ' . $formattedDate . '
                    </p>


                    <p>
                        <strong>Start Time:</strong>
                        ' . $formattedStartTime . '
                    </p>


                    <!-- VENUE -->

                    <div style="
                        width:100%;
                        margin:15px 0 20px;
                        padding:15px;
                        box-sizing:border-box;
                        border:1px solid #e5ddd2;
                        border-radius:10px;
                        background:#ffffff;
                    ">

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="width:100%;"
                        >

                            <tr>

                                <td
                                    valign="middle"
                                    style="
                                        width:65%;
                                        padding:5px 10px 5px 0;
                                        vertical-align:middle;
                                    "
                                >

                                    <p style="
                                        margin:0 0 10px;
                                        font-size:15px;
                                        line-height:1.5;
                                    ">

                                        <strong>
                                            Venue:
                                        </strong>

                                        <br>

                                        ' . $safeVenueName . '

                                    </p>


                                    <p style="
                                        margin:0 0 12px;
                                        font-size:13px;
                                        line-height:1.5;
                                    ">

                                        ' . $safeVenueLocation . '

                                    </p>


                                    <a
                                        href="' . $safeVenueUrl . '"
                                        target="_blank"
                                        style="
                                            display:inline-block;
                                            padding:8px 14px;
                                            border-radius:6px;
                                            background:#b99445;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-size:13px;
                                            font-weight:bold;
                                        "
                                    >
                                        View Venue Details →
                                    </a>

                                </td>


                                <td
                                    valign="middle"
                                    align="right"
                                    style="
                                        width:35%;
                                        padding:5px 0 5px 10px;
                                        text-align:right;
                                        vertical-align:middle;
                                    "
                                >

                                    <a
                                        href="' . $safeVenueUrl . '"
                                        target="_blank"
                                        style="
                                            text-decoration:none;
                                            display:inline-block;
                                        "
                                    >

                                        <img
                                            src="' . $safeVenueImageUrl . '"
                                            alt="' . $safeVenueName . '"
                                            width="140"
                                            style="
                                                display:block;
                                                width:140px;
                                                max-width:100%;
                                                height:95px;
                                                object-fit:cover;
                                                border-radius:8px;
                                                border:1px solid #e5ddd2;
                                            "
                                        >

                                    </a>

                                </td>

                            </tr>

                        </table>

                    </div>


                    <p>
                        <strong>Location:</strong>
                        ' . $safeVenueLocation . '
                    </p>

                </div>


                <div style="
                    margin:25px 0;
                    padding:20px;
                    background:#faf7f2;
                    border-left:4px solid #d4a373;
                ">

                    <h3 style="
                        margin-top:0;
                        color:#222222;
                    ">
                        Special Requests
                    </h3>

                    <p>
                        ' . $safeSpecialRequests . '
                    </p>

                </div>


                <div style="
                    margin-top:30px;
                    padding:15px;
                    background:#fff4e8;
                    text-align:center;
                ">

                    <strong>
                        Booking Status:
                    </strong>

                    <span style="
                        color:#b7791f;
                    ">
                        Pending Review
                    </span>

                </div>

            </div>


            <div style="
                background:#1c1c1c;
                padding:20px;
                text-align:center;
                color:#aaaaaa;
                font-size:12px;
            ">

                Event Solutions by S.H.E.<br>
                Creating memorable experiences.

            </div>

        </div>
        ';


        /* =================================================
           ADMIN PLAIN TEXT
        ================================================== */

        $adminMail->AltBody =
            "New "
            . $eventType
            . " Booking Request #"
            . $bookingId
            . "\n\n"

            . "Customer: "
            . $customerName
            . "\n"

            . "Email: "
            . $customerEmail
            . "\n"

            . "Phone: "
            . $phone
            . "\n"

            . "Address: "
            . $userAddress
            . "\n\n"

            . $celebrantLabel
            . ": "
            . $celebrantName
            . "\n"

            . "Event Name: "
            . $eventName
            . "\n"

            . "Event Type: "
            . $eventType
            . "\n"

            . "Package: "
            . $packageName
            . "\n"

            . "Package Details: "
            . $packageUrl
            . "\n"

            . "Package Image: "
            . $packageImageUrl
            . "\n"

            . "Date: "
            . $formattedDate
            . "\n"

            . "Start Time: "
            . $formattedStartTime
            . "\n"

            . "Venue: "
            . $venueName
            . "\n"

            . "Venue Details: "
            . $venueUrl
            . "\n"

            . "Venue Image: "
            . $venueImageUrl
            . "\n"

            . "Location: "
            . $venueLocation
            . "\n\n"

            . "Special Requests: "
            . (
                $specialRequests !== ""
                ? $specialRequests
                : "None"
            );


        try {

            $adminMail->send();
        } catch (Exception $e) {

            error_log(
                "Event booking ADMIN email error: "
                    . $adminMail->ErrorInfo
            );

            throw $e;
        }


        /* =================================================
           CUSTOMER EMAIL
        ================================================== */

        $customerMail =
            new PHPMailer(true);


        $customerMail->isSMTP();

        $customerMail->Host =
            $emailConfig["smtp_host"];

        $customerMail->SMTPAuth =
            true;

        $customerMail->Username =
            $emailConfig["smtp_username"];

        $customerMail->Password =
            $emailConfig["smtp_password"];

        $customerMail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $customerMail->Port =
            $emailConfig["smtp_port"];

        $customerMail->CharSet =
            "UTF-8";


        /*
         * Prevent PHPMailer from adding
         * the default X-Mailer header.
         */

        $customerMail->XMailer =
            "";


        $customerMail->setFrom(
            $emailConfig["from_email"],
            $emailConfig["from_name"]
        );


        $customerMail->addAddress(
            $customerEmail,
            $customerName
        );


        $customerMail->addReplyTo(
            $emailConfig["admin_email"],
            $emailConfig["admin_name"]
        );


        $customerMail->isHTML(true);


        $customerMail->Subject =
            $eventType
            . " Booking Request Submitted - #"
            . $bookingId;


        /* =================================================
           CUSTOMER BODY
        ================================================== */

        $customerMail->Body = '

        <div style="
            font-family:Arial,Helvetica,sans-serif;
            max-width:700px;
            margin:0 auto;
            background:#ffffff;
            border:1px solid #e5ddd2;
        ">

            <div style="
                background:#1c1c1c;
                padding:30px;
                text-align:center;
            ">

                <h1 style="
                    margin:0;
                    color:#d4a373;
                    font-size:26px;
                ">
                    Event Solutions by S.H.E.
                </h1>

                <p style="
                    margin:8px 0 0;
                    color:#f7efe7;
                    font-size:14px;
                ">
                    ' . $safeEventType . ' Booking Confirmation
                </p>

            </div>


            <div style="
                padding:30px;
                color:#333333;
            ">

                <h2 style="
                    margin-top:0;
                    color:#222222;
                ">
                    ' . $safeEventType . ' Booking Submitted Successfully!
                </h2>


                <p>
                    Hello ' . $safeCustomerName . ',
                </p>


                <p>
                    Thank you for submitting your '
            . $safeEventType
            . ' booking request with Event Solutions
                    by S.H.E.
                </p>


                <p>
                    Your request has been successfully
                    received and is currently pending review
                    by our team.
                </p>


                <div style="
                    margin:25px 0;
                    padding:20px;
                    background:#faf7f2;
                    border-left:4px solid #d4a373;
                ">

                    <h3 style="
                        margin-top:0;
                        color:#222222;
                    ">
                        ' . $safeEventType . ' Details
                    </h3>


                    <p>
                        <strong>
                            Booking Number:
                        </strong>
                        #' . $bookingId . '
                    </p>


                    <p>
                        <strong>
                            ' . $safeCelebrantLabel . ':
                        </strong>
                        ' . $safeCelebrantName . '
                    </p>


                    <p>
                        <strong>
                            Event Name:
                        </strong>
                        ' . $safeEventName . '
                    </p>


                    <p>
                        <strong>
                            Phone Number:
                        </strong>
                        ' . $safePhone . '
                    </p>


                    <!-- PACKAGE -->

                    <div style="
                        width:100%;
                        margin:15px 0 20px;
                        padding:12px;
                        box-sizing:border-box;
                        border:1px solid #e5ddd2;
                        border-radius:10px;
                        background:#b99445;
                    ">

                        <p style="
                            margin:0;
                            font-size:12px;
                            line-height:1.6;
                        ">

                            <strong>
                                Package:
                            </strong>

                            <br>

                            <a
                                href="' . $safePackageUrl . '"
                                target="_blank"
                                style="
                                    color:#b99445;
                                    text-decoration:none;
                                    font-weight:400;
                                "
                            >
                                ' . $safePackageName . '
                            </a>

                        </p>

                    </div>


                    <p>
                        <strong>
                            Event Type:
                        </strong>
                        ' . $safeEventType . '
                    </p>


                    <p>
                        <strong>
                            Date:
                        </strong>
                        ' . $formattedDate . '
                    </p>


                    <p>
                        <strong>
                            Start Time:
                        </strong>
                        ' . $formattedStartTime . '
                    </p>


                    <!-- VENUE -->

                    <div style="
                        width:100%;
                        margin:15px 0 20px;
                        padding:12px;
                        box-sizing:border-box;
                        border:1px solid #e5ddd2;
                        border-radius:10px;
                        background:#ffffff;
                    ">

                        <p style="
                            margin:0 0 8px;
                            font-size:12px;
                            line-height:1.6;
                        ">

                            <strong>
                                Venue:
                            </strong>

                            <br>

                            <a
                                href="' . $safeVenueUrl . '"
                                target="_blank"
                                style="
                                    color:#b99445;
                                    text-decoration:none;
                                    font-weight:600;
                                "
                            >
                                ' . $safeVenueName . '
                            </a>

                        </p>


                        <p style="
                            margin:0;
                            font-size:13px;
                            line-height:1.5;
                        ">

                            ' . $safeVenueLocation . '

                        </p>

                    </div>


                    <p>
                        <strong>
                            Location:
                        </strong>
                        ' . $safeVenueLocation . '
                    </p>

                </div>


                <div style="
                    margin:25px 0;
                    padding:18px;
                    background:#fff4e8;
                    border-left:4px solid #d4a373;
                ">

                    <strong>
                        Current Status:
                    </strong>

                    <span style="
                        color:#b7791f;
                    ">
                        Pending Review
                    </span>

                    <p style="
                        margin-bottom:0;
                        margin-top:10px;
                    ">
                        Our team will review your '
            . strtolower($safeEventType)
            . ' booking request and contact you once
                        your reservation has been confirmed.
                    </p>

                </div>


                <p>
                    If you need to make changes or have
                    questions about your '
            . strtolower($safeEventType)
            . ' booking, please contact our team.
                </p>


                <p style="
                    margin-top:30px;
                ">
                    Thank you for choosing
                    <strong>
                        Event Solutions by S.H.E.
                    </strong>
                </p>

            </div>


            <div style="
                background:#1c1c1c;
                padding:20px;
                text-align:center;
                color:#aaaaaa;
                font-size:12px;
            ">

                Event Solutions by S.H.E.<br>
                Creating memorable experiences.

            </div>

        </div>
        ';


        /* =================================================
           CUSTOMER PLAIN TEXT
        ================================================== */

        $customerMail->AltBody =
            "Hello "
            . $customerName
            . ",\n\n"

            . "Your "
            . $eventType
            . " booking request has been "
            . "submitted successfully.\n\n"

            . "Booking Number: #"
            . $bookingId
            . "\n"

            . $celebrantLabel
            . ": "
            . $celebrantName
            . "\n"

            . "Event Name: "
            . $eventName
            . "\n"

            . "Phone Number: "
            . $phone
            . "\n"

            . "Package: "
            . $packageName
            . "\n"

            . "Package Details: "
            . $packageUrl
            . "\n"

            . "Event Type: "
            . $eventType
            . "\n"

            . "Date: "
            . $formattedDate
            . "\n"

            . "Start Time: "
            . $formattedStartTime
            . "\n"

            . "Venue: "
            . $venueName
            . "\n"

            . "Venue Details: "
            . $venueUrl
            . "\n"

            . "Location: "
            . $venueLocation
            . "\n\n"

            . "Status: Pending Review\n\n"

            . "Our team will review your "
            . strtolower($eventType)
            . " booking request and contact you once "
            . "your reservation has been confirmed.\n\n"

            . "Thank you for choosing "
            . "Event Solutions by S.H.E.";


        try {

            $customerMail->send();
        } catch (Exception $e) {

            error_log(
                "Event booking CUSTOMER email error: "
                    . $customerMail->ErrorInfo
            );

            throw $e;
        }


        return true;
    } catch (Exception $e) {

        error_log(
            "Event booking email error: "
                . $e->getMessage()
        );

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
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($bookingTitle) ?> |
        Event Solutions by S.H.E
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <!-- MATERIAL SYMBOLS -->

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
        rel="stylesheet">


    <!-- HEADER -->

    <link
        rel="stylesheet"
        href="css/header.css">

    <link
        rel="stylesheet"
        href="css/footer.css">


    <!-- BOOKING CSS -->

    <link
        rel="stylesheet"
        href="css/book_event.css">


    <!-- FLATPICKR -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

</head>


<body>


    <?php include "includes/header.php"; ?>


    <main class="booking-page">


        <!-- =====================================================
         HERO
    ====================================================== -->

        <section class="booking-hero">

            <div class="booking-hero-decoration"></div>

            <div class="booking-hero-content">

                <span class="booking-label">
                    EVENT SOLUTIONS BY S.H.E
                </span>

                <h1>
                    Book Your
                    <span>
                        <?= htmlspecialchars($eventType) ?>
                    </span>
                </h1>

                <p>
                    <?= htmlspecialchars($eventDescription) ?>
                    Let us create a memorable experience for you.
                </p>

            </div>

        </section>



        <!-- =====================================================
         BOOKING CONTENT
    ====================================================== -->

        <section class="booking-section">

            <div class="booking-container">


                <!-- =================================================
                 LEFT SUMMARY
            ================================================== -->

                <aside class="booking-summary">


                    <div class="summary-card">


                        <div class="summary-card-content">


                            <div
                                class="package-top-decoration"
                                aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>


                            <span class="package-brand">
                                EVENT SOLUTIONS BY S.H.E.
                            </span>


                            <h2>
                                <?= htmlspecialchars(
                                    $packageName
                                ) ?>
                            </h2>


                            <?php if (!empty($eventType)): ?>

                                <span class="summary-event-type">
                                    <?= htmlspecialchars(
                                        $eventType
                                    ) ?>
                                </span>

                            <?php endif; ?>


                            <div class="package-divider"></div>


                            <?php if (!empty($inclusions)): ?>

                                <div class="summary-inclusions">

                                    <h3>
                                        Package Includes
                                    </h3>

                                    <ul>

                                        <?php foreach (
                                            $inclusions
                                            as $inclusion
                                        ): ?>

                                            <li>

                                                <span
                                                    class="check-icon"
                                                    aria-hidden="true">

                                                    <svg
                                                        viewBox="0 0 24 24">

                                                        <polyline
                                                            points="20 6 9 17 4 12"></polyline>

                                                    </svg>

                                                </span>

                                                <span>
                                                    <?= htmlspecialchars(
                                                        trim($inclusion)
                                                    ) ?>
                                                </span>

                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                </div>

                            <?php endif; ?>


                            <div class="summary-note">

                                <span
                                    class="note-icon"
                                    aria-hidden="true">
                                    ✓
                                </span>

                                <p>
                                    Your booking will be reviewed
                                    by our team before it is confirmed.
                                </p>

                            </div>


                            <div
                                class="package-bottom-decoration"
                                aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                        </div>


                        <div class="summary-card-image">

                            <img
                                src="<?= htmlspecialchars($packageImage) ?>"
                                alt="<?= htmlspecialchars(
                                            $packageName
                                        ) ?>"
                                loading="eager"
                                onerror="this.style.display='none'; this.parentElement.classList.add('image-failed');">

                            <div class="package-image-overlay"></div>

                            <div
                                class="image-decoration"
                                aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                        </div>


                    </div>


                    <!-- HELP -->

                    <div class="booking-help-card">

                        <span>
                            NEED HELP?
                        </span>

                        <h3>
                            Have questions about your
                            <?= htmlspecialchars(
                                strtolower($eventType)
                            ) ?> event?
                        </h3>

                        <p>
                            Our team is ready to help you
                            plan the perfect celebration.
                        </p>

                        <a href="contact.php">

                            Contact Us

                            <span aria-hidden="true">
                                →
                            </span>

                        </a>

                    </div>


                </aside>



                <!-- =================================================
                 RIGHT FORM
            ================================================== -->

                <div class="booking-form-wrapper">


                    <div class="form-header">

                        <span class="form-step">
                            STEP 01
                        </span>

                        <h2>
                            <?= htmlspecialchars($eventType) ?> Details
                        </h2>

                        <p>
                            Please provide the details of
                            the <?= htmlspecialchars(
                                    strtolower($eventType)
                                ) ?> celebration.
                        </p>

                    </div>



                    <!-- =================================================
                     SUCCESS
                ================================================== -->

                    <?php if ($success): ?>

                        <div class="booking-success">

                            <div class="success-icon">
                                ✓
                            </div>

                            <div>

                                <h3>
                                    Booking Submitted!
                                </h3>

                                <p>

                                    Your
                                    <?= htmlspecialchars(
                                        strtolower($eventType)
                                    ) ?>
                                    booking request has been
                                    submitted successfully.

                                    <?php if (!empty($bookingId)): ?>

                                        Your booking number is

                                        <strong>
                                            #<?= $bookingId ?>
                                        </strong>.

                                    <?php endif; ?>

                                </p>


                                <div class="success-actions">

                                    <a
                                        href="my-event.php"
                                        class="success-primary">

                                        View My Events

                                        <span aria-hidden="true">
                                            →
                                        </span>

                                    </a>


                                    <a
                                        href="events.php"
                                        class="success-secondary">
                                        Browse Events
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                     ERRORS
                ================================================== -->

                    <?php if (!empty($errors)): ?>

                        <div class="booking-errors">

                            <strong>
                                Please correct the following:
                            </strong>

                            <ul>

                                <?php foreach (
                                    $errors
                                    as $error
                                ): ?>

                                    <li>
                                        <?= htmlspecialchars(
                                            $error
                                        ) ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                     FORM
                ================================================== -->

                    <?php if (!$success): ?>

                        <form
                            method="POST"
                            action="book_event.php?package_id=<?= $packageId ?>"
                            id="bookingForm"
                            novalidate>


                            <input
                                type="hidden"
                                name="package_id"
                                value="<?= $packageId ?>">


                            <!-- =================================================
                             CUSTOMER INFORMATION
                        ================================================== -->

                            <div class="form-section">


                                <div class="form-section-heading">

                                    <span class="section-number">
                                        01
                                    </span>

                                    <div>

                                        <h3>
                                            Your Information
                                        </h3>

                                        <p>
                                            How can we contact you?
                                        </p>

                                    </div>

                                </div>


                                <div class="form-grid">


                                    <!-- FULL NAME -->

                                    <div class="form-group full-width">

                                        <label for="fullName">
                                            Full Name :
                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="text"
                                                id="fullName"
                                                placeholder="Enter your full name"
                                                value="<?= htmlspecialchars(
                                                            $userName
                                                        ) ?>">

                                        </div>

                                    </div>


                                    <!-- EMAIL -->

                                    <div class="form-group">

                                        <label for="email">
                                            Email Address
                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="email"
                                                id="email"
                                                placeholder="Enter your email address"
                                                value="<?= htmlspecialchars(
                                                            $userEmail
                                                        ) ?>">

                                        </div>

                                    </div>


                                    <!-- PHONE -->

                                    <div class="form-group">

                                        <label for="phone">

                                            Phone Number

                                            <span>
                                                *
                                            </span>

                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="tel"
                                                id="phone"
                                                name="phone"
                                                placeholder="09XXXXXXXXX"
                                                value="<?= htmlspecialchars(
                                                            $_POST["phone"] ?? ""
                                                        ) ?>"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="phone"></small>

                                    </div>


                                    <!-- ADDRESS -->

                                    <div class="form-group full-width">

                                        <label for="address">
                                            Address
                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true">

                                            </span>

                                            <input
                                                type="text"
                                                id="address"
                                                placeholder="Enter your address"
                                                value="<?= htmlspecialchars(
                                                            $userAddress
                                                        ) ?>">

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="address"></small>


                                        <?php if (
                                            empty($userAddress)
                                        ): ?>

                                            <small class="field-hint">
                                                Please add your address
                                                in your profile before booking.
                                            </small>

                                        <?php endif; ?>

                                    </div>


                                </div>

                            </div>



                            <!-- =================================================
                             EVENT DETAILS
                        ================================================== -->

                            <div class="form-section">


                                <div class="form-section-heading">

                                    <span class="section-number">
                                        02
                                    </span>

                                    <div>

                                        <h3>
                                            <?= htmlspecialchars(
                                                $eventType
                                            ) ?> Details
                                        </h3>

                                        <p>
                                            <?= htmlspecialchars(
                                                $eventDescription
                                            ) ?>
                                        </p>

                                    </div>

                                </div>


                                <div class="form-grid">


                                    <!-- CELEBRANT NAME -->

                                    <div class="form-group full-width">

                                        <label for="celebrantName">

                                            <?= htmlspecialchars(
                                                $celebrantLabel
                                            ) ?>

                                            <span>
                                                *
                                            </span>

                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="text"
                                                id="celebrantName"
                                                name="celebrant_name"
                                                maxlength="150"
                                                placeholder="Enter <?= htmlspecialchars(
                                                                        strtolower(
                                                                            $celebrantLabel
                                                                        )
                                                                    ) ?> name"
                                                value="<?= htmlspecialchars(
                                                            $celebrantName
                                                        ) ?>"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="celebrant_name"></small>

                                    </div>



                                    <!-- EVENT NAME -->

                                    <div class="form-group full-width">

                                        <label for="eventName">
                                            Event Name
                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true">
                                            </span>

                                            <input
                                                type="text"
                                                id="eventName"
                                                name="event_name"
                                                maxlength="200"
                                                value="<?= htmlspecialchars(
                                                            $eventName
                                                        ) ?>"
                                                readonly>

                                        </div>

                                    </div>



                                    <!-- DATE -->

                                    <div class="form-group">

                                        <label for="eventDate">

                                            Event Date

                                            <span>
                                                *
                                            </span>

                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="text"
                                                id="eventDate"
                                                name="event_date"
                                                value="<?= htmlspecialchars(
                                                            $eventDate
                                                        ) ?>"
                                                placeholder="Select event date"
                                                autocomplete="off"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="event_date"></small>

                                        <small class="field-hint">
                                            Gold dates are already booked. Click a gold date to see the confirmation.
                                        </small>

                                    </div>



                                    <!-- START TIME -->

                                    <div class="form-group">

                                        <label for="startTime">

                                            Start Time

                                            <span>
                                                *
                                            </span>

                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="time"
                                                id="startTime"
                                                name="start_time"
                                                value="<?= htmlspecialchars(
                                                            $startTime
                                                        ) ?>"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="start_time"></small>

                                    </div>



                                    <!-- VENUE -->

                                    <div class="form-group full-width">

                                        <label for="venueId">

                                            Select Venue

                                            <span>
                                                *
                                            </span>

                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <select
                                                id="venueId"
                                                name="venue_id"
                                                required>

                                                <option value="">
                                                    Select a venue
                                                </option>

                                                <?php foreach (
                                                    $venues
                                                    as $venue
                                                ): ?>

                                                    <option
                                                        value="<?= (int) $venue["id"] ?>"
                                                        <?= (
                                                            (string) $venueId ===
                                                            (string) $venue["id"]
                                                        )
                                                            ? "selected"
                                                            : ""
                                                        ?>>

                                                        <?= htmlspecialchars(
                                                            $venue["venue_name"]
                                                        ) ?>

                                                        <?php if (
                                                            !empty($venue["location"])
                                                        ): ?>

                                                            —
                                                            <?= htmlspecialchars(
                                                                $venue["location"]
                                                            ) ?>

                                                        <?php endif; ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="venue_id"></small>

                                    </div>


                                </div>

                            </div>



                            <!-- =================================================
                             ADDITIONAL INFORMATION
                        ================================================== -->

                            <div class="form-section">


                                <div class="form-section-heading">

                                    <span class="section-number">
                                        03
                                    </span>

                                    <div>

                                        <h3>
                                            Additional Information
                                        </h3>

                                        <p>
                                            Tell us anything else
                                            we should know.
                                        </p>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="specialRequests">

                                        Special Requests

                                        <span class="optional">
                                            (Optional)
                                        </span>

                                    </label>


                                    <textarea
                                        id="specialRequests"
                                        name="special_requests"
                                        rows="6"
                                        maxlength="2000"
                                        placeholder="Tell us about your theme, decorations, special arrangements, or other requests..."><?= htmlspecialchars(
                                                                                                                                            $specialRequests
                                                                                                                                        ) ?></textarea>


                                    <div class="textarea-footer">

                                        <small>
                                            Our team will review
                                            your requests.
                                        </small>

                                        <span
                                            id="characterCount">
                                            0 / 2000
                                        </span>

                                    </div>

                                </div>

                            </div>



                            <!-- =================================================
                             AGREEMENT
                        ================================================== -->

                            <div class="booking-agreement">

                                <label
                                    class="checkbox-label">

                                    <input
                                        type="checkbox"
                                        id="bookingAgreement"
                                        required>

                                    <span
                                        class="custom-checkbox"></span>

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
                                    class="field-error"></small>

                            </div>



                            <!-- =================================================
                             SUBMIT
                        ================================================== -->

                            <div class="form-submit">

                                <button
                                    type="submit"
                                    class="submit-booking"
                                    id="submitBooking">

                                    <span class="button-text">
                                        <?= htmlspecialchars(
                                            $bookingButtonText
                                        ) ?>
                                    </span>

                                    <span
                                        class="button-arrow"
                                        aria-hidden="true">
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
        aria-hidden="true">
        <div
            class="booking-loading-popup"
            role="status"
            aria-live="polite">

            <div class="booking-loading-spinner"></div>

            <h3>
                Submitting Your Booking
            </h3>

            <p>
                <?= htmlspecialchars(
                    $bookingLoadingMessage
                ) ?>
                This may take a few moments.
            </p>

            <div class="booking-loading-progress">
                <span></span>
            </div>

            <p class="booking-loading-status">
                Processing
                <?= htmlspecialchars(
                    strtolower($eventType)
                ) ?>
                booking...
            </p>

        </div>
    </div>


    <?php include "includes/footer.php"; ?>


    <!-- =========================================================
     DYNAMIC EVENT TYPE
========================================================= -->

    <script>
        window.bookingEventType =
            <?= json_encode(
                $eventType,
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            ) ?>;

        window.bookingCelebrantLabel =
            <?= json_encode(
                $celebrantLabel,
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            ) ?>;

        window.bookingButtonText =
            <?= json_encode(
                $bookingButtonText,
                JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
            ) ?>;

        window.bookedBirthdayDates =
            <?= $bookedDatesJson ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
    <script src="js/header.js"></script>
    <script src="js/book_event.js"></script>

</body>

</html>
