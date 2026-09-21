<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

require_once "config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

$emailConfig = require __DIR__ . "/config/email.php";



/* =========================================================
   USER INFORMATION
========================================================= */

$userId = (int) $_SESSION["user_id"];

$userName =
    $_SESSION["fullname"] ?? "";

$userEmail =
    $_SESSION["email"] ?? "";

$userAddress =
    $_SESSION["address"] ?? "";



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

    $userResult = $userStmt->get_result();

    $currentUser = $userResult->fetch_assoc();

    $userStmt->close();

    if ($currentUser) {

        $userName = $currentUser["fullname"];

        $userEmail = $currentUser["email"];

        $userAddress = $currentUser["address"] ?? "";
    }
}




if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {

    die("Your account does not have a valid email address. Please update your profile.");
}




if (
    !isset($_GET["package_id"]) ||
    !is_numeric($_GET["package_id"])
) {

    header("Location: events.php");
    exit;
}

$packageId = (int) $_GET["package_id"];




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
        . htmlspecialchars($conn->error));
}

$packageStmt->bind_param(
    "i",
    $packageId
);

$packageStmt->execute();

$packageResult = $packageStmt->get_result();

$package = $packageResult->fetch_assoc();

$packageStmt->close();




if (!$package) {

    header("Location: events.php");
    exit;
}




$package["event_type"] = $package["event_type"] ?? "";

$isWedding =
    strtolower(
        trim(
            $package["event_type"]
        )
    ) === "wedding";


if (!$isWedding) {

    header(
        "Location: book_now.php?package_id="
            . $packageId
    );

    exit;
}




$packageImage =
    trim(
        (string) (
            $package["image"] ?? ""
        )
    );


