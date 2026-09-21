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
| ADMIN ACCESS CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
) {
    header("Location: ../../auth/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminName =
    $_SESSION["fullname"]
    ?? $_SESSION["name"]
    ?? $_SESSION["username"]
    ?? "Admin";

$adminEmail =
    $_SESSION["email"]
    ?? "";

$adminRole =
    $_SESSION["role"]
    ?? "admin";


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Get the profile image directly from the users table.
|
*/

if (!isset($conn)) {
    $databaseFile = __DIR__ . "/../../config/database.php";

    if (file_exists($databaseFile)) {
        require_once $databaseFile;
    }
}


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE
|--------------------------------------------------------------------------
*/

$adminProfileImage = $_SESSION["profile_image"] ?? "";


/*
|--------------------------------------------------------------------------
| GET ADMIN PROFILE IMAGE FROM DATABASE
|--------------------------------------------------------------------------
*/

if (
    isset($conn) &&
    $conn instanceof mysqli &&
    !empty($_SESSION["user_id"])
) {

    $adminUserId = (int) $_SESSION["user_id"];

    $profileStmt = $conn->prepare("
        SELECT
            profile_image
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($profileStmt) {

        $profileStmt->bind_param(
            "i",
            $adminUserId
        );

        $profileStmt->execute();

        $profileResult = $profileStmt->get_result();

        if (
            $profileResult &&
            $profileResult->num_rows === 1
        ) {

            $profileRow =
                $profileResult->fetch_assoc();

            $adminProfileImage =
                trim(
                    (string) (
                        $profileRow["profile_image"]
                        ?? ""
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | UPDATE SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION["profile_image"] =
                $adminProfileImage;
        }

        $profileStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE PATH
|--------------------------------------------------------------------------
*/

function getAdminProfileImageUrl($profileImage)
{
    $profileImage =
        trim(
            (string) $profileImage
        );

    if ($profileImage === "") {
        return "";
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETE HTTP / HTTPS URL
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/^https?:\/\//i',
            $profileImage
        )
    ) {

        return $profileImage;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE PATH
    |--------------------------------------------------------------------------
    */

    $profileImage =
        str_replace(
            "\\",
            "/",
            $profileImage
        );


    /*
    |--------------------------------------------------------------------------
    | REMOVE LEADING SLASH
    |--------------------------------------------------------------------------
    */

    $profileImage =
        ltrim(
            $profileImage,
            "/"
        );


    /*
    |--------------------------------------------------------------------------
    | images/profiles/example.jpg
    |--------------------------------------------------------------------------
    */

    if (
        strpos(
            $profileImage,
            "images/"
        ) === 0
    ) {

        return "../" . $profileImage;
    }


    /*
    |--------------------------------------------------------------------------
    | profiles/example.jpg
    |--------------------------------------------------------------------------
    */

    if (
        strpos(
            $profileImage,
            "profiles/"
        ) === 0
    ) {

        return "../images/" . $profileImage;
    }


    /*
    |--------------------------------------------------------------------------
    | ONLY FILE NAME
    |--------------------------------------------------------------------------
    |
    | example.jpg
    |
    */

    return "../images/profiles/" .
        basename($profileImage);
}


$adminProfileImageUrl =
    getAdminProfileImageUrl(
        $adminProfileImage
    );


/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        $_SERVER["PHP_SELF"]
    );


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle =
    $pageTitle
    ?? "Administrator";

$pageSection =
    $pageSection
    ?? "Administrator";

$pageHeading =
    $pageHeading
    ?? $pageTitle;


/*
|--------------------------------------------------------------------------
| ADMIN PAGE CSS
|--------------------------------------------------------------------------
|
| Example:
| $pageAdminCss = "admin_dashboard.css";
|
*/

$pageAdminCss =
    $pageAdminCss
    ?? "";


/*
|--------------------------------------------------------------------------
| ADMIN PAGE JAVASCRIPT
|--------------------------------------------------------------------------
|
| Example:
| $pageAdminJs = "admin_dashboard.js";
|
*/

$pageAdminJs =
    $pageAdminJs
    ?? "";


/*
|--------------------------------------------------------------------------
| ADMIN INITIALS
|--------------------------------------------------------------------------
*/

$nameParts =
    preg_split(
        '/\s+/',
        trim($adminName)
    );

$initials = "";

if (!empty($nameParts[0])) {

    $initials .=
        strtoupper(
            substr(
                $nameParts[0],
                0,
                1
            )
        );
}

if (count($nameParts) > 1) {

    $lastIndex =
        count($nameParts) - 1;

    $initials .=
        strtoupper(
            substr(
                $nameParts[$lastIndex],
                0,
                1
            )
        );
}

if ($initials === "") {
    $initials = "A";
}


/*
|--------------------------------------------------------------------------
| ACTIVE PAGE FUNCTION
|--------------------------------------------------------------------------
*/

function adminPageActive(
    $page,
    $currentPage
) {

    return $page === $currentPage
        ? "active"
        : "";
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

        <?= htmlspecialchars($pageTitle) ?>

        | Event Solutions by S.H.E.

    </title>


    <!-- =================================================
         GOOGLE FONTS
    ================================================== -->

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
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =================================================
         FONT AWESOME
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =================================================
         ADMIN HEADER CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="admin.css/admin_header.css?v=<?= time() ?>"
    >


    <!-- =================================================
         ADMIN PAGE-SPECIFIC CSS
    ================================================== -->

    <?php if (!empty($pageAdminCss)): ?>

        <link
            rel="stylesheet"
            href="admin.css/<?= htmlspecialchars($pageAdminCss) ?>?v=<?= time() ?>"
        >

    <?php endif; ?>

</head>


<body>


<!-- =====================================================
     SIDEBAR OVERLAY
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside
    class="sidebar"
    id="adminSidebar"
>


    <!-- =================================================
         LOGO
    ================================================== -->

    <div class="sidebar-logo">

        <a
            href="admin_dashboard.php"
            class="logo-link"
        >

            <div class="logo-mark">
                S.H.E.
            </div>


            <div class="logo-text">

                <strong>
                    EVENT SOLUTIONS
                </strong>

                <span>
                    BY S.H.E.
                </span>

            </div>

        </a>

    </div>


    <!-- =================================================
         NAVIGATION
    ================================================== -->

    <nav class="sidebar-nav">


        <!-- =================================================
             MAIN
        ================================================== -->

        <div class="nav-label">
            MAIN
        </div>


        <!-- DASHBOARD -->

        <a
            href="admin_dashboard.php"
            class="nav-item <?= adminPageActive(
                "admin_dashboard.php",
                $currentPage
            ) ?>"
            title="Dashboard"
        >

            <i class="fa-solid fa-tv"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- EVENT PACKAGE MANAGEMENT -->

        <a
            href="admin_eventpackages.php"
            class="nav-item <?= adminPageActive(
                "admin_eventpackages.php",
                $currentPage
            ) ?>"
            title="Event Package "
        >

            <i class="fa-solid fa-cube"></i>

            <span>
                Packages
            </span>

        </a>


        <!-- VENUE MANAGEMENT -->

        <a
            href="admin_venues.php"
            class="nav-item <?= adminPageActive(
                "admin_venues.php",
                $currentPage
            ) ?>"
            title="Venue Management"
        >

            <i class="fa-solid fa-map-location"></i>

            <span>
                Venues
            </span>

        </a>


        <!-- HOST MANAGEMENT -->

        <a
            href="admin_host.php"
            class="nav-item <?= adminPageActive(
                "admin_host.php",
                $currentPage
            ) ?>"
            title="Host Management"
        >

            <i class="fa-solid fa-microphone"></i>

            <span>
                Host
            </span>

        </a>


        <!-- HOST PACKAGE MANAGEMENT -->

        <a
            href="admin_hostpackages.php"
            class="nav-item <?= adminPageActive(
                "admin_hostpackages.php",
                $currentPage
            ) ?>"
            title="Host Package Management"
        >

            <i class="fa-solid fa-layer-group"></i>

            <span>
                Host Packages 
            </span>

        </a>


        

        <a
            href="admin_hostbooking.php"
            class="nav-item <?= adminPageActive(
                "admin_hostbooking.php",
                $currentPage
            ) ?>"
            title="Host/Singer Bookings"
        >

            <i class="fa-duotone fa-solid fa-book"></i>

            <span>
                Host/Singer Bookings
            </span>

        </a>


        <a
            href="admin_bookings.php"
            class="nav-item <?= adminPageActive(
                "admin_bookings.php",
                $currentPage    
            ) ?>"
            title="Bookings"
        >

            <i class="fa-solid fa-book-open-reader"></i>

            <span>
                Bookings
            </span>

        </a>


        <a
            href="admin_wb.php"
            class="nav-item <?= adminPageActive(
                "admin_wb.php",
                $currentPage    
            ) ?>"
            title="Wedding_Bookings"
        >

            <i class="fa-solid fa-book-open-reader"></i>

            <span>
                Weddings Bookings
            </span>

        </a>


        <!-- DIVIDER -->

        <div class="nav-divider"></div>


        <!-- =================================================
             ACCOUNT
        ================================================== -->

        <div class="nav-label">
            ACCOUNT
        </div>


        <!-- ACTIVITY LOGS -->

        <a
            href="activity-logs.php"
            class="nav-item <?= adminPageActive(
                "activity-logs.php",
                $currentPage
            ) ?>"
            title="Activity Logs"
        >

            <i class="fa-solid fa-clock-rotate-left"></i>

            <span>
                Activity Logs
            </span>

        </a>


        <!-- PROFILE -->

        <a
            href="../profile.php"
            class="nav-item <?= adminPageActive(
                "profile.php",
                $currentPage
            ) ?>"
            title="My Profile"
        >

            <i class="fa-solid fa-user"></i>

            <span>
                My Profile
            </span>

        </a>

    </nav>


    <!-- =====================================================
         SIDEBAR FOOTER
    ===================================================== -->

    <div class="sidebar-footer">


        <!-- HELP -->

        <div class="sidebar-help">

            <div class="help-icon">

                <i class="fa-solid fa-circle-question"></i>

            </div>


            <div>

                <strong>
                    Need Help?
                </strong>

                <span>
                    Contact Event Solutions
                </span>

            </div>

        </div>


        <!-- BACK TO WEBSITE -->

        <a
            href="../index.php"
            class="back-home"
            title="Back to Website"
        >

            <i class="fa-solid fa-arrow-left"></i>

            <span>
                Back to Website
            </span>

        </a>

    </div>

