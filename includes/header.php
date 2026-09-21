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
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The database MUST be loaded before notification actions.
|
*/

require_once __DIR__ . "/../config/database.php";


/*
|--------------------------------------------------------------------------
| PROJECT BASE PATH
|--------------------------------------------------------------------------
*/

$projectRoot = realpath(__DIR__ . "/..");
$documentRoot = realpath($_SERVER["DOCUMENT_ROOT"]);

$basePath = "";

if ($projectRoot && $documentRoot) {

    $projectRoot = str_replace("\\", "/", $projectRoot);
    $documentRoot = str_replace("\\", "/", $documentRoot);

    if (strpos($projectRoot, $documentRoot) === 0) {

        $relativePath = substr(
            $projectRoot,
            strlen($documentRoot)
        );

        $relativePath = trim(
            str_replace("\\", "/", $relativePath),
            "/"
        );

        if (!empty($relativePath)) {
            $basePath = "/" . $relativePath;
        }
    }
}


/*
|--------------------------------------------------------------------------
| URL HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists("site_url")) {

    function site_url($path = "")
    {
        global $basePath;

        $path = ltrim($path, "/");

        if (empty($path)) {
            return $basePath . "/";
        }

        return $basePath . "/" . $path;
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN STATUS
|--------------------------------------------------------------------------
*/

$isLoggedIn = isset($_SESSION["user_id"]);

$currentUser = null;


