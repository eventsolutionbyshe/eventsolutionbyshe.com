<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   GET PACKAGE ID
========================================================= */

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: events.php");
    exit;
}

$packageId = (int) $_GET["id"];


/* =========================================================
   GET PACKAGE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.package_name,
        p.inclusions,
        p.image,
        et.name AS event_type
    FROM event_packages p
    LEFT JOIN event_types et
        ON p.event_type_id = et.id
    WHERE p.id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        "Unable to prepare package query: " .
        htmlspecialchars($conn->error)
    );
}

$stmt->bind_param(
    "i",
    $packageId
);

$stmt->execute();

$result = $stmt->get_result();

$package = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   PACKAGE NOT FOUND
========================================================= */

if (!$package) {
    header("Location: events.php");
    exit;
}


/* =========================================================
   PACKAGE IMAGE
========================================================= */

$packageImage = trim(
    (string) ($package["image"] ?? "")
);

if ($packageImage === "") {

    $packageImage = "images/logo.png";

} elseif (
    !preg_match(
        '/^(https?:)?\/\//i',
        $packageImage
    ) &&
    strpos($packageImage, "/") !== 0
) {

    if (
        strpos($packageImage, "images/") !== 0 &&
        strpos($packageImage, "images\\") !== 0
    ) {

        $packageImage =
            "images/" . $packageImage;
    }
}


/* =========================================================
   EVENT TYPE
========================================================= */

$eventType =
    $package["event_type"] ?? "";


/* =========================================================
   PACKAGE INCLUSIONS
========================================================= */

$inclusions = [];

if (!empty($package["inclusions"])) {

    $inclusions = preg_split(
        "/\r\n|\r|\n/",
        $package["inclusions"]
    );

    $inclusions = array_filter(
        array_map(
            "trim",
            $inclusions
        )
    );
}


/* =========================================================
   BOOKING LINK
========================================================= */

$bookingUrl =
    "book_now.php?package_id=" .
    $packageId;

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
            $package["package_name"]
        ) ?>
        | Event Solutions by S.H.E.
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
        href="https://fonts.googleapis.com"
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

    <link
        rel="stylesheet"
        href="css/footer.css"
    >


    <!-- =====================================================
         VIEW PACKAGE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/view_package.css"
    >

</head>


<body>


<?php include "includes/header.php"; ?>