</aside>


<!-- =====================================================
     MAIN WRAPPER
===================================================== -->

<div class="main-wrapper">


    <!-- =================================================
         TOP HEADER
    ================================================== -->

    <header class="top-header">


        <!-- =================================================
             LEFT SIDE
        ================================================== -->

        <div class="header-left">


            <!-- DESKTOP SIDEBAR TOGGLE -->

            <button
                type="button"
                class="header-icon-btn sidebar-toggle-btn"
                id="sidebarToggleBtn"
                aria-label="Collapse sidebar"
                title="Collapse sidebar"
            >

                <i
                    class="fa-solid fa-angles-left"
                    id="sidebarToggleIcon"
                ></i>

            </button>


            <!-- MOBILE MENU -->

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open menu"
                title="Open menu"
            >

                <i class="fa-solid fa-bars"></i>

            </button>


            <!-- PAGE TITLE -->

            <div class="header-title">

                <span>
                    <?= htmlspecialchars($pageSection) ?>
                </span>

                <h1>
                    <?= htmlspecialchars($pageHeading) ?>
                </h1>

            </div>

        </div>


        <!-- =================================================
             RIGHT SIDE
        ================================================== -->

        <div class="header-actions">


            <!-- THEME BUTTON -->

            <button
                type="button"
                class="header-icon-btn"
                id="headerThemeButton"
                aria-label="Toggle theme"
                title="Toggle theme"
            >

                <i
                    class="fa-solid fa-moon"
                    id="headerThemeIcon"
                ></i>

            </button>


            <!-- =================================================
                 PROFILE
            ================================================== -->

            <div
                class="profile-wrapper"
                id="profileWrapper"
            >


                <!-- PROFILE BUTTON -->

                <button
                    type="button"
                    class="profile-btn"
                    id="profileBtn"
                    aria-expanded="false"
                    aria-haspopup="true"
                >


                    <!-- AVATAR -->

                    <div class="profile-avatar">


                        <?php if (!empty($adminProfileImageUrl)): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $adminProfileImageUrl
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $adminName
                                ) ?>"
                                class="profile-avatar-image"
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >


                            <span
                                class="profile-avatar-fallback"
                                style="display:none;"
                            >

                                <?= htmlspecialchars(
                                    $initials
                                ) ?>

                            </span>


                        <?php else: ?>


                            <span class="profile-avatar-fallback">

                                <?= htmlspecialchars(
                                    $initials
                                ) ?>

                            </span>


                        <?php endif; ?>


                    </div>


                    <!-- PROFILE INFORMATION -->

                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $adminName
                            ) ?>

                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>


                    <!-- ARROW -->

                    <i
                        class="fa-solid fa-chevron-down profile-arrow"
                        id="profileArrow"
                    ></i>

                </button>


                <!-- =================================================
                     PROFILE DROPDOWN
                ================================================== -->

                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >


                    <!-- USER INFORMATION -->

                    <div class="dropdown-user">


                        <!-- DROPDOWN AVATAR -->

                        <div class="dropdown-avatar">


                            <?php if (!empty($adminProfileImageUrl)): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $adminProfileImageUrl
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $adminName
                                    ) ?>"
                                    class="dropdown-avatar-image"
                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='flex';
                                    "
                                >


                                <span
                                    class="dropdown-avatar-fallback"
                                    style="display:none;"
                                >

                                    <?= htmlspecialchars(
                                        $initials
                                    ) ?>

                                </span>


                            <?php else: ?>


                                <span class="dropdown-avatar-fallback">

                                    <?= htmlspecialchars(
                                        $initials
                                    ) ?>

                                </span>


                            <?php endif; ?>


                        </div>


                        <!-- ADMIN DETAILS -->

                        <div>

                            <strong>

                                <?= htmlspecialchars(
                                    $adminName
                                ) ?>

                            </strong>

                            <span>

                                <?= htmlspecialchars(
                                    $adminEmail
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <!-- DIVIDER -->

                    <div class="dropdown-divider"></div>


                    <!-- PROFILE -->

                    <a
                        href="../profile.php"
                    >

                        <i class="fa-solid fa-user"></i>

                        <span>
                            My Profile
                        </span>

                    </a>


                    <!-- THEME -->

                    <button
                        type="button"
                        id="themeToggle"
                    >

                        <i
                            class="fa-solid fa-moon"
                            id="themeIcon"
                        ></i>

                        <span id="themeText">
                            Dark Mode
                        </span>

                    </button>


                    <!-- DIVIDER -->

                    <div class="dropdown-divider"></div>


                    <!-- LOGOUT -->

                    <a
                        href="../auth/logout.php"
                        class="logout-link"
                    >

                        <i class="fa-solid fa-right-from-bracket"></i>

                        <span>
                            Logout
                        </span>

                    </a>

                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         PAGE CONTENT
    ===================================================== -->

    <main class="admin-page-content">

        <?= $pageContent ?? "" ?>

    </main>

</div>


<!-- =====================================================
     ADMIN HEADER JAVASCRIPT
===================================================== -->

<script src="admin.js/admin_header.js?v=<?= time() ?>"></script>


<!-- =====================================================
     ADMIN PAGE-SPECIFIC JAVASCRIPT
===================================================== -->

<?php if (!empty($pageAdminJs)): ?>

    <script
        src="admin.js/<?= htmlspecialchars($pageAdminJs) ?>?v=<?= time() ?>"
    ></script>

<?php endif; ?>


</body>

</html>