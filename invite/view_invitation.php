<?php

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";

if (!isset($conn) || !$conn) {
    die("Database connection failed.");
}


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

$autoloadPath = __DIR__ . "/../vendor/autoload.php";

if (!file_exists($autoloadPath)) {
    die("PHPMailer autoload file was not found.");
}

require_once $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| EMAIL CONFIGURATION
|--------------------------------------------------------------------------
*/

$emailConfig = [];

$emailConfigPath = __DIR__ . "/../config/email.php";

if (!file_exists($emailConfigPath)) {
    die("Email configuration file was not found.");
}

$loadedEmailConfig = require $emailConfigPath;

if (is_array($loadedEmailConfig)) {
    $emailConfig = $loadedEmailConfig;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["guest_invitation_csrf"])) {
    $_SESSION["guest_invitation_csrf"] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION["guest_invitation_csrf"];


/*
|--------------------------------------------------------------------------
| URL TOKENS
|--------------------------------------------------------------------------
|
| token       = shared event invitation token
| guest_token = individual guest token
|
*/

$eventToken =
    isset($_GET["token"])
        ? trim($_GET["token"])
        : "";

$guestToken =
    isset($_GET["guest_token"])
        ? trim($_GET["guest_token"])
        : "";


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$event = null;

$bookingType = "";

$bookingId = 0;

$guestRecord = null;

$guestMode = false;


/*
|--------------------------------------------------------------------------
| DETERMINE GUEST MODE
|--------------------------------------------------------------------------
*/

if ($guestToken !== "") {
    $guestMode = true;
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| GET EVENT FROM REGULAR BOOKING
|--------------------------------------------------------------------------
*/

function getRegularEventByInvitationToken(
    mysqli $conn,
    string $token
): ?array {

    $stmt = $conn->prepare("
        SELECT
            b.id,
            b.event_name,
            b.event_date,
            b.start_time,
            b.guest_count,
            b.status,
            b.invitation_token,

            b.package_id,
            b.venue_id,

            p.package_name,

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

        WHERE b.invitation_token = ?

        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param(
        "s",
        $token
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| GET EVENT FROM WEDDING BOOKING
|--------------------------------------------------------------------------
*/

function getWeddingEventByInvitationToken(
    mysqli $conn,
    string $token
): ?array {

    $stmt = $conn->prepare("
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
            bw.invitation_token,

            bw.package_id,
            bw.venue_id,

            p.package_name,

            v.venue_name

        FROM booking_wedding bw

        LEFT JOIN event_packages p
            ON bw.package_id = p.id

        LEFT JOIN venues v
            ON bw.venue_id = v.id

        WHERE bw.invitation_token = ?

        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param(
        "s",
        $token
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $stmt->close();

    if (!$row) {
        return null;
    }

    $bride =
        trim(
            $row["bride_name"] ?? ""
        );

    $groom =
        trim(
            $row["groom_name"] ?? ""
        );

    $names = "";

    if ($bride !== "" && $groom !== "") {

        $names =
            $bride .
            " & " .
            $groom;

    } elseif ($bride !== "") {

        $names = $bride;

    } elseif ($groom !== "") {

        $names = $groom;
    }

    $row["event_name"] =
        $names !== ""
            ? $names . " Wedding"
            : "Wedding";

    $row["event_type_name"] =
        "Wedding";

    return $row;
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| GET EVENT BY BOOKING ID
|--------------------------------------------------------------------------
|
| Used for guest_token mode.
|
*/

function getEventByBookingId(
    mysqli $conn,
    int $bookingId
): array {

    /*
    |--------------------------------------------------------------------------
    | FIRST: REGULAR BOOKING
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            b.id,
            b.event_name,
            b.event_date,
            b.start_time,
            b.guest_count,
            b.status,

            b.package_id,
            b.venue_id,

            p.package_name,

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

        WHERE b.id = ?

        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $bookingId
        );

        if ($stmt->execute()) {

            $result =
                $stmt->get_result();

            $row =
                $result->fetch_assoc();

            $stmt->close();

            if ($row) {

                return [
                    "event" => $row,
                    "booking_type" => "regular"
                ];
            }

        } else {

            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SECOND: WEDDING BOOKING
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
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

            bw.package_id,
            bw.venue_id,

            p.package_name,

            v.venue_name

        FROM booking_wedding bw

        LEFT JOIN event_packages p
            ON bw.package_id = p.id

        LEFT JOIN venues v
            ON bw.venue_id = v.id

        WHERE bw.id = ?

        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $bookingId
        );

        if ($stmt->execute()) {

            $result =
                $stmt->get_result();

            $row =
                $result->fetch_assoc();

            $stmt->close();

            if ($row) {

                $bride =
                    trim(
                        $row["bride_name"] ?? ""
                    );

                $groom =
                    trim(
                        $row["groom_name"] ?? ""
                    );

                $names = "";

                if (
                    $bride !== "" &&
                    $groom !== ""
                ) {

                    $names =
                        $bride .
                        " & " .
                        $groom;

                } elseif ($bride !== "") {

                    $names = $bride;

                } elseif ($groom !== "") {

                    $names = $groom;
                }

                $row["event_name"] =
                    $names !== ""
                        ? $names . " Wedding"
                        : "Wedding";

                $row["event_type_name"] =
                    "Wedding";

                return [
                    "event" => $row,
                    "booking_type" => "wedding"
                ];
            }

        } else {

            $stmt->close();
        }
    }


    return [
        "event" => null,
        "booking_type" => ""
    ];
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| GET CONFIRMED GUEST COUNT
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Regular:
| bookings -> invitations
|
| Wedding:
| booking_wedding -> invitation_wedding
|
|--------------------------------------------------------------------------
*/

function getConfirmedGuestCount(
    mysqli $conn,
    int $bookingId,
    string $bookingType = "regular"
): int {

    if ($bookingType === "wedding") {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total

            FROM invitation_wedding

            WHERE
                booking_id = ?
                AND status = 'confirmed'
        ");

    } else {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total

            FROM invitations

            WHERE
                booking_id = ?
                AND status = 'confirmed'
        ");
    }

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param(
        "i",
        $bookingId
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    $stmt->close();

    return (int) (
        $row["total"] ?? 0
    );
}


/*
|--------------------------------------------------------------------------
| GUEST TOKEN MODE
|--------------------------------------------------------------------------
*/

if ($guestMode) {

    /*
    |--------------------------------------------------------------------------
    | FIND GUEST
    |--------------------------------------------------------------------------
    */

    $guestStmt = $conn->prepare("
        SELECT
            id,
            booking_id,
            guest_name,
            guest_email,
            invitation_token,
            qrcode_image,
            status,
            confirmed_at,
            created_at

        FROM invitations

        WHERE invitation_token = ?

        LIMIT 1
    ");

    if ($guestStmt) {

        $guestStmt->bind_param(
            "s",
            $guestToken
        );

        if ($guestStmt->execute()) {

            $guestResult =
                $guestStmt->get_result();

            $guestRecord =
                $guestResult->fetch_assoc();
        }

        $guestStmt->close();
    }


    /*
    |--------------------------------------------------------------------------
    | IF NOT REGULAR, CHECK WEDDING
    |--------------------------------------------------------------------------
    */

    if (!$guestRecord) {

        $guestStmt = $conn->prepare("
            SELECT
                id,
                booking_id,
                guest_name,
                guest_email,
                invitation_token,
                qrcode_image,
                status,
                confirmed_at,
                created_at

            FROM invitation_wedding

            WHERE invitation_token = ?

            LIMIT 1
        ");

        if ($guestStmt) {

            $guestStmt->bind_param(
                "s",
                $guestToken
            );

            if ($guestStmt->execute()) {

                $guestResult =
                    $guestStmt->get_result();

                $guestRecord =
                    $guestResult->fetch_assoc();

                if ($guestRecord) {

                    $guestRecord["_booking_type"] =
                        "wedding";
                }
            }

            $guestStmt->close();
        }

    } else {

        $guestRecord["_booking_type"] =
            "regular";
    }


    /*
    |--------------------------------------------------------------------------
    | GUEST NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$guestRecord) {

        http_response_code(404);

        die("
            <!DOCTYPE html>

            <html lang='en'>

            <head>

                <meta charset='UTF-8'>

                <meta
                    name='viewport'
                    content='width=device-width, initial-scale=1.0'
                >

                <title>Guest Registration Not Found</title>

                <style>

                    body {
                        margin:0;
                        min-height:100vh;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        background:#fcfaf7;
                        font-family:Arial,sans-serif;
                        color:#333;
                        font-size:10px;
                    }

                    .box {
                        width:min(500px,calc(100% - 30px));
                        padding:40px 25px;
                        text-align:center;
                        background:#fff;
                        border:1px solid #eadfd2;
                        border-radius:12px;
                        box-shadow:0 15px 40px rgba(0,0,0,.08);
                    }

                    h2 {
                        margin:0 0 10px;
                    }

                    p {
                        color:#777;
                        line-height:1.7;
                    }

                </style>

            </head>

            <body>

                <div class='box'>

                    <h2>
                        Guest Registration Not Found
                    </h2>

                    <p>
                        This guest confirmation link is invalid
                        or no longer available.
                    </p>

                </div>

            </body>

            </html>
        ");
    }


    /*
    |--------------------------------------------------------------------------
    | GET EVENT
    |--------------------------------------------------------------------------
    */

    $bookingId =
        (int) $guestRecord["booking_id"];

    $eventLookup =
        getEventByBookingId(
            $conn,
            $bookingId
        );

    $event =
        $eventLookup["event"];

    $bookingType =
        $eventLookup["booking_type"];


    /*
    |--------------------------------------------------------------------------
    | USE THE TYPE FROM INVITATION IF AVAILABLE
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $guestRecord["_booking_type"]
        ) &&
        $guestRecord["_booking_type"] !== ""
    ) {

        $bookingType =
            $guestRecord["_booking_type"];
    }


    if (!$event) {

        http_response_code(404);

        die("
            <div style='
                font-family:Arial,sans-serif;
                text-align:center;
                padding:80px 20px;
                font-size:10px;
            '>

                <h2>Event Not Found</h2>

                <p>
                    The event connected to this
                    guest registration could not be found.
                </p>

            </div>
        ");
    }

}


/*
|--------------------------------------------------------------------------
| EVENT TOKEN MODE
|--------------------------------------------------------------------------
*/

if (!$guestMode) {

    if ($eventToken === "") {

        http_response_code(400);

        die("
            <div style='
                font-family:Arial,sans-serif;
                text-align:center;
                padding:80px 20px;
                font-size:10px;
            '>

                <h2>Invalid Invitation Link</h2>

                <p>
                    The invitation token is missing.
                </p>

            </div>
        ");
    }


    /*
    |--------------------------------------------------------------------------
    | TRY REGULAR EVENT
    |--------------------------------------------------------------------------
    */

    $event =
        getRegularEventByInvitationToken(
            $conn,
            $eventToken
        );


    if ($event) {

        $bookingType =
            "regular";

        $bookingId =
            (int) $event["id"];

    } else {

        /*
        |--------------------------------------------------------------------------
        | TRY WEDDING EVENT
        |--------------------------------------------------------------------------
        */

        $event =
            getWeddingEventByInvitationToken(
                $conn,
                $eventToken
            );


        if ($event) {

            $bookingType =
                "wedding";

            $bookingId =
                (int) $event["id"];

        }
    }
}


/*
|--------------------------------------------------------------------------
| EVENT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$event) {

    http_response_code(404);

    die("
        <!DOCTYPE html>

        <html lang='en'>

        <head>

            <meta charset='UTF-8'>

            <meta
                name='viewport'
                content='width=device-width, initial-scale=1.0'
            >

            <title>Invitation Not Found</title>

            <style>

                body {
                    margin:0;
                    min-height:100vh;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#fcfaf7;
                    font-family:Arial,sans-serif;
                    color:#333;
                    font-size:10px;
                }

                .box {
                    width:min(500px,calc(100% - 30px));
                    padding:40px 25px;
                    text-align:center;
                    background:#fff;
                    border:1px solid #eadfd2;
                    border-radius:12px;
                    box-shadow:0 15px 40px rgba(0,0,0,.08);
                }

                h2 {
                    margin:0 0 10px;
                }

                p {
                    color:#777;
                    line-height:1.7;
                }

            </style>

        </head>

        <body>

            <div class='box'>

                <h2>
                    Invitation Not Found
                </h2>

                <p>
                    This invitation link is invalid
                    or no longer available.
                </p>

            </div>

        </body>

        </html>
    ");
}


/*
|--------------------------------------------------------------------------
| EVENT STATUS
|--------------------------------------------------------------------------
*/

$status =
    strtolower(
        trim(
            $event["status"] ?? ""
        )
    );


if ($status !== "confirmed") {

    http_response_code(403);

    die("
        <!DOCTYPE html>

        <html lang='en'>

        <head>

            <meta charset='UTF-8'>

            <meta
                name='viewport'
                content='width=device-width, initial-scale=1.0'
            >

            <title>Invitation Unavailable</title>

            <style>

                body {
                    margin:0;
                    min-height:100vh;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#fcfaf7;
                    font-family:Arial,sans-serif;
                    color:#333;
                    font-size:10px;
                }

                .box {
                    width:min(500px,calc(100% - 30px));
                    padding:40px 25px;
                    text-align:center;
                    background:#fff;
                    border:1px solid #eadfd2;
                    border-radius:12px;
                    box-shadow:0 15px 40px rgba(0,0,0,.08);
                }

                h2 {
                    margin:0 0 10px;
                }

                p {
                    color:#777;
                    line-height:1.7;
                }

            </style>

        </head>

        <body>

            <div class='box'>

                <h2>
                    Invitation Unavailable
                </h2>

                <p>
                    This event is not currently
                    accepting guest registrations.
                </p>

            </div>

        </body>

        </html>
    ");
}


/*
|--------------------------------------------------------------------------
| EVENT INFORMATION
|--------------------------------------------------------------------------
*/

$eventName =
    $event["event_name"] ??
    "Event";

$eventDate =
    $event["event_date"] ??
    "";

$startTime =
    $event["start_time"] ??
    "";

$guestCapacity =
    max(
        1,
        (int) (
            $event["guest_count"] ??
            1
        )
    );

$venueName =
    $event["venue_name"] ??
    "Not specified";

$packageName =
    $event["package_name"] ??
    "Not specified";

$eventType =
    $event["event_type_name"] ??
    "Event";


/*
|--------------------------------------------------------------------------
| FORMATTED DATE
|--------------------------------------------------------------------------
*/

$displayDate =
    !empty($eventDate)
        ? date(
            "F d, Y",
            strtotime($eventDate)
        )
        : "Not specified";


/*
|--------------------------------------------------------------------------
| FORMATTED TIME
|--------------------------------------------------------------------------
*/

$displayTime =
    !empty($startTime)
        ? date(
            "h:i A",
            strtotime($startTime)
        )
        : "Not specified";


/*
|--------------------------------------------------------------------------
| GUEST COUNT
|--------------------------------------------------------------------------
*/

$confirmedGuests =
    getConfirmedGuestCount(
        $conn,
        $bookingId,
        $bookingType
    );

$remainingGuests =
    max(
        0,
        $guestCapacity -
        $confirmedGuests
    );


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$errorMessage = "";

$successMessage = "";

$emailErrorMessage = "";

$guestQrCode = "";

$registeredGuestName = "";

$registeredGuestEmail = "";

$emailWasSent = false;


/*
|--------------------------------------------------------------------------
| FUNCTION:
| BUILD EVENT INVITATION LINK
|--------------------------------------------------------------------------
*/

function buildEventInvitationLink(
    string $eventToken
): string {

    $https =
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off";

    $scheme =
        $https
            ? "https"
            : "http";

    $host =
        $_SERVER["HTTP_HOST"] ??
        "localhost";

    $directory =
        str_replace(
            "\\",
            "/",
            dirname(
                $_SERVER["SCRIPT_NAME"] ??
                ""
            )
        );

    $directory =
        rtrim(
            $directory,
            "/"
        );

    return
        $scheme .
        "://" .
        $host .
        $directory .
        "/view_invitation.php?token=" .
        rawurlencode(
            $eventToken
        );
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| BUILD GUEST LINK
|--------------------------------------------------------------------------
*/

function buildGuestLink(
    string $guestToken
): string {

    $https =
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off";

    $scheme =
        $https
            ? "https"
            : "http";

    $host =
        $_SERVER["HTTP_HOST"] ??
        "localhost";

    $directory =
        str_replace(
            "\\",
            "/",
            dirname(
                $_SERVER["SCRIPT_NAME"] ??
                ""
            )
        );

    $directory =
        rtrim(
            $directory,
            "/"
        );

    return
        $scheme .
        "://" .
        $host .
        $directory .
        "/view_invitation.php?guest_token=" .
        rawurlencode(
            $guestToken
        );
}


/*
|--------------------------------------------------------------------------
| FUNCTION:
| SEND CONFIRMATION EMAIL
|--------------------------------------------------------------------------
*/

function sendGuestConfirmationEmail(
    string $guestName,
    string $guestEmail,
    string $eventName,
    string $displayDate,
    string $displayTime,
    string $venueName,
    string $packageName,
    string $eventType,
    string $guestQrCode,
    string $guestToken,
    array $emailConfig
): array {

    if (
        !class_exists(
            "PHPMailer\\PHPMailer\\PHPMailer"
        )
    ) {

        return [
            "success" => false,
            "error" =>
                "PHPMailer is not loaded."
        ];
    }


    $requiredKeys = [
        "smtp_host",
        "smtp_username",
        "smtp_password",
        "smtp_port",
        "from_email",
        "from_name"
    ];


    foreach ($requiredKeys as $key) {

        if (
            !isset($emailConfig[$key]) ||
            trim(
                (string)
                $emailConfig[$key]
            ) === ""
        ) {

            return [
                "success" => false,
                "error" =>
                    "Missing email configuration: " .
                    $key
            ];
        }
    }


    $smtpHost =
        trim(
            (string)
            $emailConfig["smtp_host"]
        );

    $smtpUsername =
        trim(
            (string)
            $emailConfig["smtp_username"]
        );

    $smtpPassword =
        trim(
            (string)
            $emailConfig["smtp_password"]
        );

    $smtpPort =
        (int)
        $emailConfig["smtp_port"];

    $fromEmail =
        trim(
            (string)
            $emailConfig["from_email"]
        );

    $fromName =
        trim(
            (string)
            $emailConfig["from_name"]
        );


    if (
        !filter_var(
            $guestEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        return [
            "success" => false,
            "error" =>
                "Invalid guest email address."
        ];
    }


    if (
        !filter_var(
            $fromEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        return [
            "success" => false,
            "error" =>
                "Invalid from_email configuration."
        ];
    }


    $guestLink =
        buildGuestLink(
            $guestToken
        );


    $safeGuestName =
        htmlspecialchars(
            $guestName,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeEventName =
        htmlspecialchars(
            $eventName,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeEventType =
        htmlspecialchars(
            $eventType,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeDate =
        htmlspecialchars(
            $displayDate,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeTime =
        htmlspecialchars(
            $displayTime,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeVenue =
        htmlspecialchars(
            $venueName,
            ENT_QUOTES,
            "UTF-8"
        );

    $safePackage =
        htmlspecialchars(
            $packageName,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeQr =
        htmlspecialchars(
            $guestQrCode,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeGuestLink =
        htmlspecialchars(
            $guestLink,
            ENT_QUOTES,
            "UTF-8"
        );


    try {

        $mail =
            new PHPMailer(true);


        $mail->isSMTP();

        $mail->Host =
            $smtpHost;

        $mail->SMTPAuth =
            true;

        $mail->Username =
            $smtpUsername;

        $mail->Password =
            $smtpPassword;

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            $smtpPort;


        $mail->CharSet =
            "UTF-8";

        $mail->Encoding =
            "base64";

        $mail->Timeout =
            30;


        $mail->setFrom(
            $fromEmail,
            $fromName
        );


        $mail->addAddress(
            $guestEmail,
            $guestName
        );


        if (
            isset(
                $emailConfig["admin_email"]
            ) &&
            filter_var(
                $emailConfig["admin_email"],
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $adminName =
                $emailConfig["admin_name"] ??
                $fromName;

            $mail->addReplyTo(
                $emailConfig["admin_email"],
                $adminName
            );
        }


        $mail->Subject =
            "Event Registration Confirmed - " .
            $eventName;


        $mail->isHTML(true);

        $mail->Body = "

<!DOCTYPE html>

<html lang='en'>

<head>

<meta charset='UTF-8'>

<title>
Event Registration Confirmed
</title>

</head>

<body style='
margin:0;
padding:0;
background:#fcfaf7;
font-family:Arial,Helvetica,sans-serif;
color:#333333;
font-size:10px;
'>

<div style='
padding:30px 15px;
background:#fcfaf7;
'>

<div style='
max-width:620px;
margin:0 auto;
background:#ffffff;
border:1px solid #eadfd2;
border-radius:12px;
overflow:hidden;
'>


<div style='
padding:30px 25px;
text-align:center;
background:#181818;
'>

<img
src='https://eventsolutionsbyshe.com/images/logo.png'
alt='Event Solutions by S.H.E.'
style='
display:block;
width:70px;
height:70px;
object-fit:contain;
margin:0 auto 10px;
'
>

<div style='
color:#d8bd7a;
font-size:10px;
font-weight:bold;
letter-spacing:3px;
'>
EVENT SOLUTIONS BY S.H.E.
</div>

<h1 style='
margin:12px 0 0;
color:#ffffff;
font-size:23px;
font-weight:600;
'>
Registration Confirmed
</h1>

</div>


<div style='
padding:30px;
'>


<p style='
margin:0 0 12px;
font-size:10px;
color:#333333;
'>

Hello
<strong>
{$safeGuestName}
</strong>,

</p>


<p style='
margin:0 0 22px;
font-size:10px;
line-height:1.7;
color:#666666;
'>

Your registration has been
successfully recorded.

Here are your event details:

</p>


<div style='
padding:20px;
background:#fcf8f2;
border:1px solid #eee3d7;
border-radius:9px;
'>

<div style='
margin-bottom:15px;
'>

<div style='
margin-bottom:5px;
color:#9d7a32;
font-size:10px;
font-weight:bold;
letter-spacing:1px;
text-transform:uppercase;
'>
Event
</div>

<div style='
color:#181818;
font-size:18px;
font-weight:600;
'>
{$safeEventName}
</div>

</div>


<table
width='100%'
cellpadding='0'
cellspacing='0'
style='
border-collapse:collapse;
'
>

<tr>

<td style='
padding:7px 0;
width:120px;
font-size:10px;
color:#777777;
'>
Event Type
</td>

<td style='
padding:7px 0;
font-size:10px;
font-weight:600;
color:#333333;
'>
{$safeEventType}
</td>

</tr>


<tr>

<td style='
padding:7px 0;
font-size:10px;
color:#777777;
'>
Date
</td>

<td style='
padding:7px 0;
font-size:10px;
font-weight:600;
color:#333333;
'>
{$safeDate}
</td>

</tr>


<tr>

<td style='
padding:7px 0;
font-size:10px;
color:#777777;
'>
Time
</td>

<td style='
padding:7px 0;
font-size:10px;
font-weight:600;
color:#333333;
'>
{$safeTime}
</td>

</tr>


<tr>

<td style='
padding:7px 0;
font-size:10px;
color:#777777;
'>
Venue
</td>

<td style='
padding:7px 0;
font-size:10px;
font-weight:600;
color:#333333;
'>
{$safeVenue}
</td>

</tr>


<tr>

<td style='
padding:7px 0;
font-size:10px;
color:#777777;
'>
Package
</td>

<td style='
padding:7px 0;
font-size:10px;
font-weight:600;
color:#333333;
'>
{$safePackage}
</td>

</tr>

</table>

</div>


<div style='
margin-top:28px;
text-align:center;
'>

<div style='
margin-bottom:15px;
font-size:10px;
font-weight:bold;
color:#181818;
'>
Your Guest QR Code
</div>


<div style='
display:inline-block;
padding:12px;
background:#ffffff;
border:1px solid #e6ded4;
border-radius:10px;
'>

<img
src='{$safeQr}'
alt='Guest QR Code'
width='220'
height='220'
style='
display:block;
width:220px;
height:220px;
'
>

</div>


<p style='
margin:14px 0 0;
font-size:10px;
line-height:1.6;
color:#777777;
'>

Please present this QR code
at the event for check-in.

</p>

</div>


<div style='
margin-top:25px;
padding:16px;
background:#f8f4ee;
border:1px solid #e9dfd3;
border-radius:8px;
'>

<div style='
margin-bottom:7px;
color:#9d7a32;
font-size:10px;
font-weight:bold;
letter-spacing:1px;
text-transform:uppercase;
'>
Your Confirmation Link
</div>

<a
href='{$safeGuestLink}'
style='
font-size:10px;
color:#9d7a32;
word-break:break-all;
'
>
{$safeGuestLink}
</a>

</div>


<p style='
margin:25px 0 0;
font-size:10px;
line-height:1.7;
color:#777777;
'>

Thank you for registering.

We look forward to seeing you
at the event.

</p>


</div>


<div style='
padding:20px;
text-align:center;
background:#faf7f2;
border-top:1px solid #eee5dc;
'>

<div style='
font-size:10px;
color:#777777;
'>
Event Solutions by S.H.E.
</div>

</div>


</div>

</div>

</body>

</html>
";


        $mail->AltBody =
            "EVENT SOLUTIONS BY S.H.E.\n\n" .

            "Registration Confirmed\n\n" .

            "Hello " .
            $guestName .
            ",\n\n" .

            "Your registration has been successfully recorded.\n\n" .

            "Event: " .
            $eventName .
            "\n" .

            "Event Type: " .
            $eventType .
            "\n" .

            "Date: " .
            $displayDate .
            "\n" .

            "Time: " .
            $displayTime .
            "\n" .

            "Venue: " .
            $venueName .
            "\n" .

            "Package: " .
            $packageName .
            "\n\n" .

            "Your QR Code:\n" .
            $guestQrCode .
            "\n\n" .

            "Your confirmation link:\n" .
            $guestLink .
            "\n\n" .

            "Please present your QR code at the event for check-in.";


        $mail->send();


        return [
            "success" => true,
            "error" => ""
        ];

    } catch (Exception $e) {

        $error =
            $mail->ErrorInfo ??
            $e->getMessage();


        error_log(
            "Guest confirmation email error: " .
            $error
        );


        return [
            "success" => false,
            "error" => $error
        ];
    }
}


/*
|--------------------------------------------------------------------------
| PROCESS GUEST REGISTRATION
|--------------------------------------------------------------------------
|
| This only runs when using:
|
| ?token=EVENT_TOKEN
|
*/

if (
    !$guestMode &&
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $postedCsrf =
        $_POST["csrf_token"] ??
        "";


    if (
        !hash_equals(
            $csrfToken,
            $postedCsrf
        )
    ) {

        $errorMessage =
            "Security verification failed. Please refresh the page.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | FORM DATA
        |--------------------------------------------------------------------------
        */

        $guestName =
            trim(
                $_POST["guest_name"] ??
                ""
            );

        $guestEmail =
            trim(
                $_POST["guest_email"] ??
                ""
            );


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($guestName === "") {

            $errorMessage =
                "Please enter your full name.";

        } elseif (
            mb_strlen($guestName) < 2
        ) {

            $errorMessage =
                "Please enter your full name.";

        } elseif (
            mb_strlen($guestName) > 150
        ) {

            $errorMessage =
                "Guest name is too long.";

        } elseif (
            !filter_var(
                $guestEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $errorMessage =
                "Please enter a valid email address.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | RELOAD EVENT
            |--------------------------------------------------------------------------
            */

            $latestEvent = null;


            if ($bookingType === "regular") {

                $latestStmt =
                    $conn->prepare("
                        SELECT
                            b.id,
                            b.event_name,
                            b.event_date,
                            b.start_time,
                            b.guest_count,
                            b.status,

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
                            AND b.invitation_token = ?

                        LIMIT 1
                    ");

            } else {

                $latestStmt =
                    $conn->prepare("
                        SELECT
                            bw.id,

                            bw.bride_name,
                            bw.groom_name,

                            bw.event_date,
                            bw.start_time,
                            bw.guest_count,
                            bw.status,

                            p.package_name,

                            v.venue_name

                        FROM booking_wedding bw

                        LEFT JOIN event_packages p
                            ON bw.package_id = p.id

                        LEFT JOIN venues v
                            ON bw.venue_id = v.id

                        WHERE
                            bw.id = ?
                            AND bw.invitation_token = ?

                        LIMIT 1
                    ");
            }


            if (!$latestStmt) {

                $errorMessage =
                    "Unable to verify the event.";

            } else {

                $latestStmt->bind_param(
                    "is",
                    $bookingId,
                    $eventToken
                );


                if ($latestStmt->execute()) {

                    $latestResult =
                        $latestStmt->get_result();

                    $latestEvent =
                        $latestResult->fetch_assoc();
                }


                $latestStmt->close();


                /*
                |--------------------------------------------------------------------------
                | CHECK EVENT
                |--------------------------------------------------------------------------
                */

                if (!$latestEvent) {

                    $errorMessage =
                        "This event invitation is no longer available.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | WEDDING EVENT NAME
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $bookingType === "wedding"
                    ) {

                        $bride =
                            trim(
                                $latestEvent["bride_name"] ??
                                ""
                            );

                        $groom =
                            trim(
                                $latestEvent["groom_name"] ??
                                ""
                            );

                        $names = "";

                        if (
                            $bride !== "" &&
                            $groom !== ""
                        ) {

                            $names =
                                $bride .
                                " & " .
                                $groom;

                        } elseif ($bride !== "") {

                            $names = $bride;

                        } elseif ($groom !== "") {

                            $names = $groom;
                        }

                        $latestEvent["event_name"] =
                            $names !== ""
                                ? $names . " Wedding"
                                : "Wedding";

                        $latestEvent["event_type_name"] =
                            "Wedding";
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK STATUS
                    |--------------------------------------------------------------------------
                    */

                    $latestStatus =
                        strtolower(
                            trim(
                                $latestEvent["status"] ??
                                ""
                            )
                        );


                    if (
                        $latestStatus !==
                        "confirmed"
                    ) {

                        $errorMessage =
                            "This event is no longer accepting guest registrations.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | CAPACITY
                        |--------------------------------------------------------------------------
                        */

                        $latestCapacity =
                            max(
                                1,
                                (int) (
                                    $latestEvent["guest_count"] ??
                                    1
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CURRENT CONFIRMED
                        |--------------------------------------------------------------------------
                        */

                        $currentConfirmed =
                            getConfirmedGuestCount(
                                $conn,
                                $bookingId,
                                $bookingType
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | FULL
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $currentConfirmed >=
                            $latestCapacity
                        ) {

                            $errorMessage =
                                "Sorry, the guest capacity for this event has already been reached.";

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | DUPLICATE EMAIL
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $bookingType === "wedding"
                            ) {

                                $duplicateStmt =
                                    $conn->prepare("
                                        SELECT
                                            id

                                        FROM invitation_wedding

                                        WHERE
                                            booking_id = ?
                                            AND LOWER(guest_email) =
                                                LOWER(?)
                                            AND status IN (
                                                'pending',
                                                'confirmed'
                                            )

                                        LIMIT 1
                                    ");

                            } else {

                                $duplicateStmt =
                                    $conn->prepare("
                                        SELECT
                                            id

                                        FROM invitations

                                        WHERE
                                            booking_id = ?
                                            AND LOWER(guest_email) =
                                                LOWER(?)
                                            AND status IN (
                                                'pending',
                                                'confirmed'
                                            )

                                        LIMIT 1
                                    ");
                            }


                            $duplicate =
                                false;


                            if ($duplicateStmt) {

                                $duplicateStmt->bind_param(
                                    "is",
                                    $bookingId,
                                    $guestEmail
                                );


                                if (
                                    $duplicateStmt->execute()
                                ) {

                                    $duplicateResult =
                                        $duplicateStmt->get_result();

                                    $duplicate =
                                        $duplicateResult->num_rows > 0;
                                }


                                $duplicateStmt->close();
                            }


                            if ($duplicate) {

                                $errorMessage =
                                    "This email address has already been registered for this event.";

                            } else {

                                /*
                                |--------------------------------------------------------------------------
                                | GENERATE GUEST TOKEN
                                |--------------------------------------------------------------------------
                                */

                                $guestToken = "";

                                do {

                                    $guestToken =
                                        bin2hex(
                                            random_bytes(32)
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | CHECK TOKEN IN CORRECT TABLE
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $bookingType === "wedding"
                                    ) {

                                        $tokenCheckStmt =
                                            $conn->prepare("
                                                SELECT
                                                    id

                                                FROM invitation_wedding

                                                WHERE
                                                    invitation_token = ?

                                                LIMIT 1
                                            ");

                                    } else {

                                        $tokenCheckStmt =
                                            $conn->prepare("
                                                SELECT
                                                    id

                                                FROM invitations

                                                WHERE
                                                    invitation_token = ?

                                                LIMIT 1
                                            ");
                                    }


                                    $tokenExists =
                                        false;


                                    if ($tokenCheckStmt) {

                                        $tokenCheckStmt->bind_param(
                                            "s",
                                            $guestToken
                                        );


                                        if (
                                            $tokenCheckStmt->execute()
                                        ) {

                                            $tokenCheckResult =
                                                $tokenCheckStmt
                                                ->get_result();

                                            $tokenExists =
                                                $tokenCheckResult
                                                ->num_rows > 0;
                                        }


                                        $tokenCheckStmt->close();
                                    }

                                } while ($tokenExists);


                                /*
                                |--------------------------------------------------------------------------
                                | QR CODE
                                |--------------------------------------------------------------------------
                                */

                                $guestQrCode =
                                    "https://api.qrserver.com/v1/create-qr-code/" .
                                    "?size=220x220" .
                                    "&data=" .
                                    rawurlencode(
                                        $guestToken
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | INSERT INVITATION
                                |--------------------------------------------------------------------------
                                |
                                | NEW GUESTS ARE NOW PENDING.
                                |
                                | Regular:
                                |     INSERT INTO invitations
                                |
                                | Wedding:
                                |     INSERT INTO invitation_wedding
                                |
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $bookingType === "wedding"
                                ) {

                                    $insertStmt =
                                        $conn->prepare("
                                            INSERT INTO invitation_wedding
                                            (
                                                booking_id,
                                                created_at,
                                                guest_email,
                                                guest_name,
                                                qrcode_image,
                                                invitation_token,
                                                status,
                                                confirmed_at
                                            )

                                            VALUES
                                            (
                                                ?,
                                                NOW(),
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                'pending',
                                                NULL
                                            )
                                        ");

                                } else {

                                    $insertStmt =
                                        $conn->prepare("
                                            INSERT INTO invitations
                                            (
                                                booking_id,
                                                created_at,
                                                guest_email,
                                                guest_name,
                                                qrcode_image,
                                                invitation_token,
                                                status,
                                                confirmed_at
                                            )

                                            VALUES
                                            (
                                                ?,
                                                NOW(),
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                'pending',
                                                NULL
                                            )
                                        ");
                                }


                                if (!$insertStmt) {

                                    $errorMessage =
                                        "Failed to prepare guest registration: " .
                                        $conn->error;

                                } else {

                                    $insertStmt->bind_param(
                                        "issss",
                                        $bookingId,
                                        $guestEmail,
                                        $guestName,
                                        $guestQrCode,
                                        $guestToken
                                    );


                                    if (
                                        !$insertStmt->execute()
                                    ) {

                                        $errorMessage =
                                            "Failed to register guest: " .
                                            $insertStmt->error;

                                    } else {

                                        /*
                                        |--------------------------------------------------------------------------
                                        | SUCCESS
                                        |--------------------------------------------------------------------------
                                        */

                                        $registeredGuestName =
                                            $guestName;

                                        $registeredGuestEmail =
                                            $guestEmail;


                                        /*
                                        |--------------------------------------------------------------------------
                                        | UPDATE COUNTS
                                        |--------------------------------------------------------------------------
                                        |
                                        | The new registration is pending,
                                        | therefore it is NOT counted as
                                        | confirmed.
                                        |
                                        */

                                        $confirmedGuests =
                                            $currentConfirmed;


                                        $remainingGuests =
                                            max(
                                                0,
                                                $latestCapacity -
                                                $confirmedGuests
                                            );


                                        /*
                                        |--------------------------------------------------------------------------
                                        | SEND EMAIL
                                        |--------------------------------------------------------------------------
                                        */

                                        $emailResult =
                                            sendGuestConfirmationEmail(
                                                $guestName,
                                                $guestEmail,
                                                $eventName,
                                                $displayDate,
                                                $displayTime,
                                                $venueName,
                                                $packageName,
                                                $eventType,
                                                $guestQrCode,
                                                $guestToken,
                                                $emailConfig
                                            );


                                        if (
                                            $emailResult["success"]
                                        ) {

                                            $emailWasSent =
                                                true;

                                            $successMessage =
                                                "Your registration was successful. A confirmation email with your event details and QR code has been sent to " .
                                                $guestEmail .
                                                ".";

                                        } else {

                                            $emailWasSent =
                                                false;

                                            $emailErrorMessage =
                                                $emailResult["error"] ??
                                                "Unknown email error.";


                                            $successMessage =
                                                "Your registration was successful, but the confirmation email could not be sent.";
                                        }
                                    }


                                    $insertStmt->close();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SAFE DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$safeEventName =
    htmlspecialchars(
        $eventName,
        ENT_QUOTES,
        "UTF-8"
    );

$safeEventType =
    htmlspecialchars(
        $eventType,
        ENT_QUOTES,
        "UTF-8"
    );

$safeDate =
    htmlspecialchars(
        $displayDate,
        ENT_QUOTES,
        "UTF-8"
    );

$safeTime =
    htmlspecialchars(
        $displayTime,
        ENT_QUOTES,
        "UTF-8"
    );

$safeVenue =
    htmlspecialchars(
        $venueName,
        ENT_QUOTES,
        "UTF-8"
    );

$safePackage =
    htmlspecialchars(
        $packageName,
        ENT_QUOTES,
        "UTF-8"
    );

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
        <?= $safeEventName ?>
        - Invitation
    </title>


    <!-- GOOGLE FONTS -->

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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        html {
            font-size: 10px;
        }


        body {
            margin: 0;

            min-height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #fffaf6 0%,
                    #fcfaf7 100%
                );

            color: #333333;

            font-family:
                "Poppins",
                Arial,
                sans-serif;

            font-size: 10px;
        }


        .page {
            width:
                min(
                    820px,
                    calc(100% - 28px)
                );

            margin:
                0 auto;

            padding:
                25px 0 55px;
        }


        .invitation-card {
            overflow: hidden;

            background: #ffffff;

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.20
                );

            border-radius: 16px;

            box-shadow:
                0 20px 50px
                rgba(
                    24,
                    24,
                    24,
                    0.09
                );
        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .invitation-header {
            position: relative;

            overflow: hidden;

            min-height: 370px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            padding:
                48px 30px;

            text-align: center;

            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(
                        243,
                        199,
                        168,
                        0.30
                    ),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 85% 80%,
                    rgba(
                        185,
                        148,
                        69,
                        0.13
                    ),
                    transparent 32%
                ),
                linear-gradient(
                    135deg,
                    #fffaf5,
                    #f7ede4
                );

            border-bottom:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.15
                );
        }


        .invitation-header::before {
            content: "";

            position: absolute;

            width: 230px;

            height: 230px;

            top: -130px;

            left: -80px;

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.18
                );

            border-radius: 50%;
        }


        .invitation-header::after {
            content: "";

            position: absolute;

            width: 260px;

            height: 260px;

            right: -130px;

            bottom: -150px;

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.18
                );

            border-radius: 50%;
        }


        .company-logo {
            position: relative;

            z-index: 2;

            width: 78px;

            height: 78px;

            margin:
                0 auto 13px;

            padding: 7px;

            object-fit: contain;

            background: #ffffff;

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.25
                );

            border-radius: 50%;

            box-shadow:
                0 10px 25px
                rgba(
                    24,
                    24,
                    24,
                    0.08
                );
        }


        .company-name {
            position: relative;

            z-index: 2;

            margin-bottom: 16px;

            color: #9d7a32;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 2.5px;

            text-transform: uppercase;
        }


        .eyebrow {
            position: relative;

            z-index: 2;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 25px;

            padding:
                0 12px;

            color: #9d7a32;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.62
                );

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.22
                );

            border-radius: 30px;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 2px;
        }


        .invitation-header h1 {
            position: relative;

            z-index: 2;

            margin:
                15px auto 0;

            max-width: 690px;

            color: #181818;

            font-family:
                "DM Serif Display",
                serif;

            font-size:
                clamp(
                    34px,
                    7vw,
                    55px
                );

            line-height: 1.08;

            font-weight: 400;
        }


        .hero-event-meta {
            position: relative;

            z-index: 2;

            display: flex;

            flex-wrap: wrap;

            align-items: center;

            justify-content: center;

            gap: 8px;

            margin-top: 20px;
        }


        .hero-meta-item {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            min-height: 30px;

            padding:
                0 11px;

            color: #5f554b;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.70
                );

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.18
                );

            border-radius: 6px;

            font-size: 10px;

            font-weight: 500;
        }


        .hero-meta-item i {
            color: #b99445;

            font-size: 10px;
        }


        .invitation-header p {
            position: relative;

            z-index: 2;

            margin:
                17px auto 0;

            max-width: 570px;

            color: #777777;

            font-size: 10px;

            line-height: 1.8;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .content {
            padding: 25px;
        }


        /*
        |--------------------------------------------------------------------------
        | EVENT INFORMATION
        |--------------------------------------------------------------------------
        */

        .event-info {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 9px;

            margin-bottom: 20px;
        }


        .info-box {
            position: relative;

            min-height: 68px;

            padding:
                13px 14px;

            background: #fffdfa;

            border:
                1px solid
                #eee5dc;

            border-radius: 8px;
        }


        .info-box span {
            display: block;

            margin-bottom: 5px;

            color: #9a9188;

            font-size: 9px;

            font-weight: 600;

            letter-spacing: .5px;

            text-transform: uppercase;
        }


        .info-box strong {
            display: block;

            color: #242424;

            font-size: 10px;

            font-weight: 600;

            line-height: 1.5;
        }


        /*
        |--------------------------------------------------------------------------
        | CAPACITY
        |--------------------------------------------------------------------------
        */

        .capacity {
            margin-bottom: 20px;

            padding: 16px;

            text-align: center;

            background: #fcf8f2;

            border:
                1px solid
                rgba(
                    185,
                    148,
                    69,
                    0.14
                );

            border-radius: 8px;
        }


        .capacity-title {
            margin-bottom: 4px;

            color: #777777;

            font-size: 9px;

            font-weight: 600;

            letter-spacing: 1px;

            text-transform: uppercase;
        }


        .capacity strong {
            display: block;

            color: #9d7a32;

            font-size: 22px;
        }


        .capacity-details {
            color: #777777;

            font-size: 10px;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {
            margin-bottom: 15px;

            padding: 13px;

            border-radius: 8px;

            font-size: 10px;

            line-height: 1.7;
        }


        .alert-error {
            color: #9c4141;

            background: #fff0f0;

            border:
                1px solid
                #f0d0d0;
        }


        /*
        |--------------------------------------------------------------------------
        | REGISTRATION FORM
        |--------------------------------------------------------------------------
        */

        .registration {
            position: relative;

            overflow: hidden;

            padding: 24px;

            background: #ffffff;

            border:
                1px solid
                #e9dfd4;

            border-radius: 10px;

            box-shadow:
                0 10px 25px
                rgba(
                    24,
                    24,
                    24,
                    0.035
                );
        }


        .registration::before {
            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 3px;

            background:
                linear-gradient(
                    90deg,
                    #b99445,
                    #e5b99d,
                    #9d7a32
                );
        }


        .registration h2 {
            margin:
                0 0 5px;

            color: #181818;

            font-size: 17px;

            font-weight: 600;
        }


        .registration > p {
            margin:
                0 0 18px;

            color: #777777;

            font-size: 10px;

            line-height: 1.7;
        }


        .form-group {
            margin-bottom: 13px;
        }


        .form-group label {
            display: block;

            margin-bottom: 5px;

            color: #333333;

            font-size: 10px;

            font-weight: 600;
        }


        .form-group input {
            width: 100%;

            height: 43px;

            padding:
                0 12px;

            outline: none;

            background: #ffffff;

            border:
                1px solid
                #ded5cb;

            border-radius: 7px;

            color: #333333;

            font-family:
                "Poppins",
                sans-serif;

            font-size: 10px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .form-group input::placeholder {
            color: #aaa29a;

            font-size: 10px;
        }


        .form-group input:focus {
            border-color: #b99445;

            background: #fffdfa;

            box-shadow:
                0 0 0 3px
                rgba(
                    185,
                    148,
                    69,
                    0.10
                );
        }


        .submit-button {
            width: 100%;

            min-height: 45px;

            margin-top: 5px;

            border: 0;

            border-radius: 7px;

            background:
                linear-gradient(
                    135deg,
                    #b99445,
                    #9d7a32
                );

            color: #ffffff;

            font-family:
                "Poppins",
                sans-serif;

            font-size: 10px;

            font-weight: 600;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .submit-button:hover {
            transform:
                translateY(-1px);

            box-shadow:
                0 8px 18px
                rgba(
                    157,
                    122,
                    50,
                    0.18
                );
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        .success {
            padding: 25px;

            text-align: center;

            background: #eef8f1;

            border:
                1px solid
                #cce6d3;

            border-radius: 9px;
        }


        .success-icon {
            width: 52px;

            height: 52px;

            margin:
                0 auto 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #d8efdf;

            border-radius: 50%;

            color: #367653;

            font-size: 24px;

            font-weight: 700;
        }


        .success h2 {
            margin:
                0 0 8px;

            color: #24583a;

            font-size: 19px;
        }


        .success p {
            margin:
                0 auto;

            max-width: 540px;

            color: #5d7765;

            font-size: 10px;

            line-height: 1.7;
        }


        .guest-qr {
            width: 240px;

            max-width: 100%;

            margin:
                20px auto 12px;

            padding: 10px;

            background: #ffffff;

            border:
                1px solid
                #dfe8e1;

            border-radius: 8px;

            box-shadow:
                0 8px 20px
                rgba(
                    0,
                    0,
                    0,
                    0.07
                );
        }


        .guest-qr img {
            display: block;

            width: 100%;
        }


        .email-success {
            margin-top: 16px;

            padding: 12px;

            color: #367653;

            background: #ffffff;

            border:
                1px solid
                #d3e6d8;

            border-radius: 7px;

            font-size: 10px;

            line-height: 1.7;
        }


        .email-warning {
            margin-top: 16px;

            padding: 12px;

            text-align: left;

            color: #8a6121;

            background: #fff8e8;

            border:
                1px solid
                #eeddb5;

            border-radius: 7px;

            font-size: 10px;

            line-height: 1.7;
        }


        .email-error-details {
            margin-top: 8px;

            padding: 9px;

            overflow-wrap: anywhere;

            background: #fffdf8;

            border-radius: 5px;

            color: #795f36;

            font-family:
                monospace;

            font-size: 9px;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer-note {
            margin-top: 20px;

            color: #aaa;

            font-size: 9px;

            line-height: 1.6;

            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 600px
        ) {

            .page {
                width:
                    calc(100% - 14px);

                padding-top: 7px;
            }


            .invitation-card {
                border-radius: 12px;
            }


            .content {
                padding: 16px;
            }


            .invitation-header {
                min-height: 350px;

                padding:
                    38px 18px;
            }


            .company-logo {
                width: 68px;

                height: 68px;
            }


            .company-name {
                font-size: 9px;

                letter-spacing: 2px;
            }


            .invitation-header h1 {
                font-size:
                    clamp(
                        30px,
                        10vw,
                        42px
                    );
            }


            .hero-event-meta {
                gap: 6px;
            }


            .hero-meta-item {
                font-size: 9px;
            }


            .invitation-header p {
                font-size: 10px;
            }


            .event-info {
                grid-template-columns: 1fr;
            }


            .registration {
                padding: 18px;
            }

        }

    </style>

</head>


<body>


<div class="page">

    <div class="invitation-card">


        <!-- =====================================================
             HERO
        ====================================================== -->

        <div class="invitation-header">


            <img
                src="../images/logo.png"
                alt="Event Solutions by S.H.E."
                class="company-logo"
            >


            <div class="company-name">
                Event Solutions by S.H.E.
            </div>


            <?php if ($guestMode): ?>

                <span class="eyebrow">
                    GUEST CONFIRMATION
                </span>

            <?php else: ?>

                <span class="eyebrow">
                    YOU ARE INVITED
                </span>

            <?php endif; ?>


            <h1>
                <?= $safeEventName ?>
            </h1>


            <div class="hero-event-meta">


                <div class="hero-meta-item">

                    <i class="fa-solid fa-calendar-days"></i>

                    <span>
                        <?= $safeDate ?>
                    </span>

                </div>


                <div class="hero-meta-item">

                    <i class="fa-solid fa-clock"></i>

                    <span>
                        <?= $safeTime ?>
                    </span>

                </div>


                <div class="hero-meta-item">

                    <i class="fa-solid fa-location-dot"></i>

                    <span>
                        <?= $safeVenue ?>
                    </span>

                </div>


            </div>


            <?php if ($guestMode): ?>

                <p>
                    Your guest registration and event
                    information are shown below.
                </p>

            <?php else: ?>

                <p>
                    We would be delighted to have you
                    join us for this special event.
                </p>

            <?php endif; ?>

        </div>


        <div class="content">


            <!-- =================================================
                 EVENT INFORMATION
            ================================================== -->

            <div class="event-info">


                <div class="info-box">

                    <span>
                        Event Type
                    </span>

                    <strong>
                        <?= $safeEventType ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Date
                    </span>

                    <strong>
                        <?= $safeDate ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Time
                    </span>

                    <strong>
                        <?= $safeTime ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Venue
                    </span>

                    <strong>
                        <?= $safeVenue ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Package
                    </span>

                    <strong>
                        <?= $safePackage ?>
                    </strong>

                </div>


            </div>


            <!-- =================================================
                 CAPACITY
            ================================================== -->

            <div class="capacity">

                <div class="capacity-title">
                    Guest Capacity
                </div>


                <strong>
                    <?= number_format(
                        $guestCapacity
                    ) ?>
                </strong>


                <div class="capacity-details">

                    Registered:
                    <?= number_format(
                        $confirmedGuests
                    ) ?>

                    &nbsp; • &nbsp;

                    Remaining:
                    <?= number_format(
                        $remainingGuests
                    ) ?>

                </div>

            </div>


            <!-- =================================================
                 EVENT TOKEN MODE
            ================================================== -->

            <?php if (!$guestMode): ?>


                <?php if (
                    !empty($successMessage)
                ): ?>


                    <!-- =========================================
                         REGISTRATION SUCCESS
                    ========================================== -->

                    <div class="success">


                        <div class="success-icon">
                            ✓
                        </div>


                        <h2>
                            Registration Confirmed
                        </h2>


                        <p>

                            Thank you,
                            <strong>
                                <?= htmlspecialchars(
                                    $registeredGuestName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>.

                            Your guest registration has
                            been successfully recorded.

                        </p>


                        <?php if (
                            !empty($guestQrCode)
                        ): ?>


                            <div class="guest-qr">

                                <img
                                    src="<?= htmlspecialchars(
                                        $guestQrCode,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    alt="Your guest QR code"
                                >

                            </div>


                            <p>

                                Please present this QR code
                                at the event for check-in.

                            </p>


                            <?php if (
                                $emailWasSent
                            ): ?>

                                <div class="email-success">

                                    ✓ Confirmation email sent successfully to

                                    <strong>
                                        <?= htmlspecialchars(
                                            $registeredGuestEmail,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>.

                                    <br>

                                    Please check your inbox,
                                    Spam folder, or Promotions folder.

                                </div>

                            <?php else: ?>

                                <div class="email-warning">

                                    <strong>
                                        Registration saved successfully.
                                    </strong>

                                    <br><br>

                                    However, the confirmation email
                                    could not be sent.

                                    <div class="email-error-details">

                                        <?= htmlspecialchars(
                                            $emailErrorMessage,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </div>

                                    <br>

                                    Please save your QR code
                                    or contact the event coordinator.

                                </div>

                            <?php endif; ?>


                        <?php endif; ?>


                    </div>


                <?php elseif (
                    !empty($errorMessage)
                ): ?>


                    <div class="alert alert-error">

                        <?= htmlspecialchars(
                            $errorMessage,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>


                <?php elseif (
                    $remainingGuests > 0
                ): ?>


                    <!-- =========================================
                         REGISTRATION FORM
                    ========================================== -->

                    <div class="registration">


                        <h2>
                            Confirm Your Attendance
                        </h2>


                        <p>
                            Enter your name and email address
                            to register for this event.
                        </p>


                        <form
                            method="POST"
                            action=""
                        >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >


                            <div class="form-group">

                                <label
                                    for="guest_name"
                                >
                                    Full Name
                                </label>


                                <input
                                    type="text"
                                    id="guest_name"
                                    name="guest_name"
                                    maxlength="150"
                                    autocomplete="name"
                                    placeholder="Enter your full name"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label
                                    for="guest_email"
                                >
                                    Email Address
                                </label>


                                <input
                                    type="email"
                                    id="guest_email"
                                    name="guest_email"
                                    maxlength="190"
                                    autocomplete="email"
                                    placeholder="Enter your email address"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                class="submit-button"
                            >
                                Confirm Attendance
                            </button>


                        </form>


                    </div>


                <?php else: ?>


                    <div class="alert alert-error">

                        Guest registration is currently full.

                        The maximum number of guests
                        has already been reached.

                    </div>


                <?php endif; ?>


            <?php else: ?>


                <!-- =================================================
                     GUEST TOKEN MODE
                ================================================== -->

                <?php

                $guestStatus =
                    strtolower(
                        trim(
                            $guestRecord["status"] ??
                            ""
                        )
                    );

                $guestName =
                    $guestRecord["guest_name"] ??
                    "";

                $guestEmail =
                    $guestRecord["guest_email"] ??
                    "";

                $guestQr =
                    $guestRecord["qrcode_image"] ??
                    "";

                ?>


                <div class="success">


                    <?php if (
                        $guestStatus === "confirmed"
                    ): ?>

                        <div class="success-icon">
                            ✓
                        </div>


                        <h2>
                            Registration Confirmed
                        </h2>


                        <p>

                            Welcome,

                            <strong>
                                <?= htmlspecialchars(
                                    $guestName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>.

                            Your registration for this event
                            has already been confirmed.

                        </p>


                        <?php if (
                            !empty($guestQr)
                        ): ?>


                            <div class="guest-qr">

                                <img
                                    src="<?= htmlspecialchars(
                                        $guestQr,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    alt="Your guest QR code"
                                >

                            </div>


                            <p>

                                Present this QR code at the event
                                for check-in.

                            </p>


                            <div class="email-success">

                                Your registered email:

                                <strong>
                                    <?= htmlspecialchars(
                                        $guestEmail,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>

                            </div>


                        <?php endif; ?>


                    <?php else: ?>


                        <div
                            class="success-icon"
                            style="
                                background:#fff3d8;
                                color:#9d7a32;
                            "
                        >
                            !
                        </div>


                        <h2
                            style="
                                color:#8a6121;
                            "
                        >
                            Registration Pending
                        </h2>


                        <p>

                            Welcome,

                            <strong>
                                <?= htmlspecialchars(
                                    $guestName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>.

                            Your guest registration has been
                            successfully submitted and is
                            currently pending confirmation.

                        </p>


                        <div class="email-success">

                            Your registered email:

                            <strong>
                                <?= htmlspecialchars(
                                    $guestEmail,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                        </div>


                        <div
                            class="email-warning"
                            style="
                                text-align:center;
                            "
                        >

                            Please wait for the event coordinator
                            to confirm your attendance.

                            <br>

                            Your QR code will be used for
                            event check-in after confirmation.

                        </div>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="footer-note">

                Event Solutions by S.H.E.

                <br>

                Please keep your confirmation QR code
                available for event check-in.

            </div>


        </div>

    </div>

</div>


</body>

</html>