/*
|--------------------------------------------------------------------------
| NOTIFICATION UPDATE ACTION
|--------------------------------------------------------------------------
|
| AJAX actions:
|
| fetch_notifications
| mark_read
| mark_all_read
| archive
|
| IMPORTANT:
| These actions return JSON and EXIT immediately.
| The actual page/header is NOT reloaded.
|
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["notification_action"])
) {

    /*
    |--------------------------------------------------------------------------
    | FORCE JSON RESPONSE
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

    if (!$isLoggedIn) {

        echo json_encode([
            "success" => false,
            "message" => "User is not logged in.",
            "notifications" => []
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    if (!isset($conn) || !$conn) {

        echo json_encode([
            "success" => false,
            "message" => "Database connection is unavailable.",
            "notifications" => []
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | USER ID
    |--------------------------------------------------------------------------
    */

    $user_id = (int) $_SESSION["user_id"];


    /*
    |--------------------------------------------------------------------------
    | ACTION
    |--------------------------------------------------------------------------
    */

    $notificationAction = trim(
        $_POST["notification_action"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION ID
    |--------------------------------------------------------------------------
    */

    $notificationId = isset($_POST["notification_id"])
        ? (int) $_POST["notification_id"]
        : 0;


    /*
    |--------------------------------------------------------------------------
    | FETCH NOTIFICATIONS
    |--------------------------------------------------------------------------
    |
    | Used by header.js every few seconds.
    |
    */

    if ($notificationAction === "fetch_notifications") {

        $stmt = $conn->prepare("
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
            ORDER BY created_at DESC
            LIMIT 20
        ");


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "action" => "fetch_notifications",
                "user_id" => $user_id,
                "notifications" => [],
                "message" => "Database prepare failed.",
                "error" => $conn->error
            ]);

            exit;
        }


        $stmt->bind_param(
            "i",
            $user_id
        );


        if (!$stmt->execute()) {

            echo json_encode([
                "success" => false,
                "action" => "fetch_notifications",
                "user_id" => $user_id,
                "notifications" => [],
                "message" => "Database execute failed.",
                "error" => $stmt->error
            ]);

            $stmt->close();

            exit;
        }


        $result = $stmt->get_result();

        $realtimeNotifications = [];


        if ($result) {

            while ($row = $result->fetch_assoc()) {

                $realtimeNotifications[] = [
                    "id" => (int) $row["id"],

                    "title" => $row["title"],

                    "message" => $row["message"],

                    "type" => $row["type"],

                    "is_read" => (int) $row["is_read"],

                    "status" => $row["status"],

                    "created_at" => $row["created_at"]
                ];
            }
        }


        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | RETURN JSON
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            "success" => true,
            "action" => "fetch_notifications",
            "user_id" => $user_id,
            "notifications" => $realtimeNotifications
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | MARK SINGLE NOTIFICATION AS READ
    |--------------------------------------------------------------------------
    */

    if (
        $notificationAction === "mark_read"
        && $notificationId > 0
    ) {

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
            AND user_id = ?
            AND status = 'active'
        ");


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "action" => "mark_read",
                "notification_id" => $notificationId,
                "message" => "Unable to prepare mark-read query.",
                "error" => $conn->error
            ]);

            exit;
        }


        $stmt->bind_param(
            "ii",
            $notificationId,
            $user_id
        );


        $success = $stmt->execute();

        $affectedRows = $stmt->affected_rows;

        $error = $stmt->error;


        $stmt->close();


        echo json_encode([
            "success" => $success,
            "action" => "mark_read",
            "notification_id" => $notificationId,
            "affected_rows" => $affectedRows,
            "message" => $success
                ? "Notification marked as read."
                : "Unable to mark notification as read.",
            "error" => $error
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ALL NOTIFICATIONS AS READ
    |--------------------------------------------------------------------------
    */

    if ($notificationAction === "mark_all_read") {

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
            AND status = 'active'
            AND is_read = 0
        ");


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "action" => "mark_all_read",
                "message" => "Unable to prepare mark-all-read query.",
                "error" => $conn->error
            ]);

            exit;
        }


        $stmt->bind_param(
            "i",
            $user_id
        );


        $success = $stmt->execute();

        $affectedRows = $stmt->affected_rows;

        $error = $stmt->error;


        $stmt->close();


        echo json_encode([
            "success" => $success,
            "action" => "mark_all_read",
            "affected_rows" => $affectedRows,
            "message" => $success
                ? "All notifications marked as read."
                : "Unable to mark all notifications as read.",
            "error" => $error
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE NOTIFICATION
    |--------------------------------------------------------------------------
    |
    | The trash icon does NOT permanently delete the notification.
    |
    | It changes:
    |
    | status = active
    |
    | into:
    |
    | status = archived
    |
    */

    if (
        $notificationAction === "archive"
        && $notificationId > 0
    ) {

        $stmt = $conn->prepare("
            UPDATE notifications
            SET status = 'archived'
            WHERE id = ?
            AND user_id = ?
            AND status = 'active'
        ");


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "action" => "archive",
                "notification_id" => $notificationId,
                "message" => "Unable to prepare archive query.",
                "error" => $conn->error
            ]);

            exit;
        }


        $stmt->bind_param(
            "ii",
            $notificationId,
            $user_id
        );


        $success = $stmt->execute();

        $affectedRows = $stmt->affected_rows;

        $error = $stmt->error;


        $stmt->close();


        echo json_encode([
            "success" => $success,
            "action" => "archive",
            "notification_id" => $notificationId,
            "affected_rows" => $affectedRows,
            "message" => $success
                ? "Notification archived."
                : "Unable to archive notification.",
            "error" => $error
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INVALID ACTION
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => false,
        "action" => $notificationAction,
        "message" => "Invalid notification action.",
        "notifications" => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

if ($isLoggedIn && isset($conn) && $conn) {

    $user_id = (int) $_SESSION["user_id"];


    $stmt = $conn->prepare("
        SELECT
            id,
            fullname,
            email,
            password,
            profile_image,
            role
        FROM users
        WHERE id = ?
        LIMIT 1
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );


        $stmt->execute();


        $result = $stmt->get_result();


        if ($result && $result->num_rows === 1) {

            $currentUser = $result->fetch_assoc();
        }


        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| USER DISPLAY INFORMATION
|--------------------------------------------------------------------------
*/

$displayName = "User";
$displayEmail = "";
$displayRole = "customer";
$profileImage = "";
$firstLetter = "U";


if ($currentUser) {

    $displayName = $currentUser["fullname"] ?? "User";

    $displayEmail = $currentUser["email"] ?? "";

    $displayRole = $currentUser["role"] ?? "customer";


    /*
    |--------------------------------------------------------------------------
    | PROFILE IMAGE
    |--------------------------------------------------------------------------
    */

    $profileImage = trim(
        $currentUser["profile_image"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | FIRST LETTER
    |--------------------------------------------------------------------------
    */

    if (!empty($displayName)) {

        $firstLetter = strtoupper(
            mb_substr(
                $displayName,
                0,
                1
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notifications = [];


if ($isLoggedIn && isset($conn) && $conn) {

    $user_id = (int) $_SESSION["user_id"];


    $stmt = $conn->prepare("
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
        ORDER BY created_at DESC
        LIMIT 20
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );


        $stmt->execute();


        $result = $stmt->get_result();


        if ($result) {

            while ($row = $result->fetch_assoc()) {

                $notifications[] = [
                    "id" => (int) $row["id"],

                    "title" => $row["title"],

                    "message" => $row["message"],

                    "type" => $row["type"],

                    "time" => date(
                        "M d, Y h:i A",
                        strtotime($row["created_at"])
                    ),

                    "unread" => (
                        (int) $row["is_read"] === 0
                    ),

                    "status" => $row["status"],

                    "created_at" => $row["created_at"]
                ];
            }
        }


        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| NOTIFICATION COUNT
|--------------------------------------------------------------------------
*/

$unreadNotifications = 0;


foreach ($notifications as $notification) {

    if (!empty($notification["unread"])) {

        $unreadNotifications++;
    }
}

?>


<!-- ==========================================================
     BOOTSTRAP ICONS
=========================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet" />


<!-- ==========================================================
     HEADER
=========================================================== -->

<header class="site-header" id="siteHeader">

    <div class="header-container">


        <!-- ==================================================
             LOGO
        =================================================== -->

        <a
            href="<?= htmlspecialchars(site_url("index.php")) ?>"
            class="site-logo"
            aria-label="Event Solutions by S.H.E">

            <img
                src="<?= htmlspecialchars(site_url("images/logo.png")) ?>"
                alt="Event Solutions by S.H.E"
                class="site-logo-image">

        </a>


        <!-- ==================================================
             CENTER NAVIGATION
        =================================================== -->

        <nav
            class="main-nav"
            id="mainNav"
            aria-label="Main navigation">

            <a
                href="<?= htmlspecialchars(site_url("index.php")) ?>"
                class="nav-link">
                HOME
            </a>


            <a
                href="<?= htmlspecialchars(site_url("events.php")) ?>"
                class="nav-link">
                EVENT PACKAGES
            </a>


            <a
                href="<?= htmlspecialchars(site_url("venues.php")) ?>"
                class="nav-link">
                VENUES
            </a>


            <a
                href="<?= htmlspecialchars(site_url("host.php")) ?>"
                class="nav-link">
                HOST/SINGER PACKAGES
            </a>


            <a
                href="<?= htmlspecialchars(site_url("services.php")) ?>"
                class="nav-link">
                SERVICES
            </a>


            <a
                href="<?= htmlspecialchars(site_url("contact.php")) ?>"
                class="nav-link">
                CONTACT
            </a>

        </nav>


        <!-- ==================================================
             RIGHT SIDE ACTIONS
        =================================================== -->

        <div class="header-actions">


            <!-- ==================================================
                 DARK MODE BUTTON
            =================================================== -->

            <button
                type="button"
                class="theme-toggle"
                id="themeToggle"
                aria-label="Switch to dark mode"
                title="Switch to dark mode">

                <span
                    class="theme-icon theme-icon-sun"
                    aria-hidden="true">
                    <i class="bi bi-brightness-high"></i>
                </span>

                <span
                    class="theme-icon theme-icon-moon"
                    aria-hidden="true">
                    <i class="bi bi-moon"></i>
                </span>

            </button>


            <?php if ($isLoggedIn): ?>


                <!-- ==================================================
                     NOTIFICATION BUTTON
                =================================================== -->

                <div
                    class="notification-wrapper"
                    data-notification-action-url="<?= htmlspecialchars(
                                                        site_url("includes/notification_api.php"),
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>">

                    <button
                        type="button"
                        class="notification-button"
                        id="notificationButton"
                        aria-label="Notifications"
                        aria-expanded="false"
                        aria-haspopup="true"
                        title="Notifications">

                        <span
                            class="notification-bell-icon"
                            aria-hidden="true">
                            <i class="bi bi-bell"></i>
                        </span>


                        <span
                            class="notification-badge"
                            id="notificationBadge"
                            data-notification-count="<?= $unreadNotifications ?>"
                            <?= $unreadNotifications <= 0 ? 'style="display:none;"' : "" ?>>
                            <?= $unreadNotifications > 9
                                ? "9+"
                                : $unreadNotifications ?>
                        </span>

                    </button>


                    <!-- ==================================================
                         NOTIFICATION DROPDOWN
                    =================================================== -->

                    <div
                        class="notification-dropdown"
                        id="notificationDropdown"
                        aria-hidden="true">

                        <div class="notification-header">

                            <div>

                                <h3>
                                    Notifications
                                </h3>

                                <span
                                    id="notificationUnreadText">
                                    <?= $unreadNotifications ?>
                                    unread
                                </span>

                            </div>


                            <button
                                type="button"
                                class="mark-all-read"
                                id="markAllRead"
                                <?= $unreadNotifications <= 0
                                    ? 'style="display:none;"'
                                    : "" ?>>
                                Mark all read
                            </button>

                        </div>


                        <div
                            class="notification-list"
                            id="notificationList">

                            <?php if (!empty($notifications)): ?>


                                <?php foreach ($notifications as $notification): ?>

                                    <div
                                        class="notification-item <?= !empty($notification["unread"]) ? "unread" : "" ?>"
                                        data-notification-id="<?= (int) $notification["id"] ?>"
                                        data-read="<?= !empty($notification["unread"]) ? "0" : "1" ?>"
                                        data-status="<?= htmlspecialchars(
                                                            $notification["status"],
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>"
                                        data-created-at="<?= htmlspecialchars(
                                                                $notification["created_at"],
                                                                ENT_QUOTES,
                                                                "UTF-8"
                                                            ) ?>">


                                        <!-- NOTIFICATION ICON -->

                                        <div
                                            class="notification-icon <?= htmlspecialchars(
                                                                            $notification["type"],
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>">

                                            <?php

                                            switch ($notification["type"]) {

                                                case "event":

                                                    echo '
                                                        <span
                                                            class="notification-icon-symbol"
                                                            aria-hidden="true"
                                                        >
                                                            <i class="bi bi-megaphone"></i>
                                                        </span>
                                                    ';

                                                    break;


                                                case "booking":

                                                    echo '
                                                        <span
                                                            class="notification-icon-symbol"
                                                            aria-hidden="true"
                                                        >
                                                            <i class="bi bi-check-circle"></i>
                                                        </span>
                                                    ';

                                                    break;


                                                case "message":

                                                    echo '
                                                        <span
                                                            class="notification-icon-symbol"
                                                            aria-hidden="true"
                                                        >
                                                            <i class="bi bi-chat"></i>
                                                        </span>
                                                    ';

                                                    break;


                                                case "welcome":

                                                    echo '
                                                        <span
                                                            class="notification-icon-symbol"
                                                            aria-hidden="true"
                                                        >
                                                            <i class="bi bi-megaphone"></i>
                                                        </span>
                                                    ';

                                                    break;


                                                default:

                                                    echo '
                                                        <span
                                                            class="notification-icon-symbol"
                                                            aria-hidden="true"
                                                        >
                                                            <i class="bi bi-bell"></i>
                                                        </span>
                                                    ';

                                                    break;
                                            }

                                            ?>

                                        </div>


                                        <!-- NOTIFICATION CONTENT -->

                                        <div class="notification-content">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $notification["title"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>


                                            <p>
                                                <?= htmlspecialchars(
                                                    $notification["message"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>


                                            <small>
                                                <?= htmlspecialchars(
                                                    $notification["created_at"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </small>

                                        </div>


                                        <!-- UNREAD DOT -->

                                        <?php if (!empty($notification["unread"])): ?>

                                            <span
                                                class="notification-unread-dot"
                                                aria-label="Unread notification"></span>

                                        <?php endif; ?>


                                        <!-- ARCHIVE BUTTON -->

                                        <button
                                            type="button"
                                            class="notification-delete"
                                            data-notification-id="<?= (int) $notification["id"] ?>"
                                            aria-label="Archive notification"
                                            title="Archive notification">

                                            <i
                                                class="bi bi-trash3"
                                                aria-hidden="true"></i>

                                        </button>

                                    </div>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <div class="notifications-empty">

                                    <div class="empty-notification-icon">

                                        <span>
                                            <i class="bi bi-bell"></i>
                                        </span>

                                    </div>


                                    <strong>
                                        No notifications
                                    </strong>


                                    <p>
                                        You're all caught up.
                                    </p>

                                </div>


                            <?php endif; ?>

                        </div>


                        <div class="notification-footer">

                            <a
                                href="<?= htmlspecialchars(
                                            site_url("my-events.php")
                                        ) ?>">
                                View My Events
                            </a>

                        </div>

                    </div>

                </div>


            <?php endif; ?>


            <?php if (!$isLoggedIn): ?>


                <!-- ==================================================
                     LOGIN
                =================================================== -->

                <a
                    href="<?= htmlspecialchars(
                                site_url("auth/login.php")
                            ) ?>"
                    class="login-link">
                    Login
                </a>


                <!-- ==================================================
                     SIGN UP
                =================================================== -->

                <a
                    href="<?= htmlspecialchars(
                                site_url("auth/signup.php")
                            ) ?>"
                    class="signup-button">
                    Sign Up
                </a>


            <?php else: ?>


                <!-- ==================================================
                     USER MENU
                =================================================== -->

                <div class="user-menu">


                    <!-- ==================================================
                         USER BUTTON
                    =================================================== -->

                    <button
                        type="button"
                        class="user-button"
                        id="userButton"
                        aria-expanded="false"
                        aria-haspopup="true">

                        <span class="user-avatar">

                            <?php if (!empty($profileImage)): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                                site_url($profileImage),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    alt="<?= htmlspecialchars(
                                                $displayName,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    class="user-avatar-image">

                            <?php else: ?>

                                <?= htmlspecialchars(
                                    $firstLetter,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            <?php endif; ?>

                        </span>


                        <span class="user-name">
                            <?= htmlspecialchars(
                                $displayName,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>


                        <span class="user-arrow">

                            <i class="bi bi-caret-down"></i>

                        </span>

                    </button>


                    <!-- ==================================================
                         USER DROPDOWN
                    =================================================== -->

                    <div
                        class="user-dropdown"
                        id="userDropdown"
                        aria-hidden="true">


                        <!-- USER INFORMATION -->

                        <div class="dropdown-user-info">

                            <strong>
                                <?= htmlspecialchars(
                                    $displayName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>


                            <?php if (!empty($displayEmail)): ?>

                                <small>
                                    <?= htmlspecialchars(
                                        $displayEmail,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </small>

                            <?php endif; ?>


                            <span class="user-role">

                                <?= htmlspecialchars(
                                    ucfirst($displayRole),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>


                        <div class="dropdown-divider"></div>


                        <!-- MY PROFILE -->

                        <a
                            href="<?= htmlspecialchars(
                                        site_url("profile.php")
                                    ) ?>"
                            class="dropdown-link">

                            <span
                                class="dropdown-link-icon"
                                aria-hidden="true">
                                <i class="bi bi-person"></i>
                            </span>

                            <span>
                                My Profile
                            </span>

                        </a>


                        <!-- MY EVENTS -->

                        <a
                            href="<?= htmlspecialchars(
                                        site_url("my-event.php")
                                    ) ?>"
                            class="dropdown-link">

                            <span
                                class="dropdown-link-icon"
                                aria-hidden="true">
                                <i class="bi bi-calendar-event"></i>
                            </span>

                            <span>
                                My Events
                            </span>

                        </a>


                        <!-- MY HOST -->

                        <a
                            href="<?= htmlspecialchars(
                                        site_url("my-host.php")
                                    ) ?>"
                            class="dropdown-link">

                            <span
                                class="dropdown-link-icon"
                                aria-hidden="true">
                                <i class="bi bi-mic-fill"></i>
                            </span>

                            <span>
                                My Singing | Host
                            </span>

                        </a>


                        <?php if ($displayRole === "admin"): ?>


                            <div class="dropdown-divider"></div>


                            <a
                                href="<?= htmlspecialchars(
                                            site_url(
                                                "admin8765/admin_dashboard.php"
                                            )
                                        ) ?>"
                                class="dropdown-link admin-link">

                                <span
                                    class="dropdown-link-icon"
                                    aria-hidden="true">
                                    <i class="bi bi-gear"></i>
                                </span>

                                <span>
                                    Admin Dashboard
                                </span>

                            </a>


                        <?php endif; ?>


                        <div class="dropdown-divider"></div>


                        <!-- LOGOUT -->

                        <a
                            href="<?= htmlspecialchars(
                                        site_url("auth/logout.php")
                                    ) ?>"
                            class="dropdown-link logout-link">

                            <span
                                class="dropdown-link-icon"
                                aria-hidden="true">
                                <i class="bi bi-box-arrow-right"></i>
                            </span>

                            <span>
                                Logout
                            </span>

                        </a>


                    </div>

                </div>


            <?php endif; ?>


        </div>


        <!-- ==========================================================
             MOBILE MENU BUTTON
        =========================================================== -->

        <button
            type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="mainNav">

            <span></span>
            <span></span>
            <span></span>

        </button>


    </div>

</header>
