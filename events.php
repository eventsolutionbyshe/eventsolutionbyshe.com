<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| LOGIN STATUS
|--------------------------------------------------------------------------
*/

$isLoggedIn = isset($_SESSION["user_id"]);


/*
|--------------------------------------------------------------------------
| GET EVENT TYPES
|--------------------------------------------------------------------------
*/

$eventTypes = [];

$typeQuery = "
    SELECT
        id,
        name
    FROM event_types
    ORDER BY name ASC
";

$typeResult = $conn->query($typeQuery);

if ($typeResult) {
    while ($row = $typeResult->fetch_assoc()) {
        $eventTypes[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| GET EVENT PACKAGES
|--------------------------------------------------------------------------
*/

$packages = [];

$packageQuery = "
    SELECT
        ep.id,
        ep.package_name,
        ep.event_type_id,
        ep.image,
        ep.inclusions,
        et.name AS event_type,
         a.status
    FROM event_packages ep
    INNER JOIN event_types et ON ep.event_type_id = et.id
    INNER JOIN archive a on ep.status = a.archiveid
    WHERE LOWER(TRIM(a.status)) = 'active'
    ORDER BY ep.id DESC
";

$packageResult = $conn->query($packageQuery);

if ($packageResult) {

    while ($row = $packageResult->fetch_assoc()) {

        /*
        |--------------------------------------------------------------------------
        | PREPARE INCLUSIONS
        |--------------------------------------------------------------------------
        */

        $inclusions = preg_split(
            "/\r\n|\r|\n/",
            $row["inclusions"]
        );

        $cleanInclusions = [];

        foreach ($inclusions as $inclusion) {

            $inclusion = trim($inclusion);

            if ($inclusion !== "") {
                $cleanInclusions[] = $inclusion;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GUEST ACCESS
        |--------------------------------------------------------------------------
        */

        if (!$isLoggedIn) {

            // Guests can see only first 3 inclusions
            $row["inclusions"] = array_slice(
                $cleanInclusions,
                0,
                3
            );

            $row["has_more"] =
                count($cleanInclusions) > 3;
        } else {

            // Logged-in users see everything
            $row["inclusions"] =
                $cleanInclusions;

            $row["has_more"] = false;
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH DATA
        |--------------------------------------------------------------------------
        */

        $row["search_text"] = strtolower(
            $row["package_name"]
                . " "
                . $row["event_type"]
        );

        $packages[] = $row;
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
        Event Packages | Event Solutions by S.H.E.
    </title>

    <link
        rel="icon"
        type="image/png"
        href="images/logo.png">


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


    <!-- HEADER CSS -->

    <link
        rel="stylesheet"
        href="css/header.css">

    <link
        rel="stylesheet"
        href="css/footer.css">

    <!-- EVENTS CSS -->

    <link
        rel="stylesheet"
        href="css/events.css">

</head>


<body>


    <!-- =========================================================
     HEADER
========================================================= -->

    <?php include "includes/header.php"; ?>


    <!-- =========================================================
     HERO
========================================================= -->

    <section class="events-hero">

        <div class="events-hero-overlay"></div>

        <div class="events-hero-content">

            <span class="events-eyebrow">
                EVENT SOLUTIONS BY S.H.E.
            </span>

            <h1>
                Our
                <span>Event Packages</span>
            </h1>

            <p>
                Discover the perfect package for your special occasion.
            </p>

        </div>

    </section>


    <!-- =========================================================
     MAIN EVENTS PAGE
========================================================= -->

    <main class="events-page">

        <section class="events-section">


            <!-- =================================================
             HEADING
        ================================================== -->

            <div class="events-heading">

                <span class="section-label">
                    FIND YOUR PERFECT EVENT
                </span>

                <h2>
                    Explore Our Packages
                </h2>

                <p>
                    Browse our event packages and find the perfect
                    solution for your celebration.
                </p>

            </div>


            <!-- =================================================
             SEARCH + FILTER
        ================================================== -->

            <div class="events-controls">


                <!-- SEARCH -->

                <div class="event-search">

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true">

                        <circle
                            cx="11"
                            cy="11"
                            r="7"></circle>

                        <path
                            d="m20 20-3.5-3.5"></path>

                    </svg>

                    <input
                        type="text"
                        id="packageSearch"
                        placeholder="Search event packages..."
                        autocomplete="off">

                </div>


                <!-- EVENT TYPE -->

                <div class="event-filter">

                    <label for="eventTypeFilter">
                        Event Type
                    </label>

                    <select id="eventTypeFilter">

                        <option value="all">
                            All Events
                        </option>

                        <?php foreach ($eventTypes as $type): ?>

                            <option
                                value="<?= htmlspecialchars($type["id"]) ?>">

                                <?= htmlspecialchars($type["name"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- =================================================
             GUEST NOTICE
        ================================================== -->

            <?php if (!$isLoggedIn): ?>

                <div class="guest-notice">

                    <div class="guest-notice-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true">

                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="10"
                                rx="2"></rect>

                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"></path>

                            <circle
                                cx="12"
                                cy="15"
                                r="1"></circle>

                        </svg>

                    </div>


                    <div class="guest-notice-content">

                        <strong>
                            Want to see the full package details?
                        </strong>

                        <span>
                            Log in to view all package inclusions
                            and access the complete event information.
                        </span>

                    </div>


                    <a
                        href="auth/login.php"
                        class="guest-login-button">

                        Login

                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
             RESULT COUNT
        ================================================== -->

            <div class="package-result-info">

                <span id="packageCount">

                    <?= count($packages) ?>

                    <?= count($packages) === 1
                        ? " Package"
                        : " Packages"
                    ?>

                </span>

            </div>


            <!-- =================================================
             PACKAGE GRID
        ================================================== -->

            <div
                class="packages-grid"
                id="packagesGrid">

                <?php if (!empty($packages)): ?>

                    <?php foreach ($packages as $package): ?>

                        <article
                            class="package-card"
                            data-name="<?= htmlspecialchars($package["search_text"]) ?>"
                            data-event-type="<?= htmlspecialchars($package["event_type_id"]) ?>">


                            <!-- LEFT CONTENT -->

                            <div class="package-content">


                                <!-- DECORATIVE TOP -->

                                <div class="package-top-decoration">

                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>

                                </div>


                                <!-- BRAND -->

                                <span class="package-brand">
                                    EVENT SOLUTIONS BY S.H.E.
                                </span>


                                <!-- PACKAGE NAME -->

                                <h3>

                                    <?= htmlspecialchars(
                                        $package["package_name"]
                                    ) ?>

                                </h3>


                                <!-- EVENT TYPE -->

                                <span class="package-type">

                                    <?= htmlspecialchars(
                                        $package["event_type"]
                                    ) ?>

                                </span>


                                <!-- DIVIDER -->

                                <div class="package-divider"></div>


                                <!-- INCLUSIONS -->

                                <div class="package-inclusions">

                                    <h4>
                                        Inclusions:
                                    </h4>

                                    <ul>

                                        <?php foreach (
                                            $package["inclusions"]
                                            as $inclusion
                                        ): ?>

                                            <li>

                                                <span class="check-icon">

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true">

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


                                    <!-- GUEST LOCK -->

                                    <?php if (
                                        !$isLoggedIn &&
                                        $package["has_more"]
                                    ): ?>

                                        <div class="package-locked">

                                            <span class="lock-icon">

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    aria-hidden="true">

                                                    <rect
                                                        x="5"
                                                        y="10"
                                                        width="14"
                                                        height="10"
                                                        rx="2"></rect>

                                                    <path
                                                        d="M8 10V7a4 4 0 0 1 8 0v3"></path>

                                                </svg>

                                            </span>

                                            <span>
                                                More inclusions available
                                            </span>

                                        </div>


                                        <p class="login-required-text">

                                            Please log in to view the complete
                                            package details.

                                        </p>


                                        <a
                                            href="auth/login.php"
                                            class="package-login-button">

                                            Login to View Details

                                            <svg
                                                viewBox="0 0 24 24"
                                                aria-hidden="true">

                                                <path d="M5 12h14"></path>

                                                <path
                                                    d="m13 6 6 6-6 6"></path>

                                            </svg>

                                        </a>


                                    <?php elseif ($isLoggedIn): ?>


                                        <!-- BOOK NOW -->

                                        <?php

                                        $eventTypeName =
                                            strtolower(
                                                trim(
                                                    $package["event_type"]
                                                )
                                            );

                                        if (
                                            $eventTypeName === "wedding"
                                        ) {

                                            $bookingUrl =
                                                "book_wedding.php?package_id=" .
                                                (int) $package["id"];
                                        } else {

                                            $bookingUrl =
                                                "book_event.php?package_id=" .
                                                (int) $package["id"];
                                        }

                                        ?>

                                        <a
                                            href="<?= htmlspecialchars($bookingUrl) ?>"
                                            class="package-button">

                                            <span>
                                                Book Now
                                            </span>

                                            <svg
                                                viewBox="0 0 24 24"
                                                aria-hidden="true">

                                                <path
                                                    d="M5 12h14"></path>

                                                <path
                                                    d="m13 6 6 6-6 6"></path>

                                            </svg>

                                        </a>

                                    <?php endif; ?>

                                </div>


                                <!-- DECORATIVE BOTTOM -->

                                <div class="package-bottom-decoration">

                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>

                                </div>

                            </div>


                            <!-- RIGHT IMAGE -->

                            <div class="package-image">

                                <img
                                    src="<?= htmlspecialchars(
                                                $package["image"]
                                            ) ?>"
                                    alt="<?= htmlspecialchars(
                                                $package["package_name"]
                                            ) ?>"
                                    loading="lazy">

                                <!-- IMPORTANT:
                                 This overlay no longer blocks buttons -->

                                <div class="package-image-overlay"></div>


                                <!-- VERTICAL DECORATION -->

                                <div class="image-decoration">

                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="no-packages">

                        <div class="no-packages-icon">

                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true">

                                <rect
                                    x="3"
                                    y="4"
                                    width="18"
                                    height="16"
                                    rx="2"></rect>

                                <path d="M8 9h8"></path>

                                <path d="M8 13h5"></path>

                            </svg>

                        </div>

                        <h3>
                            No Event Packages Yet
                        </h3>

                        <p>
                            Our event packages will appear here soon.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
             PAGINATION
        ================================================== -->

            <div
                class="events-pagination"
                id="eventsPagination"
                aria-label="Event package pages"></div>


            <!-- =================================================
             NO SEARCH RESULTS
        ================================================== -->

            <div
                class="no-search-results"
                id="noSearchResults">

                <div class="no-results-icon">

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true">

                        <circle
                            cx="11"
                            cy="11"
                            r="7"></circle>

                        <path
                            d="m20 20-3.5-3.5"></path>

                    </svg>

                </div>

                <h3>
                    No Packages Found
                </h3>

                <p>
                    Try changing your search or selecting another
                    event type.
                </p>

            </div>

        </section>

    </main>


    <!-- =========================================================
     CTA
========================================================= -->

    <section class="events-cta">

        <div class="events-cta-content">

            <span>
                READY TO CREATE SOMETHING SPECIAL?
            </span>

            <h2>

                Let's Plan Your

                <strong>
                    Perfect Event
                </strong>

            </h2>

            <p>
                Tell us about your event and let our team
                help bring your vision to life.
            </p>

            <a
                href="contact.php"
                class="cta-button">

                Contact Us

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true">

                    <path d="M5 12h14"></path>

                    <path d="m13 6 6 6-6 6"></path>

                </svg>

            </a>

        </div>

    </section>


    <?php include "includes/footer.php"; ?>


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script src="js/header.js"></script>
    <script src="js/events.js"></script>

</body>

</html>
