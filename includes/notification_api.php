<?php

/*
|--------------------------------------------------------------------------
| NOTIFICATION API
|--------------------------------------------------------------------------
| Handles:
|
| fetch_notifications
| mark_read
| mark_all_read
| archive
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


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

header("Content-Type: application/json; charset=UTF-8");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "User is not logged in.",
        "notifications" => []
    ]);

    exit;
}


$userId =
    (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| ONLY POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method.",
        "notifications" => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACTION
|--------------------------------------------------------------------------
*/

$action =
    trim(
        $_POST["notification_action"]
        ?? ""
    );


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function notificationResponse(
    bool $success,
    string $message = "",
    array $notifications = [],
    array $extra = []
): void {

    echo json_encode(
        array_merge(
            [
                "success" =>
                    $success,

                "message" =>
                    $message,

                "notifications" =>
                    $notifications
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    http_response_code(500);

    notificationResponse(
        false,
        "Database connection is unavailable."
    );
}


/*
|--------------------------------------------------------------------------
| FETCH NOTIFICATIONS
|--------------------------------------------------------------------------
*/

function getUserNotifications(
    mysqli $conn,
    int $userId
): array {

    $notifications = [];


    $sql = "
        SELECT
            id,
            title,
            message,
            type,
            is_read,
            status,
            created_at
        FROM notifications
        WHERE user_id = ?
          AND status = 'active'
        ORDER BY created_at DESC, id DESC
        LIMIT 50
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        throw new Exception(
            "Unable to prepare notification query: "
            . $conn->error
        );
    }


    $stmt->bind_param(
        "i",
        $userId
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        throw new Exception(
            "Unable to execute notification query: "
            . $error
        );
    }


    $result =
        $stmt->get_result();


    while (
        $row =
            $result->fetch_assoc()
    ) {

        $notifications[] = [

            "id" =>
                (int) $row["id"],

            "title" =>
                (string) (
                    $row["title"]
                    ?? ""
                ),

            "message" =>
                (string) (
                    $row["message"]
                    ?? ""
                ),

            "type" =>
                (string) (
                    $row["type"]
                    ?? "general"
                ),

            "is_read" =>
                (int) (
                    $row["is_read"]
                    ?? 0
                ),

            "status" =>
                (string) (
                    $row["status"]
                    ?? "active"
                ),

            "created_at" =>
                (string) (
                    $row["created_at"]
                    ?? ""
                )
        ];
    }


    $stmt->close();


    return $notifications;
}


/*
|--------------------------------------------------------------------------
| FETCH NOTIFICATIONS
|--------------------------------------------------------------------------
*/

if (
    $action ===
    "fetch_notifications"
) {

    try {

        $notifications =
            getUserNotifications(
                $conn,
                $userId
            );


        notificationResponse(
            true,
            "Notifications loaded.",
            $notifications,
            [
                "user_id" =>
                    $userId
            ]
        );


    } catch (
        Throwable $e
    ) {

        http_response_code(500);

        notificationResponse(
            false,
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| NOTIFICATION ID
|--------------------------------------------------------------------------
*/

$notificationId =
    (int) (
        $_POST["notification_id"]
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| MARK ONE AS READ
|--------------------------------------------------------------------------
*/

if (
    $action ===
    "mark_read"
) {

    if (
        $notificationId <= 0
    ) {

        notificationResponse(
            false,
            "Invalid notification ID."
        );
    }


    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
          AND user_id = ?
          AND status = 'active'
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to prepare mark-read query: "
            . $conn->error
        );
    }


    $stmt->bind_param(
        "ii",
        $notificationId,
        $userId
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to mark notification as read: "
            . $error
        );
    }


    $affectedRows =
        $stmt->affected_rows;


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | RETURN CURRENT NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    try {

        $notifications =
            getUserNotifications(
                $conn,
                $userId
            );


        notificationResponse(
            true,
            "Notification marked as read.",
            $notifications,
            [
                "action" =>
                    "mark_read",

                "notification_id" =>
                    $notificationId,

                "affected_rows" =>
                    $affectedRows
            ]
        );


    } catch (
        Throwable $e
    ) {

        http_response_code(500);

        notificationResponse(
            false,
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| MARK ALL AS READ
|--------------------------------------------------------------------------
*/

if (
    $action ===
    "mark_all_read"
) {

    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
          AND status = 'active'
          AND is_read = 0
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to prepare mark-all-read query: "
            . $conn->error
        );
    }


    $stmt->bind_param(
        "i",
        $userId
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to mark notifications as read: "
            . $error
        );
    }


    $affectedRows =
        $stmt->affected_rows;


    $stmt->close();


    try {

        $notifications =
            getUserNotifications(
                $conn,
                $userId
            );


        notificationResponse(
            true,
            "All notifications marked as read.",
            $notifications,
            [
                "action" =>
                    "mark_all_read",

                "affected_rows" =>
                    $affectedRows
            ]
        );


    } catch (
        Throwable $e
    ) {

        http_response_code(500);

        notificationResponse(
            false,
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| ARCHIVE NOTIFICATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| This does NOT physically DELETE the notification.
| It changes status from active → archived.
|--------------------------------------------------------------------------
*/

if (
    $action ===
    "archive"
) {

    if (
        $notificationId <= 0
    ) {

        notificationResponse(
            false,
            "Invalid notification ID."
        );
    }


    $sql = "
        UPDATE notifications
        SET status = 'archived'
        WHERE id = ?
          AND user_id = ?
          AND status = 'active'
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to prepare archive query: "
            . $conn->error
        );
    }


    $stmt->bind_param(
        "ii",
        $notificationId,
        $userId
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        http_response_code(500);

        notificationResponse(
            false,
            "Unable to archive notification: "
            . $error
        );
    }


    $affectedRows =
        $stmt->affected_rows;


    $stmt->close();


    try {

        $notifications =
            getUserNotifications(
                $conn,
                $userId
            );


        notificationResponse(
            true,
            "Notification archived.",
            $notifications,
            [
                "action" =>
                    "archive",

                "notification_id" =>
                    $notificationId,

                "affected_rows" =>
                    $affectedRows
            ]
        );


    } catch (
        Throwable $e
    ) {

        http_response_code(500);

        notificationResponse(
            false,
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| INVALID ACTION
|--------------------------------------------------------------------------
*/

http_response_code(400);

notificationResponse(
    false,
    "Invalid notification action."
);