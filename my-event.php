<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

require_once "config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

$emailConfig = require __DIR__ . "/config/email.php";


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
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

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flashMessage = $_SESSION['my_event_message'] ?? null;
$flashType = $_SESSION['my_event_message_type'] ?? 'success';

unset($_SESSION['my_event_message']);
unset($_SESSION['my_event_message_type']);


/*
|--------------------------------------------------------------------------
| CANCEL BOOKING
|--------------------------------------------------------------------------
|
| Cancellation is handled directly inside this page.
|
| Regular booking:
|       bookings
|
| Wedding booking:
|       booking_wedding
|
| IMPORTANT:
|
| The booking will ONLY be permanently cancelled if the
| administrator notification email is successfully sent.
|
| If the email fails:
|
|       1. Database transaction is rolled back.
|       2. Booking keeps its previous status.
|       3. User receives an error message.
|
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'cancel_booking'
) {

    $postedToken =
        isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    $bookingId =
        isset($_POST['booking_id'])
        ? (int) $_POST['booking_id']
        : 0;

    $bookingType =
        isset($_POST['booking_type'])
        ? strtolower(trim($_POST['booking_type']))
        : 'regular';


    /*
    |----------------------------------------------------------------------
    | CSRF VALIDATION
    |----------------------------------------------------------------------
    */

    if (
        empty($postedToken) ||
        !hash_equals($csrfToken, $postedToken)
    ) {

        $_SESSION['my_event_message'] =
            "Your session has expired. Please try again.";

        $_SESSION['my_event_message_type'] =
            "error";

        header("Location: my-event.php");
        exit;
    }


    /*
    |----------------------------------------------------------------------
    | BASIC VALIDATION
    |----------------------------------------------------------------------
    */

    if ($bookingId <= 0) {

        $_SESSION['my_event_message'] =
            "Invalid booking.";

        $_SESSION['my_event_message_type'] =
            "error";

        header("Location: my-event.php");
        exit;
    }


    if (
        $bookingType !== 'regular' &&
        $bookingType !== 'wedding'
    ) {

        $_SESSION['my_event_message'] =
            "Invalid booking type.";

        $_SESSION['my_event_message_type'] =
            "error";

        header("Location: my-event.php");
        exit;
    }


    /*
    |----------------------------------------------------------------------
    | BOOKING INFORMATION FOR EMAIL
    |----------------------------------------------------------------------
    */

    $bookingData = null;


    /*
    |----------------------------------------------------------------------
    | REGULAR BOOKING
    |----------------------------------------------------------------------
    */

    if ($bookingType === 'regular') {

        $cancelStmt = $conn->prepare("
            SELECT
                b.id,
                b.event_name,
                b.event_date,
                b.start_time,
                b.guest_count,
                b.phone,
                b.status,
                b.created_at,
                u.fullname,
                u.email,
                p.package_name,
                p.image AS package_image,
                v.venue_name,
                et.name AS event_type_name

            FROM bookings b

            INNER JOIN users u
                ON b.user_id = u.id

            LEFT JOIN event_packages p
                ON b.package_id = p.id

            LEFT JOIN venues v
                ON b.venue_id = v.id

            LEFT JOIN event_types et
                ON p.event_type_id = et.id

            WHERE b.id = ?
              AND b.user_id = ?

            LIMIT 1
        ");


        if (!$cancelStmt) {

            $_SESSION['my_event_message'] =
                "Unable to process the booking cancellation.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        $cancelStmt->bind_param(
            "ii",
            $bookingId,
            $userId
        );

        $cancelStmt->execute();

        $cancelResult =
            $cancelStmt->get_result();

        $bookingData =
            $cancelResult->fetch_assoc();

        $cancelStmt->close();


        if (!$bookingData) {

            $_SESSION['my_event_message'] =
                "Booking not found or you do not have permission to cancel it.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        /*
        |------------------------------------------------------------------
        | CHECK CURRENT STATUS
        |------------------------------------------------------------------
        */

        $currentStatus =
            strtolower(
                trim(
                    $bookingData['status'] ?? ''
                )
            );


        if ($currentStatus === 'cancelled') {

            $_SESSION['my_event_message'] =
                "This booking has already been cancelled.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        if ($currentStatus === 'completed') {

            $_SESSION['my_event_message'] =
                "A completed booking cannot be cancelled.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }
    }


    /*
    |----------------------------------------------------------------------
    | WEDDING BOOKING
    |----------------------------------------------------------------------
    */

    if ($bookingType === 'wedding') {

        $cancelStmt = $conn->prepare("
            SELECT
                bw.id,
                bw.bride_name,
                bw.groom_name,
                bw.event_date,
                bw.start_time,
                bw.guest_count,
                bw.church,
                bw.special_request,
                bw.status,
                bw.created_at,
                u.fullname,
                u.email,
                p.package_name,
                p.image AS package_image,
                v.venue_name

            FROM booking_wedding bw

            INNER JOIN users u
                ON bw.user_id = u.id

            LEFT JOIN event_packages p
                ON bw.package_id = p.id

            LEFT JOIN venues v
                ON bw.venue_id = v.id

            WHERE bw.id = ?
              AND bw.user_id = ?

            LIMIT 1
        ");


        if (!$cancelStmt) {

            $_SESSION['my_event_message'] =
                "Unable to process the wedding cancellation.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        $cancelStmt->bind_param(
            "ii",
            $bookingId,
            $userId
        );

        $cancelStmt->execute();

        $cancelResult =
            $cancelStmt->get_result();

        $bookingData =
            $cancelResult->fetch_assoc();

        $cancelStmt->close();


        if (!$bookingData) {

            $_SESSION['my_event_message'] =
                "Wedding booking not found or you do not have permission to cancel it.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        /*
        |------------------------------------------------------------------
        | CHECK CURRENT STATUS
        |------------------------------------------------------------------
        */

        $currentStatus =
            strtolower(
                trim(
                    $bookingData['status'] ?? ''
                )
            );


        if ($currentStatus === 'cancelled') {

            $_SESSION['my_event_message'] =
                "This wedding booking has already been cancelled.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        if ($currentStatus === 'completed') {

            $_SESSION['my_event_message'] =
                "A completed wedding booking cannot be cancelled.";

            $_SESSION['my_event_message_type'] =
                "error";

            header("Location: my-event.php");
            exit;
        }


        /*
        |------------------------------------------------------------------
        | CREATE EVENT NAME FOR EMAIL
        |------------------------------------------------------------------
        */

        $bookingData['event_name'] =
            trim(
                ($bookingData['bride_name'] ?? '') .
                    ' & ' .
                    ($bookingData['groom_name'] ?? '') .
                    ' Wedding'
            );

        $bookingData['event_type_name'] =
            'Wedding';
    }


    /*
    |----------------------------------------------------------------------
    | START DATABASE TRANSACTION
    |----------------------------------------------------------------------
    */

    $transactionStarted = false;


    try {

        /*
        |------------------------------------------------------------------
        | START TRANSACTION
        |------------------------------------------------------------------
        */

        if (!$conn->begin_transaction()) {

            throw new Exception(
                "Unable to start the cancellation transaction."
            );
        }

        $transactionStarted = true;


        /*
        |------------------------------------------------------------------
        | UPDATE REGULAR BOOKING
        |------------------------------------------------------------------
        */

        if ($bookingType === 'regular') {

            $updateStmt = $conn->prepare("
                UPDATE bookings
                SET status = 'cancelled'
                WHERE id = ?
                  AND user_id = ?
            ");


            if (!$updateStmt) {

                throw new Exception(
                    "Unable to prepare the booking cancellation."
                );
            }


            $updateStmt->bind_param(
                "ii",
                $bookingId,
                $userId
            );

            $updateStmt->execute();


            $updated =
                $updateStmt->affected_rows > 0;


            $updateStmt->close();


            if (!$updated) {

                throw new Exception(
                    "The booking could not be cancelled."
                );
            }
        }


        /*
        |------------------------------------------------------------------
        | UPDATE WEDDING BOOKING
        |------------------------------------------------------------------
        */

        if ($bookingType === 'wedding') {

            $updateStmt = $conn->prepare("
                UPDATE booking_wedding
                SET status = 'cancelled'
                WHERE id = ?
                  AND user_id = ?
            ");


            if (!$updateStmt) {

                throw new Exception(
                    "Unable to prepare the wedding cancellation."
                );
            }


            $updateStmt->bind_param(
                "ii",
                $bookingId,
                $userId
            );

            $updateStmt->execute();


            $updated =
                $updateStmt->affected_rows > 0;


            $updateStmt->close();


            if (!$updated) {

                throw new Exception(
                    "The wedding booking could not be cancelled."
                );
            }
        }


        /*
        |------------------------------------------------------------------
        | SEND EMAIL TO ADMIN
        |------------------------------------------------------------------
        */

        $mail = new PHPMailer(true);


        /*
        |------------------------------------------------------------------
        | VALIDATE EMAIL CONFIGURATION
        |------------------------------------------------------------------
        */

        if (!is_array($emailConfig)) {

            throw new Exception(
                "Email configuration could not be loaded."
            );
        }


        /*
        |------------------------------------------------------------------
        | SMTP SETTINGS
        |------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | These names MUST match config/email.php:
        |
        | smtp_host
        | smtp_port
        | smtp_username
        | smtp_password
        |
        |------------------------------------------------------------------
        */

        $smtpHost =
            trim(
                (string) (
                    $emailConfig['smtp_host']
                    ?? ''
                )
            );


        $smtpUsername =
            trim(
                (string) (
                    $emailConfig['smtp_username']
                    ?? ''
                )
            );


        $smtpPassword =
            (string) (
                $emailConfig['smtp_password']
                ?? ''
            );


        $smtpPort =
            (int) (
                $emailConfig['smtp_port']
                ?? 587
            );


        /*
        |------------------------------------------------------------------
        | SMTP ENCRYPTION
        |------------------------------------------------------------------
        |
        | Your config/email.php does not need an encryption setting.
        |
        | Gmail SMTP port 587 uses STARTTLS.
        |
        |------------------------------------------------------------------
        */

        $smtpEncryption =
            strtolower(
                trim(
                    (string) (
                        $emailConfig['smtp_encryption']
                        ?? 'tls'
                    )
                )
            );


        /*
        |------------------------------------------------------------------
        | FROM INFORMATION
        |------------------------------------------------------------------
        */

        $fromEmail =
            trim(
                (string) (
                    $emailConfig['from_email']
                    ?? ''
                )
            );


        $fromName =
            trim(
                (string) (
                    $emailConfig['from_name']
                    ?? 'Event Solutions by S.H.E.'
                )
            );


        /*
        |------------------------------------------------------------------
        | ADMIN INFORMATION
        |------------------------------------------------------------------
        */

        $adminEmail =
            trim(
                (string) (
                    $emailConfig['admin_email']
                    ?? ''
                )
            );


        $adminName =
            trim(
                (string) (
                    $emailConfig['admin_name']
                    ?? 'Event Solutions by S.H.E.'
                )
            );


        /*
        |------------------------------------------------------------------
        | VALIDATE SMTP HOST
        |------------------------------------------------------------------
        */

        if ($smtpHost === '') {

            throw new Exception(
                "SMTP host is not configured."
            );
        }


        /*
        |------------------------------------------------------------------
        | VALIDATE SMTP USERNAME
        |------------------------------------------------------------------
        */

        if ($smtpUsername === '') {

            throw new Exception(
                "SMTP username is not configured."
            );
        }


        /*
        |------------------------------------------------------------------
        | VALIDATE SMTP PASSWORD
        |------------------------------------------------------------------
        */

        if ($smtpPassword === '') {

            throw new Exception(
                "SMTP password or App Password is not configured."
            );
        }


        /*
        |------------------------------------------------------------------
        | VALIDATE SMTP PORT
        |------------------------------------------------------------------
        */

        if ($smtpPort <= 0) {

            throw new Exception(
                "SMTP port is not configured correctly."
            );
        }


        /*
        |------------------------------------------------------------------
        | VALIDATE FROM EMAIL
        |------------------------------------------------------------------
        */

        if ($fromEmail === '') {

            throw new Exception(
                "Sender email address is not configured."
            );
        }


        if (
            !filter_var(
                $fromEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new Exception(
                "The sender email address is invalid."
            );
        }


        /*
        |------------------------------------------------------------------
        | VALIDATE ADMIN EMAIL
        |------------------------------------------------------------------
        */

        if ($adminEmail === '') {

            throw new Exception(
                "Administrator email address is not configured."
            );
        }


        if (
            !filter_var(
                $adminEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new Exception(
                "The administrator email address is invalid."
            );
        }


        /*
        |------------------------------------------------------------------
        | SMTP CONNECTION
        |------------------------------------------------------------------
        */

        $mail->isSMTP();


        $mail->Host =
            $smtpHost;


        $mail->SMTPAuth =
            true;


        $mail->Username =
            $smtpUsername;


        $mail->Password =
            $smtpPassword;


        /*
        |------------------------------------------------------------------
        | SMTP ENCRYPTION
        |------------------------------------------------------------------
        |
        | Gmail:
        |
        | Port 587 = STARTTLS
        | Port 465 = SMTPS
        |
        | Your current config uses port 587,
        | so STARTTLS will be used.
        |
        |------------------------------------------------------------------
        */

        if (
            $smtpEncryption === 'ssl' ||
            $smtpEncryption === 'smtps' ||
            $smtpPort === 465
        ) {

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_SMTPS;


            $mail->Port =
                465;
        } else {

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;


            $mail->Port =
                $smtpPort;
        }


        /*
        |------------------------------------------------------------------
        | CHARACTER SET
        |------------------------------------------------------------------
        */

        $mail->CharSet =
            'UTF-8';


        /*
        |------------------------------------------------------------------
        | CUSTOMER INFORMATION
        |------------------------------------------------------------------
        */

        $customerName =
            $bookingData['fullname']
            ??
            'Customer';


        $customerEmail =
            $bookingData['email']
            ??
            '';


        $eventName =
            $bookingData['event_name']
            ??
            'Event';


        $eventType =
            $bookingData['event_type_name']
            ??
            'Event';


        $eventDate =
            !empty($bookingData['event_date'])
            ? date(
                'F d, Y',
                strtotime(
                    $bookingData['event_date']
                )
            )
            : 'Not specified';


        $startTime =
            !empty($bookingData['start_time'])
            ? date(
                'h:i A',
                strtotime(
                    $bookingData['start_time']
                )
            )
            : 'Not specified';


        $guestCount =
            isset(
                $bookingData['guest_count']
            )
            ? number_format(
                (int)
                $bookingData['guest_count']
            )
            : '0';


        $venueName =
            $bookingData['venue_name']
            ??
            'Not specified';


        $packageName =
            $bookingData['package_name']
            ??
            'Not specified';


        /*
        |------------------------------------------------------------------
        | SET FROM
        |------------------------------------------------------------------
        */

        $mail->setFrom(
            $fromEmail,
            $fromName
        );


        /*
        |------------------------------------------------------------------
        | SET ADMIN RECIPIENT
        |------------------------------------------------------------------
        */

        $mail->addAddress(
            $adminEmail,
            $adminName
        );


        /*
        |------------------------------------------------------------------
        | CUSTOMER REPLY-TO
        |------------------------------------------------------------------
        */

        if (
            !empty($customerEmail) &&
            filter_var(
                $customerEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $mail->addReplyTo(
                $customerEmail,
                $customerName
            );
        }


        /*
        |------------------------------------------------------------------
        | EMAIL FORMAT
        |------------------------------------------------------------------
        */

        $mail->isHTML(true);


        /*
        |------------------------------------------------------------------
        | SUBJECT
        |------------------------------------------------------------------
        */

        $mail->Subject =
            "Booking Cancellation - " .
            $eventName;


        /*
        |------------------------------------------------------------------
        | EMAIL HTML
        |------------------------------------------------------------------
        */

        $mail->Body = '

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="UTF-8">

            <style>

                body {
                    margin: 0;
                    padding: 0;
                    background: #f8f5f1;
                    font-family: Arial, Helvetica, sans-serif;
                    color: #242424;
                }

                .email-wrapper {
                    width: 100%;
                    padding: 35px 15px;
                    box-sizing: border-box;
                }

                .email-card {
                    max-width: 650px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 14px;
                    overflow: hidden;
                    border: 1px solid #e6dfd8;
                }

                .email-header {
                    background: #242424;
                    color: #ffffff;
                    padding: 30px;
                    text-align: center;
                }

                .email-header h1 {
                    margin: 0;
                    font-size: 25px;
                }

                .email-header p {
                    margin: 8px 0 0;
                    color: #e7d6ad;
                    font-size: 13px;
                }

                .email-body {
                    padding: 30px;
                }

                .email-body h2 {
                    margin-top: 0;
                    color: #9d7a32;
                    font-size: 20px;
                }

                .notice {
                    background: #fff4f0;
                    border-left: 4px solid #b94b45;
                    padding: 14px 16px;
                    margin: 20px 0;
                    border-radius: 5px;
                }

                .details {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }

                .details td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #eee7df;
                    vertical-align: top;
                }

                .details td:first-child {
                    width: 38%;
                    font-weight: bold;
                    color: #6b6259;
                }

                .cancelled {
                    color: #b94b45;
                    font-weight: bold;
                }

                .email-footer {
                    padding: 20px 30px;
                    background: #faf8f5;
                    text-align: center;
                    color: #777;
                    font-size: 12px;
                }

            </style>

        </head>

        <body>

            <div class="email-wrapper">

                <div class="email-card">

                    <div class="email-header">

                        <h1>
                            Event Solutions by S.H.E.
                        </h1>

                        <p>
                            Booking Management Notification
                        </p>

                    </div>


                    <div class="email-body">

                        <h2>
                            Booking Cancellation Notice
                        </h2>

                        <p>
                            A customer has requested to cancel
                            a booking from the customer event
                            management page.
                        </p>


                        <div class="notice">

                            <strong>
                                Booking Status:
                            </strong>

                            <span class="cancelled">
                                CANCELLED
                            </span>

                        </div>


                        <table class="details">

                            <tr>

                                <td>
                                    Customer
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $customerName,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Customer Email
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $customerEmail,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Event
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $eventName,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Event Type
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $eventType,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Event Date
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $eventDate,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Start Time
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $startTime,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Guests
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $guestCount,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Venue
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $venueName,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Package
                                </td>

                                <td>
                                    ' .
            htmlspecialchars(
                $packageName,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '
                                </td>

                            </tr>


                        </table>


                        <p style="margin-top:25px;">

                            Please review the booking
                            and update any related event,
                            venue, package, or availability
                            records if necessary.

                        </p>

                    </div>


                    <div class="email-footer">

                        Event Solutions by S.H.E.<br>

                        This is an automated booking notification.

                    </div>

                </div>

            </div>

        </body>

        </html>

        ';


        /*
        |------------------------------------------------------------------
        | PLAIN TEXT VERSION
        |------------------------------------------------------------------
        */

        $mail->AltBody =
            "Booking Cancellation Notice\n\n" .
            "Customer: " .
            $customerName .
            "\n" .
            "Email: " .
            $customerEmail .
            "\n" .
            "Event: " .
            $eventName .
            "\n" .
            "Event Type: " .
            $eventType .
            "\n" .
            "Event Date: " .
            $eventDate .
            "\n" .
            "Start Time: " .
            $startTime .
            "\n" .
            "Guests: " .
            $guestCount .
            "\n" .
            "Venue: " .
            $venueName .
            "\n" .
            "Package: " .
            $packageName .
            "\n" .
            "Status: CANCELLED";


        /*
        |------------------------------------------------------------------
        | SEND EMAIL
        |------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | If send() fails, PHPMailer throws an exception.
        |
        | That exception goes to the catch block below.
        |
        | The transaction is then rolled back.
        |
        | Therefore the booking WILL NOT remain cancelled
        | when the email fails.
        |
        |------------------------------------------------------------------
        */

        $mail->send();


        /*
        |------------------------------------------------------------------
        | EMAIL WAS SUCCESSFUL
        |------------------------------------------------------------------
        |
        | Only commit the cancellation AFTER the email has
        | successfully been accepted by the SMTP server.
        |
        */

        if ($transactionStarted) {

            if (!$conn->commit()) {

                throw new Exception(
                    "The cancellation could not be committed to the database."
                );
            }

            $transactionStarted = false;
        }


        /*
        |------------------------------------------------------------------
        | SUCCESS MESSAGE
        |------------------------------------------------------------------
        */

        $_SESSION['my_event_message'] =
            "Your booking has been cancelled successfully. " .
            "The administrator has been notified by email.";

        $_SESSION['my_event_message_type'] =
            "success";
    } catch (Exception $e) {

        /*
        |------------------------------------------------------------------
        | GET ERROR
        |------------------------------------------------------------------
        */

        $emailError =
            $e->getMessage();


        /*
        |------------------------------------------------------------------
        | ROLLBACK
        |------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | If anything fails, including the email,
        | the booking returns to its previous status.
        |
        */

        if ($transactionStarted) {

            try {

                $conn->rollback();
            } catch (Throwable $rollbackError) {

                error_log(
                    "Event Solutions by S.H.E. rollback failed: " .
                        $rollbackError->getMessage()
                );
            }

            $transactionStarted = false;
        }


        /*
        |------------------------------------------------------------------
        | LOG ERROR
        |------------------------------------------------------------------
        |
        | The actual PHPMailer error is written to the PHP error log.
        |
        | This prevents SMTP technical information from being shown
        | directly to the customer.
        |
        */

        error_log(
            "Event Solutions by S.H.E. cancellation failed: " .
                $emailError
        );


        /*
        |------------------------------------------------------------------
        | ERROR MESSAGE
        |------------------------------------------------------------------
        */

        $_SESSION['my_event_message'] =
            "The booking was NOT cancelled because " .
            "the administrator notification email could not be sent. " .
            "Please check your email configuration and try again.";

        $_SESSION['my_event_message_type'] =
            "error";
    }


    /*
    |----------------------------------------------------------------------
    | REDIRECT
    |----------------------------------------------------------------------
    */

    header("Location: my-event.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userStmt = $conn->prepare("
    SELECT
        id,
        fullname,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$userStmt) {
    die("Failed to prepare user query.");
}

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


if (!$currentUser) {

    session_destroy();

    header("Location: auth/login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$eventTypeFilter = isset($_GET['event_type'])
    ? trim($_GET['event_type'])
    : '';

$eventDateFilter = isset($_GET['event_date'])
    ? trim($_GET['event_date'])
    : '';

$sortOrder = isset($_GET['sort'])
    ? strtolower(trim($_GET['sort']))
    : 'asc';


if ($sortOrder !== 'desc') {
    $sortOrder = 'asc';
}


/*
|--------------------------------------------------------------------------
| EVENT TYPES
|--------------------------------------------------------------------------
*/

$eventTypes = [];

$typeStmt = $conn->prepare("
    SELECT
        id,
        name
    FROM event_types
    ORDER BY name ASC
");

if ($typeStmt) {

    $typeStmt->execute();

    $typeResult =
        $typeStmt->get_result();

    while ($typeRow =
        $typeResult->fetch_assoc()
    ) {

        $eventTypes[] =
            $typeRow;
    }

    $typeStmt->close();
}


/*
|--------------------------------------------------------------------------
| REGULAR EVENTS
|--------------------------------------------------------------------------
*/

$events = [];

$regularSql = "
    SELECT

        b.id,
        b.event_name,
        b.event_date,
        b.start_time,
        b.guest_count,
        b.phone,
        b.status,
        b.created_at,
        b.invitation_token,

        p.id AS package_id,
        p.package_name,
        p.image AS package_image,

        v.id AS venue_id,
        v.venue_name,

        et.id AS event_type_id,
        et.name AS event_type_name

    FROM bookings b

    LEFT JOIN event_packages p
        ON b.package_id = p.id

    LEFT JOIN venues v
        ON b.venue_id = v.id

    LEFT JOIN event_types et
        ON p.event_type_id = et.id

    WHERE b.user_id = ?
";


$regularParams =
    [$userId];

$regularTypes =
    "i";


/*
|--------------------------------------------------------------------------
| EVENT TYPE FILTER
|--------------------------------------------------------------------------
*/

if ($eventTypeFilter !== '') {

    /*
     * Wedding bookings are stored separately.
     */

    if (
        strtolower($eventTypeFilter) ===
        'wedding'
    ) {

        /*
         * Wedding events are loaded separately.
         */
    } else {

        $regularSql .=
            " AND et.id = ? ";

        $regularParams[] =
            (int) $eventTypeFilter;

        $regularTypes .=
            "i";
    }
}


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

if ($eventDateFilter !== '') {

    $regularSql .=
        " AND b.event_date = ? ";

    $regularParams[] =
        $eventDateFilter;

    $regularTypes .=
        "s";
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$regularSql .=
    "
        ORDER BY b.event_date " .
    (
        $sortOrder === 'desc'
        ? 'DESC'
        : 'ASC'
    ) .
    ",
    b.start_time " .
    (
        $sortOrder === 'desc'
        ? 'DESC'
        : 'ASC'
    );


/*
|--------------------------------------------------------------------------
| EXECUTE REGULAR EVENT QUERY
|--------------------------------------------------------------------------
*/

$eventStmt =
    $conn->prepare(
        $regularSql
    );

if ($eventStmt) {

    $eventStmt->bind_param(
        $regularTypes,
        ...$regularParams
    );

    $eventStmt->execute();

    $eventResult =
        $eventStmt->get_result();

    while (
        $row =
        $eventResult->fetch_assoc()
    ) {

        $row['booking_source'] =
            'regular';

        $events[] =
            $row;
    }

    $eventStmt->close();
}


/*
|--------------------------------------------------------------------------
| WEDDING EVENTS
|--------------------------------------------------------------------------
*/

$weddingEvents = [];

$showWedding = false;


if (
    $eventTypeFilter === '' ||
    strtolower($eventTypeFilter) ===
    'wedding'
) {

    $showWedding = true;
}


if ($showWedding) {

    $weddingSql = "
        SELECT

            bw.id,
            bw.bride_name,
            bw.groom_name,
            bw.event_date,
            bw.start_time,
            bw.guest_count,
            bw.church,
            bw.special_request,
            bw.package_id,
            bw.venue_id,
            bw.status,
            bw.created_at,
            bw.Update_at AS updated_at,
            bw.invitation_token,

            p.package_name,
            p.image AS package_image,

            v.venue_name

        FROM booking_wedding bw

        LEFT JOIN event_packages p
            ON bw.package_id = p.id

        LEFT JOIN venues v
            ON bw.venue_id = v.id

        WHERE bw.user_id = ?
    ";


    $weddingParams =
        [$userId];

    $weddingTypes =
        "i";


    /*
    |----------------------------------------------------------------------
    | WEDDING DATE FILTER
    |----------------------------------------------------------------------
    */

    if ($eventDateFilter !== '') {

        $weddingSql .=
            " AND bw.event_date = ? ";

        $weddingParams[] =
            $eventDateFilter;

        $weddingTypes .=
            "s";
    }


    /*
    |----------------------------------------------------------------------
    | WEDDING SORT
    |----------------------------------------------------------------------
    */

    $weddingSql .=
        "
            ORDER BY bw.event_date " .
        (
            $sortOrder === 'desc'
            ? 'DESC'
            : 'ASC'
        ) .
        ",
        bw.start_time " .
        (
            $sortOrder === 'desc'
            ? 'DESC'
            : 'ASC'
        );


    /*
    |----------------------------------------------------------------------
    | EXECUTE WEDDING QUERY
    |----------------------------------------------------------------------
    */

    $weddingStmt =
        $conn->prepare(
            $weddingSql
        );

    if ($weddingStmt) {

        $weddingStmt->bind_param(
            $weddingTypes,
            ...$weddingParams
        );

        $weddingStmt->execute();

        $weddingResult =
            $weddingStmt->get_result();

        while (
            $row =
            $weddingResult->fetch_assoc()
        ) {

            $row['booking_source'] =
                'wedding';


            /*
             * Generate event name.
             */

            $row['event_name'] =
                trim(
                    $row['bride_name'] .
                        ' & ' .
                        $row['groom_name'] .
                        ' Wedding'
                );


            $row['event_type_name'] =
                'Wedding';


            $weddingEvents[] =
                $row;
        }

        $weddingStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| DISPLAY EVENTS
|--------------------------------------------------------------------------
*/

$isWeddingFilter =
    strtolower($eventTypeFilter) ===
    'wedding';


if ($isWeddingFilter) {

    /*
     * Wedding filter selected.
     */

    $displayEvents =
        $weddingEvents;
} elseif ($eventTypeFilter === '') {

    /*
     * All Events selected.
     *
     * Combine regular and wedding bookings.
     */

    $displayEvents =
        array_merge(
            $events,
            $weddingEvents
        );


    /*
     * Sort combined events.
     */

    usort(
        $displayEvents,
        function (
            $a,
            $b
        ) use (
            $sortOrder
        ) {

            $dateA =
                ($a['event_date'] ?? '') .
                ' ' .
                ($a['start_time'] ?? '');

            $dateB =
                ($b['event_date'] ?? '') .
                ' ' .
                ($b['start_time'] ?? '');


            $timeA =
                strtotime($dateA);

            $timeB =
                strtotime($dateB);


            if ($timeA === $timeB) {
                return 0;
            }


            if (
                $sortOrder ===
                'desc'
            ) {

                return $timeB <=>
                    $timeA;
            }


            return $timeA <=>
                $timeB;
        }
    );
} else {

    /*
     * Normal event type selected.
     */

    $displayEvents =
        $events;
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Events";

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?>
        -
        Event Solutions by S.H.E.
    </title>


    <!-- =====================================================
         POPPINS
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <!-- =====================================================
         MATERIAL SYMBOLS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
        rel="stylesheet">


    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/header.css">


    <!-- =====================================================
         FOOTER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/footer.css">


    <!-- =====================================================
         MY EVENT CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/myevent.css">

</head>


<body>


    <?php include "includes/header.php"; ?>


    <main class="events-page">


        <!-- =====================================================
         HERO
    ====================================================== -->

        <section class="events-hero">

            <div class="events-hero-content">

                <span class="page-eyebrow">
                    MY EVENTS
                </span>


                <h1>
                    My Events
                </h1>


                <p>
                    Manage your bookings, view event details,
                    and create invitations for your confirmed events.
                </p>

            </div>

        </section>


        <!-- =====================================================
         EVENTS CONTAINER
    ====================================================== -->

        <section class="events-container">


            <!-- =================================================
             FLASH MESSAGE
        ================================================== -->

            <?php if (!empty($flashMessage)): ?>

                <div
                    class="booking-alert booking-alert-<?= htmlspecialchars($flashType) ?>"
                    role="alert">

                    <span class="material-symbols-outlined">

                        <?=
                        $flashType === 'success'
                            ? 'check_circle'
                            : (
                                $flashType === 'warning'
                                ? 'warning'
                                : 'error'
                            )
                        ?>

                    </span>


                    <span>

                        <?= htmlspecialchars($flashMessage) ?>

                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
             FILTER CARD
        ================================================== -->

            <div class="filter-card">

                <div class="filter-header">

                    <div>

                        <h2>
                            Event Bookings
                        </h2>

                        <p>
                            Filter and manage your events
                        </p>

                    </div>

                </div>


                <div class="filter-content">


                    <form
                        method="GET"
                        action="my-event.php"
                        class="filter-form"
                        id="eventFilterForm">


                        <!-- EVENT TYPE -->

                        <div class="filter-group">

                            <label for="event_type">
                                Event Type
                            </label>


                            <select
                                name="event_type"
                                id="event_type">

                                <option value="">
                                    All Events
                                </option>


                                <?php foreach (
                                    $eventTypes
                                    as $type
                                ): ?>

                                    <?php

                                    $isWeddingType =
                                        strtolower(
                                            trim(
                                                $type['name']
                                            )
                                        ) ===
                                        'wedding';

                                    $optionValue =
                                        $isWeddingType
                                        ? 'wedding'
                                        : (string) $type['id'];

                                    ?>


                                    <?php if (!$isWeddingType): ?>

                                        <option
                                            value="<?= htmlspecialchars($optionValue) ?>"
                                            <?= (
                                                $eventTypeFilter !== '' &&
                                                (string) $eventTypeFilter ===
                                                (string) $optionValue
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>>

                                            <?= htmlspecialchars(
                                                $type['name']
                                            ) ?>

                                        </option>

                                    <?php endif; ?>

                                <?php endforeach; ?>


                                <option
                                    value="wedding"
                                    <?= $isWeddingFilter
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    Wedding
                                </option>

                            </select>

                        </div>


                        <!-- EVENT DATE -->

                        <div class="filter-group">

                            <label for="event_date">
                                Event Date
                            </label>


                            <input
                                type="date"
                                name="event_date"
                                id="event_date"
                                value="<?= htmlspecialchars($eventDateFilter) ?>">

                        </div>


                        <!-- SORT -->

                        <div class="filter-group">

                            <label for="sort">
                                Sort
                            </label>


                            <select
                                name="sort"
                                id="sort">

                                <option
                                    value="asc"
                                    <?= $sortOrder === 'asc'
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    Oldest First
                                </option>


                                <option
                                    value="desc"
                                    <?= $sortOrder === 'desc'
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    Newest First
                                </option>

                            </select>

                        </div>


                        <!-- FILTER ACTIONS -->

                        <div class="filter-actions">


                            <button
                                type="submit"
                                class="filter-button">

                                <span class="material-symbols-outlined">
                                    filter_alt
                                </span>

                                Filter

                            </button>


                            <a
                                href="my-event.php"
                                class="clear-filter-button">
                                Clear
                            </a>

                        </div>

                    </form>


                    <!-- =================================================
                     CREATE INVITATION
                ================================================== -->

                    <div class="invitation-action">

                        <button
                            type="button"
                            id="createInvitationButton"
                            class="create-invitation-button"
                            disabled>

                            <span class="material-symbols-outlined">
                                mail
                            </span>


                            <span class="invitation-button-text">
                                Create Invitation
                            </span>

                        </button>

                    </div>

                </div>

            </div>


            <!-- =====================================================
             EVENT TABLE
        ====================================================== -->

            <div class="table-card">


                <div class="table-card-header">

                    <div>

                        <h2>

                            <?= $isWeddingFilter
                                ? 'Wedding Bookings'
                                : 'Event Bookings'
                            ?>

                        </h2>


                        <span class="event-count">

                            <?= count($displayEvents) ?>

                            <?= count($displayEvents) === 1
                                ? 'event'
                                : 'events'
                            ?>

                        </span>

                    </div>


                    <div class="selection-help">

                        <span class="material-symbols-outlined">
                            info
                        </span>

                        Select one confirmed event to create an invitation.

                    </div>

                </div>


                <?php if (empty($displayEvents)): ?>


                    <!-- =================================================
                     EMPTY STATE
                ================================================== -->

                    <div class="empty-state">


                        <div class="empty-icon">

                            <span class="material-symbols-outlined">
                                event_busy
                            </span>

                        </div>


                        <h3>
                            No events found
                        </h3>


                        <p>
                            There are no bookings matching your
                            current filter.
                        </p>


                        <a
                            href="events.php"
                            class="empty-button">
                            Browse Events
                        </a>

                    </div>


                <?php else: ?>


                    <div class="table-wrapper">


                        <table class="events-table">


                            <thead>

                                <tr>


                                    <!-- SELECT -->

                                    <th class="select-column">

                                        <span class="sr-only">
                                            Select
                                        </span>

                                    </th>


                                    <!-- EVENT -->

                                    <th>
                                        Event
                                    </th>


                                    <!-- DATE -->

                                    <th>
                                        Event Date
                                    </th>


                                    <!-- TIME -->

                                    <th>
                                        Start Time
                                    </th>


                                    <!-- GUESTS -->

                                    <th>
                                        Guests
                                    </th>


                                    <!-- VENUE -->

                                    <th>
                                        Venue
                                    </th>


                                    <!-- PACKAGE -->

                                    <th>
                                        Package
                                    </th>


                                    <!-- STATUS -->

                                    <th>
                                        Status
                                    </th>


                                    <!-- ACTION -->

                                    <th class="action-column">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $displayEvents
                                    as $event
                                ): ?>


                                    <?php

                                    /*
                            |------------------------------------------------
                            | EVENT DATA
                            |------------------------------------------------
                            */

                                    $status =
                                        strtolower(
                                            trim(
                                                $event['status']
                                                    ??
                                                    'pending'
                                            )
                                        );


                                    $isConfirmed =
                                        $status ===
                                        'confirmed';


                                    $canCancel =
                                        $status === 'pending' ||
                                        $status === 'confirmed';


                                    $bookingSource =
                                        $event['booking_source']
                                        ??
                                        'regular';


                                    $eventId =
                                        (int)
                                        $event['id'];


                                    $hasInvitation =
                                        !empty(trim(
                                            $event['invitation_token']
                                                ??
                                                ''
                                        ));


                                    /*
                            |------------------------------------------------
                            | INVITATION URL
                            |------------------------------------------------
                            */

                                    if (
                                        $bookingSource ===
                                        'wedding'
                                    ) {

                                        $invitationUrl =
                                            "invite/create_invitation.php" .
                                            "?booking_id=" .
                                            $eventId .
                                            "&booking_type=wedding";

                                        $guestListUrl =
                                            "guest_list.php" .
                                            "?booking_id=" .
                                            $eventId .
                                            "&booking_type=wedding";
                                    } else {

                                        $invitationUrl =
                                            "invite/create_invitation.php" .
                                            "?booking_id=" .
                                            $eventId .
                                            "&booking_type=regular";

                                        $guestListUrl =
                                            "guest_list.php" .
                                            "?booking_id=" .
                                            $eventId .
                                            "&booking_type=regular";
                                    }


                                    /*
                            |------------------------------------------------
                            | VALUES
                            |------------------------------------------------
                            */

                                    $eventName =
                                        $event['event_name']
                                        ??
                                        'Event';


                                    $eventDate =
                                        $event['event_date']
                                        ??
                                        '';


                                    $startTime =
                                        $event['start_time']
                                        ??
                                        '';


                                    $guestCount =
                                        $event['guest_count']
                                        ??
                                        0;


                                    $venueName =
                                        $event['venue_name']
                                        ??
                                        'Not specified';


                                    $packageName =
                                        $event['package_name']
                                        ??
                                        'Not specified';


                                    $packageImage =
                                        $event['package_image']
                                        ??
                                        '';


                                    $churchValue =
                                        $event['church']
                                        ??
                                        '';


                                    $specialRequestValue =
                                        $event['special_request']
                                        ??
                                        '';


                                    /*
                            |------------------------------------------------
                            | CAN SELECT FOR INVITATION
                            |------------------------------------------------
                            */

                                    $canSelectForInvitation =
                                        $isConfirmed &&
                                        !$hasInvitation;

                                    ?>


                                    <tr
                                        class="event-row <?= $isConfirmed
                                                                ? 'is-confirmed'
                                                                : ''
                                                            ?>"
                                        data-status="<?= htmlspecialchars($status) ?>"
                                        data-booking-source="<?= htmlspecialchars($bookingSource) ?>"
                                        data-event-id="<?= $eventId ?>">


                                        <!-- =====================================
                                     SELECT
                                ====================================== -->

                                        <td
                                            class="select-column"
                                            data-label="Select">


                                            <?php if (
                                                $canSelectForInvitation
                                            ): ?>


                                                <label
                                                    class="event-checkbox">

                                                    <input
                                                        type="checkbox"
                                                        class="event-select"
                                                        value="<?= $eventId ?>"
                                                        data-event-id="<?= $eventId ?>"
                                                        data-booking-type="<?= htmlspecialchars($bookingSource) ?>"
                                                        data-invitation-url="<?= htmlspecialchars($invitationUrl) ?>"
                                                        aria-label="Select <?= htmlspecialchars($eventName) ?>">


                                                    <span
                                                        class="checkbox-mark"></span>

                                                </label>


                                            <?php elseif (
                                                $hasInvitation
                                            ): ?>


                                                <span
                                                    class="selection-created">

                                                    <span class="material-symbols-outlined">
                                                        check_circle
                                                    </span>

                                                    Created

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="selection-disabled">
                                                    —
                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <!-- =====================================
                                     EVENT NAME
                                ====================================== -->

                                        <td
                                            class="event-name-cell"
                                            data-label="Event">

                                            <div
                                                class="event-name-wrapper">

                                                <span
                                                    class="event-name">

                                                    <?= htmlspecialchars(
                                                        $eventName
                                                    ) ?>

                                                </span>


                                                <span
                                                    class="event-type">

                                                    <?= htmlspecialchars(
                                                        $event['event_type_name']
                                                            ??
                                                            'Event'
                                                    ) ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- =====================================
                                     DATE
                                ====================================== -->

                                        <td
                                            data-label="Event Date">

                                            <span
                                                class="date-value">

                                                <?= !empty($eventDate)
                                                    ? htmlspecialchars(
                                                        date(
                                                            'F d, Y',
                                                            strtotime(
                                                                $eventDate
                                                            )
                                                        )
                                                    )
                                                    : '—'
                                                ?>

                                            </span>

                                        </td>


                                        <!-- =====================================
                                     START TIME
                                ====================================== -->

                                        <td
                                            data-label="Start Time">

                                            <?php if (
                                                !empty($startTime)
                                            ): ?>

                                                <span
                                                    class="time-value">

                                                    <?= htmlspecialchars(
                                                        date(
                                                            'h:i A',
                                                            strtotime(
                                                                $startTime
                                                            )
                                                        )
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="not-available">
                                                    —
                                                </span>


                                            <?php endif; ?>

                                        </td>


                                        <!-- =====================================
                                     GUESTS
                                ====================================== -->

                                        <td
                                            data-label="Guests">

                                            <span
                                                class="guest-value">

                                                <?= number_format(
                                                    (int)
                                                    $guestCount
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================================
                                     VENUE
                                ====================================== -->

                                        <td
                                            data-label="Venue">

                                            <span
                                                class="table-text">

                                                <?= htmlspecialchars(
                                                    $venueName
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================================
                                     PACKAGE
                                ====================================== -->

                                        <td
                                            data-label="Package">

                                            <span
                                                class="table-text">

                                                <?= htmlspecialchars(
                                                    $packageName
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================================
                                     STATUS
                                ====================================== -->

                                        <td
                                            data-label="Status">

                                            <span
                                                class="status-badge status-<?= htmlspecialchars($status) ?>">

                                                <span
                                                    class="status-dot"></span>


                                                <?= ucfirst(
                                                    htmlspecialchars(
                                                        $status
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================================
                                     ACTIONS
                                ====================================== -->

                                        <td
                                            class="action-column"
                                            data-label="Action">

                                            <div
                                                class="row-actions">


                                                <!-- =================================
                                             VIEW EVENT
                                        ================================== -->

                                                <button
                                                    type="button"
                                                    class="action-button view-button"

                                                    data-event-id="<?= $eventId ?>"

                                                    data-event-name="<?= htmlspecialchars(
                                                                            $eventName,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-event-type="<?= htmlspecialchars(
                                                                            $event['event_type_name']
                                                                                ??
                                                                                'Event',
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-event-date="<?= htmlspecialchars(
                                                                            $eventDate,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-start-time="<?= htmlspecialchars(
                                                                            $startTime,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-guest-count="<?= htmlspecialchars(
                                                                            (string)
                                                                            $guestCount,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-venue="<?= htmlspecialchars(
                                                                    $venueName,
                                                                    ENT_QUOTES
                                                                ) ?>"

                                                    data-package-name="<?= htmlspecialchars(
                                                                            $packageName,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-package-image="<?= htmlspecialchars(
                                                                            $packageImage,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-status="<?= htmlspecialchars(
                                                                        $status,
                                                                        ENT_QUOTES
                                                                    ) ?>"

                                                    data-booking-type="<?= htmlspecialchars(
                                                                            $bookingSource,
                                                                            ENT_QUOTES
                                                                        ) ?>"

                                                    data-church="<?= htmlspecialchars(
                                                                        $churchValue,
                                                                        ENT_QUOTES
                                                                    ) ?>"

                                                    data-special-request="<?= htmlspecialchars(
                                                                                $specialRequestValue,
                                                                                ENT_QUOTES
                                                                            ) ?>"

                                                    title="View event">


                                                    <span
                                                        class="material-symbols-outlined">
                                                        visibility
                                                    </span>


                                                    <span>
                                                        View
                                                    </span>


                                                </button>


                                                <!-- =================================
                                             CANCEL BOOKING
                                        ================================== -->

                                                <?php if (
                                                    $canCancel
                                                ): ?>


                                                    <form
                                                        method="POST"
                                                        action="my-event.php"
                                                        class="cancel-booking-form">


                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="cancel_booking">


                                                        <input
                                                            type="hidden"
                                                            name="booking_id"
                                                            value="<?= $eventId ?>">


                                                        <input
                                                            type="hidden"
                                                            name="booking_type"
                                                            value="<?= htmlspecialchars(
                                                                        $bookingSource
                                                                    ) ?>">


                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars(
                                                                        $csrfToken
                                                                    ) ?>">


                                                        <button
                                                            type="submit"
                                                            class="action-button cancel-button"
                                                            data-event-name="<?= htmlspecialchars(
                                                                                    $eventName,
                                                                                    ENT_QUOTES
                                                                                ) ?>"
                                                            title="Cancel booking">


                                                            <span
                                                                class="material-symbols-outlined">
                                                                cancel
                                                            </span>


                                                            <span>
                                                                Cancel
                                                            </span>


                                                        </button>


                                                    </form>


                                                <?php else: ?>


                                                    <button
                                                        type="button"
                                                        class="action-button cancel-button disabled"
                                                        disabled
                                                        title="This booking cannot be cancelled">


                                                        <span
                                                            class="material-symbols-outlined">
                                                            block
                                                        </span>


                                                        <span>
                                                            Cancel
                                                        </span>


                                                    </button>


                                                <?php endif; ?>


                                                <!-- =================================
                                             GUEST LIST
                                        ================================== -->

                                                <?php if (
                                                    $hasInvitation &&
                                                    $isConfirmed
                                                ): ?>


                                                    <a
                                                        href="<?= htmlspecialchars(
                                                                    $guestListUrl
                                                                ) ?>"
                                                        class="action-button guest-list-button"
                                                        title="View guest list">


                                                        <span
                                                            class="material-symbols-outlined">
                                                            groups
                                                        </span>


                                                        <span>
                                                            Guest List
                                                        </span>


                                                    </a>


                                                <?php endif; ?>


                                            </div>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


            </div>

        </section>

    </main>


    <!-- =========================================================
     EVENT VIEW MODAL
========================================================== -->

    <div
        class="event-modal"
        id="eventModal"
        aria-hidden="true">


        <div
            class="event-modal-overlay"
            id="eventModalOverlay"></div>


        <div
            class="event-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="eventModalTitle">


            <!-- =============================================
             MODAL HEADER
        ============================================== -->

            <div
                class="event-modal-header">


                <div>

                    <span
                        class="modal-eyebrow">
                        EVENT DETAILS
                    </span>


                    <h2
                        id="eventModalTitle">
                        Event Details
                    </h2>

                </div>


                <button
                    type="button"
                    class="modal-close"
                    id="closeEventModal"
                    aria-label="Close event details">

                    <span
                        class="material-symbols-outlined">
                        close
                    </span>

                </button>

            </div>


            <!-- =============================================
             MODAL BODY
        ============================================== -->

            <div
                class="event-modal-body">


                <!-- EVENT IMAGE -->

                <div
                    class="event-modal-image-wrapper"
                    id="eventModalImageWrapper">

                    <img
                        id="eventModalImage"
                        class="event-modal-image"
                        src=""
                        alt="Event package">

                    <div
                        class="event-modal-image-empty"
                        id="eventModalImageEmpty">

                        <span
                            class="material-symbols-outlined">
                            image
                        </span>

                        <span>
                            No event image available
                        </span>

                    </div>

                </div>


                <!-- EVENT DETAILS -->

                <div
                    class="event-modal-details">


                    <div
                        class="event-detail-main">

                        <span
                            class="modal-detail-label">
                            EVENT
                        </span>


                        <h3
                            id="modalEventName">
                            Event
                        </h3>


                        <span
                            class="modal-event-type"
                            id="modalEventType">
                            Event
                        </span>

                    </div>


                    <div
                        class="event-detail-grid">


                        <!-- DATE -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                calendar_month
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    EVENT DATE
                                </span>


                                <strong
                                    id="modalEventDate">
                                    —
                                </strong>

                            </div>

                        </div>


                        <!-- TIME -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                schedule
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    START TIME
                                </span>


                                <strong
                                    id="modalStartTime">
                                    —
                                </strong>

                            </div>

                        </div>


                        <!-- GUESTS -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                groups
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    GUESTS
                                </span>


                                <strong
                                    id="modalGuestCount">
                                    —
                                </strong>

                            </div>

                        </div>


                        <!-- VENUE -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                location_on
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    VENUE
                                </span>


                                <strong
                                    id="modalVenue">
                                    —
                                </strong>

                            </div>

                        </div>


                        <!-- PACKAGE -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                inventory_2
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    PACKAGE
                                </span>


                                <strong
                                    id="modalPackage">
                                    —
                                </strong>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div
                            class="event-detail-item">

                            <span
                                class="material-symbols-outlined">
                                verified
                            </span>


                            <div>

                                <span
                                    class="modal-detail-label">
                                    STATUS
                                </span>


                                <strong
                                    id="modalStatus">
                                    —
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- WEDDING DETAILS -->

                    <div
                        class="event-modal-extra"
                        id="modalWeddingDetails">


                        <div
                            class="event-detail-extra-item"
                            id="modalChurchWrapper">

                            <span
                                class="modal-detail-label">
                                CHURCH / CEREMONY LOCATION
                            </span>


                            <p
                                id="modalChurch">
                                —
                            </p>

                        </div>


                        <div
                            class="event-detail-extra-item"
                            id="modalSpecialRequestWrapper">

                            <span
                                class="modal-detail-label">
                                SPECIAL REQUEST
                            </span>


                            <p
                                id="modalSpecialRequest">
                                —
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
     IMAGE LIGHTBOX
========================================================== -->

    <div
        class="image-lightbox"
        id="imageLightbox"
        aria-hidden="true">


        <div
            class="image-lightbox-overlay"
            id="imageLightboxOverlay"></div>


        <div
            class="image-lightbox-content">


            <button
                type="button"
                class="modal-close image-lightbox-close"
                id="closeImageLightbox"
                aria-label="Close image">

                <span
                    class="material-symbols-outlined">
                    close
                </span>

            </button>


            <img
                id="lightboxImage"
                src=""
                alt="Event image">

        </div>

    </div>


    <!-- =========================================================
     CANCEL BOOKING MODAL
========================================================== -->

    <div
        class="cancel-modal"
        id="cancelModal"
        aria-hidden="true">


        <div
            class="cancel-modal-overlay"
            id="cancelModalOverlay"></div>


        <div
            class="cancel-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cancelModalTitle">


            <div
                class="cancel-modal-icon">

                <span
                    class="material-symbols-outlined">
                    warning
                </span>

            </div>


            <div
                class="cancel-modal-content">

                <span
                    class="modal-eyebrow">
                    CANCEL BOOKING
                </span>


                <h2
                    id="cancelModalTitle">
                    Cancel this booking?
                </h2>


                <p>

                    Are you sure you want to cancel

                    <strong
                        id="cancelEventName">
                        this event
                    </strong>?

                </p>


                <p
                    class="cancel-modal-notice">

                    The booking will only be cancelled after the
                    administrator has been successfully notified
                    by email.

                </p>

            </div>


            <div
                class="cancel-modal-actions"
                id="cancelModalActions">


                <button
                    type="button"
                    class="cancel-modal-button cancel-modal-back"
                    id="closeCancelModal">

                    Keep Booking

                </button>


                <button
                    type="button"
                    class="cancel-modal-button cancel-modal-confirm"
                    id="confirmCancelBooking">

                    <span
                        class="material-symbols-outlined">
                        cancel
                    </span>


                    Confirm Cancellation

                </button>

            </div>

        </div>

    </div>
    <!-- =========================================================
     CANCELLATION LOADING POPUP
========================================================== -->

    <div
        class="cancellation-loading"
        id="cancellationLoading"
        aria-hidden="true">

        <div
            class="cancellation-loading-overlay"></div>


        <div
            class="cancellation-loading-dialog"
            role="status"
            aria-live="polite">

            <div
                class="cancellation-loading-icon">

                <span
                    class="material-symbols-outlined">
                    mail
                </span>

                <span
                    class="loading-spinner"></span>

            </div>


            <div
                class="cancellation-loading-content">

                <span
                    class="modal-eyebrow">
                    PLEASE WAIT
                </span>


                <h2>
                    Processing Cancellation
                </h2>


                <p>
                    We are notifying the administrator by email.
                    Please do not close or refresh this page.
                </p>

            </div>

        </div>

    </div>


    <!-- =========================================================
     CREATE INVITATION MODAL
========================================================== -->

    <div
        class="invitation-modal"
        id="invitationModal"
        aria-hidden="true">


        <div
            class="invitation-modal-overlay"></div>


        <div
            class="invitation-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invitationModalTitle">


            <div
                class="invitation-modal-header">


                <div>

                    <span
                        class="modal-eyebrow">
                        INVITATION
                    </span>


                    <h2
                        id="invitationModalTitle">
                        Create Invitation
                    </h2>

                </div>


                <button
                    type="button"
                    class="modal-close"
                    id="closeInvitationModal"
                    aria-label="Close invitation">

                    <span
                        class="material-symbols-outlined">
                        close
                    </span>

                </button>

            </div>


            <div
                class="invitation-modal-body">


                <div
                    class="iframe-loading invitation-loading">

                    <span
                        class="loading-spinner"></span>


                    <span>
                        Loading invitation builder...
                    </span>

                </div>


                <iframe
                    id="invitationModalFrame"
                    title="Create Invitation"
                    src="about:blank"></iframe>

            </div>

        </div>

    </div>


    <?php include "includes/footer.php"; ?>


    <script>
        /*
    |----------------------------------------------------------
    | CSRF TOKEN
    |----------------------------------------------------------
    */

        window.MY_EVENT_CSRF =
            <?= json_encode($csrfToken) ?>;


        /*
        |----------------------------------------------------------
        | CURRENT USER
        |----------------------------------------------------------
        */

        window.MY_EVENT_USER = <?= json_encode([
                                    'id' =>
                                    (int) ($currentUser['id'] ?? 0),

                                    'fullname' =>
                                    $currentUser['fullname'] ?? '',

                                    'email' =>
                                    $currentUser['email'] ?? '',

                                    'role' =>
                                    $currentUser['role'] ?? ''
                                ]) ?>;
    </script>


    <script
        src="js/header.js"></script>


    <script
        src="js/myevent.js?v=4"></script>


</body>

</html>
