<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn = isset($_SESSION["user_id"]);


/* =========================================================
   VENUES
========================================================= */

$venues = [];
$venueTypes = [];


/* =========================================================
   FETCH VENUES
========================================================= */

$sql = "
    SELECT
        id,
        venue_name,
        location,
        venue_type,
        description,
        image,
        status
    FROM venues
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $venues[] = $row;

        if (!empty($row["venue_type"])) {
            $venueTypes[] = $row["venue_type"];
        }
    }
}


/* =========================================================
   REMOVE DUPLICATE VENUE TYPES
========================================================= */

$venueTypes = array_unique($venueTypes);

sort($venueTypes);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Venues | Event Solutions by S.H.E.
    </title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

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


    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/header.css">

    <link
        rel="stylesheet"
        href="css/footer.css">


    <!-- =====================================================
         VENUES CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/venues.css">

</head>


<body>


    <!-- =========================================================
     HEADER
========================================================= -->

    <?php include "includes/header.php"; ?>


    <!-- =========================================================
     HERO
========================================================= -->

    <section class="venues-hero">

        <div class="venues-hero-overlay"></div>


        <div class="venues-hero-content">

            <span class="venues-eyebrow">
                FIND THE PERFECT SETTING
            </span>


            <h1>

                Beautiful

                <span>
                    Venues
                </span>

            </h1>


            <p>

                Discover elegant and memorable spaces designed
                to bring your special events to life.

            </p>

        </div>

    </section>


    <!-- =========================================================
     MAIN VENUES
