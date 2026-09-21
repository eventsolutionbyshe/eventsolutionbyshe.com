<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Redirect if already logged in
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$errors = [];
$success = "";

$fullname = "";
$email = "";
$address = "";


/*
|--------------------------------------------------------------------------
| Handle Signup
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Full Name Validation
    |--------------------------------------------------------------------------
    */

    if (empty($fullname)) {

        $errors[] = "Full name is required.";
    } elseif (strlen($fullname) < 2) {

        $errors[] = "Please enter your full name.";
    }


    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    */

    if (empty($email)) {

        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";
    }


    if (empty($address)) {

        $errors[] = "Your address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a your address.";
    }


    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    */

    if (empty($password)) {

        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {

        $errors[] = "Password must be at least 6 characters.";
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    if (empty($confirm_password)) {

        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {

        $errors[] = "Passwords do not match.";
    }


    /*
    |--------------------------------------------------------------------------
    | Check Existing Email
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $errors[] =
                    "An account with this email already exists.";
            }

            $stmt->close();
        } else {

            $errors[] =
                "Unable to check the email address.";
        }
    }


    /*
|--------------------------------------------------------------------------
| Create Account
|--------------------------------------------------------------------------
*/

    if (empty($errors)) {

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        $role = "customer";


        $stmt = $conn->prepare("
        INSERT INTO users
        (
            fullname,
            email,
            address,
            password,
            role
        )
        VALUES (?, ?, ?, ?, ?)
    ");


        if ($stmt) {

            $stmt->bind_param(
                "sssss",
                $fullname,
                $email,
                $address,
                $hashedPassword,
                $role
            );


            if ($stmt->execute()) {

                /*
            |--------------------------------------------------------------------------
            | GET NEW CUSTOMER ID
            |--------------------------------------------------------------------------
            */

                $newUserId =
                    $stmt->insert_id;


                /*
            |--------------------------------------------------------------------------
            | INSERT NOTIFICATION FOR NEW CUSTOMER
            |--------------------------------------------------------------------------
            */

                $notificationTitle =
                    "Welcome to Event Solutions";

                $notificationMessage =
                    "Your account is ready. Start planning your next event.";

                $notificationType =
                    "welcome";

                $notificationStatus =
                    "active";

                $notificationIsRead =
                    0;


                $notificationStmt = $conn->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type,
                    status,
                    is_read
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");


                if ($notificationStmt) {

                    $notificationStmt->bind_param(
                        "issssi",
                        $newUserId,
                        $notificationTitle,
                        $notificationMessage,
                        $notificationType,
                        $notificationStatus,
                        $notificationIsRead
                    );


                    $notificationStmt->execute();


                    $notificationStmt->close();
                }


                /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

                $success =
                    "Your account has been created successfully.";

                $fullname = "";

                $email = "";
            } else {

                $errors[] =
                    "Something went wrong. Please try again.";
            }


            $stmt->close();
        } else {

            $errors[] =
                "Unable to create your account.";
        }
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
        Create Account | Event Solutions by S.H.E
    </title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Monsieur+La+Doulaise&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">


    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../css/header.css">

    <link
        rel="stylesheet"
        href="../css/footer.css">


    <!-- =====================================================
         SIGNUP CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../css/signup.css">

</head>


<body>

    <?php include "../includes/header.php"; ?>


    <!-- =====================================================
         SIGNUP PAGE
    ====================================================== -->

    <main class="signup-page">


        <!-- =================================================
             SIGNUP CONTAINER
        ================================================== -->

        <div class="signup-container">


            <!-- =================================================
                 LEFT SIDE - IMAGE CAROUSEL
            ================================================== -->

            <section
                class="signup-carousel"
                aria-label="Event highlights">


                <!-- =================================================
                     SLIDE 1
                ================================================== -->

                <div
                    class="carousel-slide active">

                    <img
                        src="../images/img1.jpeg"
                        alt="Beautiful event setup">

                    <div class="carousel-overlay"></div>


                    <div class="carousel-content">

                        <span>
                            EVENT SOLUTIONS BY S.H.E
                        </span>


                        <h1>

                            Begin Your

                            <strong>
                                Journey
                            </strong>

                        </h1>


                        <p>
                            Create unforgettable moments
                            and bring your dream event to life.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     SLIDE 2
                ================================================== -->

                <div
                    class="carousel-slide">

                    <img
                        src="../images/img2.jpeg"
                        alt="Elegant event celebration">

                    <div class="carousel-overlay"></div>


                    <div class="carousel-content">

                        <span>
                            CREATE MEMORIES
                        </span>


                        <h1>

                            Celebrate

                            <strong>
                                Together
                            </strong>

                        </h1>


                        <p>
                            Every celebration deserves
                            a beautifully planned experience.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     SLIDE 3
                ================================================== -->

                <div
                    class="carousel-slide">

                    <img
                        src="../images/img3.jpeg"
                        alt="Event celebration">

                    <div class="carousel-overlay"></div>


                    <div class="carousel-content">

                        <span>
                            YOUR VISION, OUR PASSION
                        </span>


                        <h1>

                            Make It

                            <strong>
                                Unforgettable
                            </strong>

                        </h1>


                        <p>
                            Let us help you create an event
                            that people will remember.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     CAROUSEL DOTS
                ================================================== -->

                <div
                    class="carousel-dots"
                    aria-label="Carousel navigation">

                    <button
                        type="button"
                        class="carousel-dot active"
                        data-slide="0"
                        aria-label="Show slide 1"></button>


                    <button
                        type="button"
                        class="carousel-dot"
                        data-slide="1"
                        aria-label="Show slide 2"></button>


                    <button
                        type="button"
                        class="carousel-dot"
                        data-slide="2"
                        aria-label="Show slide 3"></button>

                </div>

            </section>


            <!-- =================================================
                 RIGHT SIDE - SIGNUP FORM
            ================================================== -->

            <section class="signup-card">


                <!-- =================================================
                     CARD HEADER
                ================================================== -->

                <div class="signup-card-header">

                    <span>
                        WELCOME
                    </span>


                    <h2>
                        Create an Account
                    </h2>


                    <p>
                        Sign up to get started.
                    </p>

                </div>


                <!-- =================================================
                     ERROR MESSAGES
                ================================================== -->

                <?php if (!empty($errors)): ?>

                    <div
                        class="form-message error"
                        role="alert">

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?= htmlspecialchars($error) ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     SUCCESS MESSAGE
                ================================================== -->

                <?php if (!empty($success)): ?>

                    <div
                        class="form-message success"
                        role="status">

                        <p>
                            <?= htmlspecialchars($success) ?>
                        </p>


                        <a href="login.php">
                            Continue to Login
                        </a>

                    </div>

                <?php endif; ?>


                <?php if (empty($success)): ?>


                    <!-- =================================================
                         SIGNUP FORM
                    ================================================== -->

                    <form
                        action="signup.php"
                        method="POST"
                        class="signup-form"
                        id="signupForm"
                        novalidate>


                        <!-- =================================================
                             FULL NAME
                        ================================================== -->

                        <div class="form-group">

                            <label for="fullname">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="fullname"
                                name="fullname"
                                placeholder="Enter your full name"
                                value="<?= htmlspecialchars($fullname) ?>"
                                autocomplete="name"
                                required>


                            <small
                                class="field-error"
                                id="fullnameError"></small>

                        </div>


                        <!-- =================================================
                             EMAIL
                        ================================================== -->

                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter your email"
                                value="<?= htmlspecialchars($email) ?>"
                                autocomplete="email"
                                required>


                            <small
                                class="field-error"
                                id="emailError"></small>

                        </div>


                        <div class="form-group">

                            <label for="address">
                                Address
                            </label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                placeholder="Enter your Address"
                                value="<?= htmlspecialchars($address) ?>"
                                autocomplete="street-address"
                                maxlength="255"
                                required>

                            <small
                                class="field-error"
                                id="addressError"></small>

                        </div>


                        <!-- =================================================
                             PASSWORD
                        ================================================== -->

                        <div class="form-group">

                            <label for="password">
                                Password
                            </label>


                            <div class="password-wrapper">


                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Create a password"
                                    autocomplete="new-password"
                                    required>


                                <!-- SHOW / HIDE BUTTON -->

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password"
                                    aria-label="Show password"
                                    title="Show password">


                                    <!-- EYE ICON -->

                                    <svg
                                        class="eye-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path
                                            d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>


                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5"></circle>

                                    </svg>


                                    <!-- EYE OFF ICON -->

                                    <svg
                                        class="eye-off-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path
                                            d="M3 3l18 18"></path>


                                        <path
                                            d="M10.6 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-3.1 3.9"></path>


                                        <path
                                            d="M6.7 6.7C3.6 8.7 2 12 2 12s3.5 7 10 7c1.5 0 2.8-.3 4-.8"></path>


                                        <path
                                            d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>

                                    </svg>

                                </button>

                            </div>


                            <!-- =================================================
                                 PASSWORD STRENGTH
                            ================================================== -->

                            <div
                                class="password-strength"
                                id="passwordStrength">


                                <div class="strength-track">

                                    <span></span>

                                    <span></span>

                                    <span></span>

                                </div>


                                <div class="strength-info">

                                    <span>
                                        Password strength
                                    </span>


                                    <strong
                                        id="strengthText">
                                        —
                                    </strong>

                                </div>

                            </div>


                            <small class="password-hint">

                                Use at least 6 characters with
                                letters, numbers and symbols.

                            </small>


                            <small
                                class="field-error"
                                id="passwordError"></small>

                        </div>


                        <!-- =================================================
                             CONFIRM PASSWORD
                        ================================================== -->

                        <div class="form-group">

                            <label for="confirm_password">
                                Confirm Password
                            </label>


                            <div class="password-wrapper">


                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Confirm your password"
                                    autocomplete="new-password"
                                    required>


                                <!-- SHOW / HIDE BUTTON -->

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="confirm_password"
                                    aria-label="Show password"
                                    title="Show password">


                                    <!-- EYE ICON -->

                                    <svg
                                        class="eye-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path
                                            d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>


                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5"></circle>

                                    </svg>


                                    <!-- EYE OFF ICON -->

                                    <svg
                                        class="eye-off-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path
                                            d="M3 3l18 18"></path>


                                        <path
                                            d="M10.6 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-3.1 3.9"></path>


                                        <path
                                            d="M6.7 6.7C3.6 8.7 2 12 2 12s3.5 7 10 7c1.5 0 2.8-.3 4-.8"></path>


                                        <path
                                            d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>

                                    </svg>

                                </button>

                            </div>


                            <small
                                class="field-error"
                                id="confirmPasswordError"></small>

                        </div>


                        <!-- =================================================
                             SUBMIT BUTTON
                        ================================================== -->

                        <button
                            type="submit"
                            class="signup-submit">

                            <span>
                                Create Account
                            </span>


                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true">

                                <path
                                    d="M5 12h14M13 6l6 6-6 6"></path>

                            </svg>

                        </button>

                    </form>


                    <!-- =================================================
                         LOGIN LINK
                    ================================================== -->

                    <div class="signup-login">

                        <span>
                            Already have an account?
                        </span>


                        <a href="login.php">
                            Login
                        </a>

                    </div>


                <?php endif; ?>


            </section>

        </div>

    </main>

    <?php include "../includes/footer.php"; ?>
    <!-- =====================================================
         HEADER JS
    ====================================================== -->

    <script src="../js/header.js"></script>



    <!-- =====================================================
         SIGNUP JS
    ====================================================== -->

    <script src="../js/signup.js"></script>


</body>

</html>
