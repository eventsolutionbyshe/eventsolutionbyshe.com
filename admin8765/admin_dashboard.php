<?php

/* =====================================================
   ADMIN DASHBOARD
   Event Solutions by S.H.E.
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   DATABASE
===================================================== */

require_once __DIR__ . "/../config/database.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

mysqli_report(MYSQLI_REPORT_OFF);


/* =====================================================
   PAGE SETTINGS
===================================================== */

$pageTitle = "Admin Dashboard";
$pageSection = "Administrator";
$pageHeading = "Dashboard";

$pageAdminCss = "admin_dashboard.css";
$pageAdminJs = "admin_dashboard.js";


/* =====================================================
   ADMIN INFORMATION
===================================================== */

$adminName = $_SESSION["fullname"] ?? "Admin";
$adminEmail = $_SESSION["email"] ?? "";
$adminRole = $_SESSION["role"] ?? "admin";


/* =====================================================
   HELPER
===================================================== */

function getCount(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return isset($row["total"])
        ? (int)$row["total"]
        : 0;
}


/* =====================================================
   DASHBOARD COUNTS
===================================================== */

function getDashboardCounts(mysqli $conn): array
{
    /* =================================================
       CUSTOMERS
    ================================================= */

    $customers = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM users
        WHERE LOWER(TRIM(role)) = 'customer'
        "
    );


    /* =================================================
       REGULAR BOOKINGS
    ================================================= */

    $regularBookings = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM bookings
        "
    );


    /* =================================================
       WEDDING BOOKINGS
    ================================================= */

    $weddingBookings = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM booking_wedding
        "
    );


    /* =================================================
       TOTAL BOOKINGS
    ================================================= */

    $totalBookings =
        $regularBookings +
        $weddingBookings;


    /* =================================================
       PENDING BOOKINGS
    ================================================= */

    $pendingRegular = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM bookings
        WHERE LOWER(TRIM(status)) = 'pending'
        "
    );


    $pendingWedding = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM booking_wedding
        WHERE LOWER(TRIM(status)) = 'pending'
        "
    );


    $pendingBookings =
        $pendingRegular +
        $pendingWedding;


    /* =================================================
       CONFIRMED BOOKINGS
    ================================================= */

    $confirmedRegular = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM bookings
        WHERE LOWER(TRIM(status)) = 'confirmed'
        "
    );


    $confirmedWedding = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM booking_wedding
        WHERE LOWER(TRIM(status)) = 'confirmed'
        "
    );


    $confirmedBookings =
        $confirmedRegular +
        $confirmedWedding;


    /* =================================================
       UPCOMING EVENTS
    ================================================= */

    $upcomingRegular = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM bookings
        WHERE LOWER(TRIM(status)) = 'confirmed'
        AND event_date >= CURDATE()
        "
    );


    $upcomingWedding = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM booking_wedding
        WHERE LOWER(TRIM(status)) = 'confirmed'
        AND event_date >= CURDATE()
        "
    );


    $upcomingEvents =
        $upcomingRegular +
        $upcomingWedding;


    /* =================================================
       VENUES
    ================================================= */

    $venues = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM venues
        "
    );


    /* =================================================
       HOSTS
    ================================================= */

    $hosts = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM hosts
        "
    );


    /* =================================================
       PACKAGES
    ================================================= */

    $packages = getCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM event_packages
        "
    );


    return [
        "customers" => $customers,

        "regularBookings" => $regularBookings,

        "weddingBookings" => $weddingBookings,

        "totalBookings" => $totalBookings,

        "pendingBookings" => $pendingBookings,

        "confirmedBookings" => $confirmedBookings,

        "regularEvents" => $confirmedRegular,

        "weddingEvents" => $confirmedWedding,

        "upcomingRegular" => $upcomingRegular,

        "upcomingWedding" => $upcomingWedding,

        "upcomingEvents" => $upcomingEvents,

        "venues" => $venues,

        "hosts" => $hosts,

        "packages" => $packages
    ];
}


