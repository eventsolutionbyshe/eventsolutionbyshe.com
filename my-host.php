<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| EMAIL CONFIGURATION
|--------------------------------------------------------------------------
*/

$emailConfig = [];

$emailConfigFile =
    __DIR__ . "/config/email.php";


if (file_exists($emailConfigFile)) {

    $emailConfig =
        require $emailConfigFile;

}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: auth/login.php");

    exit;
}


$userId =
    (int) $_SESSION['user_id'];


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


$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flashMessage =
    $_SESSION['my_host_message']
    ?? null;


$flashType =
    $_SESSION['my_host_message_type']
    ?? 'success';


unset($_SESSION['my_host_message']);

unset($_SESSION['my_host_message_type']);


/*
|--------------------------------------------------------------------------
| CANCEL HOST BOOKING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'cancel_host_booking'
) {


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $postedToken =
        isset($_POST['csrf_token'])
            ? (string) $_POST['csrf_token']
            : '';


    if (
        empty($postedToken) ||
        !hash_equals(
            $csrfToken,
            $postedToken
        )
    ) {

        $_SESSION['my_host_message'] =
            "Your session has expired. Please try again.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | BOOKING ID
    |--------------------------------------------------------------------------
    */

    $bookingId =
        isset($_POST['booking_id'])
            ? (int) $_POST['booking_id']
            : 0;


    if ($bookingId <= 0) {

        $_SESSION['my_host_message'] =
            "Invalid host booking.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING
    |--------------------------------------------------------------------------
    */

    $bookingStmt =
        $conn->prepare("
            SELECT

                hb.id,
                hb.event_name,
                hb.event_date,
                hb.start_time,
                hb.status,
                hb.host_id,
                hb.host_package_id,
                hb.user_id,
                hb.address,
                hb.special_requests,
                hb.created_at,
                hb.updated_at,

                h.host_name,
                h.host_type,
                h.email AS host_email,
                h.phone AS host_phone,

                hp.package_name,
                hp.price AS package_price

            FROM host_bookings hb

            LEFT JOIN hosts h
                ON hb.host_id = h.id

            LEFT JOIN host_packages hp
                ON hb.host_package_id = hp.id

            WHERE hb.id = ?
              AND hb.user_id = ?

            LIMIT 1
        ");


    if (!$bookingStmt) {

        $_SESSION['my_host_message'] =
            "Unable to process the host booking cancellation.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    $bookingStmt->bind_param(
        "ii",
        $bookingId,
        $userId
    );


    $bookingStmt->execute();


    $bookingResult =
        $bookingStmt->get_result();


    $bookingData =
        $bookingResult->fetch_assoc();


    $bookingStmt->close();


    if (!$bookingData) {

        $_SESSION['my_host_message'] =
            "Host booking not found or you do not have permission to cancel it.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CURRENT STATUS
    |--------------------------------------------------------------------------
    */

    $currentStatus =
        strtolower(
            trim(
                $bookingData['status']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | CONFIRMED
    |--------------------------------------------------------------------------
    */

    if ($currentStatus === 'confirmed') {

        $_SESSION['my_host_message'] =
            "A confirmed host booking cannot be cancelled.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETED
    |--------------------------------------------------------------------------
    */

    if ($currentStatus === 'completed') {

        $_SESSION['my_host_message'] =
            "A completed host booking cannot be cancelled.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ALREADY CANCELLED
    |--------------------------------------------------------------------------
    */

    if ($currentStatus === 'cancelled') {

        $_SESSION['my_host_message'] =
            "This host booking has already been cancelled.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ONLY PENDING CAN BE CANCELLED
    |--------------------------------------------------------------------------
    */

    if ($currentStatus !== 'pending') {

        $_SESSION['my_host_message'] =
            "This host booking cannot be cancelled at this time.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING
    |--------------------------------------------------------------------------
    |
    | The status is checked again here.
    |
    | This protects against two browser tabs or two requests
    | attempting to cancel the same booking.
    |
    */

    $updateStmt =
        $conn->prepare("
            UPDATE host_bookings

            SET
                status = 'cancelled',
                updated_at = NOW()

            WHERE id = ?
              AND user_id = ?
              AND LOWER(status) = 'pending'
        ");


    if (!$updateStmt) {

        $_SESSION['my_host_message'] =
            "Unable to prepare the host booking cancellation.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
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

        $_SESSION['my_host_message'] =
            "The host booking could not be cancelled. It may have already been updated.";

        $_SESSION['my_host_message_type'] =
            "error";

        header("Location: my-host.php");

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER INFORMATION
    |--------------------------------------------------------------------------
    */

    $customerName =
        '';

    $customerEmail =
        '';


    $customerStmt =
        $conn->prepare("
            SELECT
                fullname,
                email

            FROM users

            WHERE id = ?

            LIMIT 1
        ");


    if ($customerStmt) {

        $customerStmt->bind_param(
            "i",
            $userId
        );


        $customerStmt->execute();


        $customerResult =
            $customerStmt->get_result();


        $customerData =
            $customerResult->fetch_assoc();


        $customerStmt->close();


        if ($customerData) {

            $customerName =
                $customerData['fullname']
                ?? '';

            $customerEmail =
                $customerData['email']
                ?? '';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | EMAIL VALUES
    |--------------------------------------------------------------------------
    */

    $emailHostName =
        $bookingData['host_name']
        ?? 'Host';


    $emailHostType =
        $bookingData['host_type']
        ?? 'Host';


    $emailEventName =
        $bookingData['event_name']
        ?? 'Event';


    $emailEventDate =
        $bookingData['event_date']
        ?? '';


    $emailStartTime =
        $bookingData['start_time']
        ?? '';


    $emailPackageName =
        $bookingData['package_name']
        ?? 'Host Package';


    $emailPackagePrice =
        $bookingData['package_price']
        ?? 0;


    $emailAddress =
        $bookingData['address']
        ?? '';


    $emailSpecialRequests =
        $bookingData['special_requests']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | SEND CANCELLATION EMAIL TO ADMIN
    |--------------------------------------------------------------------------
    */

    try {

        if (
            !empty($emailConfig) &&
            !empty($emailConfig['admin_email'])
        ) {


            $adminMail =
                new PHPMailer(true);


            /*
            |--------------------------------------------------------------------------
            | SMTP
            |--------------------------------------------------------------------------
            */

            $adminMail->isSMTP();


            $adminMail->Host =
                $emailConfig['smtp_host']
                ?? '';


            $adminMail->SMTPAuth =
                true;


            $adminMail->Username =
                $emailConfig['smtp_username']
                ?? '';


            $adminMail->Password =
                $emailConfig['smtp_password']
                ?? '';


            $adminMail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;


            $adminMail->Port =
                $emailConfig['smtp_port']
                ?? 587;


            $adminMail->CharSet =
                'UTF-8';


            /*
            |--------------------------------------------------------------------------
            | FROM
            |--------------------------------------------------------------------------
            */

            $adminMail->setFrom(
                $emailConfig['from_email']
                ?? $emailConfig['smtp_username']
                ?? '',
                $emailConfig['from_name']
                ?? 'Event Solutions by S.H.E.'
            );


            /*
            |--------------------------------------------------------------------------
            | ADMIN
            |--------------------------------------------------------------------------
            */

            $adminMail->addAddress(
                $emailConfig['admin_email'],
                $emailConfig['admin_name']
                ?? 'Administrator'
            );


            /*
            |--------------------------------------------------------------------------
            | REPLY TO CUSTOMER
            |--------------------------------------------------------------------------
            */

            if (
                filter_var(
                    $customerEmail,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $adminMail->addReplyTo(
                    $customerEmail,
                    $customerName !== ''
                        ? $customerName
                        : 'Customer'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | HTML EMAIL
            |--------------------------------------------------------------------------
            */

            $adminMail->isHTML(true);


            /*
            |--------------------------------------------------------------------------
            | SAFE EMAIL VALUES
            |--------------------------------------------------------------------------
            */

            $safeBookingId =
                htmlspecialchars(
                    (string) $bookingId,
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeCustomerName =
                htmlspecialchars(
                    $customerName !== ''
                        ? $customerName
                        : 'Customer',
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeCustomerEmail =
                htmlspecialchars(
                    $customerEmail !== ''
                        ? $customerEmail
                        : 'Not provided',
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeHostName =
                htmlspecialchars(
                    $emailHostName,
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeHostType =
                htmlspecialchars(
                    $emailHostType,
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeEventName =
                htmlspecialchars(
                    $emailEventName,
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeEventDate =
                htmlspecialchars(
                    !empty($emailEventDate)
                        ? date(
                            'F d, Y',
                            strtotime($emailEventDate)
                        )
                        : 'Not provided',
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeStartTime =
                htmlspecialchars(
                    !empty($emailStartTime)
                        ? date(
                            'h:i A',
                            strtotime($emailStartTime)
                        )
                        : 'Not provided',
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safePackageName =
                htmlspecialchars(
                    $emailPackageName,
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safePrice =
                htmlspecialchars(
                    '₱' .
                    number_format(
                        (float) $emailPackagePrice,
                        2
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeAddress =
                htmlspecialchars(
                    $emailAddress !== ''
                        ? $emailAddress
                        : 'Not provided',
                    ENT_QUOTES,
                    'UTF-8'
                );


            $safeSpecialRequests =
                nl2br(
                    htmlspecialchars(
                        $emailSpecialRequests !== ''
                            ? $emailSpecialRequests
                            : 'None',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | SUBJECT
            |--------------------------------------------------------------------------
            */

            $adminMail->Subject =
                "Host Booking Cancelled #"
                . $bookingId;


            /*
            |--------------------------------------------------------------------------
            | EMAIL BODY
            |--------------------------------------------------------------------------
            */

            $adminMail->Body = "

                <div style=\"
                    margin:0;
                    padding:40px 20px;
                    background:#f7f4ef;
                    font-family:Arial,Helvetica,sans-serif;
                    color:#242424;
                \">

                    <div style=\"
                        max-width:680px;
                        margin:0 auto;
                        background:#ffffff;
                        border:1px solid #e6dfd8;
                    \">

                        <div style=\"
                            padding:30px 34px;
                            background:#181818;
                            color:#ffffff;
                            text-align:center;
                        \">

                            <div style=\"
                                font-size:11px;
                                letter-spacing:3px;
                                color:#d8bd7a;
                                font-weight:700;
                                text-transform:uppercase;
                                margin-bottom:10px;
                            \">
                                EVENT SOLUTIONS BY S.H.E.
                            </div>

                            <div style=\"
                                font-size:26px;
                                font-weight:600;
                            \">
                                Host Booking Cancelled
                            </div>

                        </div>


                        <div style=\"
                            padding:34px;
                        \">

                            <div style=\"
                                padding:16px 18px;
                                margin-bottom:26px;
                                background:#fdf2ef;
                                border-left:4px solid #a14d48;
                                color:#7f3b37;
                            \">

                                <strong>
                                    A customer has cancelled a host booking.
                                </strong>

                                <div style=\"
                                    margin-top:5px;
                                    font-size:13px;
                                \">
                                    Booking #{$safeBookingId}
                                </div>

                            </div>


                            <h2 style=\"
                                margin:0 0 20px;
                                font-size:19px;
                                color:#242424;
                            \">
                                Customer Information
                            </h2>


                            <table
                                width=\"100%\"
                                cellpadding=\"0\"
                                cellspacing=\"0\"
                                style=\"
                                    border-collapse:collapse;
                                    margin-bottom:28px;
                                \"
                            >

                                <tr>

                                    <td style=\"
                                        padding:10px 0;
                                        width:38%;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Customer
                                    </td>

                                    <td style=\"
                                        padding:10px 0;
                                        font-size:14px;
                                        font-weight:600;
                                    \">
                                        {$safeCustomerName}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:10px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Email
                                    </td>

                                    <td style=\"
                                        padding:10px 0;
                                        font-size:14px;
                                    \">
                                        {$safeCustomerEmail}
                                    </td>

                                </tr>

                            </table>


                            <h2 style=\"
                                margin:0 0 20px;
                                font-size:19px;
                                color:#242424;
                            \">
                                Host Booking Details
                            </h2>


                            <table
                                width=\"100%\"
                                cellpadding=\"0\"
                                cellspacing=\"0\"
                                style=\"
                                    border-collapse:collapse;
                                \"
                            >

                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        width:38%;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Host
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                        font-weight:600;
                                    \">
                                        {$safeHostName}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Category
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safeHostType}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Event
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safeEventName}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Event Date
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safeEventDate}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Start Time
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safeStartTime}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Package
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safePackageName}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Price
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                        font-weight:600;
                                        color:#94712f;
                                    \">
                                        {$safePrice}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                    \">
                                        Status
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                        font-weight:700;
                                        color:#a14d48;
                                    \">
                                        Cancelled
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                        vertical-align:top;
                                    \">
                                        Address
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                    \">
                                        {$safeAddress}
                                    </td>

                                </tr>


                                <tr>

                                    <td style=\"
                                        padding:11px 0;
                                        color:#888888;
                                        font-size:12px;
                                        text-transform:uppercase;
                                        letter-spacing:1px;
                                        vertical-align:top;
                                    \">
                                        Special Requests
                                    </td>

                                    <td style=\"
                                        padding:11px 0;
                                        font-size:14px;
                                        line-height:1.6;
                                    \">
                                        {$safeSpecialRequests}
                                    </td>

                                </tr>

                            </table>


                            <div style=\"
                                margin-top:30px;
                                padding-top:22px;
                                border-top:1px solid #e6dfd8;
                                color:#888888;
                                font-size:12px;
                                line-height:1.7;
                            \">

                                This notification was automatically generated
                                by Event Solutions by S.H.E. after the customer
                                cancelled the host booking.

                            </div>

                        </div>

                    </div>

                </div>
            ";


            /*
            |--------------------------------------------------------------------------
            | PLAIN TEXT
            |--------------------------------------------------------------------------
            */

            $adminMail->AltBody =
                "Host Booking Cancelled\n\n"
                . "Booking ID: #" . $bookingId . "\n"
                . "Customer: " . $customerName . "\n"
                . "Customer Email: " . $customerEmail . "\n"
                . "Host: " . $emailHostName . "\n"
                . "Category: " . $emailHostType . "\n"
                . "Event: " . $emailEventName . "\n"
                . "Event Date: " . $emailEventDate . "\n"
                . "Start Time: " . $emailStartTime . "\n"
                . "Package: " . $emailPackageName . "\n"
                . "Price: ₱"
                . number_format(
                    (float) $emailPackagePrice,
                    2
                )
                . "\n"
                . "Status: Cancelled\n"
                . "Address: "
                . (
                    $emailAddress !== ''
                        ? $emailAddress
                        : 'Not provided'
                )
                . "\n"
                . "Special Requests: "
                . (
                    $emailSpecialRequests !== ''
                        ? $emailSpecialRequests
                        : 'None'
                );


            /*
            |--------------------------------------------------------------------------
            | SEND
            |--------------------------------------------------------------------------
            */

            $adminMail->send();

        }

    } catch (Exception $mailException) {

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | The booking is already cancelled successfully.
        |
        | Email failure must NOT undo the database cancellation.
        |
        */

        error_log(
            "Host cancellation email error: "
            . $mailException->getMessage()
        );

    } catch (\Throwable $mailException) {

        error_log(
            "Host cancellation email error: "
            . $mailException->getMessage()
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    $_SESSION['my_host_message'] =
        "Your host booking has been cancelled successfully.";

    $_SESSION['my_host_message_type'] =
        "success";


    header("Location: my-host.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userStmt =
    $conn->prepare("
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

$searchFilter =
    isset($_GET['search'])
        ? trim($_GET['search'])
        : '';


$categoryFilter =
    isset($_GET['category'])
        ? trim($_GET['category'])
        : '';


$statusFilter =
    isset($_GET['status'])
        ? strtolower(trim($_GET['status']))
        : '';


$sortOrder =
    isset($_GET['sort'])
        ? strtolower(trim($_GET['sort']))
        : 'asc';


if (
    $sortOrder !== 'desc' &&
    $sortOrder !== 'asc'
) {

    $sortOrder = 'asc';

}


/*
|--------------------------------------------------------------------------
| HOST CATEGORIES
|--------------------------------------------------------------------------
*/

$hostCategories = [];


$categoryStmt =
    $conn->prepare("
        SELECT DISTINCT
            host_type

        FROM hosts

        WHERE host_type IS NOT NULL
          AND TRIM(host_type) <> ''

        ORDER BY host_type ASC
    ");


if ($categoryStmt) {

    $categoryStmt->execute();


    $categoryResult =
        $categoryStmt->get_result();


    while (
        $categoryRow =
        $categoryResult->fetch_assoc()
    ) {

        $hostCategories[] =
            $categoryRow['host_type'];

    }


    $categoryStmt->close();

}


/*
|--------------------------------------------------------------------------
| HOST BOOKINGS
|--------------------------------------------------------------------------
*/

$hostBookings = [];


$hostSql = "
    SELECT

        hb.id,
        hb.address,
        hb.created_at,
        hb.event_date,
        hb.event_id,
        hb.event_name,
        hb.host_id,
        hb.host_package_id,
        hb.special_requests,
        hb.start_time,
        hb.status,
        hb.updated_at,
        hb.user_id,

        h.host_name,
        h.host_type,
        h.description AS host_description,
        h.email AS host_email,
        h.phone AS host_phone,
        h.image AS host_image,

        hp.package_name AS host_package_name,
        hp.inclusions AS host_inclusions,
        hp.price AS package_price

    FROM host_bookings hb

    LEFT JOIN hosts h
        ON hb.host_id = h.id

    LEFT JOIN host_packages hp
        ON hb.host_package_id = hp.id

    WHERE hb.user_id = ?
";


$hostParams =
    [$userId];


$hostTypes =
    "i";


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($searchFilter !== '') {

    $hostSql .= "
        AND (
            h.host_name LIKE ?
            OR h.host_type LIKE ?
            OR hb.event_name LIKE ?
            OR hp.package_name LIKE ?
        )
    ";


    $searchValue =
        '%' . $searchFilter . '%';


    $hostParams[] =
        $searchValue;

    $hostParams[] =
        $searchValue;

    $hostParams[] =
        $searchValue;

    $hostParams[] =
        $searchValue;


    $hostTypes .=
        "ssss";
}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if ($categoryFilter !== '') {

    $hostSql .=
        " AND h.host_type = ? ";


    $hostParams[] =
        $categoryFilter;


    $hostTypes .=
        "s";
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'pending',
    'confirmed',
    'completed',
    'cancelled'
];


if (
    $statusFilter !== '' &&
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $hostSql .=
        " AND LOWER(hb.status) = ? ";


    $hostParams[] =
        $statusFilter;


    $hostTypes .=
        "s";
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$hostSql .=
    "
        ORDER BY
            hb.event_date "
            .
            (
                $sortOrder === 'desc'
                    ? 'DESC'
                    : 'ASC'
            )
            . ",
            hb.start_time "
            .
            (
                $sortOrder === 'desc'
                    ? 'DESC'
                    : 'ASC'
            )
            . ",
            hb.id "
            .
            (
                $sortOrder === 'desc'
                    ? 'DESC'
                    : 'ASC'
            );


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$hostStmt =
    $conn->prepare(
        $hostSql
    );


if ($hostStmt) {

    $hostStmt->bind_param(
        $hostTypes,
        ...$hostParams
    );


    $hostStmt->execute();


    $hostResult =
        $hostStmt->get_result();


    while (
        $row =
        $hostResult->fetch_assoc()
    ) {

        $hostBookings[] =
            $row;

    }


    $hostStmt->close();

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Hosts";

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

        <?= htmlspecialchars($pageTitle) ?>

        -

        Event Solutions by S.H.E.

    </title>


    <!-- =====================================================
         POPPINS
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         MATERIAL SYMBOLS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,0"
        rel="stylesheet"
    >


    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/header.css"
    >


    <!-- =====================================================
         FOOTER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/footer.css"
    >


    <!-- =====================================================
         MY HOST CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/myhost.css"
    >

</head>


<body>


<?php include "includes/header.php"; ?>


<main class="hosts-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hosts-hero">

        <div class="hosts-hero-content">

            <span class="page-eyebrow">
                MY HOSTS
            </span>


            <h1>
                My Host Bookings
            </h1>


            <p>
                Manage your host bookings, view host details,
                and keep track of your event entertainment.
            </p>

        </div>

    </section>


    <!-- =====================================================
         HOSTS CONTAINER
    ====================================================== -->

    <section class="hosts-container">


        <!-- =================================================
             FLASH MESSAGE
        ================================================== -->

        <?php if (!empty($flashMessage)): ?>

            <div
                class="host-alert host-alert-<?= htmlspecialchars($flashType) ?>"
                role="alert"
            >

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

        <div class="host-filter-card">


            <div class="host-filter-header">

                <div>

                    <span class="filter-eyebrow">
                        BOOKING MANAGEMENT
                    </span>


                    <h2>
                        My Host Bookings
                    </h2>


                    <p>
                        Search and manage your booked hosts.
                    </p>

                </div>


                <div class="host-total-badge">

                    <span class="material-symbols-outlined">
                        mic_external_on
                    </span>


                    <span>

                        <?= count($hostBookings) ?>

                        <?= count($hostBookings) === 1
                            ? 'Booking'
                            : 'Bookings'
                        ?>

                    </span>

                </div>

            </div>


            <div class="host-filter-content">


                <form
                    method="GET"
                    action="my-host.php"
                    class="host-filter-form"
                    id="hostFilterForm"
                >


                    <!-- SEARCH -->

                    <div class="host-filter-group host-search-group">

                        <label for="hostSearch">
                            Search
                        </label>


                        <div class="host-search-wrapper">

                            <span class="material-symbols-outlined">
                                search
                            </span>


                            <input
                                type="search"
                                name="search"
                                id="hostSearch"
                                value="<?= htmlspecialchars($searchFilter) ?>"
                                placeholder="Search host or event..."
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <!-- CATEGORY -->

                    <div class="host-filter-group">

                        <label for="hostCategory">
                            Category
                        </label>


                        <select
                            name="category"
                            id="hostCategory"
                        >

                            <option value="">
                                All Categories
                            </option>


                            <?php foreach (
                                $hostCategories
                                as $category
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars($category) ?>"
                                    <?= $categoryFilter === $category
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars($category) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="host-filter-group">

                        <label for="hostStatus">
                            Status
                        </label>


                        <select
                            name="status"
                            id="hostStatus"
                        >

                            <option value="">
                                All Status
                            </option>


                            <option
                                value="pending"
                                <?= $statusFilter === 'pending'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pending
                            </option>


                            <option
                                value="confirmed"
                                <?= $statusFilter === 'confirmed'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Confirmed
                            </option>


                            <option
                                value="completed"
                                <?= $statusFilter === 'completed'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Completed
                            </option>


                            <option
                                value="cancelled"
                                <?= $statusFilter === 'cancelled'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <!-- SORT -->

                    <div class="host-filter-group">

                        <label for="hostSort">
                            Sort
                        </label>


                        <select
                            name="sort"
                            id="hostSort"
                        >

                            <option
                                value="asc"
                                <?= $sortOrder === 'asc'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Oldest First
                            </option>


                            <option
                                value="desc"
                                <?= $sortOrder === 'desc'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Newest First
                            </option>

                        </select>

                    </div>


                    <!-- ACTIONS -->

                    <div class="host-filter-actions">


                        <button
                            type="submit"
                            class="host-filter-button"
                        >

                            <span class="material-symbols-outlined">
                                filter_alt
                            </span>


                            Filter

                        </button>


                        <a
                            href="my-host.php"
                            class="host-clear-button"
                        >
                            Clear
                        </a>

                    </div>

                </form>

            </div>

        </div>


        <!-- =====================================================
             HOST BOOKING TABLE
        ====================================================== -->

        <div class="host-table-card">


            <div class="host-table-header">

                <div>

                    <span class="table-eyebrow">
                        HOST BOOKINGS
                    </span>


                    <h2>
                        Booked Hosts
                    </h2>


                    <span class="host-event-count">

                        <?= count($hostBookings) ?>

                        <?= count($hostBookings) === 1
                            ? 'booking'
                            : 'bookings'
                        ?>

                    </span>

                </div>


                <div class="host-table-help">

                    <span class="material-symbols-outlined">
                        info
                    </span>

                    View your host information or cancel an eligible booking.

                </div>

            </div>


            <?php if (empty($hostBookings)): ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================== -->

                <div class="host-empty-state">


                    <div class="host-empty-icon">

                        <span class="material-symbols-outlined">
                            mic_off
                        </span>

                    </div>


                    <h3>
                        No host bookings found
                    </h3>


                    <p>
                        There are no host bookings matching
                        your current filters.
                    </p>


                    <a
                        href="host.php"
                        class="host-empty-button"
                    >

                        Browse Hosts

                    </a>

                </div>


            <?php else: ?>


                <div class="host-table-wrapper">


                    <table class="hosts-table">


                        <thead>

                            <tr>

                                <th>
                                    Host
                                </th>


                                <th>
                                    Category
                                </th>


                                <th>
                                    Event
                                </th>


                                <th>
                                    Event Date
                                </th>


                                <th>
                                    Start Time
                                </th>


                                <th>
                                    Package
                                </th>


                                <th>
                                    Price
                                </th>


                                <th>
                                    Status
                                </th>


                                <th class="host-action-column">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $hostBookings
                            as $booking
                        ): ?>


                            <?php

                            /*
                            |------------------------------------------------
                            | STATUS
                            |------------------------------------------------
                            */

                            $status =
                                strtolower(
                                    trim(
                                        $booking['status']
                                        ??
                                        'pending'
                                    )
                                );


                            /*
                            |------------------------------------------------
                            | CAN CANCEL
                            |------------------------------------------------
                            |
                            | ONLY PENDING BOOKINGS CAN BE CANCELLED.
                            |
                            | CONFIRMED = DISABLED
                            | COMPLETED = DISABLED
                            | CANCELLED = DISABLED
                            |
                            */

                            $canCancel =
                                $status === 'pending';


                            /*
                            |------------------------------------------------
                            | VALUES
                            |------------------------------------------------
                            */

                            $bookingId =
                                (int)
                                $booking['id'];


                            $hostName =
                                $booking['host_name']
                                ??
                                'Host';


                            $hostType =
                                $booking['host_type']
                                ??
                                'Host';


                            $eventName =
                                $booking['event_name']
                                ??
                                'Event';


                            $eventDate =
                                $booking['event_date']
                                ??
                                '';


                            $startTime =
                                $booking['start_time']
                                ??
                                '';


                            $packageName =
                                $booking['host_package_name']
                                ??
                                'Not specified';


                            $price =
                                $booking['package_price']
                                ??
                                0;


                            $hostImage =
                                $booking['host_image']
                                ??
                                '';


                            $hostDescription =
                                $booking['host_description']
                                ??
                                '';


                            $hostEmail =
                                $booking['host_email']
                                ??
                                '';


                            $hostPhone =
                                $booking['host_phone']
                                ??
                                '';


                            $address =
                                $booking['address']
                                ??
                                '';


                            /*
                            |------------------------------------------------
                            | SPECIAL REQUEST
                            |------------------------------------------------
                            */

                            $specialRequest =
                                $booking['special_requests']
                                ??
                                '';


                            $hostInclusions =
                                $booking['host_inclusions']
                                ??
                                '';

                            ?>


                            <tr
                                class="host-booking-row"
                                data-status="<?= htmlspecialchars($status) ?>"
                                data-category="<?= htmlspecialchars($hostType) ?>"
                                data-booking-id="<?= $bookingId ?>"
                            >


                                <!-- =====================================
                                     HOST
                                ====================================== -->

                                <td
                                    class="host-name-cell"
                                    data-label="Host"
                                >

                                    <div class="host-name-wrapper">


                                        <?php if (!empty($hostImage)): ?>

                                            <div class="host-table-image">

                                                <img
                                                    src="<?= htmlspecialchars($hostImage) ?>"
                                                    alt="<?= htmlspecialchars($hostName) ?>"
                                                    loading="lazy"
                                                >

                                            </div>

                                        <?php else: ?>

                                            <div class="host-table-image host-table-image-empty">

                                                <span class="material-symbols-outlined">
                                                    mic_external_on
                                                </span>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <span class="host-name">
                                                <?= htmlspecialchars($hostName) ?>
                                            </span>


                                            <span class="host-type-small">
                                                <?= htmlspecialchars($hostType) ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- =====================================
                                     CATEGORY
                                ====================================== -->

                                <td
                                    data-label="Category"
                                >

                                    <span class="host-category-badge">

                                        <?= htmlspecialchars($hostType) ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     EVENT
                                ====================================== -->

                                <td
                                    class="host-event-cell"
                                    data-label="Event"
                                >

                                    <span class="host-event-name">

                                        <?= htmlspecialchars($eventName) ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     DATE
                                ====================================== -->

                                <td
                                    data-label="Event Date"
                                >

                                    <span class="host-date-value">

                                        <?= !empty($eventDate)
                                            ? htmlspecialchars(
                                                date(
                                                    'F d, Y',
                                                    strtotime($eventDate)
                                                )
                                            )
                                            : '—'
                                        ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     TIME
                                ====================================== -->

                                <td
                                    data-label="Start Time"
                                >

                                    <?php if (!empty($startTime)): ?>

                                        <span class="host-time-value">

                                            <?= htmlspecialchars(
                                                date(
                                                    'h:i A',
                                                    strtotime($startTime)
                                                )
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="host-not-available">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =====================================
                                     PACKAGE
                                ====================================== -->

                                <td
                                    data-label="Package"
                                >

                                    <span class="host-package-value">

                                        <?= htmlspecialchars($packageName) ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     PRICE
                                ====================================== -->

                                <td
                                    data-label="Price"
                                >

                                    <span class="host-price-value">

                                        ₱<?= number_format(
                                            (float) $price,
                                            2
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     STATUS
                                ====================================== -->

                                <td
                                    data-label="Status"
                                >

                                    <span
                                        class="host-status-badge host-status-<?= htmlspecialchars($status) ?>"
                                    >

                                        <span class="host-status-dot"></span>


                                        <?= ucfirst(
                                            htmlspecialchars($status)
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =====================================
                                     ACTION
                                ====================================== -->

                                <td
                                    class="host-action-column"
                                    data-label="Action"
                                >

                                    <div class="host-row-actions">


                                        <!-- =================================
                                             VIEW
                                        ================================== -->

                                        <button
                                            type="button"
                                            class="host-action-button host-view-button"

                                            data-booking-id="<?= $bookingId ?>"

                                            data-host-name="<?= htmlspecialchars(
                                                $hostName,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-host-type="<?= htmlspecialchars(
                                                $hostType,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-host-image="<?= htmlspecialchars(
                                                $hostImage,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-host-description="<?= htmlspecialchars(
                                                $hostDescription,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-host-email="<?= htmlspecialchars(
                                                $hostEmail,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-host-phone="<?= htmlspecialchars(
                                                $hostPhone,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-event-name="<?= htmlspecialchars(
                                                $eventName,
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

                                            data-package="<?= htmlspecialchars(
                                                $packageName,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-price="<?= htmlspecialchars(
                                                number_format(
                                                    (float) $price,
                                                    2
                                                ),
                                                ENT_QUOTES
                                            ) ?>"

                                            data-address="<?= htmlspecialchars(
                                                $address,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-special-request="<?= htmlspecialchars(
                                                $specialRequest,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-inclusions="<?= htmlspecialchars(
                                                $hostInclusions,
                                                ENT_QUOTES
                                            ) ?>"

                                            data-status="<?= htmlspecialchars(
                                                $status,
                                                ENT_QUOTES
                                            ) ?>"

                                            title="View host booking"
                                        >

                                            <span class="material-symbols-outlined">
                                                visibility
                                            </span>


                                            <span>
                                                View
                                            </span>

                                        </button>


                                        <!-- =================================
                                             CANCEL
                                        ================================== -->

                                        <?php if ($canCancel): ?>


                                            <form
                                                method="POST"
                                                action="my-host.php"
                                                class="cancel-host-booking-form"
                                                data-status="<?= htmlspecialchars(
                                                    $status,
                                                    ENT_QUOTES
                                                ) ?>"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="cancel_host_booking"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= $bookingId ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars(
                                                        $csrfToken
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="host-action-button host-cancel-button"

                                                    data-status="<?= htmlspecialchars(
                                                        $status,
                                                        ENT_QUOTES
                                                    ) ?>"

                                                    data-host-name="<?= htmlspecialchars(
                                                        $hostName,
                                                        ENT_QUOTES
                                                    ) ?>"

                                                    data-event-name="<?= htmlspecialchars(
                                                        $eventName,
                                                        ENT_QUOTES
                                                    ) ?>"

                                                    title="Cancel host booking"
                                                >

                                                    <span class="material-symbols-outlined">
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
                                                class="host-action-button host-cancel-button disabled"

                                                data-status="<?= htmlspecialchars(
                                                    $status,
                                                    ENT_QUOTES
                                                ) ?>"

                                                disabled

                                                title="This booking cannot be cancelled"
                                            >

                                                <span class="material-symbols-outlined">
                                                    block
                                                </span>


                                                <span>
                                                    Cancel
                                                </span>

                                            </button>


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
     HOST VIEW MODAL
========================================================== -->

<div
    class="host-modal"
    id="hostModal"
    aria-hidden="true"
>


    <div
        class="host-modal-overlay"
        id="hostModalOverlay"
    ></div>


    <div
        class="host-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="hostModalTitle"
    >


        <!-- HEADER -->

        <div class="host-modal-header">

            <div>

                <span class="modal-eyebrow">
                    HOST BOOKING
                </span>


                <h2 id="hostModalTitle">
                    Host Details
                </h2>

            </div>


            <button
                type="button"
                class="host-modal-close"
                id="closeHostModal"
                aria-label="Close host details"
            >

                <span class="material-symbols-outlined">
                    close
                </span>

            </button>

        </div>


        <!-- BODY -->

        <div class="host-modal-body">


            <!-- HOST IMAGE -->

            <div class="host-modal-image-wrapper">

                <img
                    id="hostModalImage"
                    class="host-modal-image"
                    src=""
                    alt="Host"
                >


                <div
                    id="hostModalImageEmpty"
                    class="host-modal-image-empty"
                >

                    <span class="material-symbols-outlined">
                        mic_external_on
                    </span>


                    <span>
                        No host image available
                    </span>

                </div>

            </div>


            <!-- HOST INFORMATION -->

            <div class="host-modal-content">


                <div class="host-modal-main">

                    <span class="modal-detail-label">
                        HOST
                    </span>


                    <h3 id="modalHostName">
                        Host
                    </h3>


                    <span
                        id="modalHostType"
                        class="modal-host-type"
                    >
                        Host
                    </span>

                </div>


                <!-- DESCRIPTION -->

                <div class="host-modal-description">

                    <span class="modal-detail-label">
                        ABOUT THE HOST
                    </span>


                    <p id="modalHostDescription">
                        —
                    </p>

                </div>


                <!-- BOOKING DETAILS -->

                <div class="host-detail-grid">


                    <!-- EVENT -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            event
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                EVENT
                            </span>


                            <strong id="modalHostEvent">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- DATE -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            calendar_month
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                EVENT DATE
                            </span>


                            <strong id="modalHostDate">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- TIME -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            schedule
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                START TIME
                            </span>


                            <strong id="modalHostTime">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- PACKAGE -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            inventory_2
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                PACKAGE
                            </span>


                            <strong id="modalHostPackage">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- PRICE -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            payments
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                PRICE
                            </span>


                            <strong id="modalHostPrice">
                                —
                            </strong>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="host-detail-item">

                        <span class="material-symbols-outlined">
                            verified
                        </span>


                        <div>

                            <span class="modal-detail-label">
                                STATUS
                            </span>


                            <strong id="modalHostStatus">
                                —
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- CONTACT -->

                <div class="host-contact-section">


                    <div>

                        <span class="modal-detail-label">
                            HOST EMAIL
                        </span>


                        <p id="modalHostEmail">
                            —
                        </p>

                    </div>


                    <div>

                        <span class="modal-detail-label">
                            HOST PHONE
                        </span>


                        <p id="modalHostPhone">
                            —
                        </p>

                    </div>


                    <div>

                        <span class="modal-detail-label">
                            EVENT ADDRESS
                        </span>


                        <p id="modalHostAddress">
                            —
                        </p>

                    </div>

                </div>


                <!-- INCLUSIONS -->

                <div
                    class="host-extra-section"
                    id="modalHostInclusionsWrapper"
                >

                    <span class="modal-detail-label">
                        PACKAGE INCLUSIONS
                    </span>


                    <p id="modalHostInclusions">
                        —
                    </p>

                </div>


                <!-- SPECIAL REQUEST -->

                <div
                    class="host-extra-section"
                    id="modalHostSpecialRequestWrapper"
                >

                    <span class="modal-detail-label">
                        SPECIAL REQUEST
                    </span>


                    <p id="modalHostSpecialRequest">
                        —
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     CANCEL HOST BOOKING MODAL
========================================================== -->

<div
    class="host-cancel-modal"
    id="hostCancelModal"
    aria-hidden="true"
>


    <div
        class="host-cancel-modal-overlay"
        id="hostCancelModalOverlay"
    ></div>


    <div
        class="host-cancel-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="hostCancelModalTitle"
    >


        <div class="host-cancel-modal-icon">

            <span class="material-symbols-outlined">
                warning
            </span>

        </div>


        <div class="host-cancel-modal-content">

            <span class="modal-eyebrow">
                CANCEL HOST BOOKING
            </span>


            <h2 id="hostCancelModalTitle">
                Cancel this booking?
            </h2>


            <p>

                Are you sure you want to cancel the host booking for

                <strong id="cancelHostName">
                    this host
                </strong>

                for

                <strong id="cancelHostEvent">
                    this event
                </strong>?

            </p>


            <p class="host-cancel-modal-notice">

                This action will change the booking status
                to cancelled.

            </p>

        </div>


        <div class="host-cancel-modal-actions">


            <button
                type="button"
                class="host-cancel-modal-button host-cancel-modal-back"
                id="closeHostCancelModal"
            >

                Keep Booking

            </button>


            <button
                type="button"
                class="host-cancel-modal-button host-cancel-modal-confirm"
                id="confirmHostCancellation"
            >

                <span class="material-symbols-outlined">
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
    class="host-cancellation-loading"
    id="hostCancellationLoading"
    aria-hidden="true"
>


    <div class="host-cancellation-loading-overlay"></div>


    <div
        class="host-cancellation-loading-dialog"
        role="status"
        aria-live="polite"
    >


        <div class="host-cancellation-loading-icon">

            <span class="material-symbols-outlined">
                sync
            </span>


            <span class="host-loading-spinner"></span>

        </div>


        <div class="host-cancellation-loading-content">

            <span class="modal-eyebrow">
                PLEASE WAIT
            </span>


            <h2>
                Cancelling Host Booking
            </h2>


            <p>
                Your host booking cancellation is being processed.
                Please do not close or refresh this page.
            </p>

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

    window.MY_HOST_CSRF =
        <?= json_encode($csrfToken) ?>;


    /*
    |----------------------------------------------------------
    | CURRENT USER
    |----------------------------------------------------------
    */

    window.MY_HOST_USER =
        <?= json_encode([
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
    src="js/header.js"
></script>


<script
    src="js/myhost.js?v=2"
></script>


</body>

</html>