<main class="package-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="package-hero">

        <div class="package-hero-overlay"></div>

        <div
            class="package-hero-decoration package-decoration-one"
            aria-hidden="true"
        ></div>

        <div
            class="package-hero-decoration package-decoration-two"
            aria-hidden="true"
        ></div>


        <div class="package-hero-content">

            <span class="package-label">
                EVENT PACKAGE
            </span>


            <h1>

                <?= htmlspecialchars(
                    $package["package_name"]
                ) ?>

            </h1>


            <?php if (!empty($eventType)): ?>

                <div class="hero-event-type">

                    <?= htmlspecialchars(
                        $eventType
                    ) ?>

                </div>

            <?php endif; ?>


            <p>
                Explore the details and inclusions
                of this event package from
                Event Solutions by S.H.E.
            </p>

        </div>

    </section>



    <!-- =====================================================
         PACKAGE CONTENT
    ====================================================== -->

    <section class="package-section">

        <div class="package-container">


            <!-- =================================================
                 BREADCRUMB
            ================================================== -->

            <div class="package-breadcrumb">

                <a href="events.php">
                    Events
                </a>

                <span aria-hidden="true">
                    /
                </span>

                <span>
                    <?= htmlspecialchars(
                        $package["package_name"]
                    ) ?>
                </span>

            </div>



            <!-- =================================================
                 PACKAGE CARD
            ================================================== -->

            <div class="package-card">


                <!-- =================================================
                     IMAGE
                ================================================== -->

                <div class="package-image">

                    <img
                        src="<?= htmlspecialchars(
                            $packageImage
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $package["package_name"]
                        ) ?>"
                        loading="eager"
                        onerror="
                            this.style.display='none';
                            this.parentElement.classList.add('image-failed');
                        "
                    >


                    <div
                        class="package-image-overlay"
                        aria-hidden="true"
                    ></div>


                    <div
                        class="image-decoration"
                        aria-hidden="true"
                    >

                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>

                    </div>


                    <div class="image-caption">

                        <span>
                            EVENT SOLUTIONS BY S.H.E.
                        </span>

                    </div>

                </div>



                <!-- =================================================
                     DETAILS
                ================================================== -->

                <div class="package-details">


                    <span class="package-brand">
                        EVENT SOLUTIONS BY S.H.E.
                    </span>


                    <h2>

                        <?= htmlspecialchars(
                            $package["package_name"]
                        ) ?>

                    </h2>


                    <?php if (!empty($eventType)): ?>

                        <div class="event-type">

                            <?= htmlspecialchars(
                                $eventType
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <div class="package-divider"></div>



                    <!-- =================================================
                         INCLUSIONS
                    ================================================== -->

                    <div class="package-inclusions">

                        <div class="section-heading">

                            <span class="heading-number">
                                01
                            </span>

                            <div>

                                <h3>
                                    Package Includes
                                </h3>

                                <p>
                                    Everything included
                                    in this package.
                                </p>

                            </div>

                        </div>


                        <?php if (!empty($inclusions)): ?>

                            <ul class="inclusions">

                                <?php foreach (
                                    $inclusions
                                    as $inclusion
                                ): ?>

                                    <li>

                                        <span
                                            class="check"
                                            aria-hidden="true"
                                        >

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >

                                                <polyline
                                                    points="20 6 9 17 4 12"
                                                ></polyline>

                                            </svg>

                                        </span>


                                        <span class="inclusion-text">

                                            <?= htmlspecialchars(
                                                $inclusion
                                            ) ?>

                                        </span>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        <?php else: ?>

                            <div class="no-inclusions">

                                <span
                                    class="no-inclusions-icon"
                                    aria-hidden="true"
                                >
                                    i
                                </span>

                                <p>
                                    Package details are
                                    currently being updated.
                                    Please contact our team
                                    for more information.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>



                    <!-- =================================================
                         BOOKING NOTE
                    ================================================== -->

                    <div class="package-note">

                        <span
                            class="package-note-icon"
                            aria-hidden="true"
                        >
                            ✓
                        </span>

                        <div>

                            <strong>
                                Ready to plan your event?
                            </strong>

                            <p>
                                Submit a booking request
                                and our team will review
                                your event details.
                            </p>

                        </div>

                    </div>



                    <!-- =================================================
                         ACTIONS
                    ================================================== -->

                    <div class="package-actions">

                        <a
                            href="<?= htmlspecialchars(
                                $bookingUrl
                            ) ?>"
                            class="book-button"
                        >

                            <span>
                                Book This Package
                            </span>

                            <span
                                class="button-arrow"
                                aria-hidden="true"
                            >
                                →
                            </span>

                        </a>


                        <a
                            href="events.php"
                            class="back-button"
                        >

                            <span aria-hidden="true">
                                ←
                            </span>

                            <span>
                                Back to Events
                            </span>

                        </a>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 BOTTOM INFORMATION
            ================================================== -->

            <div class="package-bottom-grid">


                <!-- CONTACT CARD -->

                <div class="package-info-card">

                    <div
                        class="info-card-icon"
                        aria-hidden="true"
                    >
                        ?
                    </div>

                    <div>

                        <span class="info-card-label">
                            NEED HELP?
                        </span>

                        <h3>
                            Have questions about
                            this package?
                        </h3>

                        <p>
                            Our team is happy to help
                            you understand the package
                            and plan your event.
                        </p>

                        <a href="contact.php">

                            Contact Us

                            <span aria-hidden="true">
                                →
                            </span>

                        </a>

                    </div>

                </div>



                <!-- BOOKING CARD -->

                <div class="package-info-card package-info-card-dark">

                    <div
                        class="info-card-icon"
                        aria-hidden="true"
                    >
                        ✦
                    </div>

                    <div>

                        <span class="info-card-label">
                            NEXT STEP
                        </span>

                        <h3>
                            Make your event
                            unforgettable.
                        </h3>

                        <p>
                            Choose your date, venue,
                            and event details to
                            get started.
                        </p>

                        <a
                            href="<?= htmlspecialchars(
                                $bookingUrl
                            ) ?>"
                        >

                            Start Booking

                            <span aria-hidden="true">
                                →
                            </span>

                        </a>

                    </div>

                </div>

            </div>


        </div>

    </section>


</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<?php include "includes/footer.php"; ?>


<script src="js/header.js"></script>

</body>

</html>