if ($packageImage === "") {

    $packageImage = "images/logo.png";
} elseif (

    !preg_match(
        '/^(https?:)?\/\//i',
        $packageImage
    )

    &&

    strpos(
        $packageImage,
        "/"
    ) !== 0

) {

    if (

        strpos(
            $packageImage,
            "images/"
        ) !== 0

        &&

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

    $inclusions =
        preg_split(
            "/\r\n|\r|\n/",
            $package["inclusions"]
        );

    $inclusions =
        array_filter(
            array_map(
                "trim",
                $inclusions
            )
        );
}




$venues = [];




$venueQuery =
    $conn->query("
        SELECT
            id,
            venue_name,
            location
        FROM venues
        WHERE status = 'available'
        ORDER BY venue_name ASC
    ");




if (
    $venueQuery &&
    $venueQuery->num_rows > 0
) {

    while (
        $venue =
        $venueQuery->fetch_assoc()
    ) {

        $venues[] =
            $venue;
    }
} else {

    $venueQuery =
        $conn->query("
            SELECT
                id,
                venue_name,
                location
            FROM venues
            ORDER BY venue_name ASC
        ");

    if ($venueQuery) {

        while (
            $venue =
            $venueQuery->fetch_assoc()
        ) {

            $venues[] =
                $venue;
        }
    }
}




/* =========================================================
   GET CONFIRMED BOOKED DATES
 *
 * Only CONFIRMED bookings block the date.
 *
 * Start time is NOT checked.
 * Venue is NOT checked.
 *
 * Pending bookings DO NOT block the date.
 *
 * Both normal bookings and wedding bookings are included.
========================================================= */

$confirmedBookingDates = [];


/* ---------------------------------------------------------
   WEDDING BOOKINGS
--------------------------------------------------------- */

$confirmedBookingDatesQuery = $conn->prepare("
    SELECT DISTINCT
        event_date
    FROM booking_wedding
    WHERE status = 'confirmed'
      AND event_date IS NOT NULL
      AND event_date >= CURDATE()
    ORDER BY event_date ASC
");

if ($confirmedBookingDatesQuery) {

    $confirmedBookingDatesQuery->execute();

    $confirmedWeddingResult =
        $confirmedBookingDatesQuery->get_result();

    while (
        $bookingDate =
        $confirmedWeddingResult->fetch_assoc()
    ) {

        if (!empty($bookingDate["event_date"])) {

            $confirmedBookingDates[] =
                date(
                    "Y-m-d",
                    strtotime(
                        $bookingDate["event_date"]
                    )
                );
        }
    }

    $confirmedBookingDatesQuery->close();
}


/* ---------------------------------------------------------
   NORMAL BOOKINGS
--------------------------------------------------------- */

$confirmedNormalDatesQuery = $conn->prepare("
    SELECT DISTINCT
        event_date
    FROM bookings
    WHERE status = 'confirmed'
      AND event_date IS NOT NULL
      AND event_date >= CURDATE()
    ORDER BY event_date ASC
");

if ($confirmedNormalDatesQuery) {

    $confirmedNormalDatesQuery->execute();

    $confirmedNormalResult =
        $confirmedNormalDatesQuery->get_result();

    while (
        $bookingDate =
        $confirmedNormalResult->fetch_assoc()
    ) {

        if (!empty($bookingDate["event_date"])) {

            $confirmedBookingDates[] =
                date(
                    "Y-m-d",
                    strtotime(
                        $bookingDate["event_date"]
                    )
                );
        }
    }

    $confirmedNormalDatesQuery->close();
}


/* =========================================================
   REMOVE DUPLICATES / SORT
========================================================= */

$confirmedBookingDates =
    array_values(
        array_unique(
            $confirmedBookingDates
        )
    );

sort($confirmedBookingDates);




$errors = [];

$success = false;

$brideName = "";

$groomName = "";

$eventName = "";

$eventDate = "";

$phone = "";

$startTime = "";

$venueId = "";

$church = "";

$specialRequests = "";

$bookingId = 0;




$guestCount = 0;




if ($_SERVER["REQUEST_METHOD"] === "POST") {




    $postedPackageId =
        isset($_POST["package_id"])
        ? (int) $_POST["package_id"]
        : 0;


    if ($postedPackageId !== $packageId) {

        $errors[] =
            "Invalid package selection.";
    }




    $brideName =
        trim(
            $_POST["bride_name"] ?? ""
        );


    $groomName =
        trim(
            $_POST["groom_name"] ?? ""
        );


    $eventDate =
        trim(
            $_POST["event_date"] ?? ""
        );




    $phone =
        trim(
            $_POST["phone"] ?? ""
        );


    $startTime =
        trim(
            $_POST["start_time"] ?? ""
        );


    $venueId =
        trim(
            $_POST["venue_id"] ?? ""
        );


    $church =
        trim(
            $_POST["church"] ?? ""
        );


    $specialRequests =
        trim(
            $_POST["special_requests"] ?? ""
        );




    $eventName =
        trim(
            $brideName
                . " & "
                . $groomName
                . " Wedding"
        );




    $guestCount = 0;




    if (empty($brideName)) {

        $errors[] =
            "Please enter the bride name.";
    } elseif (
        mb_strlen($brideName) > 150
    ) {

        $errors[] =
            "Bride name must not exceed 150 characters.";
    }




    if (empty($groomName)) {

        $errors[] =
            "Please enter the groom name.";
    } elseif (
        mb_strlen($groomName) > 150
    ) {

        $errors[] =
            "Groom name must not exceed 150 characters.";
    }




    if (empty($eventDate)) {

        $errors[] =
            "Please select the wedding date.";
    } else {

        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $eventDate
            );


        $dateErrors =
            DateTime::getLastErrors();


        if (
            $dateObject === false ||
            (
                $dateErrors !== false &&
                (
                    $dateErrors["warning_count"] > 0 ||
                    $dateErrors["error_count"] > 0
                )
            )
        ) {

            $errors[] =
                "Please select a valid wedding date.";
        } else {

            $today =
                date("Y-m-d");


            if ($eventDate < $today) {

                $errors[] =
                    "Wedding date cannot be in the past.";
            }
        }
    }




    if (empty($phone)) {

        $errors[] =
            "Please enter your phone number.";
    } else {



        $phoneDigits =
            preg_replace(
                "/[^0-9]/",
                "",
                $phone
            );




        if (
            !preg_match(
                "/^09[0-9]{9}$/",
                $phoneDigits
            )
        ) {

            $errors[] =
                "Please enter a valid Philippine phone number.";
        } else {



            $phone =
                $phoneDigits;
        }
    }




    /* =====================================================
       VALIDATE START TIME
     *
     * Start time is still required and saved.
     * It is NOT used for availability checking.
    ====================================================== */

    if (empty($startTime)) {

        $errors[] =
            "Please select the wedding time.";
    } else {



        $timeObject =
            DateTime::createFromFormat(
                "H:i",
                $startTime
            );


        if (
            $timeObject === false
        ) {

            $timeObject =
                DateTime::createFromFormat(
                    "H:i:s",
                    $startTime
                );
        }


        if (
            $timeObject === false
        ) {

            $errors[] =
                "Please select a valid wedding time.";
        }
    }




    $userAddress =
        trim(
            (string) $userAddress
        );


    if (empty($userAddress)) {

        $errors[] =
            "Please add your address to your profile.";
    }




    if (empty($venueId)) {

        $errors[] =
            "Please select a wedding venue.";
    } elseif (!is_numeric($venueId)) {

        $errors[] =
            "Invalid venue selection.";
    } else {

        $venueId =
            (int) $venueId;


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


        if ($venueCheck) {

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
        } else {

            $errors[] =
                "Unable to verify the selected venue.";
        }
    }




    if (empty($church)) {

        $errors[] =
            "Please enter the church or ceremony location.";
    } elseif (
        mb_strlen($church) > 255
    ) {

        $errors[] =
            "Church or ceremony location must not exceed 255 characters.";
    }


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
       CHECK DATE CONFLICT - NORMAL BOOKINGS
     *
     * Only CONFIRMED normal bookings block the selected
     * date.
     *
     * Start time is NOT checked.
     * Venue is NOT checked.
     *
     * Pending normal bookings DO NOT block the date.
    ====================================================== */

    if (
        empty($errors) &&
        !empty($eventDate)
    ) {

        $normalConflictStmt =
            $conn->prepare("
                SELECT
                    id
                FROM bookings
                WHERE event_date = ?
                  AND status = 'confirmed'
                LIMIT 1
            ");


        if (!$normalConflictStmt) {

            $errors[] =
                "Unable to check date availability.";
        } else {

            $normalConflictStmt->bind_param(
                "s",
                $eventDate
            );


            $normalConflictStmt->execute();


            $normalConflictResult =
                $normalConflictStmt->get_result();


            if (
                $normalConflictResult->num_rows > 0
            ) {

                $errors[] =
                    "This date is already booked. Please select a new date.";
            }


            $normalConflictStmt->close();
        }
    }




    if (empty($errors)) {




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


        if (!$emailVenueStmt) {

            $errors[] =
                "Unable to prepare venue information.";
        } else {

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
                    $emailVenue["venue_name"];

                $venueLocation =
                    $emailVenue["location"];
            } else {

                $errors[] =
                    "Unable to retrieve the selected venue information.";
            }
        }




        if (empty($errors)) {

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
        }




        $baseUrl =
            "https://eventsolution.page.gd";

        $packageUrl =
            $baseUrl
            . "/view_package.php?id="
            . $packageId;


        $venueUrl =
            $baseUrl
            . "/view_venue.php?id="
            . $venueId;




        $packageImageUrl =
            buildWeddingPublicImageUrl(
                $packageImage,
                $baseUrl
            );




        $venueImageUrl =
            buildWeddingPublicImageUrl(
                $venueImage,
                $baseUrl
            );




        $bookingId =
            "Pending Assignment";




        if (empty($errors)) {

            $emailSent =
                sendWeddingBookingEmails(
                    $emailConfig,
                    $bookingId,
                    $userName,
                    $userEmail,
                    $eventName,
                    $eventDate,
                    $startTime,
                    $guestCount,
                    $phone,
                    $venueId,
                    $venueName,
                    $venueLocation,
                    $packageId,
                    $package["package_name"],
                    $package["event_type"],
                    $specialRequests,
                    $userAddress,
                    $brideName,
                    $groomName,
                    $church,
                    $packageUrl,
                    $venueUrl,
                    $packageImageUrl,
                    $venueImageUrl
                );




            if (!$emailSent) {

                $errors[] =
                    "Unable to send the booking email. Your wedding booking was not saved. Please try again.";
            }
        }
    }




    if (empty($errors)) {




        $insertStmt =
            $conn->prepare("
                INSERT INTO booking_wedding (
                    user_id,
                    bride_name,
                    groom_name,
                    event_date,
                    start_time,
                    guest_count,
                    phone,
                    package_id,
                    venue_id,
                    church,
                    special_request,
                    invitation_token
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
                    ?,
                    ?,
                    NULL
                )
            ");


        if (!$insertStmt) {

            $errors[] =
                "Unable to prepare wedding booking request.";
        } else {

            $guestCount = 0;




            $insertStmt->bind_param(
                "issssisiiss",
                $userId,
                $brideName,
                $groomName,
                $eventDate,
                $startTime,
                $guestCount,
                $phone,
                $packageId,
                $venueId,
                $church,
                $specialRequests
            );


            if ($insertStmt->execute()) {



                $bookingId =
                    $conn->insert_id;


                $success = true;




                header(
                    "Location: book_wedding.php?package_id="
                        . $packageId
                        . "&success=1&booking_id="
                        . $bookingId
                );

                exit;
            } else {

                $errors[] =
                    "Unable to submit your wedding booking. Please try again.";
            }


            $insertStmt->close();
        }
    }
}




function buildWeddingPublicImageUrl(
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




function sendWeddingBookingEmails(
    $emailConfig,
    $bookingId,
    $customerName,
    $customerEmail,
    $eventName,
    $eventDate,
    $startTime,
    $guestCount,
    $phone,
    $venueId,
    $venueName,
    $venueLocation,
    $packageId,
    $packageName,
    $eventType,
    $specialRequests,
    $userAddress,
    $brideName,
    $groomName,
    $church,
    $packageUrl,
    $venueUrl,
    $packageImageUrl,
    $venueImageUrl
) {

    try {


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


        $safeBrideName =
            htmlspecialchars(
                $brideName,
                ENT_QUOTES,
                "UTF-8"
            );


        $safeGroomName =
            htmlspecialchars(
                $groomName,
                ENT_QUOTES,
                "UTF-8"
            );


        $safeEventName =
            htmlspecialchars(
                $eventName,
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


        $safeChurch =
            htmlspecialchars(
                $church,
                ENT_QUOTES,
                "UTF-8"
            );


        $safeEventType =
            htmlspecialchars(
                $eventType,
                ENT_QUOTES,
                "UTF-8"
            );


        $safeBookingId =
            htmlspecialchars(
                (string) $bookingId,
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



        $adminMail->XMailer = "";


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
            "New Wedding Booking Request #"
            . $bookingId;




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
                    New Wedding Booking Request
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
                    Wedding Booking Request #' . $safeBookingId . '
                </h2>

                <p>
                    A new wedding booking request has been
                    submitted through the Event Solutions
                    by S.H.E. website.
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
                        Wedding Information
                    </h3>

                    <p>
                        <strong>Event Name:</strong>
                        ' . $safeEventName . '
                    </p>

                    <p>
                        <strong>Bride:</strong>
                        ' . $safeBrideName . '
                    </p>

                    <p>
                        <strong>Groom:</strong>
                        ' . $safeGroomName . '
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
                        <strong>Wedding Date:</strong>
                        ' . $formattedDate . '
                    </p>

                    <p>
                        <strong>Wedding Time:</strong>
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
                        <strong>Church / Ceremony:</strong>
                        ' . $safeChurch . '
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




        $adminMail->AltBody =
            "New Wedding Booking Request #"
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

            . "Event Name: "
            . $eventName
            . "\n"

            . "Bride: "
            . $brideName
            . "\n"

            . "Groom: "
            . $groomName
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

            . "Event Type: "
            . $eventType
            . "\n"

            . "Date: "
            . $formattedDate
            . "\n"

            . "Time: "
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

            . "Venue Location: "
            . $venueLocation
            . "\n"

            . "Church / Ceremony: "
            . $church
            . "\n\n"

            . "Special Requests: "
            . (
                $specialRequests !== ""
                ? $specialRequests
                : "None"
            );




        $adminMail->send();




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
        $customerMail->XMailer = "";
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
            "Wedding Booking Request Submitted - #"
            . $bookingId;




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
                    Wedding Booking Confirmation
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
                    Wedding Booking Submitted Successfully!
                </h2>


                <p>
                    Hello ' . $safeCustomerName . ',
                </p>


                <p>
                    Thank you for submitting your wedding
                    booking request with Event Solutions
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
                        Wedding Details
                    </h3>

                    <p>
                        <strong>
                            Booking Number:
                        </strong>
                        #' . $safeBookingId . '
                    </p>

                    <p>
                        <strong>
                            Bride:
                        </strong>
                        ' . $safeBrideName . '
                    </p>

                    <p>
                        <strong>
                            Groom:
                        </strong>
                        ' . $safeGroomName . '
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
                        padding:15px;
                        box-sizing:border-box;
                        border:1px solid #e5ddd2;
                        border-radius:10px;
                        background:#ffffff;
                    ">

                        <p style="
                            margin:0;
                            font-size:15px;
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
                                    font-weight:600;
                                "
                            >
                                ' . $safePackageName . '
                            </a>

                        </p>

                    </div>


                    <p>
                        <strong>
                            Wedding Date:
                        </strong>
                        ' . $formattedDate . '
                    </p>

                    <p>
                        <strong>
                            Wedding Time:
                        </strong>
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

                        <p style="
                            margin:0 0 8px;
                            font-size:15px;
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

                    <p>
                        <strong>
                            Church / Ceremony:
                        </strong>
                        ' . $safeChurch . '
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
                        Our team will review your wedding
                        booking request and contact you once
                        your reservation has been confirmed.
                    </p>

                </div>


                <p>
                    If you need to make changes or have
                    questions about your wedding booking,
                    please contact our team.
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




        $customerMail->AltBody =
            "Hello "
            . $customerName
            . ",\n\n"

            . "Your wedding booking request has been "
            . "submitted successfully.\n\n"

            . "Booking Number: #"
            . $bookingId
            . "\n"

            . "Bride: "
            . $brideName
            . "\n"

            . "Groom: "
            . $groomName
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

            . "Wedding Date: "
            . $formattedDate
            . "\n"

            . "Wedding Time: "
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
            . "\n"

            . "Church / Ceremony: "
            . $church
            . "\n\n"

            . "Status: Pending Review\n\n"

            . "Our team will review your wedding booking "
            . "request and contact you once your "
            . "reservation has been confirmed.\n\n"

            . "Thank you for choosing "
            . "Event Solutions by S.H.E.";




        $customerMail->send();




        return true;
    } catch (Exception $e) {

        error_log(
            "Wedding booking email error: "
                . $e->getMessage()
        );

        return false;
    }
}




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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Book Your Wedding | Event Solutions by S.H.E
    </title>

    <link rel="icon" type="image/png" href="images/logo.png">


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="css/header.css">

    <link
        rel="stylesheet"
        href="css/footer.css">

    <link
        rel="stylesheet"
        href="css/book_wedding.css">

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
                    Plan Your
                    <span>
                        Wedding
                    </span>
                </h1>

                <p>
                    Tell us about your special day and let us
                    help create a beautiful and memorable wedding.
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
                                    $package["package_name"]
                                ) ?>

                            </h2>


                            <span class="summary-event-type">
                                Wedding
                            </span>


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
                                                        $inclusion
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
                                    Your wedding booking will be reviewed
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
                                            $package["package_name"]
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
                            Have questions about your wedding?
                        </h3>

                        <p>
                            Our team is ready to help you
                            plan your perfect wedding experience.
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
                            WEDDING BOOKING
                        </span>

                        <h2>
                            Plan Your Wedding
                        </h2>

                        <p>
                            Please provide the details of
                            your special day.
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
                                    Wedding Booking Submitted!
                                </h3>

                                <p>

                                    Your wedding booking request
                                    has been submitted successfully.

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
                            action="book_wedding.php?package_id=<?= $packageId ?>"
                            id="bookingForm"
                            novalidate
                            autocomplete="on">


                            <input
                                type="hidden"
                                name="package_id"
                                value="<?= $packageId ?>">


                            <!-- =================================================
                             STEP 01
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


                                    <div class="form-group full-width">

                                        <label for="fullName">
                                            Full Name
                                        </label>

                                        <div class="input-wrapper">

                                            <span
                                                class="input-icon"
                                                aria-hidden="true"></span>

                                            <input
                                                type="text"
                                                id="fullName"
                                                value="<?= htmlspecialchars(
                                                            $userName
                                                        ) ?>">

                                        </div>

                                    </div>


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
                                                value="<?= htmlspecialchars(
                                                            $userEmail
                                                        ) ?>">

                                        </div>

                                    </div>


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
                                                            $phone
                                                        ) ?>"
                                                required
                                                autocomplete="tel">

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="phone"></small>

                                    </div>


                                    <div class="form-group full-width">

                                        <label for="address">

                                            Address

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
                                                id="address"
                                                value="<?= htmlspecialchars(
                                                            $userAddress
                                                        ) ?>">

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="address"></small>


                                        <?php if (empty($userAddress)): ?>

                                            <small class="field-hint">
                                                Please add your address in your profile before booking.
                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                             STEP 02
                        ================================================== -->

                            <div class="form-section">

                                <div class="form-section-heading">

                                    <span class="section-number">
                                        02
                                    </span>

                                    <div>

                                        <h3>
                                            Wedding Details
                                        </h3>

                                        <p>
                                            Tell us about your wedding plans.
                                        </p>

                                    </div>

                                </div>


                                <input
                                    type="hidden"
                                    id="eventName"
                                    name="event_name"
                                    value="<?= htmlspecialchars(
                                                $eventName
                                            ) ?>">


                                <div class="form-grid">


                                    <!-- BRIDE -->

                                    <div class="form-group">

                                        <label for="brideName">

                                            Bride Name

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
                                                id="brideName"
                                                name="bride_name"
                                                maxlength="150"
                                                placeholder="Enter bride name"
                                                value="<?= htmlspecialchars(
                                                            $brideName
                                                        ) ?>"
                                                required
                                                autocomplete="name">

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="bride_name"></small>

                                    </div>


                                    <!-- GROOM -->

                                    <div class="form-group">

                                        <label for="groomName">

                                            Groom Name

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
                                                id="groomName"
                                                name="groom_name"
                                                maxlength="150"
                                                placeholder="Enter groom name"
                                                value="<?= htmlspecialchars(
                                                            $groomName
                                                        ) ?>"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="groom_name"></small>

                                    </div>


                                    <!-- DATE -->

                                    <div class="form-group">

                                        <label for="eventDate">

                                            Plan Date

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
                                                placeholder="Select your wedding date"
                                                autocomplete="off"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="event_date"></small>

                                    </div>


                                    <!-- TIME -->

                                    <div class="form-group">

                                        <label for="startTime">

                                            Plan Time

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

                                            Plan Venue

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
                             STEP 03
                        ================================================== -->

                            <div class="form-section">

                                <div class="form-section-heading">

                                    <span class="section-number">
                                        03
                                    </span>

                                    <div>

                                        <h3>
                                            Ceremony & Additional Information
                                        </h3>

                                        <p>
                                            Tell us anything else we should know.
                                        </p>

                                    </div>

                                </div>


                                <div class="form-grid">


                                    <!-- CHURCH -->

                                    <div class="form-group full-width">

                                        <label for="church">

                                            Church / Ceremony Location

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
                                                id="church"
                                                name="church"
                                                maxlength="255"
                                                placeholder="Enter church or ceremony location"
                                                value="<?= htmlspecialchars(
                                                            $church
                                                        ) ?>"
                                                required>

                                        </div>

                                        <small
                                            class="field-error"
                                            data-error-for="church"></small>

                                    </div>


                                    <!-- SPECIAL REQUESTS -->

                                    <div class="form-group full-width">

                                        <label for="specialRequests">

                                            Special Requests

                                            <span class="optional">
                                                Optional
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

                                            <span id="characterCount">
                                                0 / 2000
                                            </span>

                                        </div>

                                    </div>


                                </div>

                            </div>


                            <!-- =================================================
                             AGREEMENT
                        ================================================== -->

                            <div class="booking-agreement">

                                <label class="checkbox-label">

                                    <input
                                        type="checkbox"
                                        id="bookingAgreement"
                                        required>

                                    <span class="custom-checkbox"></span>

                                    <span class="agreement-text">

                                        I confirm that the information
                                        provided above is correct and
                                        I understand that this is a
                                        wedding booking request and
                                        not yet a confirmed reservation.

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
                                        Submit Booking
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
                                    after our team reviews your wedding
                                    booking request.
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
                Please wait while we process your wedding booking.
                This may take a few moments.
            </p>

            <div class="booking-loading-progress">
                <span></span>
            </div>

            <p class="booking-loading-status">
                Processing booking...
            </p>

        </div>
    </div>


    <?php include "includes/footer.php"; ?>

    <script>
        window.bookedWeddingDates =
            <?= json_encode(
                $confirmedBookingDates,
                JSON_UNESCAPED_SLASHES
            ) ?>;

        window.confirmedBookingDates =
            window.bookedWeddingDates;
    </script>

    <script
        src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


    <script src="js/header.js"></script>

    <!-- Cache-busting ensures the newest JavaScript is loaded -->
    <script src="js/book_wedding.js?v=5"></script>


</body>

</html>