/* =====================================================
   GET VENUE NAME COLUMN
===================================================== */

$venueNameColumn = null;

$venueColumnsResult = $conn->query(
    "SHOW COLUMNS FROM venues"
);

if ($venueColumnsResult) {

    $venueColumns = [];

    while ($column = $venueColumnsResult->fetch_assoc()) {
        $venueColumns[] = $column["Field"];
    }

    $possibleVenueNameColumns = [
        "venue_name",
        "name",
        "title",
        "venue",
        "location_name"
    ];

    foreach ($possibleVenueNameColumns as $possibleColumn) {

        if (
            in_array(
                $possibleColumn,
                $venueColumns,
                true
            )
        ) {
            $venueNameColumn = $possibleColumn;
            break;
        }
    }
}


/* =====================================================
   VENUE DISPLAY FIELD
===================================================== */

if ($venueNameColumn !== null) {

    $venueDisplayField =
        "v.`" .
        str_replace(
            "`",
            "``",
            $venueNameColumn
        ) .
        "`";
} else {

    $venueDisplayField = "NULL";
}


/* =====================================================
   GET UPCOMING EVENTS
===================================================== */

function getUpcomingEvents(
    mysqli $conn,
    string $venueDisplayField
): array {

    $events = [];

    $sql = "
        SELECT
            b.id,

            CONVERT(
                b.event_name USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_name,

            b.event_date,
            b.start_time,
            b.guest_count,

            CONVERT(
                b.status USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS status,

            CONVERT(
                COALESCE(
                    p.package_name,
                    'No package'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS package_name,

            CONVERT(
                COALESCE(
                    {$venueDisplayField},
                    'Venue not specified'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS venue_name,

            CONVERT(
                'Regular Event' USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_type

        FROM bookings b

        LEFT JOIN event_packages p
            ON b.package_id = p.id

        LEFT JOIN venues v
            ON b.venue_id = v.id

        WHERE LOWER(TRIM(b.status)) = 'confirmed'
        AND b.event_date >= CURDATE()


        UNION ALL


        SELECT
            bw.id,

            CONVERT(
                CONCAT(
                    COALESCE(bw.bride_name, ''),
                    ' & ',
                    COALESCE(bw.groom_name, ''),
                    ' Wedding'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_name,

            bw.event_date,
            bw.start_time,
            bw.guest_count,

            CONVERT(
                bw.status USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS status,

            CONVERT(
                COALESCE(
                    p.package_name,
                    'No package'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS package_name,

            CONVERT(
                COALESCE(
                    {$venueDisplayField},
                    'Venue not specified'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS venue_name,

            CONVERT(
                'Wedding' USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_type

        FROM booking_wedding bw

        LEFT JOIN event_packages p
            ON bw.package_id = p.id

        LEFT JOIN venues v
            ON bw.venue_id = v.id

        WHERE LOWER(TRIM(bw.status)) = 'confirmed'
        AND bw.event_date >= CURDATE()

        ORDER BY
            event_date ASC,
            start_time ASC

        LIMIT 8
    ";

    $result = $conn->query($sql);

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    return $events;
}


/* =====================================================
   GET RECENT BOOKINGS
===================================================== */

function getRecentBookings(
    mysqli $conn
): array {

    $bookings = [];

    $sql = "
        SELECT
            b.id,

            CONVERT(
                b.event_name USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_name,

            b.event_date,
            b.start_time,

            CONVERT(
                b.status USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS status,

            CONVERT(
                COALESCE(
                    u.fullname,
                    'Customer'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS customer_name,

            CONVERT(
                COALESCE(
                    u.email,
                    ''
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS customer_email,

            COALESCE(
                u.profile_image,
                ''
            ) AS profile_image,

            CONVERT(
                COALESCE(
                    p.package_name,
                    'No package'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS package_name,

            CONVERT(
                'Regular Event' USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_type

        FROM bookings b

        LEFT JOIN users u
            ON b.user_id = u.id

        LEFT JOIN event_packages p
            ON b.package_id = p.id


        UNION ALL


        SELECT
            bw.id,

            CONVERT(
                CONCAT(
                    COALESCE(bw.bride_name, ''),
                    ' & ',
                    COALESCE(bw.groom_name, ''),
                    ' Wedding'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_name,

            bw.event_date,
            bw.start_time,

            CONVERT(
                bw.status USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS status,

            CONVERT(
                COALESCE(
                    u.fullname,
                    'Customer'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS customer_name,

            CONVERT(
                COALESCE(
                    u.email,
                    ''
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS customer_email,

            COALESCE(
                u.profile_image,
                ''
            ) AS profile_image,

            CONVERT(
                COALESCE(
                    p.package_name,
                    'No package'
                ) USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS package_name,

            CONVERT(
                'Wedding' USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS event_type

        FROM booking_wedding bw

        LEFT JOIN users u
            ON bw.user_id = u.id

        LEFT JOIN event_packages p
            ON bw.package_id = p.id

        ORDER BY
            event_date DESC

        LIMIT 8
    ";

    $result = $conn->query($sql);

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }

    return $bookings;
}


/* =====================================================
   PROFILE IMAGE URL
===================================================== */

function getProfileImageUrl(
    $profileImage
): string {

    $profileImage =
        trim(
            (string)$profileImage
        );

    /* NULL or empty profile image */
    if ($profileImage === "") {

        return "";
    }

    /*
       External image URL
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
       Normalize slashes
    */

    $normalized =
        str_replace(
            "\\",
            "/",
            $profileImage
        );

    $normalized =
        ltrim(
            $normalized,
            "/"
        );

    /*
       Already contains images/
    */

    if (
        strpos(
            $normalized,
            "images/"
        ) === 0
    ) {

        return "../../" .
            $normalized;
    }

    /*
       Contains profiles/
    */

    if (
        strpos(
            $normalized,
            "profiles/"
        ) === 0
    ) {

        return "../../images/" .
            $normalized;
    }

    /*
       Filename only
    */

    return "../../images/profiles/" .
        basename($normalized);
}




/* =====================================================
   GET POPULAR PACKAGES
===================================================== */

function getPopularPackages(
    mysqli $conn
): array {

    $packages = [];

    $sql = "
        SELECT
        p.id,
        p.package_name,
        COUNT(b.id) + COUNT(w.id) AS booking_count
        FROM event_packages p
        LEFT JOIN bookings b ON p.id = b.package_id
        AND LOWER(TRIM(b.status)) IN ('pending', 'confirmed')
        LEFT JOIN booking_wedding w ON p.id = w.package_id
        AND LOWER(TRIM(w.status)) IN ('pending', 'confirmed')
        GROUP BY p.id,
        p.package_name
        ORDER BY booking_count DESC LIMIT 5;
    ";

    $result = $conn->query($sql);

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
    }

    return $packages;
}


/* =====================================================
   LIVE AJAX ENDPOINT
===================================================== */

if (
    isset($_GET["action"]) &&
    $_GET["action"] === "live_dashboard"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    header(
        "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
    );

    header(
        "Cache-Control: post-check=0, pre-check=0",
        false
    );

    header("Pragma: no-cache");

    header("Expires: 0");


    $liveCounts =
        getDashboardCounts($conn);


    $liveUpcoming =
        getUpcomingEvents(
            $conn,
            $venueDisplayField
        );


    $liveRecent =
        getRecentBookings($conn);


    $livePackages =
        getPopularPackages($conn);


    echo json_encode(
        [
            "success" => true,

            "timestamp" => time(),

            "data" => [

                "customers" =>
                (int)$liveCounts["customers"],

                "regularBookings" =>
                (int)$liveCounts["regularBookings"],

                "weddingBookings" =>
                (int)$liveCounts["weddingBookings"],

                "totalBookings" =>
                (int)$liveCounts["totalBookings"],

                "pendingBookings" =>
                (int)$liveCounts["pendingBookings"],

                "confirmedBookings" =>
                (int)$liveCounts["confirmedBookings"],

                "regularEvents" =>
                (int)$liveCounts["regularEvents"],

                "weddingEvents" =>
                (int)$liveCounts["weddingEvents"],

                "upcomingRegular" =>
                (int)$liveCounts["upcomingRegular"],

                "upcomingWedding" =>
                (int)$liveCounts["upcomingWedding"],

                "upcomingEvents" =>
                (int)$liveCounts["upcomingEvents"],

                "venues" =>
                (int)$liveCounts["venues"],

                "hosts" =>
                (int)$liveCounts["hosts"],

                "packages" =>
                (int)$liveCounts["packages"],

                "upcomingEventList" =>
                $liveUpcoming,

                "recentBookings" =>
                $liveRecent,

                "popularPackages" =>
                $livePackages
            ]
        ],

        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
    );

    exit;
}


/* =====================================================
   INITIAL PAGE DATA
===================================================== */

$dashboardCounts =
    getDashboardCounts($conn);


$customerCount =
    $dashboardCounts["customers"];

$regularBookingCount =
    $dashboardCounts["regularBookings"];

$weddingBookingCount =
    $dashboardCounts["weddingBookings"];

$totalBookingCount =
    $dashboardCounts["totalBookings"];

$pendingBookingCount =
    $dashboardCounts["pendingBookings"];

$confirmedBookingCount =
    $dashboardCounts["confirmedBookings"];

$upcomingEventCount =
    $dashboardCounts["upcomingEvents"];

$venueCount =
    $dashboardCounts["venues"];

$hostCount =
    $dashboardCounts["hosts"];

$packageCount =
    $dashboardCounts["packages"];

$regularConfirmed =
    $dashboardCounts["regularEvents"];

$weddingConfirmed =
    $dashboardCounts["weddingEvents"];


$upcomingEvents =
    getUpcomingEvents(
        $conn,
        $venueDisplayField
    );


$recentBookings =
    getRecentBookings($conn);


$popularPackages =
    getPopularPackages($conn);


/* =====================================================
   PAGE CONTENT
===================================================== */

ob_start();

?>

<section class="admin-dashboard">


    <!-- =================================================
         WELCOME
    ================================================== -->

    <div class="dashboard-welcome">

        <div class="welcome-content">

            <div class="welcome-top">

                <span class="welcome-label">
                    ADMIN DASHBOARD
                </span>

                <div
                    class="system-live"
                    id="systemLive">

                    <span class="live-dot"></span>

                    <span id="systemStatusText">
                        System Live
                    </span>

                </div>

            </div>


            <h2>
                Welcome back,

                <?= htmlspecialchars(
                    $adminName,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>!
            </h2>


            <p>
                Manage bookings, events, venues,
                packages, and customers from one place.
            </p>

        </div>

    </div>


    <!-- =================================================
         PRIMARY STATISTICS
    ================================================== -->

    <div class="stats-grid">


        <!-- UPCOMING -->

        <div class="stat-card">

            <div class="stat-card-top">

                <div class="stat-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>

                <span class="stat-label">
                    Upcoming Events
                </span>

            </div>


            <strong
                class="stat-value"
                id="upcomingEventsCount">
                <?= number_format($upcomingEventCount) ?>
            </strong>


            <span class="stat-description">
                Confirmed upcoming events
            </span>

        </div>


        <!-- CUSTOMERS -->

        <div class="stat-card">

            <div class="stat-card-top">

                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span class="stat-label">
                    Customers
                </span>

            </div>


            <strong
                class="stat-value"
                id="customersCount">
                <?= number_format($customerCount) ?>
            </strong>


            <span class="stat-description">
                Registered customers
            </span>

        </div>


        <!-- PENDING -->

        <div class="stat-card">

            <div class="stat-card-top">

                <div class="stat-icon">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>

                <span class="stat-label">
                    Pending Bookings
                </span>

            </div>


            <strong
                class="stat-value"
                id="pendingBookingsCount">
                <?= number_format($pendingBookingCount) ?>
            </strong>


            <span class="stat-description">
                Waiting for approval
            </span>

        </div>


        <!-- CONFIRMED -->

        <div class="stat-card">

            <div class="stat-card-top">

                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <span class="stat-label">
                    Confirmed Bookings
                </span>

            </div>


            <strong
                class="stat-value"
                id="confirmedBookingsCount">
                <?= number_format($confirmedBookingCount) ?>
            </strong>


            <span class="stat-description">
                Approved bookings
            </span>

        </div>

    </div>


    <!-- =================================================
         SECONDARY STATISTICS
    ================================================== -->

    <div class="secondary-stats">


        <div class="mini-stat">

            <div class="mini-stat-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div>

                <strong id="totalBookingsCount">
                    <?= number_format($totalBookingCount) ?>
                </strong>

                <span>
                    Total Bookings
                </span>

            </div>

        </div>


        <div class="mini-stat">

            <div class="mini-stat-icon">
                <i class="fa-solid fa-building"></i>
            </div>

            <div>

                <strong id="venuesCount">
                    <?= number_format($venueCount) ?>
                </strong>

                <span>
                    Venues
                </span>

            </div>

        </div>


        <div class="mini-stat">

            <div class="mini-stat-icon">
                <i class="fa-solid fa-microphone"></i>
            </div>

            <div>

                <strong id="hostsCount">
                    <?= number_format($hostCount) ?>
                </strong>

                <span>
                    Hosts
                </span>

            </div>

        </div>


        <div class="mini-stat">

            <div class="mini-stat-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <div>

                <strong id="packagesCount">
                    <?= number_format($packageCount) ?>
                </strong>

                <span>
                    Event Packages
                </span>

            </div>

        </div>

    </div>


    <!-- =================================================
         MAIN COLUMNS
    ================================================== -->

    <div class="dashboard-columns">


        <!-- UPCOMING EVENTS -->

        <section class="dashboard-panel upcoming-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-label">
                        SCHEDULE
                    </span>

                    <h3>
                        Upcoming Events
                    </h3>

                </div>


                <a
                    href="../../events.php"
                    class="panel-link">
                    View All
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <div
                class="events-list"
                id="upcomingEventsList">

                <?php if (!empty($upcomingEvents)): ?>

                    <?php foreach ($upcomingEvents as $event): ?>

                        <div class="event-row">

                            <div class="event-date">

                                <span>
                                    <?= date(
                                        "M",
                                        strtotime(
                                            $event["event_date"]
                                        )
                                    ) ?>
                                </span>

                                <strong>
                                    <?= date(
                                        "d",
                                        strtotime(
                                            $event["event_date"]
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="event-details">

                                <strong>
                                    <?= htmlspecialchars(
                                        $event["event_name"] ?? "Event",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>


                                <span>
                                    <i class="fa-regular fa-clock"></i>

                                    <?= !empty($event["start_time"])
                                        ? date(
                                            "g:i A",
                                            strtotime(
                                                $event["start_time"]
                                            )
                                        )
                                        : "Time not specified"
                                    ?>
                                </span>


                                <span>
                                    <i class="fa-solid fa-location-dot"></i>

                                    <?= htmlspecialchars(
                                        $event["venue_name"]
                                            ?: "Venue not specified",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </span>

                            </div>


                            <div class="event-meta">

                                <span class="event-type">

                                    <?= htmlspecialchars(
                                        $event["event_type"] ?? "Event",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>


                                <span class="guest-count">

                                    <i class="fa-solid fa-users"></i>

                                    <?= number_format(
                                        (int)(
                                            $event["guest_count"] ?? 0
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty-state">

                        <i class="fa-regular fa-calendar-xmark"></i>

                        <strong>
                            No upcoming events
                        </strong>

                        <span>
                            Confirmed events will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- EVENT BREAKDOWN -->

        <section class="dashboard-panel breakdown-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-label">
                        OVERVIEW
                    </span>

                    <h3>
                        Event Breakdown
                    </h3>

                </div>

            </div>


            <div class="breakdown-content">


                <div class="breakdown-item">

                    <div class="breakdown-icon regular">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>


                    <div class="breakdown-info">

                        <strong id="regularEventsCount">
                            <?= number_format($regularConfirmed) ?>
                        </strong>

                        <span>
                            Regular Events
                        </span>

                    </div>

                </div>


                <div class="breakdown-item">

                    <div class="breakdown-icon wedding">
                        <i class="fa-solid fa-ring"></i>
                    </div>


                    <div class="breakdown-info">

                        <strong id="weddingEventsCount">
                            <?= number_format($weddingConfirmed) ?>
                        </strong>

                        <span>
                            Weddings
                        </span>

                    </div>

                </div>


            </div>

        </section>

    </div>


    <!-- =================================================
         LOWER COLUMNS
    ================================================== -->

    <div class="dashboard-columns lower-columns">


        <!-- RECENT BOOKINGS -->

        <section class="dashboard-panel recent-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-label">
                        ACTIVITY
                    </span>

                    <h3>
                        Recent Bookings
                    </h3>

                </div>

            </div>


            <div
                class="recent-table-wrapper"
                id="recentBookingsWrapper">

                <?php if (!empty($recentBookings)): ?>

                    <table class="recent-table">

                        <thead>

                            <tr>

                                <th>Customer</th>

                                <th>Event</th>

                                <th>Date</th>

                                <th>Package</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody id="recentBookingsBody">

                            <?php foreach ($recentBookings as $booking): ?>

                                <?php

                                $bookingCustomer =
                                    $booking["customer_name"]
                                    ?? "Customer";

                                $bookingEmail =
                                    $booking["customer_email"]
                                    ?? "";

                                $bookingEvent =
                                    $booking["event_name"]
                                    ?? "Event";

                                $bookingStatus =
                                    strtolower(
                                        trim(
                                            $booking["status"]
                                                ?? "pending"
                                        )
                                    );

                                $bookingInitial =
                                    strtoupper(
                                        substr(
                                            trim(
                                                $bookingCustomer
                                            ),
                                            0,
                                            1
                                        )
                                    );

                                if ($bookingInitial === "") {
                                    $bookingInitial = "C";
                                }


                                /*
                                   PROFILE IMAGE
                                */

                                $profileImageUrl =
                                    getProfileImageUrl(
                                        $booking["profile_image"]
                                            ?? ""
                                    );

                                ?>


                                <tr>

                                    <td>

                                        <div class="customer-cell">

                                            <div class="customer-avatar">

                                                <?php if ($profileImageUrl !== ""): ?>

                                                    <img
                                                        src="<?= htmlspecialchars(
                                                                    $profileImageUrl,
                                                                    ENT_QUOTES,
                                                                    "UTF-8"
                                                                ) ?>"
                                                        alt="<?= htmlspecialchars(
                                                                    $bookingCustomer,
                                                                    ENT_QUOTES,
                                                                    "UTF-8"
                                                                ) ?>"
                                                        onerror="
                                                            this.style.display='none';
                                                            this.nextElementSibling.style.display='flex';
                                                        ">

                                                    <span
                                                        class="customer-avatar-fallback"
                                                        style="display:none;">
                                                        <?= htmlspecialchars(
                                                            $bookingInitial,
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="customer-avatar-fallback">

                                                        <?= htmlspecialchars(
                                                            $bookingInitial,
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $bookingCustomer,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>

                                                </strong>


                                                <span>

                                                    <?= htmlspecialchars(
                                                        $bookingEmail,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $bookingEvent,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </strong>


                                        <span class="table-event-type">

                                            <?= htmlspecialchars(
                                                $booking["event_type"]
                                                    ?? "Event",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= !empty($booking["event_date"])
                                            ? date(
                                                "M d, Y",
                                                strtotime(
                                                    $booking["event_date"]
                                                )
                                            )
                                            : "No date"
                                        ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $booking["package_name"]
                                                ?: "No package",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge status-<?= htmlspecialchars(
                                                                            $bookingStatus,
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>">

                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $bookingStatus
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <i class="fa-regular fa-folder-open"></i>

                        <strong>
                            No bookings yet
                        </strong>

                        <span>
                            New bookings will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- POPULAR PACKAGES -->

        <section class="dashboard-panel packages-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-label">
                        PACKAGES
                    </span>

                    <h3>
                        Popular Packages
                    </h3>

                </div>


                <span class="package-count-label">
                    Top 5
                </span>

            </div>


            <div
                class="popular-packages"
                id="popularPackagesList">

                <?php if (!empty($popularPackages)): ?>

                    <?php $packagePosition = 1; ?>

                    <?php foreach ($popularPackages as $package): ?>

                        <div class="popular-package">

                            <div class="package-rank">

                                <?= $packagePosition ?>

                            </div>


                            <div class="package-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $package["package_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </strong>


                                <span>

                                    <?= number_format(
                                        (int)(
                                            $package["booking_count"]
                                            ?? 0
                                        )
                                    ) ?>

                                    bookings

                                </span>

                            </div>


                            <div class="package-arrow">

                                <i class="fa-solid fa-chevron-right"></i>

                            </div>

                        </div>


                        <?php $packagePosition++; ?>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty-state">

                        <i class="fa-solid fa-box-open"></i>

                        <strong>
                            No package data
                        </strong>

                        <span>
                            Package bookings will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </div>


    <!-- =================================================
         QUICK ACTIONS
    ================================================== -->

    <section class="dashboard-panel quick-actions-panel">

        <div class="panel-header">

            <div>

                <span class="panel-label">
                    MANAGEMENT
                </span>

                <h3>
                    Quick Actions
                </h3>

            </div>

        </div>


        <div class="quick-actions">


            <a
                href="admin_eventpackages.php"
                class="quick-action">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <div>

                    <strong>
                        Event Packages
                    </strong>

                    <span>
                        Manage event packages
                    </span>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>


            <a
                href="admin_venues.php"
                class="quick-action">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div>

                    <strong>
                        Venues
                    </strong>

                    <span>
                        Manage event venues
                    </span>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>


            <a
                href="admin_host.php"
                class="quick-action">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-microphone"></i>
                </div>

                <div>

                    <strong>
                        Hosts
                    </strong>

                    <span>
                        Manage event hosts
                    </span>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>


            <a
                href="../../profile.php"
                class="quick-action">

                <div class="quick-action-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>

                    <strong>
                        My Profile
                    </strong>

                    <span>
                        Manage administrator profile
                    </span>

                </div>

                <i class="fa-solid fa-arrow-right"></i>

            </a>


        </div>

    </section>


</section>


<!-- =====================================================
     INITIAL DASHBOARD DATA
===================================================== -->

<script>
    window.adminDashboardData = <?= json_encode(
                                    [
                                        "customers" => $customerCount,

                                        "regularBookings" => $regularBookingCount,

                                        "weddingBookings" => $weddingBookingCount,

                                        "totalBookings" => $totalBookingCount,

                                        "pendingBookings" => $pendingBookingCount,

                                        "confirmedBookings" => $confirmedBookingCount,

                                        "regularEvents" => $regularConfirmed,

                                        "weddingEvents" => $weddingConfirmed,

                                        "upcomingEvents" => $upcomingEventCount,

                                        "venues" => $venueCount,

                                        "hosts" => $hostCount,

                                        "packages" => $packageCount
                                    ],

                                    JSON_UNESCAPED_SLASHES |
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_HEX_TAG |
                                        JSON_HEX_AMP |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT
                                ) ?>;
</script>


<?php

/* =====================================================
   GET PAGE CONTENT
===================================================== */

$pageContent = ob_get_clean();


/* =====================================================
   LOAD ADMIN HEADER
===================================================== */

require_once __DIR__ .
    "/admin_include/admin_header.php";

?>
