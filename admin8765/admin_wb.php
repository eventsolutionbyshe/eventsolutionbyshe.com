<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   BOOKING FLASH MESSAGE
====================================================== */

$message = "";
$messageType = "";

if (
    isset(
        $_SESSION["booking_flash_message"]
    )
) {

    $message =
        (string)
        $_SESSION["booking_flash_message"];

    $messageType =
        (string)
        (
            $_SESSION["booking_flash_type"]
            ?? ""
        );


    unset(
        $_SESSION["booking_flash_message"],
        $_SESSION["booking_flash_type"]
    );
}


/* =========================================================
   PROJECT PATHS
========================================================= */

$adminDirectory =
    __DIR__;


$projectRoot =
    dirname(
        $adminDirectory
    );


/* =========================================================
   DATABASE CONNECTION
========================================================= */

$databasePath =
    $projectRoot
    . "/config/database.php";


if (!file_exists($databasePath)) {

    die(
        "Database configuration file not found.<br><br>"
        . "Expected path:<br>"
        . htmlspecialchars(
            $databasePath,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


require_once $databasePath;


/* =========================================================
   COMPOSER / PHPMailer AUTOLOAD
========================================================= */

$autoloadPath =
    $projectRoot
    . "/vendor/autoload.php";


$composerAvailable =
    false;


if (file_exists($autoloadPath)) {

    require_once $autoloadPath;

    $composerAvailable =
        true;
}


/* =========================================================
   EMAIL CONFIG
========================================================= */

$emailConfig =
    [];


/* =========================================================
   OPTION 1
========================================================= */

$emailConfigPath =
    $projectRoot
    . "/config/email.php";


if (file_exists($emailConfigPath)) {

    $loadedEmailConfig =
        require $emailConfigPath;


    if (is_array($loadedEmailConfig)) {

        $emailConfig =
            $loadedEmailConfig;
    }
}


/* =========================================================
   OPTION 2
========================================================= */

if (empty($emailConfig)) {

    $adminEmailConfigPath =
        $adminDirectory
        . "/config/email.php";


    if (file_exists($adminEmailConfigPath)) {

        $loadedEmailConfig =
            require $adminEmailConfigPath;


        if (is_array($loadedEmailConfig)) {

            $emailConfig =
                $loadedEmailConfig;
        }
    }
}


/* =========================================================
   PHPMailer
========================================================= */

$phpMailerAvailable =
    false;


if (
    $composerAvailable &&
    class_exists(
        "PHPMailer\\PHPMailer\\PHPMailer"
    )
) {

    $phpMailerAvailable =
        true;
}


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header(
        "Location: ../auth/login.php"
    );

    exit;
}


/* =========================================================
   USER
========================================================= */

$userId =
    (int) $_SESSION["user_id"];


/* =========================================================
   PAGE INFORMATION
========================================================= */

$pageSection =
    "ADMINISTRATION";


$pageHeading =
    "Event Bookings";


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
   HELPER FUNCTIONS
========================================================= */

function getBookingStatusLabel(
    string $status
): string {

    $labels = [

        "pending" =>
            "Pending",

        "confirmed" =>
            "Confirmed",

        "completed" =>
            "Completed",

        "cancelled" =>
            "Cancelled"

    ];


    return
        $labels[$status]
        ?? ucfirst($status);
}


function getBookingStatusIcon(
    string $status
): string {

    $icons = [

        "pending" =>
            "fa-clock",

        "confirmed" =>
            "fa-circle-check",

        "completed" =>
            "fa-flag-checkered",

        "cancelled" =>
            "fa-ban"

    ];


    return
        $icons[$status]
        ?? "fa-circle";
}


function formatBookingDate(
    $date
): string {

    if (empty($date)) {

        return "—";
    }


    $timestamp =
        strtotime($date);


    if ($timestamp === false) {

        return
            (string) $date;
    }


    return
        date(
            "M d, Y",
            $timestamp
        );
}


function formatBookingTime(
    $time
): string {

    if (empty($time)) {

        return "—";
    }


    $timestamp =
        strtotime($time);


    if ($timestamp === false) {

        return
            (string) $time;
    }


    return
        date(
            "g:i A",
            $timestamp
        );
}


/* =========================================================
   EMAIL BOOKING STATUS
========================================================= */

function sendBookingStatusEmail(
    mysqli $conn,
    array $emailConfig,
    int $bookingId,
    string $newStatus
): array {

    $response = [

        "success" =>
            false,

        "message" =>
            ""

    ];


    /* =====================================================
       PHPMailer CHECK
    ====================================================== */

    if (
        !class_exists(
            "PHPMailer\\PHPMailer\\PHPMailer"
        )
    ) {

        $response["message"] =
            "PHPMailer is not installed. Booking status was updated successfully.";

        return $response;
    }


    /* =====================================================
       GET BOOKING AND CUSTOMER INFORMATION
    ====================================================== */

    $sql = "

        SELECT

            bw.id,

            CONCAT(
                COALESCE(bw.bride_name, ''),
                CASE
                    WHEN
                        bw.bride_name IS NOT NULL
                        AND bw.bride_name <> ''
                        AND bw.groom_name IS NOT NULL
                        AND bw.groom_name <> ''
                    THEN ' & '
                    ELSE ''
                END,
                COALESCE(bw.groom_name, '')
            ) AS event_name,

            bw.event_date,

            bw.start_time,

            bw.status,

            bw.user_id,

            u.fullname AS customer_name,

            u.email AS customer_email,

            ep.package_name,

            v.venue_name

        FROM booking_wedding bw

        LEFT JOIN users u
            ON u.id = bw.user_id

        LEFT JOIN event_packages ep
            ON ep.id = bw.package_id

        LEFT JOIN venues v
            ON v.id = bw.venue_id

        WHERE bw.id = ?

        LIMIT 1

    ";


    $stmt =
        $conn->prepare(
            $sql
        );


    if (!$stmt) {

        $response["message"] =
            "Unable to prepare booking email query.";

        return $response;
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


    if (!$booking) {

        $response["message"] =
            "Booking Wedding information was not found.";

        return $response;
    }


    $customerEmail =
        trim(
            (string) (
                $booking["customer_email"]
                ?? ""
            )
        );


    if ($customerEmail === "") {

        $response["message"] =
            "Customer does not have an email address.";

        return $response;
    }


    /* =====================================================
       EMAIL CONFIGURATION
    ====================================================== */

    $smtpHost =
        trim(
            (string) (
                $emailConfig["smtp_host"]
                ?? ""
            )
        );


    $smtpPort =
        (int) (
            $emailConfig["smtp_port"]
            ?? 587
        );


    $smtpUsername =
        trim(
            (string) (
                $emailConfig["smtp_username"]
                ?? ""
            )
        );


    $smtpPassword =
        (string) (
            $emailConfig["smtp_password"]
            ?? ""
        );


    $fromEmail =
        trim(
            (string) (
                $emailConfig["from_email"]
                ?? $smtpUsername
            )
        );


    $fromName =
        trim(
            (string) (
                $emailConfig["from_name"]
                ?? "Event Solutions"
            )
        );


    if (
        $smtpHost === "" ||
        $smtpUsername === "" ||
        $smtpPassword === ""
    ) {

        $response["message"] =
            "Email configuration is incomplete.";

        return $response;
    }


    try {

        $mail =
            new \PHPMailer\PHPMailer\PHPMailer(
                true
            );


        $mail->isSMTP();


        $mail->Host =
            $smtpHost;


        $mail->SMTPAuth =
            true;


        $mail->Username =
            $smtpUsername;


        $mail->Password =
            $smtpPassword;


        $mail->Port =
            $smtpPort;


        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;


        $mail->CharSet =
            "UTF-8";


        $mail->setFrom(
            $fromEmail,
            $fromName
        );


        $mail->addAddress(
            $customerEmail,
            $booking["customer_name"]
            ?? ""
        );


        $statusLabel =
            getBookingStatusLabel(
                $newStatus
            );


        $mail->isHTML(
            true
        );


        $mail->Subject =
            "Booking Status Updated - "
            . (
                $booking["event_name"]
                ?? "Event Booking"
            );


        $mail->Body = "

            <div
                style=\"
                    font-family: Arial, Helvetica, sans-serif;
                    background-color: #f7f3ed;
                    padding: 40px 20px;
                    color: #252525;
                \"
            >

                <div
                    style=\"
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
                    \"
                >

                    <div
                        style=\"
                            background-color: #252525;
                            padding: 30px 35px;
                            text-align: center;
                        \"
                    >

                        <div
                            style=\"
                                font-size: 25px;
                                font-weight: bold;
                                letter-spacing: 2px;
                                color: #b8954a;
                                margin-bottom: 6px;
                            \"
                        >
                            EVENT SOLUTIONS
                        </div>

                        <div
                            style=\"
                                font-size: 12px;
                                letter-spacing: 3px;
                                color: #f3c7a8;
                            \"
                        >
                            BY S.H.E.
                        </div>

                    </div>


                    <div
                        style=\"
                            padding: 35px;
                        \"
                    >

                        <div
                            style=\"
                                text-align: center;
                                margin-bottom: 25px;
                            \"
                        >

                            <div
                                style=\"
                                    display: inline-block;
                                    width: 58px;
                                    height: 58px;
                                    line-height: 58px;
                                    border-radius: 50%;
                                    background-color: #f7f1e4;
                                    color: #b8954a;
                                    font-size: 25px;
                                    font-weight: bold;
                                \"
                            >
                                ✓
                            </div>

                            <h2
                                style=\"
                                    margin: 18px 0 8px;
                                    color: #252525;
                                    font-size: 24px;
                                \"
                            >
                                Booking Status Updated
                            </h2>

                            <p
                                style=\"
                                    margin: 0;
                                    color: #777777;
                                    font-size: 14px;
                                \"
                            >
                                There has been an update to your event booking.
                            </p>

                        </div>


                        <p
                            style=\"
                                font-size: 15px;
                                line-height: 1.7;
                                margin-bottom: 20px;
                            \"
                        >
                            Hello
                            <strong style=\"color: #b8954a;\">
                                "
                                . htmlspecialchars(
                                    $booking["customer_name"]
                                    ?? "Customer",
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                . "
                            </strong>,
                        </p>


                        <p
                            style=\"
                                font-size: 15px;
                                line-height: 1.7;
                                color: #555555;
                                margin-bottom: 25px;
                            \"
                        >
                            Your event booking status has been updated.
                            Please review the details below for the latest information
                            regarding your booking.
                        </p>


                        <div
                            style=\"
                                border: 1px solid #e8e8e5;
                                border-radius: 12px;
                                overflow: hidden;
                                margin-bottom: 25px;
                            \"
                        >

                            <div
                                style=\"
                                    background-color: #f7f1e4;
                                    padding: 15px 18px;
                                    color: #252525;
                                    font-size: 14px;
                                    font-weight: bold;
                                    letter-spacing: 0.5px;
                                \"
                            >
                                Booking Details
                            </div>


                            <table
                                cellpadding=\"0\"
                                cellspacing=\"0\"
                                border=\"0\"
                                style=\"
                                    width: 100%;
                                    border-collapse: collapse;
                                \"
                            >

                                <tr>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #777777;
                                            font-size: 13px;
                                            width: 35%;
                                        \"
                                    >
                                        Event
                                    </td>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #252525;
                                            font-size: 14px;
                                            font-weight: 600;
                                        \"
                                    >
                                        "
                                        . htmlspecialchars(
                                            $booking["event_name"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        . "
                                    </td>

                                </tr>


                                <tr>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #777777;
                                            font-size: 13px;
                                        \"
                                    >
                                        Event Date
                                    </td>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #252525;
                                            font-size: 14px;
                                            font-weight: 600;
                                        \"
                                    >
                                        "
                                        . formatBookingDate(
                                            $booking["event_date"]
                                            ?? ""
                                        )
                                        . "
                                    </td>

                                </tr>


                                <tr>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #777777;
                                            font-size: 13px;
                                        \"
                                    >
                                        Package
                                    </td>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #252525;
                                            font-size: 14px;
                                            font-weight: 600;
                                        \"
                                    >
                                        "
                                        . htmlspecialchars(
                                            $booking["package_name"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        . "
                                    </td>

                                </tr>


                                <tr>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #777777;
                                            font-size: 13px;
                                        \"
                                    >
                                        Venue
                                    </td>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            border-bottom: 1px solid #e8e8e5;
                                            color: #252525;
                                            font-size: 14px;
                                            font-weight: 600;
                                        \"
                                    >
                                        "
                                        . htmlspecialchars(
                                            $booking["venue_name"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        . "
                                    </td>

                                </tr>


                                <tr>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            color: #777777;
                                            font-size: 13px;
                                        \"
                                    >
                                        Status
                                    </td>

                                    <td
                                        style=\"
                                            padding: 15px 18px;
                                            color: #b8954a;
                                            font-size: 14px;
                                            font-weight: bold;
                                        \"
                                    >
                                        "
                                        . htmlspecialchars(
                                            $statusLabel,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        . "
                                    </td>

                                </tr>

                            </table>

                        </div>


                        <div
                            style=\"
                                background-color: #faf8f4;
                                border-left: 4px solid #b8954a;
                                padding: 16px 18px;
                                border-radius: 8px;
                                margin-bottom: 25px;
                            \"
                        >

                            <p
                                style=\"
                                    margin: 0;
                                    color: #555555;
                                    font-size: 14px;
                                    line-height: 1.6;
                                \"
                            >

                                Your booking is currently marked as

                                <strong style=\"color: #b8954a;\">
                                    "
                                    . htmlspecialchars(
                                        $statusLabel,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                    . "
                                </strong>.

                            </p>

                        </div>


                        <p
                            style=\"
                                font-size: 14px;
                                line-height: 1.7;
                                color: #555555;
                                margin-bottom: 5px;
                            \"
                        >
                            Thank you for choosing
                            <strong style=\"color: #252525;\">
                                Event Solutions by S.H.E.
                            </strong>
                        </p>

                        <p
                            style=\"
                                font-size: 13px;
                                line-height: 1.6;
                                color: #999999;
                                margin-top: 5px;
                            \"
                        >
                            We look forward to helping make your event
                            memorable and special.
                        </p>

                    </div>


                    <div
                        style=\"
                            background-color: #252525;
                            padding: 22px 30px;
                            text-align: center;
                        \"
                    >

                        <p
                            style=\"
                                margin: 0 0 6px;
                                color: #f3c7a8;
                                font-size: 12px;
                                letter-spacing: 1px;
                            \"
                        >
                            EVENT SOLUTIONS BY S.H.E.
                        </p>

                        <p
                            style=\"
                                margin: 0;
                                color: #888888;
                                font-size: 11px;
                            \"
                        >
                            This is an automated notification regarding your booking.
                        </p>

                    </div>

                </div>

            </div>

        ";


        $mail->AltBody =

            "Booking Status Updated\n\n"
            . "Hello "
            . (
                $booking["customer_name"]
                ?? "Customer"
            )
            . ",\n\n"
            . "Your event booking status has been updated.\n\n"
            . "Event: "
            . (
                $booking["event_name"]
                ?? ""
            )
            . "\n"
            . "Event Date: "
            . formatBookingDate(
                $booking["event_date"]
                ?? ""
            )
            . "\n"
            . "Package: "
            . (
                $booking["package_name"]
                ?? ""
            )
            . "\n"
            . "Venue: "
            . (
                $booking["venue_name"]
                ?? ""
            )
            . "\n"
            . "Status: "
            . $statusLabel
            . "\n\n"
            . "Thank you for choosing Event Solutions by S.H.E.";


        $mail->send();


        $response["success"] =
            true;


        $response["message"] =
            "Customer notification email sent successfully.";


    } catch (
        \Exception $e
    ) {

        $response["message"] =
            "Booking was updated, but the notification email could not be sent.";
    }


    return $response;
}


/* =========================================================
   HANDLE POST REQUEST
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $action =
        trim(
            $_POST["action"]
            ?? ""
        );


    /* =====================================================
       UPDATE BOOKING STATUS
    ====================================================== */

    if (
        $action ===
        "update_status"
    ) {

        $bookingId =
            (int) (
                $_POST["booking_id"]
                ?? 0
            );


        $newStatus =
            strtolower(
                trim(
                    $_POST["status"]
                    ?? ""
                )
            );


        if ($bookingId <= 0) {

            $message =
                "Invalid booking ID.";

            $messageType =
                "error";


        } elseif (
            !in_array(
                $newStatus,
                $statusOptions,
                true
            )
        ) {

            $message =
                "Invalid booking status.";

            $messageType =
                "error";


        } else {

            /* =================================================
               GET CURRENT BOOKING STATUS
            ================================================== */

            $currentStatus = "";

            $checkStmt =
                $conn->prepare(
                    "
                    SELECT status
                    FROM booking_wedding
                    WHERE id = ?
                    LIMIT 1
                    "
                );


            if ($checkStmt) {

                $checkStmt->bind_param(
                    "i",
                    $bookingId
                );


                if ($checkStmt->execute()) {

                    $checkResult =
                        $checkStmt->get_result();


                    if (
                        $checkResult &&
                        $checkResult->num_rows > 0
                    ) {

                        $bookingRow =
                            $checkResult->fetch_assoc();


                        $currentStatus =
                            strtolower(
                                trim(
                                    (string) (
                                        $bookingRow["status"]
                                        ?? ""
                                    )
                                )
                            );

                    } else {

                        $message =
                            "Booking not found.";

                        $messageType =
                            "error";
                    }


                } else {

                    $message =
                        "Unable to check booking status.";

                    $messageType =
                        "error";
                }


                $checkStmt->close();


            } else {

                $message =
                    "Database error while checking booking status.";

                $messageType =
                    "error";
            }


            /* =================================================
               ONLY UPDATE IF STATUS ACTUALLY CHANGED
            ================================================== */

            if (
                empty($message) &&
                $currentStatus === $newStatus
            ) {

                $message =
                    "Booking status is already " .
                    ucfirst($newStatus) .
                    ".";

                $messageType =
                    "error";


            } elseif (
                empty($message)
            ) {

                $stmt =
                    $conn->prepare(
                        "
                        UPDATE booking_wedding

                        SET

                            status = ?,

                            Update_at = NOW()

                        WHERE id = ?

                        "
                    );


                if ($stmt) {

                    $stmt->bind_param(
                        "si",
                        $newStatus,
                        $bookingId
                    );


                    if ($stmt->execute()) {

                        if (
                            $stmt->affected_rows > 0
                        ) {

                            $message =
                                "Booking status updated successfully.";

                            $messageType =
                                "success";


                            /* =================================================
                               SEND EMAIL ONLY AFTER A REAL STATUS CHANGE
                            ================================================== */

                            if (
                                $phpMailerAvailable &&
                                !empty($emailConfig)
                            ) {

                                sendBookingStatusEmail(
                                    $conn,
                                    $emailConfig,
                                    $bookingId,
                                    $newStatus
                                );
                            }


                        } else {

                            $message =
                                "Unable to update booking status.";

                            $messageType =
                                "error";
                        }


                    } else {

                        $message =
                            "Unable to update booking status.";

                        $messageType =
                            "error";
                    }


                    $stmt->close();


                } else {

                    $message =
                        "Database error while updating booking status.";

                    $messageType =
                        "error";
                }
            }
        }


        /* =====================================================
           POST → REDIRECT → GET
           PREVENT FORM RESUBMISSION ON REFRESH
        ====================================================== */

        $_SESSION["booking_flash_message"] =
            $message;

        $_SESSION["booking_flash_type"] =
            $messageType;


        header(
            "Location: admin_wb.php"
        );

        exit;
    }


    /* =====================================================
       CREATE GUEST LIST TOKEN
    ====================================================== */

    if (
        $action ===
        "create_token"
    ) {

        $bookingId =
            (int) (
                $_POST["booking_id"]
                ?? 0
            );


        /*
         * This is the actual number of guests
         * entered by the admin.
         */

        $guestCount =
            (int) (
                $_POST["guest_count"]
                ?? 0
            );


        /*
         * invitation_guest_count is intentionally
         * kept at 1.
         */

        $invitationGuestCount =
            1;


        if ($bookingId <= 0) {

            $message =
                "Invalid booking ID.";

            $messageType =
                "error";


        } elseif (
            $guestCount < 1 ||
            $guestCount > 10000
        ) {

            $message =
                "Guest count must be between 1 and 10,000.";

            $messageType =
                "error";


        } else {

            $token =
                bin2hex(
                    random_bytes(
                        32
                    )
                );


            /*
             * guest_count
             * = actual number of guests
             *
             * invitation_guest_count
             * = 1
             */

            $stmt =
                $conn->prepare(
                    "

                    UPDATE booking_wedding

                    SET

                        invitation_token = ?,

                        guest_count = ?,

                        invitation_guest_count = ?,

                        Update_at = NOW()

                    WHERE id = ?

                    "
                );


            if ($stmt) {

                $stmt->bind_param(
                    "siii",
                    $token,
                    $guestCount,
                    $invitationGuestCount,
                    $bookingId
                );


                if ($stmt->execute()) {

                    /*
                     * MySQL can return 0 affected rows
                     * when the values are unchanged.
                     *
                     * The token is newly generated, so
                     * a successful execute is enough.
                     */

                    if (
                        $stmt->errno === 0
                    ) {

                        $message =
                            "Guest list link created successfully.";

                        $messageType =
                            "success";


                    } else {

                        $message =
                            "Unable to create guest list link.";

                        $messageType =
                            "error";
                    }


                } else {

                    $message =
                        "Unable to create guest list link.";

                    $messageType =
                        "error";
                }


                $stmt->close();


            } else {

                $message =
                    "Database error while creating guest list.";

                $messageType =
                    "error";
            }
        }


        /*
         * POST → REDIRECT → GET
         */

        $_SESSION["booking_flash_message"] =
            $message;

        $_SESSION["booking_flash_type"] =
            $messageType;


        header(
            "Location: admin_wb.php"
        );

        exit;
    }
}


/* =========================================================
   FILTER VARIABLES
========================================================= */

$search =
    trim(
        $_GET["search"]
        ?? ""
    );


$statusFilter =
    strtolower(
        trim(
            $_GET["status"]
            ?? ""
        )
    );


$eventDate =
    trim(
        $_GET["event_date"]
        ?? ""
    );


/* =========================================================
   STATISTICS
========================================================= */

$totalBookings =
    0;


$pendingBookings =
    0;


$confirmedBookings =
    0;


$completedBookings =
    0;


$cancelledBookings =
    0;


$withGuestList =
    0;


/* =========================================================
   GET BOOKING STATISTICS
========================================================= */

$statisticsSql = "

    SELECT

        COUNT(*) AS total_bookings,

        SUM(
            status = 'pending'
        ) AS pending_bookings,

        SUM(
            status = 'confirmed'
        ) AS confirmed_bookings,

        SUM(
            status = 'completed'
        ) AS completed_bookings,

        SUM(
            status = 'cancelled'
        ) AS cancelled_bookings,

        SUM(

            CASE

                WHEN
                    invitation_token IS NOT NULL
                    AND invitation_token <> ''

                THEN 1

                ELSE 0

            END

        ) AS guest_lists

    FROM booking_wedding

";


$statisticsResult =
    $conn->query(
        $statisticsSql
    );


if ($statisticsResult) {

    $statistics =
        $statisticsResult->fetch_assoc();


    $totalBookings =
        (int) (
            $statistics["total_bookings"]
            ?? 0
        );


    $pendingBookings =
        (int) (
            $statistics["pending_bookings"]
            ?? 0
        );


    $confirmedBookings =
        (int) (
            $statistics["confirmed_bookings"]
            ?? 0
        );


    $completedBookings =
        (int) (
            $statistics["completed_bookings"]
            ?? 0
        );


    $cancelledBookings =
        (int) (
            $statistics["cancelled_bookings"]
            ?? 0
        );


    $withGuestList =
        (int) (
            $statistics["guest_lists"]
            ?? 0
        );
}


/* =========================================================
   BUILD WHERE CLAUSE
========================================================= */

$where =
    [];


$params =
    [];


$types =
    "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $where[] = "

        (

            b.bride_name LIKE ?

            OR b.groom_name LIKE ?

            OR b.phone LIKE ?

            OR b.special_request LIKE ?

            OR b.church LIKE ?

            OR CAST(b.id AS CHAR) LIKE ?

            OR ep.package_name LIKE ?

            OR et.name LIKE ?

            OR v.venue_name LIKE ?

        )

    ";


    $searchValue =
        "%"
        . $search
        . "%";


    for (
        $i = 0;
        $i < 9;
        $i++
    ) {

        $params[] =
            $searchValue;

        $types .=
            "s";
    }
}


/* =========================================================
   STATUS FILTER
========================================================= */

if (
    $statusFilter !== ""
    &&
    in_array(
        $statusFilter,
        $statusOptions,
        true
    )
) {

    $where[] =
        "b.status = ?";


    $params[] =
        $statusFilter;


    $types .=
        "s";
}


/* =========================================================
   EVENT DATE FILTER
========================================================= */

if ($eventDate !== "") {

    $where[] =
        "b.event_date = ?";


    $params[] =
        $eventDate;


    $types .=
        "s";
}


/* =========================================================
   WHERE SQL
========================================================= */

$whereSql =
    "";


if (!empty($where)) {

    $whereSql =
        "WHERE "
        . implode(
            " AND ",
            $where
        );
}


/* =========================================================
   GET BOOKINGS
========================================================= */

$bookings =
    [];


$sql = "

    SELECT

        b.id,

        b.user_id,

        b.package_id,

        b.venue_id,

        CONCAT(

            COALESCE(
                b.bride_name,
                ''
            ),

            CASE

                WHEN
                    b.bride_name IS NOT NULL
                    AND b.bride_name <> ''
                    AND b.groom_name IS NOT NULL
                    AND b.groom_name <> ''

                THEN ' & '

                ELSE ''

            END,

            COALESCE(
                b.groom_name,
                ''
            )

        ) AS event_name,

        b.bride_name,

        b.groom_name,

        b.church,

        b.event_date,

        b.start_time,

        b.guest_count,

        /*
         * Keep invitation_guest_count available
         * for the existing booking details.
         */

        b.invitation_guest_count,

        CONVERT(
            b.phone USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS phone,

        CONVERT(
            b.special_request USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS special_requests,

        CONVERT(
            b.status USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS status,

        CONVERT(
            b.invitation_token USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS invitation_token,

        b.created_at,

        b.Update_at AS updated_at,

        CONVERT(
            ep.package_name USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS package_name,

        CONVERT(
            ep.image USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS package_image,

        CONVERT(
            et.name USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS event_type_name,

        CONVERT(
            v.venue_name USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS venue_name,

        CONVERT(
            v.location USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS venue_location,

        CONVERT(
            v.venue_type USING utf8mb4
        )
        COLLATE utf8mb4_unicode_ci
        AS venue_type

    FROM booking_wedding b

    LEFT JOIN event_packages ep
        ON ep.id = b.package_id

    LEFT JOIN event_types et
        ON et.id = ep.event_type_id

    LEFT JOIN venues v
        ON v.id = b.venue_id

    $whereSql

    ORDER BY
        b.event_date ASC,
        b.start_time ASC,
        b.id DESC

";


$stmt =
    $conn->prepare(
        $sql
    );


if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }


    if ($stmt->execute()) {

        $result =
            $stmt->get_result();


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $bookings[] =
                $row;
        }
    }


    $stmt->close();
}


/* =========================================================
   START PAGE OUTPUT
========================================================= */

ob_start();

?>

<link
    rel="stylesheet"
    href="admin.css/admin_wb.css"
>

<div class="admin-bookings">

<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="bookings-header">


<div class="bookings-header-left">

    <div class="bookings-title-icon">

        <i class="fa-solid fa-calendar-check"></i>

    </div>


    <div>

        <span class="bookings-page-label">

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
            Manage customer event bookings,
            schedules, packages and guest lists.
        </p>

    </div>

</div>


<div class="bookings-header-actions">

    <button
        type="button"
        class="btn-bookings-report"
        id="btnBookingsReport"
    >

        <i class="fa-solid fa-print"></i>

        <span>
            Print Report
        </span>

    </button>

</div>


</div>

<!-- =====================================================
     ERROR
====================================================== -->

<?php if (
    $message !== ""
    &&
    $messageType === "error"
): ?>


<div
    class="bookings-alert error"
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
    $message !== ""
    &&
    $messageType === "success"
): ?>


<div
    class="bookings-toast"
    id="bookingsToast"
    role="status"
    aria-live="polite"
>

    <div class="bookings-toast-icon">

        <i class="fa-solid fa-circle-check"></i>

    </div>


    <div class="bookings-toast-content">

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
        class="bookings-toast-close"
        id="bookingsToastClose"
        aria-label="Close"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>


    <div class="bookings-toast-progress"></div>

</div>


<?php endif; ?>

<!-- =====================================================
     STATISTICS
====================================================== -->

<div class="booking-stat-grid">


<div class="booking-stat-card">

    <div class="booking-stat-icon total">

        <i class="fa-solid fa-calendar-days"></i>

    </div>


    <div class="booking-stat-content">

        <span>
            Total Bookings
        </span>

        <strong>
            <?= number_format($totalBookings); ?>
        </strong>

    </div>

</div>


<div class="booking-stat-card">

    <div class="booking-stat-icon pending">

        <i class="fa-solid fa-clock"></i>

    </div>


    <div class="booking-stat-content">

        <span>
            Pending
        </span>

        <strong>
            <?= number_format($pendingBookings); ?>
        </strong>

    </div>

</div>


<div class="booking-stat-card">

    <div class="booking-stat-icon confirmed">

        <i class="fa-solid fa-circle-check"></i>

    </div>


    <div class="booking-stat-content">

        <span>
            Confirmed
        </span>

        <strong>
            <?= number_format($confirmedBookings); ?>
        </strong>

    </div>

</div>


<div class="booking-stat-card">

    <div class="booking-stat-icon completed">

        <i class="fa-solid fa-flag-checkered"></i>

    </div>


    <div class="booking-stat-content">

        <span>
            Completed
        </span>

        <strong>
            <?= number_format($completedBookings); ?>
        </strong>

    </div>

</div>


<div class="booking-stat-card">

    <div class="booking-stat-icon guest-list">

        <i class="fa-solid fa-user-group"></i>

    </div>


    <div class="booking-stat-content">

        <span>
            Guest Lists
        </span>

        <strong>
            <?= number_format($withGuestList); ?>
        </strong>

    </div>

</div>


</div>

<!-- =====================================================
     MAIN PANEL
====================================================== -->

<div class="bookings-panel">


<div class="bookings-panel-header">

    <div>

        <span class="bookings-panel-label">
            EVENT BOOKINGS
        </span>

        <strong>

            <?= number_format(
                count($bookings)
            ); ?>

            displayed

        </strong>

    </div>

</div>


<!-- =================================================
     FILTERS
================================================== -->

<form
    method="GET"
    action="admin_wb.php"
    class="bookings-filter-bar"
    id="bookingsFilterForm"
>

    <div class="bookings-search">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars(
                $search,
                ENT_QUOTES,
                "UTF-8"
            ); ?>"
            placeholder="Search event, package, venue..."
            autocomplete="off"
        >

    </div>


    <div class="bookings-filter">

        <select name="status">

            <option value="">
                All Status
            </option>


            <?php foreach (
                $statusOptions
                as $status
            ): ?>

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
                        getBookingStatusLabel($status),
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <div class="bookings-date-filter">

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
        class="btn-bookings-search"
    >

        <i class="fa-solid fa-magnifying-glass"></i>

        <span>
            Search
        </span>

    </button>


    <a
        href="admin_wb.php"
        class="btn-bookings-reset"
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
    class="bookings-report"
    id="bookingsReport"
>

    <div class="bookings-report-header">

        <div>

            <h3>
                Event Booking Report
            </h3>

            <p>
                Customer event bookings and reservations
            </p>

        </div>


        <span class="bookings-report-date">

            <?= date(
                "F d, Y h:i A"
            ); ?>

        </span>

    </div>


    <!-- =================================================
         TABLE
    ================================================== -->

    <div class="bookings-table-wrapper">

        <table class="bookings-table">

            <thead>

                <tr>

                    <th>Booking</th>

                    <th>Event</th>

                    <th>Customer</th>

                    <th>Schedule</th>

                    <th>Package</th>

                    <th>Venue</th>

                    <th>Guests</th>

                    <th>Status</th>

                    <th>Guest List</th>

                    <th class="no-print">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php if (!empty($bookings)): ?>

                <?php foreach (
                    $bookings
                    as $booking
                ): ?>

                    <?php

                    $bookingId =
                        (int) (
                            $booking["id"]
                            ?? 0
                        );


                    $eventName =
                        trim(
                            (string) (
                                $booking["event_name"]
                                ?? ""
                            )
                        );


                    if ($eventName === "") {

                        $eventName =
                            "Unnamed Event";
                    }


                    $eventType =
                        trim(
                            (string) (
                                $booking["event_type_name"]
                                ?? ""
                            )
                        );


                    $packageName =
                        trim(
                            (string) (
                                $booking["package_name"]
                                ?? ""
                            )
                        );


                    if ($packageName === "") {

                        $packageName =
                            "Package #"
                            . (
                                (int) (
                                    $booking["package_id"]
                                    ?? 0
                                )
                            );
                    }


                    $venueName =
                        trim(
                            (string) (
                                $booking["venue_name"]
                                ?? ""
                            )
                        );


                    $venueLocation =
                        trim(
                            (string) (
                                $booking["venue_location"]
                                ?? ""
                            )
                        );


                    $status =
                        strtolower(
                            trim(
                                (string) (
                                    $booking["status"]
                                    ?? "pending"
                                )
                            )
                        );


                    $token =
                        trim(
                            (string) (
                                $booking["invitation_token"]
                                ?? ""
                            )
                        );


                    $specialRequest =
                        trim(
                            (string) (
                                $booking["special_requests"]
                                ?? ""
                            )
                        );


                    /*
                     * ACTUAL NUMBER OF GUESTS
                     */

                    $guestCount =
                        (int) (
                            $booking["guest_count"]
                            ?? 0
                        );


                    /*
                     * INVITATION GUEST COUNT
                     */

                    $invitationGuestCount =
                        (int) (
                            $booking[
                                "invitation_guest_count"
                            ]
                            ?? 0
                        );

                    ?>


                    <tr>


                        <!-- BOOKING -->

                        <td>

                            <div class="booking-id">

                                <span>
                                    BOOKING
                                </span>

                                <strong>
                                    #<?= $bookingId; ?>
                                </strong>

                                <small>

                                    Customer #

                                    <?= (int) (
                                        $booking["user_id"]
                                        ?? 0
                                    ); ?>

                                </small>

                            </div>

                        </td>


                        <!-- EVENT -->

                        <td>

                            <div class="booking-event">

                                <strong>

                                    <?= htmlspecialchars(
                                        $eventName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                </strong>


                                <?php if (
                                    $eventType !== ""
                                ): ?>

                                    <span>

                                        <i class="fa-solid fa-tag"></i>

                                        <?= htmlspecialchars(
                                            $eventType,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- CUSTOMER -->

                        <td>

                            <div class="booking-customer">

                                <strong>
                                    Customer
                                </strong>


                                <?php if (
                                    !empty(
                                        $booking["phone"]
                                    )
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

                                    <small>
                                        No phone
                                    </small>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- SCHEDULE -->

                        <td>

                            <div class="booking-schedule">

                                <strong>

                                    <?= formatBookingDate(
                                        $booking["event_date"]
                                        ?? ""
                                    ); ?>

                                </strong>


                                <span>

                                    <i class="fa-regular fa-clock"></i>

                                    <?= formatBookingTime(
                                        $booking["start_time"]
                                        ?? ""
                                    ); ?>

                                </span>

                            </div>

                        </td>


                        <!-- PACKAGE -->

                        <td>

                            <div class="booking-package">

                                <span>

                                    <i class="fa-solid fa-box-open"></i>

                                    <?= htmlspecialchars(
                                        $packageName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                </span>

                            </div>

                        </td>


                        <!-- VENUE -->

                        <td>

                            <div class="booking-venue">

                                <?php if (
                                    $venueName !== ""
                                ): ?>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $venueName,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>

                                    </strong>


                                    <?php if (
                                        $venueLocation !== ""
                                    ): ?>

                                        <small>

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?= htmlspecialchars(
                                                $venueLocation,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                        </small>

                                    <?php endif; ?>


                                <?php else: ?>

                                    <span class="muted">
                                        No venue
                                    </span>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- GUESTS -->

                        <td>

                            <div class="booking-guests">

                                <strong>

                                    <?= number_format(
                                        $guestCount
                                    ); ?>

                                </strong>

                                <span>
                                    guests
                                </span>


                                <?php if (
                                    $guestCount > 0
                                ): ?>

                                    <small>

                                        <?= number_format(
                                            $guestCount
                                        ); ?>

                                        invited

                                    </small>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span
                                class="booking-status <?= htmlspecialchars(
                                    $status,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                            >

                                <i
                                    class="fa-solid <?= htmlspecialchars(
                                        getBookingStatusIcon(
                                            $status
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                ></i>


                                <?= htmlspecialchars(
                                    getBookingStatusLabel(
                                        $status
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </span>

                        </td>


                        <!-- GUEST LIST -->

                        <td>

                            <?php if (
                                $token !== ""
                            ): ?>

                                <a
                                    href="admin_wg.php?token=<?= urlencode(
                                        $token
                                    ); ?>"
                                    class="btn-booking-guest-list"
                                    title="Open Guest List"
                                >

                                    <i class="fa-solid fa-users"></i>

                                    <span>
                                        Guest List
                                    </span>

                                </a>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="btn-create-token"
                                    title="Create Guest List Link"

                                    data-create-guest-link

                                    data-booking-id="<?= $bookingId; ?>"

                                    data-event-name="<?= htmlspecialchars(
                                        $eventName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"

                                    data-guest-count="<?= $guestCount; ?>"
                                >

                                    <i class="fa-solid fa-link"></i>

                                    <span>
                                        Create
                                    </span>

                                </button>

                            <?php endif; ?>

                        </td>


                        <!-- ACTION -->

                        <td class="no-print">

                            <button
                                type="button"
                                class="btn-booking-view"

                                data-booking-id="<?= $bookingId; ?>"

                                data-user-id="<?= (int) (
                                    $booking["user_id"]
                                    ?? 0
                                ); ?>"

                                data-package-id="<?= (int) (
                                    $booking["package_id"]
                                    ?? 0
                                ); ?>"

                                data-venue-id="<?= (int) (
                                    $booking["venue_id"]
                                    ?? 0
                                ); ?>"

                                data-event-name="<?= htmlspecialchars(
                                    $eventName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-event-type="<?= htmlspecialchars(
                                    $eventType,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-event-date="<?= htmlspecialchars(
                                    formatBookingDate(
                                        $booking["event_date"]
                                        ?? ""
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-start-time="<?= htmlspecialchars(
                                    formatBookingTime(
                                        $booking["start_time"]
                                        ?? ""
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-phone="<?= htmlspecialchars(
                                    $booking["phone"]
                                    ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-package-name="<?= htmlspecialchars(
                                    $packageName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-venue-name="<?= htmlspecialchars(
                                    $venueName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-venue-location="<?= htmlspecialchars(
                                    $venueLocation,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-guest-count="<?= $guestCount; ?>"

                                data-invitation-guest-count="<?= $invitationGuestCount; ?>"

                                data-special-request="<?= htmlspecialchars(
                                    $specialRequest,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-status="<?= htmlspecialchars(
                                    $status,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-token="<?= htmlspecialchars(
                                    $token,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"

                                data-created-at="<?= htmlspecialchars(
                                    $booking["created_at"]
                                    ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                            >

                                <i class="fa-solid fa-eye"></i>

                                <span>
                                    View
                                </span>

                            </button>

                        </td>

                    </tr>


                    <!-- SPECIAL REQUEST -->

                    <?php if (
                        $specialRequest !== ""
                    ): ?>

                        <tr class="special-request-row">

                            <td colspan="10">

                                <div class="booking-special-request">

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
                                                    $specialRequest,
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
                        colspan="10"
                        class="no-table-data"
                    >

                        <div class="bookings-empty">

                            <div class="bookings-empty-icon">

                                <i class="fa-solid fa-calendar-xmark"></i>

                            </div>


                            <h3>
                                No Bookings Found
                            </h3>


                            <p>
                                There are no event bookings
                                matching your current filters.
                            </p>


                            <?php if (
                                $search !== ""
                                ||
                                $statusFilter !== ""
                                ||
                                $eventDate !== ""
                            ): ?>

                                <a
                                    href="admin_wb.php"
                                    class="bookings-empty-button"
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
     BOOKING DETAILS / STATUS MODAL
========================================================= -->

<div
    class="booking-modal"
    id="bookingModal"
    aria-hidden="true"
>

<div
    class="booking-modal-overlay"
    data-close-booking-modal
></div>

<div
    class="booking-modal-content"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bookingModalTitle"
>


<button
    type="button"
    class="booking-modal-close"
    id="bookingModalClose"
    aria-label="Close"
>

    <i class="fa-solid fa-xmark"></i>

</button>


<div class="booking-modal-header">

    <div class="booking-modal-icon">

        <i class="fa-solid fa-calendar-check"></i>

    </div>


    <div>

        <span>
            BOOKING DETAILS
        </span>

        <h3 id="bookingModalTitle">
            Event Booking
        </h3>

    </div>

</div>


<div class="booking-detail-grid">

    <div class="booking-detail-item">

        <span>Booking ID</span>

        <strong id="detailBookingId">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Customer ID</span>

        <strong id="detailUserId">—</strong>

    </div>


    <div class="booking-detail-item full">

        <span>Event</span>

        <strong id="detailEventName">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Event Type</span>

        <strong id="detailEventType">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Package</span>

        <strong id="detailPackage">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Event Date</span>

        <strong id="detailEventDate">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Start Time</span>

        <strong id="detailStartTime">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Phone</span>

        <strong id="detailPhone">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Venue</span>

        <strong id="detailVenue">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Guests</span>

        <strong id="detailGuests">—</strong>

    </div>


    <div
        class="booking-detail-item full"
        id="specialRequestDetail"
    >

        <span>Special Request</span>

        <strong id="detailSpecialRequest">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Created</span>

        <strong id="detailCreatedAt">—</strong>

    </div>


    <div class="booking-detail-item">

        <span>Guest List</span>

        <strong id="detailGuestListStatus">—</strong>

    </div>

</div>


<div class="booking-status-section">

    <span class="booking-status-section-label">
        BOOKING STATUS
    </span>


    <div
        class="booking-status-options"
        id="bookingStatusOptions"
    >

        <button
            type="button"
            class="booking-status-option pending"
            data-status-option="pending"
        >

            <i class="fa-solid fa-clock"></i>

            <span>Pending</span>

        </button>


        <button
            type="button"
            class="booking-status-option confirmed"
            data-status-option="confirmed"
        >

            <i class="fa-solid fa-circle-check"></i>

            <span>Confirmed</span>

        </button>


        <button
            type="button"
            class="booking-status-option completed"
            data-status-option="completed"
        >

            <i class="fa-solid fa-flag-checkered"></i>

            <span>Completed</span>

        </button>


        <button
            type="button"
            class="booking-status-option cancelled"
            data-status-option="cancelled"
        >

            <i class="fa-solid fa-ban"></i>

            <span>Cancelled</span>

        </button>

    </div>


    <form
        method="POST"
        action="admin_wb.php"
        id="bookingStatusForm"
    >

        <input
            type="hidden"
            name="action"
            value="update_status"
        >


        <input
            type="hidden"
            name="booking_id"
            id="bookingStatusBookingId"
            value=""
        >


        <input
            type="hidden"
            name="status"
            id="bookingSelectedStatus"
            value=""
        >


        <div class="booking-modal-actions">

            <button
                type="button"
                class="booking-modal-cancel"
                id="bookingModalCancel"
            >

                <i class="fa-solid fa-xmark"></i>

                Cancel

            </button>


            <button
                type="submit"
                class="booking-modal-save"
                id="bookingStatusConfirm"
                disabled
            >

                <i class="fa-solid fa-circle-check"></i>

                Update Status

            </button>

        </div>

    </form>

</div>


</div>

</div>

<!-- =========================================================
     GUEST CREATE MODAL
========================================================= -->

<div
    class="guest-create-modal"
    id="guestCreateModal"
    aria-hidden="true"
>

<div
    class="guest-create-overlay"
    data-close-guest-create
></div>

<div
    class="guest-create-content"
    role="dialog"
    aria-modal="true"
    aria-labelledby="guestCreateTitle"
>


<button
    type="button"
    class="guest-create-close"
    id="guestCreateClose"
    aria-label="Close"
>

    <i class="fa-solid fa-xmark"></i>

</button>


<div class="guest-create-icon">

    <i class="fa-solid fa-link"></i>

</div>


<span class="guest-create-label">
    GUEST LIST
</span>


<h3 id="guestCreateTitle">
    Create Guest List Link
</h3>


<p>
    Enter the expected number of guests for this
    event before generating the guest-list link.
</p>


<div class="guest-create-event">

    <i class="fa-solid fa-calendar-days"></i>

    <span id="guestCreateEventName">
        Event
    </span>

</div>


<div class="guest-create-field">

    <label for="guestCreateCount">
        Number of Guests
    </label>


    <div class="guest-create-input">

        <i class="fa-solid fa-users"></i>


        <input
            type="number"
            id="guestCreateCount"
            min="1"
            max="10000"
            step="1"
            placeholder="Enter guest number"
            autocomplete="off"
        >

    </div>


    <small>
        Enter a number from 1 to 10,000.
    </small>

</div>


<form
    method="POST"
    action="admin_wb.php"
    id="guestCreateForm"
>

    <input
        type="hidden"
        name="action"
        value="create_token"
    >


    <input
        type="hidden"
        name="booking_id"
        id="guestCreateBookingId"
        value=""
    >


    <input
        type="hidden"
        name="guest_count"
        id="guestCreateGuestCount"
        value=""
    >


    <div class="guest-create-actions">

        <button
            type="button"
            class="guest-create-cancel"
            id="guestCreateCancel"
        >

            Cancel

        </button>


        <button
            type="submit"
            class="guest-create-generate"
            id="guestCreateGenerate"
            disabled
        >

            <i class="fa-solid fa-link"></i>

            Generate Link

        </button>

    </div>

</form>


</div>

</div>

<!-- =========================================================
     GUEST TOKEN MODAL
========================================================= -->

<div
    class="guest-token-modal"
    id="guestTokenModal"
    aria-hidden="true"
>

<div
    class="guest-token-overlay"
    data-close-token-modal
></div>

<div
    class="guest-token-content"
    role="dialog"
    aria-modal="true"
    aria-labelledby="guestTokenTitle"
>


<button
    type="button"
    class="guest-token-close"
    id="guestTokenClose"
    aria-label="Close"
>

    <i class="fa-solid fa-xmark"></i>

</button>


<div class="guest-token-icon">

    <i class="fa-solid fa-link"></i>

</div>


<span class="guest-token-label">
    GUEST LIST
</span>


<h3 id="guestTokenTitle">
    Guest List Token
</h3>


<p>
    This booking has a public guest-list token.
    Guests can use the generated invitation link
    to access the guest list.
</p>


<div class="guest-token-box">

    <input
        type="text"
        id="guestTokenValue"
        readonly
    >


    <button
        type="button"
        id="guestTokenCopy"
        title="Copy token"
    >

        <i class="fa-regular fa-copy"></i>

    </button>

</div>


<div class="guest-token-actions">

    <a
        href="admin_wg.php"
        id="guestListOpen"
        class="guest-list-open"
    >

        <i class="fa-solid fa-users"></i>

        Open Guest List

    </a>


    <button
        type="button"
        id="guestTokenModalCancel"
        class="guest-token-cancel"
    >

        Close

    </button>

</div>


</div>

</div>

<script src="admin.js/admin_wb.js"></script>

<?php

$pageContent =
    ob_get_clean();


/* =========================================================
   ADMIN HEADER
========================================================= */

$adminHeaderPath =
    __DIR__
    . "/admin_include/admin_header.php";


if (file_exists($adminHeaderPath)) {

    require_once $adminHeaderPath;


} else {

    die(
        "Admin header file not found.<br><br>"
        . "Expected path:<br>"
        . htmlspecialchars(
            $adminHeaderPath,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}

?>