========================================================= -->

    <main class="venues-page">

        <section class="venues-section">


            <!-- =================================================
             SECTION HEADING
        ================================================== -->

            <div class="venues-heading">


                <span class="section-label">

                    OUR VENUE COLLECTION

                </span>


                <h2>

                    Find Your Perfect Venue

                </h2>


                <p>

                    Explore our selection of beautiful event spaces,
                    each carefully chosen to create the perfect
                    atmosphere for your celebration.

                </p>


            </div>


            <!-- =================================================
             SEARCH AND FILTER
        ================================================== -->

            <div class="venues-controls">


                <!-- SEARCH -->

                <div class="venue-search">


                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true">

                        <circle
                            cx="11"
                            cy="11"
                            r="7"></circle>


                        <path
                            d="M20 20l-4-4"></path>

                    </svg>


                    <input
                        type="text"
                        id="venueSearch"
                        placeholder="Search venues..."
                        autocomplete="off">


                </div>


                <!-- FILTER -->

                <div class="venue-filter">


                    <label for="venueType">

                        VENUE TYPE

                    </label>


                    <select id="venueType">


                        <option value="all">

                            All Venues

                        </option>


                        <?php foreach ($venueTypes as $type): ?>

                            <option
                                value="<?= htmlspecialchars(
                                            strtolower($type)
                                        ) ?>">

                                <?= htmlspecialchars($type) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>


                </div>


            </div>


            <!-- =================================================
             RESULT COUNT
        ================================================== -->

            <div
                class="venue-result-info"
                id="venueResultInfo">

                <?= count($venues) ?>

                <?= count($venues) === 1
                    ? "venue"
                    : "venues" ?>

                available

            </div>


            <?php if (!empty($venues)): ?>


                <!-- =================================================
                 VENUE GRID
            ================================================== -->

                <div
                    class="venues-grid"
                    id="venuesGrid">


                    <?php foreach ($venues as $venue): ?>


                        <?php

                        $venueName =
                            $venue["venue_name"];

                        $location =
                            $venue["location"];

                        $venueType =
                            $venue["venue_type"];

                        $description =
                            $venue["description"];

                        $image =
                            $venue["image"];

                        $status =
                            strtolower(
                                $venue["status"]
                            );

                        ?>


                        <!-- =================================================
                         VENUE CARD
                    ================================================== -->

                        <article
                            class="venue-card"

                            data-name="<?= htmlspecialchars(
                                            strtolower($venueName)
                                        ) ?>"

                            data-location="<?= htmlspecialchars(
                                                strtolower($location)
                                            ) ?>"

                            data-type="<?= htmlspecialchars(
                                            strtolower($venueType)
                                        ) ?>">


                            <!-- =================================================
                             VENUE IMAGE
                        ================================================== -->

                            <div class="venue-image">


                                <?php if (!empty($image)): ?>


                                    <img
                                        src="<?= htmlspecialchars($image) ?>"
                                        alt="<?= htmlspecialchars($venueName) ?>"
                                        loading="lazy">


                                <?php else: ?>


                                    <div class="venue-image-placeholder">


                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true">


                                            <rect
                                                x="3"
                                                y="4"
                                                width="18"
                                                height="16"
                                                rx="2"></rect>


                                            <circle
                                                cx="8"
                                                cy="9"
                                                r="1.5"></circle>


                                            <path
                                                d="M21 15l-5-5L5 20"></path>


                                        </svg>


                                    </div>


                                <?php endif; ?>


                                <div class="venue-image-overlay"></div>


                                <!-- STATUS -->

                                <span
                                    class="venue-status <?= $status === "available"
                                                            ? "available"
                                                            : "unavailable" ?>">


                                    <span class="status-dot"></span>


                                    <?= htmlspecialchars(
                                        ucfirst($status)
                                    ) ?>


                                </span>


                            </div>


                            <!-- =================================================
                             VENUE CONTENT
                        ================================================== -->

                            <div class="venue-content">


                                <!-- BRAND -->

                                <span class="venue-brand">

                                    EVENT SOLUTIONS BY S.H.E.

                                </span>


                                <!-- VENUE NAME -->

                                <h3>

                                    <?= htmlspecialchars(
                                        $venueName
                                    ) ?>

                                </h3>


                                <!-- =================================================
                                 VENUE META
                            ================================================== -->

                                <div class="venue-meta">


                                    <!-- LOCATION -->

                                    <span>


                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true">


                                            <path
                                                d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"></path>


                                            <circle
                                                cx="12"
                                                cy="9"
                                                r="2.3"></circle>


                                        </svg>


                                        <?= htmlspecialchars(
                                            $location
                                        ) ?>


                                    </span>


                                    <!-- VENUE TYPE -->

                                    <?php if (!empty($venueType)): ?>


                                        <span>


                                            <svg
                                                viewBox="0 0 24 24"
                                                aria-hidden="true">


                                                <rect
                                                    x="3"
                                                    y="5"
                                                    width="18"
                                                    height="14"
                                                    rx="2"></rect>


                                                <path
                                                    d="M8 5V3M16 5V3M3 10h18"></path>


                                            </svg>


                                            <?= htmlspecialchars(
                                                $venueType
                                            ) ?>


                                        </span>


                                    <?php endif; ?>


                                </div>


                                <!-- DIVIDER -->

                                <div class="venue-divider"></div>


                                <!-- DESCRIPTION -->

                                <p class="venue-description">


                                    <?= htmlspecialchars(

                                        !empty($description)

                                            ? $description

                                            : "A beautiful venue designed for memorable celebrations and special occasions."

                                    ) ?>


                                </p>


                                <!-- =================================================
                                 VENUE BUTTON
                            ================================================== -->

                                <div class="venue-card-bottom">


                                    <a

                                        href="view_venue.php?id=<?= (int) $venue["id"] ?>"

                                        class="venue-button"

                                        data-venue="<?= htmlspecialchars(
                                                        $venueName
                                                    ) ?>"

                                        data-logged-in="<?= $isLoggedIn
                                                            ? "true"
                                                            : "false" ?>">


                                        <?php if ($isLoggedIn): ?>


                                            View Venue


                                        <?php else: ?>


                                            Login to View Details


                                        <?php endif; ?>


                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true">


                                            <path
                                                d="M5 12h14"></path>


                                            <path
                                                d="M13 6l6 6-6 6"></path>


                                        </svg>


                                    </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


                <!-- =================================================
                 NO SEARCH RESULTS
            ================================================== -->

                <div
                    class="no-venue-results"
                    id="noVenueResults">


                    <div class="no-results-icon">


                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true">


                            <circle
                                cx="11"
                                cy="11"
                                r="7"></circle>


                            <path
                                d="M20 20l-4-4"></path>


                        </svg>


                    </div>


                    <h3>

                        No Venues Found

                    </h3>


                    <p>

                        Try searching for another venue or selecting
                        a different venue type.

                    </p>


                </div>


                <!-- =================================================
                 PAGINATION
            ================================================== -->

                <nav
                    class="venues-pagination"
                    id="venuesPagination"
                    aria-label="Venue pagination"></nav>


            <?php else: ?>


                <!-- =================================================
                 NO VENUES
            ================================================== -->

                <div class="no-venues">


                    <div class="no-venues-icon">


                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true">


                            <rect
                                x="3"
                                y="4"
                                width="18"
                                height="16"
                                rx="2"></rect>


                            <path
                                d="M7 9h10M7 13h7"></path>


                        </svg>


                    </div>


                    <h3>

                        No Venues Available

                    </h3>


                    <p>

                        Our venue collection is currently being updated.
                        Please check back soon.

                    </p>


                </div>


            <?php endif; ?>


        </section>

    </main>


    <!-- =========================================================
     CALL TO ACTION
========================================================= -->

    <section class="venues-cta">


        <div class="venues-cta-content">


            <span>

                LET'S CREATE SOMETHING BEAUTIFUL

            </span>


            <h2>

                Found Your

                <strong>
                    Perfect Venue?
                </strong>

            </h2>


            <p>

                Let us help you turn the space into an unforgettable
                experience for you and your guests.

            </p>


            <a
                href="contact.php"
                class="cta-button">


                Contact Us


                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true">


                    <path
                        d="M5 12h14"></path>


                    <path
                        d="M13 6l6 6-6 6"></path>


                </svg>


            </a>


        </div>


    </section>

    <?php include "includes/footer.php"; ?>

    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script src="js/header.js"></script>

    <script src="js/venues.js"></script>


</body>

</html>
