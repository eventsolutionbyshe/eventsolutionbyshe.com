<?php

/* =========================================================
   SERVICES PAGE
   EVENT SOLUTIONS BY S.H.E.
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

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
    Services | Event Solutions by S.H.E.
</title>


<!-- =====================================================
     GOOGLE FONTS
====================================================== -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Montserrat:wght@400;500;600;700&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
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
     SERVICES CSS
====================================================== -->

<link
    rel="stylesheet"
    href="css/services.css"
>


<!-- =====================================================
     FOOTER CSS
====================================================== -->

<link
    rel="stylesheet"
    href="css/footer.css"
>

</head>


<body>


<?php include "includes/header.php"; ?>


<!-- =========================================================
     SERVICES HERO
========================================================= -->

<section class="services-hero">

    <div class="services-hero-overlay"></div>

    <div class="services-hero-content">

        <span class="services-eyebrow">
            EVENT SOLUTIONS BY S.H.E.
        </span>

        <h1>
            Our
            <span>Services</span>
        </h1>

        <p>
            Professional event solutions thoughtfully designed
            to turn your ideas into beautifully organized experiences.
        </p>

        <div class="hero-line"></div>

    </div>

</section>


<!-- =========================================================
     SERVICES PAGE
========================================================= -->

<main class="services-page">


<!-- =====================================================
     SERVICES SECTION
====================================================== -->

<section class="services-section">


<!-- =================================================
     SERVICES HEADING
================================================== -->

<div class="services-heading">

    <div class="heading-left">

        <span class="section-label">
            WHAT WE OFFER
        </span>

        <h2>
            Everything Your Event Needs
        </h2>

    </div>


    <div class="heading-right">

        <p>
            From the first idea to the final celebration,
            our services bring together planning, coordination,
            styling, venues, and entertainment in one seamless
            event experience.
        </p>

    </div>

</div>


<!-- =================================================
     SERVICES GRID
================================================== -->

<div class="services-grid">


    <!-- =================================================
         01 EVENT PLANNING
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/event-planning.jpg"
                alt="Event Planning"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                01
            </span>

            <span class="service-image-label">
                PLANNING
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                PLANNING
            </span>

            <h3>
                Event Planning
            </h3>

            <p>
                A structured approach to planning your event
                from concept and budget to the final program.
            </p>

        </div>

    </article>


    <!-- =================================================
         02 EVENT COORDINATION
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/event_coordinator.jpg"
                alt="Event Coordination"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                02
            </span>

            <span class="service-image-label">
                COORDINATION
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                COORDINATION
            </span>

            <h3>
                Event Coordination
            </h3>

            <p>
                Keep your event organized and running smoothly
                with professional coordination and on-site support.
            </p>

        </div>

    </article>


    <!-- =================================================
         03 VENUE ASSISTANCE
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/esprutingkle_business_hotel_20260913175547_cfbf63.jpg"
                alt="Venue Assistance"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                03
            </span>

            <span class="service-image-label">
                VENUE
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                VENUE
            </span>

            <h3>
                Venue Assistance
            </h3>

            <p>
                Find and prepare a venue that matches your
                event requirements, guest capacity, and layout.
            </p>

        </div>

    </article>


    <!-- =================================================
         04 ENTERTAINMENT
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/img1.jpeg"
                alt="Event Entertainment"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                04
            </span>

            <span class="service-image-label">
                ENTERTAINMENT
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                ENTERTAINMENT
            </span>

            <h3>
                Entertainment
            </h3>

            <p>
                Bring energy and personality to your event
                with carefully selected entertainment options.
            </p>

        </div>

    </article>


    <!-- =================================================
         05 EVENT STYLING
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/img2.jpeg"
                alt="Event Styling"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                05
            </span>

            <span class="service-image-label">
                STYLING
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                STYLING
            </span>

            <h3>
                Event Styling
            </h3>

            <p>
                Create a polished atmosphere through thoughtful
                themes, colors, decorations, and visual details.
            </p>

        </div>

    </article>


    <!-- =================================================
         06 SPECIAL EVENTS
    ================================================== -->

    <article class="service-card">

        <div class="service-image">

            <img
                src="images/img7.jpeg"
                alt="Special Events"
                loading="lazy"
            >

            <div class="image-overlay"></div>

            <span class="service-number">
                06
            </span>

            <span class="service-image-label">
                SPECIAL EVENTS
            </span>

        </div>


        <div class="service-card-content">

            <span class="service-category">
                SPECIAL EVENTS
            </span>

            <h3>
                Special Events
            </h3>

            <p>
                Flexible solutions customized around your
                occasion, theme, requirements, and celebration.
            </p>

        </div>

    </article>


</div>

</section>


<!-- =========================================================
     WHY CHOOSE US
========================================================= -->

<section class="services-benefits">


<div class="services-benefits-heading">

    <span class="section-label">
        WHY CHOOSE US
    </span>

    <h2>
        Designed Around Your Event
    </h2>

    <p>
        We bring planning, organization, creativity, and
        coordination together to make every celebration
        easier and more memorable.
    </p>

</div>


<!-- =====================================================
     BENEFITS GRID
====================================================== -->

<div class="services-benefits-grid">


    <!-- =================================================
         BENEFIT 01
    ================================================== -->

    <div class="service-benefit">

        <span class="benefit-number">
            01
        </span>

        <div class="benefit-icon">
            <span></span>
        </div>

        <h3>
            Personalized Planning
        </h3>

        <p>
            Every event is shaped around your preferences,
            requirements, guests, and celebration goals.
        </p>

    </div>


    <!-- =================================================
         BENEFIT 02
    ================================================== -->

    <div class="service-benefit">

        <span class="benefit-number">
            02
        </span>

        <div class="benefit-icon">
            <span></span>
        </div>

        <h3>
            Professional Coordination
        </h3>

        <p>
            We help organize schedules, suppliers, activities,
            and important event-day details.
        </p>

    </div>


    <!-- =================================================
         BENEFIT 03
    ================================================== -->

    <div class="service-benefit">

        <span class="benefit-number">
            03
        </span>

        <div class="benefit-icon">
            <span></span>
        </div>

        <h3>
            Memorable Experiences
        </h3>

        <p>
            We focus on the details that make your event
            organized, welcoming, beautiful, and memorable.
        </p>

    </div>


</div>

</section>


</main>


<!-- =========================================================
     CTA
========================================================= -->

<section class="services-cta">

<div class="services-cta-content">

    <span>
        LET'S CREATE SOMETHING SPECIAL
    </span>

    <h2>
        Your Event.
        <strong>Our Expertise.</strong>
    </h2>

    <p>
        Let us help you create an event that is beautifully
        organized, professionally coordinated, and memorable.
    </p>

    <a
        href="contact.php"
        class="cta-button"
    >

        Start Planning

        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >

            <path d="M5 12h14"></path>

            <path d="M13 6l6 6-6 6"></path>

        </svg>

    </a>

</div>

</section>


<?php include "includes/footer.php"; ?>


<script src="js/header.js"></script>

<script src="js/services.js"></script>


</body>

</html>