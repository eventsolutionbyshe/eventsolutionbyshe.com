<?php
/* =========================================================
   CONTACT PAGE
   EVENT SOLUTIONS BY S.H.E.
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   VARIABLES
========================================================= */

$successMessage = "";
$errorMessage = "";
$loginRequired = false;


/* =========================================================
   HANDLE CONTACT FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =====================================================
       CHECK LOGIN BEFORE SENDING MESSAGE
    ====================================================== */

    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {

        $loginRequired = true;

    } else {

        /* =================================================
           GET FORM DATA
        ================================================== */

        $fullname = trim($_POST["fullname"] ?? "");
        $address  = trim($_POST["address"] ?? "");
        $phone    = trim($_POST["phone"] ?? "");
        $message  = trim($_POST["message"] ?? "");


        /* =================================================
           SERVER-SIDE VALIDATION
        ================================================== */

        if (
            $fullname === "" ||
            $address === "" ||
            $phone === "" ||
            $message === ""
        ) {

            $errorMessage =
                "Please complete all required fields before sending your message.";

        } elseif (strlen($message) < 10) {

            $errorMessage =
                "Your message must contain at least 10 characters.";

        } elseif (strlen($message) > 1000) {

            $errorMessage =
                "Your message cannot exceed 1000 characters.";

        } else {

            /* =============================================
               INSERT MESSAGE
            ============================================== */

            $sql = "
                INSERT INTO messages
                (
                    fullname,
                    address,
                    phone,
                    message,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'unread'
                )
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "ssss",
                    $fullname,
                    $address,
                    $phone,
                    $message
                );

                if ($stmt->execute()) {

                    /*
                     * Store success message in session so
                     * refresh will not submit the form again.
                     */

                    $_SESSION["contact_success"] =
                        "Your message has been sent successfully.";

                    $stmt->close();

                    header("Location: contact.php");
                    exit;

                } else {

                    $errorMessage =
                        "We could not send your message right now. Please try again.";

                    $stmt->close();
                }

            } else {

                $errorMessage =
                    "We could not process your message right now. Please try again.";
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGE AFTER REDIRECT
========================================================= */

if (isset($_SESSION["contact_success"])) {

    $successMessage =
        $_SESSION["contact_success"];

    unset($_SESSION["contact_success"]);
}


/* =========================================================
   LOGIN REQUIRED MESSAGE
========================================================= */

if ($loginRequired) {

    $errorMessage =
        "Please log in to your account before sending a message.";
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
        Contact | Event Solutions by S.H.E.
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
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/header.css"
    >

    <!-- =====================================================
         CONTACT CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/contact.css"
    >

</head>

<body>

<?php include "includes/header.php"; ?>


<!-- =========================================================
     CONTACT MESSAGE POPUP
========================================================= -->

<?php if ($successMessage !== "" || $errorMessage !== ""): ?>

    <div
        class="contact-message-overlay"
        id="contactMessageOverlay"
        data-show="true"
        role="presentation"
    >

        <div
            class="contact-message-popup <?php echo $successMessage !== "" ? "success" : "error"; ?>"
            role="dialog"
            aria-modal="true"
            aria-labelledby="contactMessageTitle"
        >

            <button
                type="button"
                class="contact-message-close"
                id="contactMessageClose"
                aria-label="Close"
            >
                &times;
            </button>


            <?php if ($successMessage !== ""): ?>

                <div class="contact-message-icon success-icon">

                    <i class="fa-solid fa-check"></i>

                </div>

                <span class="contact-message-label">
                    MESSAGE SENT
                </span>

                <h3 id="contactMessageTitle">
                    Message Sent Successfully
                </h3>

                <p>
                    Thank you for contacting Event Solutions by S.H.E.
                    We have received your message and will get back
                    to you as soon as possible.
                </p>

                <button
                    type="button"
                    class="contact-message-button"
                    id="contactMessageButton"
                >
                    Continue
                </button>


            <?php else: ?>

                <div class="contact-message-icon error-icon">

                    <i class="fa-solid fa-exclamation"></i>

                </div>

                <span class="contact-message-label">
                    MESSAGE NOTICE
                </span>

                <h3 id="contactMessageTitle">
                    Message Not Sent
                </h3>

                <p>
                    <?php echo htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </p>


                <?php if ($loginRequired): ?>

                    <a
                        href="auth/login.php"
                        class="contact-message-button"
                        id="contactMessageButton"
                    >
                        Login to Continue
                    </a>

                <?php else: ?>

                    <button
                        type="button"
                        class="contact-message-button"
                        id="contactMessageButton"
                    >
                        Try Again
                    </button>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

<?php endif; ?>


<!-- =========================================================
     CONTACT HERO
========================================================= -->

<section class="contact-hero">

    <div class="contact-hero-overlay"></div>

    <div class="contact-hero-content">

        <span class="contact-eyebrow">
            EVENT SOLUTIONS BY S.H.E.
        </span>

        <h1>
            Let's
            <span>Connect</span>
        </h1>

        <p>
            Have an event in mind?
            Let's turn your vision into an unforgettable experience.
        </p>

    </div>

</section>


<!-- =========================================================
     MAIN CONTACT PAGE
========================================================= -->

<main class="contact-page">

    <section class="contact-section">

        <div class="contact-container">


            <!-- =================================================
                 CONTACT INFORMATION
            ================================================== -->

            <div class="contact-information">

                <span class="section-label">
                    GET IN TOUCH
                </span>

                <h2>
                    We'd Love To
                    <span>Hear From You</span>
                </h2>

                <p class="contact-introduction">
                    Whether you're planning a wedding, birthday,
                    corporate gathering, or a special celebration,
                    we're here to help you create an event that
                    feels personal, beautiful, and memorable.
                </p>


                <!-- PHONE -->

                <div class="contact-detail">

                    <div class="contact-detail-icon">

                        <i class="fa-solid fa-phone"></i>

                    </div>

                    <div class="contact-detail-content">

                        <span>
                            PHONE
                        </span>

                        <strong>
                            +63 945-981-7471
                        </strong>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="contact-detail">

                    <div class="contact-detail-icon">

                        <i class="fa-regular fa-envelope"></i>

                    </div>

                    <div class="contact-detail-content">

                        <span>
                            EMAIL
                        </span>

                        <strong>
                            iamelena.ego@gmail.com
                        </strong>

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="contact-detail">

                    <div class="contact-detail-icon">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>

                    <div class="contact-detail-content">

                        <span>
                            LOCATION
                        </span>

                        <strong>
                            Stall #4, Joselina Bldg, Datu Ingkal St.,
                            Kidapawan, Philippines, 9400
                        </strong>

                    </div>

                </div>


                <!-- FACEBOOK -->

                <div class="contact-detail">

                    <div class="contact-detail-icon">

                        <i class="fa-brands fa-facebook-f"></i>

                    </div>

                    <div class="contact-detail-content">

                        <span>
                            FACEBOOK
                        </span>

                        <strong>
                            Event Solutions by S.H.E.
                        </strong>

                    </div>

                </div>


                <div class="contact-information-line"></div>


                <p class="contact-note">

                    We carefully review every inquiry and will
                    get back to you as soon as possible.

                </p>

            </div>


            <!-- =================================================
                 CONTACT FORM
            ================================================== -->

            <div class="contact-form-wrapper">

                <div class="contact-form-header">

                    <span class="form-label">
                        SEND AN INQUIRY
                    </span>

                    <h2>
                        Let's Start
                        <span>A Conversation</span>
                    </h2>

                    <p>
                        Tell us a little about yourself and how
                        we can help make your event special.
                    </p>

                </div>


                <form
                    action="contact.php"
                    method="POST"
                    class="contact-form"
                    id="contactForm"
                >


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label for="fullname">
                            Full Name
                        </label>

                        <div class="form-input-wrapper">

                            <i class="fa-regular fa-user"></i>

                            <input
                                type="text"
                                name="fullname"
                                id="fullname"
                                placeholder="Enter your full name"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <div class="form-input-wrapper">

                            <i class="fa-solid fa-location-dot"></i>

                            <input
                                type="text"
                                name="address"
                                id="address"
                                placeholder="Enter your address"
                                autocomplete="street-address"
                                required
                            >

                        </div>

                    </div>


                    <!-- PHONE NUMBER -->

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <div class="form-input-wrapper">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="tel"
                                name="phone"
                                id="phone"
                                placeholder="Enter your phone number"
                                autocomplete="tel"
                                required
                            >

                        </div>

                    </div>


                    <!-- MESSAGE -->

                    <div class="form-group">

                        <label for="message">
                            Message
                        </label>

                        <div class="form-textarea-wrapper">

                            <i class="fa-regular fa-comment-dots"></i>

                            <textarea
                                name="message"
                                id="message"
                                rows="6"
                                maxlength="1000"
                                placeholder="Tell us about your event or inquiry..."
                                required
                            ></textarea>

                        </div>

                        <div class="message-counter">

                            <span id="messageCount">
                                0
                            </span>

                            /

                            <span>
                                1000
                            </span>

                        </div>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="contact-submit-button"
                        id="contactSubmitButton"
                    >

                        <span>
                            Send Message
                        </span>

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>

                </form>

            </div>

        </div>

    </section>


    <!-- =====================================================
         AVAILABILITY
    ====================================================== -->

    <section class="contact-hours-section">

        <div class="contact-hours-container">

            <div class="contact-hours-icon">

                <i class="fa-regular fa-clock"></i>

            </div>

            <div class="contact-hours-content">

                <span class="section-label">
                    AVAILABILITY
                </span>

                <h3>
                    We're Here When You Need Us
                </h3>

                <div class="contact-hours-list">

                    <div class="contact-hour-item">

                        <span>
                            Monday – Friday
                        </span>

                        <strong>
                            7:00 AM – 5:00 PM
                        </strong>

                    </div>

                    <div class="contact-hour-item">

                        <span>
                            Saturday
                        </span>

                        <strong>
                            9:00 AM – 5:00 PM
                        </strong>

                    </div>

                    <div class="contact-hour-item">

                        <span>
                            Sunday
                        </span>

                        <strong>
                            By Appointment
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CONTACT CTA
    ====================================================== -->

    <section class="contact-cta">

        <div class="contact-cta-decoration contact-cta-decoration-one"></div>

        <div class="contact-cta-decoration contact-cta-decoration-two"></div>

        <div class="contact-cta-content">

            <span>
                READY TO PLAN?
            </span>

            <h2>
                Your Event,
                <strong>Your Story.</strong>
            </h2>

            <p>
                Explore our event services and discover how
                Event Solutions by S.H.E. can help bring
                your celebration to life.
            </p>

            <a
                href="services.php"
                class="contact-cta-button"
            >

                <span>
                    Explore Our Services
                </span>

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </section>

</main>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="js/header.js"></script>

<script src="js/contact.js"></script>

</body>

</html>

