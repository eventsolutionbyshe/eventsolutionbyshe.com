<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {

    header("Location: auth/login.php");

    exit;
}


require_once "config/database.php";


/* =========================================================
   VALIDATE VENUE ID
========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: venues.php");

    exit;
}


$venueId = (int) $_GET["id"];


/* =========================================================
   GET VENUE INFORMATION
========================================================= */

$stmt = $conn->prepare("
    SELECT 
        id,
        venue_name,
        description,
        location,
        image
    FROM venues
    WHERE id = ?
    LIMIT 1
");


$stmt->bind_param(
    "i",
    $venueId
);


$stmt->execute();


$result = $stmt->get_result();


$venue = $result->fetch_assoc();


$stmt->close();


/* =========================================================
   VENUE NOT FOUND
========================================================= */

if (!$venue) {

    header("Location: venues.php");

    exit;
}


/* =========================================================
   VENUE IMAGE
========================================================= */

$imagePath = "";


if (!empty($venue["image"])) {

    /*
     * If database contains:
     *
     * venues/example.jpg
     *
     * or
     *
     * images/venues/example.jpg
     *
     * use the stored path.
     *
     * Otherwise:
     *
     * example.jpg
     *
     * becomes:
     *
     * images/venues/example.jpg
     */

    if (
        str_starts_with(
            $venue["image"],
            "venues/"
        )
        ||
        str_starts_with(
            $venue["image"],
            "images/"
        )
    ) {

        $imagePath =
            $venue["image"];

    } else {

        $imagePath =
            "images/venues/" .
            $venue["image"];

    }

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

        <?= htmlspecialchars(
            $venue["venue_name"]
        ) ?>

        |

        Event Solutions by S.H.E

    </title>


    <!-- =====================================================
         GOOGLE FONTS
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
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
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
         VIEW VENUE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/view_venue.css"
    >

</head>


<body class="dark-mode">


<!-- =========================================================
     HEADER
========================================================= -->

<?php include "includes/header.php"; ?>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="view-venue-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="venue-hero">


        <div class="venue-hero-overlay"></div>


        <div class="venue-hero-content">


            <span class="venue-label">

                OUR VENUE

            </span>


            <h1>

                <?= htmlspecialchars(
                    $venue["venue_name"]
                ) ?>

            </h1>


            <?php if (!empty($venue["location"])): ?>

                <p class="venue-location">

                    <span aria-hidden="true">
                        📍
                    </span>

                    <?= htmlspecialchars(
                        $venue["location"]
                    ) ?>

                </p>

            <?php endif; ?>


        </div>


    </section>



    <!-- =====================================================
         VENUE DETAILS
    ====================================================== -->

    <section class="venue-details">


        <div class="venue-details-container">


            <!-- =================================================
                 VENUE IMAGE
            ================================================== -->

            <div class="venue-image-wrapper">


                <?php if (!empty($imagePath)): ?>


                    <img
                        src="<?= htmlspecialchars($imagePath) ?>"
                        alt="<?= htmlspecialchars($venue["venue_name"]) ?>"
                        class="venue-image"
                    >


                <?php else: ?>


                    <div class="venue-image-placeholder">


                        <span>

                            No Image Available

                        </span>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 VENUE INFORMATION
            ================================================== -->

            <div class="venue-information">


                <!-- BRAND -->

                <span class="section-label">

                    EVENT SOLUTIONS BY S.H.E

                </span>


                <!-- VENUE NAME -->

                <h2>

                    <?= htmlspecialchars(
                        $venue["venue_name"]
                    ) ?>

                </h2>



                <!-- =================================================
                     LOCATION
                ================================================== -->

                <?php if (!empty($venue["location"])): ?>


                    <div class="venue-info-location">


                        <span
                            class="location-icon"
                            aria-hidden="true"
                        >

                            📍

                        </span>


                        <span>

                            <?= htmlspecialchars(
                                $venue["location"]
                            ) ?>

                        </span>


                    </div>


                <?php endif; ?>



                <!-- =================================================
                     DESCRIPTION
                ================================================== -->

                <?php if (!empty($venue["description"])): ?>


                    <div class="venue-description">


                        <h3>

                            About This Venue

                        </h3>


                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $venue["description"]
                                )
                            ) ?>

                        </p>


                    </div>


                <?php endif; ?>



                <!-- =================================================
                     ACTIONS
                ================================================== -->

                <div class="venue-actions">


                    <!-- BACK -->

                    <a
                        href="venues.php"
                        class="back-button"
                    >

                        <span
                            aria-hidden="true"
                        >
                            ←
                        </span>

                        Back to Venues

                    </a>


                    <!-- CONTACT -->

                    <a
                        href="contact.php"
                        class="contact-button"
                    >

                        Contact Us

                        <span
                            aria-hidden="true"
                        >
                            →
                        </span>

                    </a>


                </div>


            </div>


        </div>


    </section>


</main>



<!-- =========================================================
     CALL TO ACTION
========================================================= -->

<section class="venue-cta">


    <div class="venue-cta-content">


        <span class="venue-cta-label">

            READY TO CREATE SOMETHING BEAUTIFUL?

        </span>


        <h2>

            Make Your Event

            <strong>
                Unforgettable
            </strong>

        </h2>


        <p>

            Let Event Solutions by S.H.E. help you transform
            this beautiful venue into an experience your guests
            will remember.

        </p>


        <a
            href="contact.php"
            class="venue-cta-button"
        >

            Contact Us

            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >

                <path
                    d="M5 12h14"
                ></path>

                <path
                    d="M13 6l6 6-6 6"
                ></path>

            </svg>

        </a>


    </div>


</section>



<!-- =========================================================
     MODERN FOOTER
========================================================= -->

<footer class="site-footer">


    <div class="footer-container">


        <!-- =================================================
             FOOTER BRAND
        ================================================== -->

        <div class="footer-brand">


            <a
                href="index.php"
                class="footer-logo"
            >


                <img
                    src="images/logo.png"
                    alt="Event Solutions by S.H.E"
                >


            </a>


            <p>

                Creating memorable experiences
                through professional event
                solutions.

            </p>


        </div>



        <!-- =================================================
             FOOTER LINKS
        ================================================== -->

        <div class="footer-links">


            <!-- =================================================
                 NAVIGATION
            ================================================== -->

            <div>


                <h4>

                    Navigation

                </h4>


                <a href="index.php">

                    Home

                </a>


                <a href="events.php">

                    Events

                </a>


                <a href="venues.php">

                    Venues

                </a>


                <a href="hosts.php">

                    Hosts

                </a>


            </div>



            <!-- =================================================
                 COMPANY
            ================================================== -->

            <div>


                <h4>

                    Company

                </h4>


                <a href="services.php">

                    Services

                </a>


                <a href="contact.php">

                    Contact

                </a>


                <a href="auth/login.php">

                    Login

                </a>


                <a href="auth/signup.php">

                    Sign Up

                </a>


            </div>



            <!-- =================================================
                 ACCOUNT
            ================================================== -->

            <div>


                <h4>

                    Account

                </h4>


                <a href="profile.php">

                    My Profile

                </a>


                <a href="my-events.php">

                    My Events

                </a>


            </div>


        </div>


    </div>



    <!-- =================================================
         FOOTER BOTTOM
    ================================================== -->

    <div class="footer-bottom">


        <p>

            &copy;

            <?= date("Y") ?>

            Event Solutions by S.H.E.

            All rights reserved.

        </p>


    </div>


</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="js/header.js"></script>


<script src="js/view_venue.js"></script>


</body>

</html>