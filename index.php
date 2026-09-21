<?php session_start();
require_once "config/database.php"; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Event Solution by S.H.E</title>
    <meta name="title" content="Event Solution by S.H.E" />
    <meta name="description" content="Event Solutions by S.H.E provides professional event planning, event packages, venues, singers, hosts, and personalized event coordination for memorable celebrations." />


    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://eventsolution.page.gd/" />
    <meta property="og:title" content="Event Solution by S.H.E" />
    <meta property="og:description" content="Event Solutions by S.H.E provides professional event planning, event packages, venues, singers, hosts, and personalized event coordination for memorable celebrations." />
    <meta property="og:image" content="https://i.imgur.com/KQVu2Iq.png" />
    <meta property="og:site_name" content="Event Solution by S.H.E" />


    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="https://eventsolution.page.gd/" />
    <meta property="twitter:title" content="Event Solution by S.H.E" />
    <meta property="twitter:description" content="Event Solutions by S.H.E provides professional event planning, event packages, venues, singers, hosts, and personalized event coordination for memorable celebrations." />
    <meta property="twitter:image" content="https://i.imgur.com/KQVu2Iq.png" />

    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">

</head>


<body>

    <?php include "includes/header.php"; ?>

    <section
        class="hero" aria-label="Event Solutions by S.H.E introduction">


        <div class="hero-glow hero-glow-one"></div>
        <div class="hero-glow hero-glow-two"></div>
        <div class="hero-circle hero-circle-one"></div>
        <div class="hero-circle hero-circle-two"></div>


        <div class="hero-container">

            <div class="hero-content">

                <span class="hero-label">
                    EVENT SOLUTIONS BY S.H.E
                </span>

                <h1>
                    Creating

                    <span>
                        Unforgettable
                    </span>

                    Moments
                </h1>


                <p>
                    From intimate celebrations to
                    unforgettable gatherings, we bring
                    your vision to life with creativity,
                    elegance, and professional event
                    coordination.
                </p>


                <div class="hero-buttons">


                    <a
                        href="events.php"
                        class="hero-button primary">

                        Explore Events

                    </a>


                    <a
                        href="services.php"
                        class="hero-button secondary">

                        Plan Your Event

                    </a>


                </div>


                <!-- =================================================
                     HERO INFO
                ================================================== -->

                <div class="hero-info">


                    <div class="hero-info-item">

                        <strong>
                            01
                        </strong>

                        <span>
                            Choose
                        </span>

                    </div>


                    <div class="hero-info-line"></div>


                    <div class="hero-info-item">

                        <strong>
                            02
                        </strong>

                        <span>
                            Customize
                        </span>

                    </div>


                    <div class="hero-info-line"></div>


                    <div class="hero-info-item">

                        <strong>
                            03
                        </strong>

                        <span>
                            Celebrate
                        </span>

                    </div>


                </div>


            </div>


            <!-- =====================================================
                 HERO CARD AREA
            ====================================================== -->

            <div
                class="hero-card-area"
                aria-label="Featured event images">


                <div class="hero-card-heading">

                    <span>
                        YOUR MOMENT
                    </span>

                    <small>
                        Explore our celebrations
                    </small>

                </div>


                <!-- HERO CARD 01 -->

                <div
                    class="hero-card hero-card-one active"
                    data-slide="0"
                    tabindex="0">


                    <img
                        src="images/img1.jpeg"
                        alt="Elegant event celebration"
                        fetchpriority="high"
                        decoding="async">


                    <div class="hero-card-overlay"></div>


                    <div class="hero-card-content">

                        <span>
                            01
                        </span>

                        <strong>
                            Beautiful
                            Celebrations
                        </strong>

                    </div>


                </div>


                <!-- HERO CARD 02 -->

                <div
                    class="hero-card hero-card-two"
                    data-slide="1"
                    tabindex="0">


                    <img
                        src="images/img2.jpeg"
                        alt="Special event gathering"
                        loading="lazy"
                        decoding="async">


                    <div class="hero-card-overlay"></div>


                    <div class="hero-card-content">

                        <span>
                            02
                        </span>

                        <strong>
                            Meaningful
                            Moments
                        </strong>

                    </div>


                </div>


                <!-- HERO CARD 03 -->

                <div
                    class="hero-card hero-card-three"
                    data-slide="2"
                    tabindex="0">


                    <img
                        src="images/img8.jpeg"
                        alt="Memorable celebration"
                        loading="lazy"
                        decoding="async">


                    <div class="hero-card-overlay"></div>


                    <div class="hero-card-content">

                        <span>
                            03
                        </span>

                        <strong>
                            Extraordinary
                            Events
                        </strong>

                    </div>


                </div>


                <!-- HERO CARD DOTS -->

                <div
                    class="hero-card-dots"
                    role="tablist"
                    aria-label="Featured images">


                    <button
                        type="button"
                        class="hero-card-dot active"
                        data-slide="0"
                        role="tab"
                        aria-selected="true"
                        aria-label="Show celebration image 1">
                    </button>


                    <button
                        type="button"
                        class="hero-card-dot"
                        data-slide="1"
                        role="tab"
                        aria-selected="false"
                        aria-label="Show celebration image 2">
                    </button>


                    <button
                        type="button"
                        class="hero-card-dot"
                        data-slide="2"
                        role="tab"
                        aria-selected="false"
                        aria-label="Show celebration image 3">
                    </button>


                </div>


            </div>


        </div>


        <!-- =========================================================
             HERO WAVE
        ========================================================== -->

        <div
            class="hero-wave"
            aria-hidden="true">


            <svg
                viewBox="0 0 1440 180"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg">


                <path
                    class="wave-fill"
                    d="M0,110 C180,180 330,20 520,85 C710,150 820,175 1010,80 C1190,-10 1320,70 1440,115 L1440,180 L0,180 Z">
                </path>


                <path
                    class="wave-line"
                    d="M0,110 C180,180 330,20 520,85 C710,150 820,175 1010,80 C1190,-10 1320,70 1440,115">
                </path>


            </svg>


        </div>


    </section>


    <!-- =========================================================
         ABOUT SECTION
    ========================================================== -->

    <section
        class="about-section"
        id="about">


        <div class="about-container">


            <div class="about-image">


                <img
                    src="images/img2.jpeg"
                    alt="Event Solutions by S.H.E event"
                    loading="lazy"
                    decoding="async">


                <div class="about-image-badge"></div>


            </div>


            <div class="about-content">


                <span class="section-label">
                    ABOUT US
                </span>


                <h2>
                    Your Vision.
                    <br>
                    Our Expertise.
                </h2>


                <p>
                    Event Solutions by S.H.E is an
                    event management company dedicated
                    to creating meaningful, organized,
                    and memorable experiences.
                </p>


                <p>
                    We understand that every celebration
                    is different. Whether it is a
                    birthday, wedding, corporate event,
                    private gathering, or special
                    celebration, we help transform your
                    ideas into an experience worth
                    remembering.
                </p>


                <p>
                    From choosing the right package to
                    coordinating the important details,
                    our goal is to make your event
                    planning simple and stress-free.
                </p>


                <a
                    href="contact.php"
                    class="dark-btn">

                    Start Planning

                </a>


            </div>


        </div>


    </section>


    <!-- =========================================================
         HIGHLIGHTS SECTION
    ========================================================== -->

    <section class="highlights-section">


        <div class="section-container">


            <div class="section-header centered">


                <span class="section-label">
                    WHY CHOOSE US
                </span>


                <h2>
                    Designed Around Your Celebration
                </h2>


                <p>
                    We take care of the details so
                    you can focus on the moment.
                </p>


            </div>


            <div class="highlight-grid">


                <!-- HIGHLIGHT 01 -->

                <article class="highlight-card">


                    <div class="highlight-number">
                        01
                    </div>


                    <h3>
                        Professional Planning
                    </h3>


                    <p>
                        Every important detail is
                        carefully organized to help
                        your event run smoothly.
                    </p>


                </article>


                <!-- HIGHLIGHT 02 -->

                <article class="highlight-card">


                    <div class="highlight-number">
                        02
                    </div>


                    <h3>
                        Personalized Events
                    </h3>


                    <p>
                        We work around your vision,
                        preferences, occasion, and
                        event requirements.
                    </p>


                </article>


                <!-- HIGHLIGHT 03 -->

                <article class="highlight-card">


                    <div class="highlight-number">
                        03
                    </div>


                    <h3>
                        Talented Hosts & Singers
                    </h3>


                    <p>
                        Add the right entertainment
                        to your celebration with
                        professional performers.
                    </p>


                </article>


                <!-- HIGHLIGHT 04 -->

                <article class="highlight-card">


                    <div class="highlight-number">
                        04
                    </div>


                    <h3>
                        Reliable Coordination
                    </h3>


                    <p>
                        We help coordinate the details
                        from preparation through your
                        special day.
                    </p>


                </article>


            </div>


        </div>


    </section>


    <!-- =========================================================
         TALENT SECTION
    ========================================================== -->

    <section class="talent-section">


        <div class="section-container">


            <div class="section-header centered">


                <span class="section-label">
                    SINGERS & HOSTS
                </span>


                <h2>
                    The People Who Bring
                    Your Event to Life
                </h2>


                <p>
                    Complete your celebration with
                    talented singers and engaging
                    professional hosts.
                </p>


            </div>


            <div class="talent-grid">


                <article class="talent-card">


                    <div class="talent-content">


                        <span class="talent-category">
                            LIVE ENTERTAINMENT & EVENT HOSTING
                        </span>


                        <h3>
                            Featured Singer & Host
                        </h3>


                        <p>
                            Make your celebration more memorable
                            with professional live entertainment
                            and engaging event hosting.
                        </p>


                        <p>
                            Our featured talent can help create
                            the right atmosphere, entertain your
                            guests, and keep your event program
                            flowing smoothly from beginning to end.
                        </p>


                        <div class="talent-features">


                            <span>
                                ✓ Live Entertainment
                            </span>


                            <span>
                                ✓ Professional Hosting
                            </span>


                            <span>
                                ✓ Guest Engagement
                            </span>


                            <span>
                                ✓ Event Program Coordination
                            </span>


                        </div>


                    </div>


                    <div class="talent-image">


                        <img
                            src="images/img4.jpeg"
                            alt="Featured singer and event host"
                            loading="lazy"
                            decoding="async">


                        <div
                            class="talent-overlay"
                            aria-hidden="true">
                        </div>


                    </div>


                </article>


            </div>


            <div class="section-action">


                <a
                    href="host.php"
                    class="dark-btn">

                    Explore All Hosts & Singers

                </a>


            </div>


        </div>


    </section>


    <!-- =========================================================
         BOOKING SECTION
    ========================================================== -->

    <section class="booking-section">


        <div class="section-container">


            <div class="section-header centered">


                <span class="section-label">
                    HOW IT WORKS
                </span>


                <h2>
                    Your Event Starts Here
                </h2>


                <p>
                    We've made the booking process
                    simple, clear, and convenient.
                </p>


            </div>


            <div class="booking-process">


                <!-- PROCESS 01 -->

                <div class="process-item">


                    <div class="process-number">
                        01
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ✦

                    </div>


                    <h3>
                        Create an Account
                    </h3>


                    <p>
                        Sign up and create your
                        personal Event Solutions
                        account.
                    </p>


                </div>


                <!-- PROCESS 02 -->

                <div class="process-item">


                    <div class="process-number">
                        02
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ◆

                    </div>


                    <h3>
                        Choose Event Package
                    </h3>


                    <p>
                        Explore our available event
                        packages and select the one
                        that fits your occasion.
                    </p>


                </div>


                <!-- PROCESS 03 -->

                <div class="process-item">


                    <div class="process-number">
                        03
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ✎

                    </div>


                    <h3>
                        Fill Up Information
                    </h3>


                    <p>
                        Tell us about your event,
                        including the date, venue,
                        guests, and other details.
                    </p>


                </div>


                <!-- PROCESS 04 -->

                <div class="process-item">


                    <div class="process-number">
                        04
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ✓

                    </div>


                    <h3>
                        Submit Your Booking
                    </h3>


                    <p>
                        Review your information and
                        submit your booking request
                        for our team to review.
                    </p>


                </div>


                <!-- PROCESS 05 -->

                <div class="process-item">


                    <div class="process-number">
                        05
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ◷

                    </div>


                    <h3>
                        Wait for Confirmation
                    </h3>


                    <p>
                        Our team will review your
                        request and notify you once
                        your booking is confirmed.
                    </p>


                </div>


                <!-- PROCESS 06 -->

                <div class="process-item">


                    <div class="process-number">
                        06
                    </div>


                    <div
                        class="process-icon"
                        aria-hidden="true">

                        ♡

                    </div>


                    <h3>
                        Enjoy Your Event
                    </h3>


                    <p>
                        Everything is ready.
                        Relax, celebrate, and enjoy
                        your special day.
                    </p>


                </div>


            </div>


            <div class="booking-action">


                <a
                    href="auth/signup.php"
                    class="dark-btn">

                    Create Your Account

                </a>


            </div>


        </div>


    </section>


    <!-- =========================================================
         TESTIMONIALS SECTION
    ========================================================== -->

    <section class="testimonials-section">


        <div class="section-container">


            <div class="section-header centered">


                <span class="section-label">
                    TESTIMONIALS
                </span>


                <h2>
                    Moments Worth Remembering
                </h2>


                <p>
                    Every celebration tells a story.
                    Here are some of the stories
                    shared by our clients.
                </p>


            </div>


            <div class="testimonial-grid">


                <!-- TESTIMONIAL 01 -->

                <article class="testimonial-card">


                    <div class="testimonial-image">


                        <img
                            src="images/img3.jpeg"
                            alt="Birthday celebration"
                            loading="lazy"
                            decoding="async">


                    </div>


                    <div class="testimonial-content">


                        <div
                            class="quote-mark"
                            aria-hidden="true">

                            “

                        </div>


                        <p>
                            Event Solutions by S.H.E
                            made our celebration feel
                            effortless. Everything was
                            organized beautifully and
                            our guests had an amazing
                            time.
                        </p>


                        <div class="testimonial-author">


                            <strong>
                                Maria
                            </strong>


                            <span>
                                Birthday Celebration
                            </span>


                        </div>


                    </div>


                </article>


                <!-- TESTIMONIAL 02 -->

                <article class="testimonial-card">


                    <div class="testimonial-image">


                        <img
                            src="images/img2.jpeg"
                            alt="Private celebration"
                            loading="lazy"
                            decoding="async">


                    </div>


                    <div class="testimonial-content">


                        <div
                            class="quote-mark"
                            aria-hidden="true">

                            “

                        </div>


                        <p>
                            From planning to the actual
                            event, the team was
                            professional and attentive
                            to every detail. We truly
                            enjoyed our special day.
                        </p>


                        <div class="testimonial-author">


                            <strong>
                                Angela
                            </strong>


                            <span>
                                Private Celebration
                            </span>


                        </div>


                    </div>


                </article>


                <!-- TESTIMONIAL 03 -->

                <article class="testimonial-card">


                    <div class="testimonial-image">


                        <img
                            src="images/img1.jpeg"
                            alt="Corporate event"
                            loading="lazy"
                            decoding="async">


                    </div>


                    <div class="testimonial-content">


                        <div
                            class="quote-mark"
                            aria-hidden="true">

                            “

                        </div>


                        <p>
                            Choosing the right package
                            was easy and the booking
                            process was simple. The event
                            turned out even better than
                            we imagined.
                        </p>


                        <div class="testimonial-author">


                            <strong>
                                Daniel
                            </strong>


                            <span>
                                Corporate Event
                            </span>


                        </div>


                    </div>


                </article>


                <!-- TESTIMONIAL 04 -->

                <article class="testimonial-card">


                    <div class="testimonial-image">


                        <img
                            src="images/img8.jpeg"
                            alt="Special event"
                            loading="lazy"
                            decoding="async">


                    </div>


                    <div class="testimonial-content">


                        <div
                            class="quote-mark"
                            aria-hidden="true">

                            “

                        </div>


                        <p>
                            Our event was memorable
                            from beginning to end.
                            The team helped us create
                            an experience our guests
                            continue to talk about.
                        </p>


                        <div class="testimonial-author">


                            <strong>
                                Sophia
                            </strong>


                            <span>
                                Special Event
                            </span>


                        </div>


                    </div>


                </article>


            </div>


        </div>


    </section>


    <!-- =========================================================
         CTA SECTION
    ========================================================== -->

    <section class="cta-section">


        <div class="cta-container">


            <span class="section-label">
                LET'S CREATE SOMETHING SPECIAL
            </span>


            <h2>
                Your Celebration
                Deserves to Be Extraordinary.
            </h2>


            <p>
                Tell us your vision and let
                Event Solutions by S.H.E
                help make it happen.
            </p>


            <div class="cta-buttons">


                <a
                    href="events.php"
                    class="cta-button primary">

                    Explore Packages

                </a>


                <a
                    href="contact.php"
                    class="cta-button secondary">

                    Contact Us

                </a>


            </div>


        </div>


    </section>


    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <?php include "includes/footer.php"; ?>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script src="js/header.js"></script>

    <script src="js/script.js"></script>


</body>

</html>
