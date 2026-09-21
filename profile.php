<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];


/* =========================================================
   VARIABLES
========================================================= */

$errors = [];
$success = "";

$fullname = "";
$email = "";
$role = "customer";
$profileImage = "";
$createdAt = "";


/* =========================================================
   GET CURRENT USER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        fullname,
        email,
        role,
        profile_image,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to prepare user query.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   USER NOT FOUND
========================================================= */

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: auth/login.php");
    exit;
}


/* =========================================================
   LOAD USER DATA
========================================================= */

$fullname = $user["fullname"] ?? "";
$email = $user["email"] ?? "";
$role = $user["role"] ?? "customer";
$profileImage = trim(
    (string) ($user["profile_image"] ?? "")
);
$createdAt = $user["created_at"] ?? "";


/* =========================================================
   INITIAL LETTER
========================================================= */

$firstLetter = strtoupper(
    substr(
        trim($fullname),
        0,
        1
    )
);

if ($firstLetter === "") {
    $firstLetter = "U";
}


/* =========================================================
   PROFILE IMAGE URL
========================================================= */

$profileImageUrl = "";

if ($profileImage !== "") {

    if (
        preg_match(
            '/^(https?:)?\/\//i',
            $profileImage
        )
    ) {

        $profileImageUrl = $profileImage;

    } else {

        $profileImageUrl = ltrim(
            $profileImage,
            "/"
        );
    }
}


/* =========================================================
   HANDLE PROFILE UPDATE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {

    $newFullname = trim(
        $_POST["fullname"] ?? ""
    );

    $newEmail = trim(
        $_POST["email"] ?? ""
    );


    /* -----------------------------------------------------
       VALIDATE FULL NAME
    ----------------------------------------------------- */

    if ($newFullname === "") {

        $errors[] =
            "Full name is required.";

    } elseif (mb_strlen($newFullname) > 150) {

        $errors[] =
            "Full name must not exceed 150 characters.";
    }


    /* -----------------------------------------------------
       VALIDATE EMAIL
    ----------------------------------------------------- */

    if ($newEmail === "") {

        $errors[] =
            "Email address is required.";

    } elseif (
        !filter_var(
            $newEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    } elseif (mb_strlen($newEmail) > 150) {

        $errors[] =
            "Email address must not exceed 150 characters.";
    }


    /* -----------------------------------------------------
       CHECK DUPLICATE EMAIL
    ----------------------------------------------------- */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $newEmail,
                $userId
            );

            $stmt->execute();

            $emailResult =
                $stmt->get_result();

            if ($emailResult->num_rows > 0) {

                $errors[] =
                    "That email address is already being used.";
            }

            $stmt->close();
        }
    }


    /* -----------------------------------------------------
       UPDATE PROFILE
    ----------------------------------------------------- */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            UPDATE users
            SET
                fullname = ?,
                email = ?
            WHERE id = ?
        ");

        if (!$stmt) {

            $errors[] =
                "Unable to prepare profile update.";

        } else {

            $stmt->bind_param(
                "ssi",
                $newFullname,
                $newEmail,
                $userId
            );

            if ($stmt->execute()) {

                $fullname = $newFullname;
                $email = $newEmail;

                $_SESSION["fullname"] =
                    $newFullname;

                $_SESSION["email"] =
                    $newEmail;

                $firstLetter = strtoupper(
                    substr(
                        trim($fullname),
                        0,
                        1
                    )
                );

                if ($firstLetter === "") {
                    $firstLetter = "U";
                }

                $success =
                    "Your profile has been updated successfully.";

            } else {

                $errors[] =
                    "Unable to update your profile.";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   HANDLE PASSWORD CHANGE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $currentPassword =
        $_POST["current_password"] ?? "";

    $newPassword =
        $_POST["new_password"] ?? "";

    $confirmPassword =
        $_POST["confirm_password"] ?? "";


    /* -----------------------------------------------------
       VALIDATE PASSWORD
    ----------------------------------------------------- */

    if ($currentPassword === "") {

        $errors[] =
            "Current password is required.";
    }

    if ($newPassword === "") {

        $errors[] =
            "New password is required.";

    } elseif (strlen($newPassword) < 8) {

        $errors[] =
            "New password must be at least 8 characters.";
    }

    if ($confirmPassword === "") {

        $errors[] =
            "Please confirm your new password.";

    } elseif ($newPassword !== $confirmPassword) {

        $errors[] =
            "New passwords do not match.";
    }


    /* -----------------------------------------------------
       VERIFY CURRENT PASSWORD
    ----------------------------------------------------- */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $errors[] =
                "Unable to verify your password.";

        } else {

            $stmt->bind_param(
                "i",
                $userId
            );

            $stmt->execute();

            $passwordResult =
                $stmt->get_result();

            $passwordUser =
                $passwordResult->fetch_assoc();

            $stmt->close();


            if (
                !$passwordUser ||
                !password_verify(
                    $currentPassword,
                    $passwordUser["password"]
                )
            ) {

                $errors[] =
                    "Your current password is incorrect.";
            }
        }
    }


    /* -----------------------------------------------------
       UPDATE PASSWORD
    ----------------------------------------------------- */

    if (empty($errors)) {

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        if (!$stmt) {

            $errors[] =
                "Unable to prepare password update.";

        } else {

            $stmt->bind_param(
                "si",
                $hashedPassword,
                $userId
            );

            if ($stmt->execute()) {

                $success =
                    "Your password has been changed successfully.";

            } else {

                $errors[] =
                    "Unable to change your password.";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   HANDLE PROFILE IMAGE UPLOAD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["upload_profile_image"])
) {

    if (
        !isset($_FILES["profile_image"]) ||
        $_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK
    ) {

        $errors[] =
            "Please select a profile image.";

    } else {

        $file = $_FILES["profile_image"];

        $maxFileSize =
            5 * 1024 * 1024;


        /* -------------------------------------------------
           FILE SIZE
        ------------------------------------------------- */

        if ($file["size"] > $maxFileSize) {

            $errors[] =
                "Profile image must not exceed 5 MB.";
        }


        /* -------------------------------------------------
           FILE TYPE
        ------------------------------------------------- */

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType =
            $finfo->file(
                $file["tmp_name"]
            );

        if (
            !in_array(
                $mimeType,
                $allowedTypes,
                true
            )
        ) {

            $errors[] =
                "Only JPG, PNG, and WEBP images are allowed.";
        }


        /* -------------------------------------------------
           UPLOAD
        ------------------------------------------------- */

        if (empty($errors)) {

            $uploadDirectory =
                __DIR__ . "/images/profiles/";


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    $errors[] =
                        "Unable to create profile image folder.";
                }
            }
        }


        /* -------------------------------------------------
           CREATE FILE
        ------------------------------------------------- */

        if (empty($errors)) {

            switch ($mimeType) {

                case "image/jpeg":
                    $extension = "jpg";
                    break;

                case "image/png":
                    $extension = "png";
                    break;

                case "image/webp":
                    $extension = "webp";
                    break;

                default:
                    $extension = "jpg";
                    break;
            }


            $newFileName =
                "profile_" .
                $userId .
                "_" .
                time() .
                "." .
                $extension;


            $targetPath =
                $uploadDirectory .
                $newFileName;


            /* ---------------------------------------------
               MOVE UPLOADED FILE
            --------------------------------------------- */

            if (
                move_uploaded_file(
                    $file["tmp_name"],
                    $targetPath
                )
            ) {

                $databasePath =
                    "images/profiles/" .
                    $newFileName;


                /* -----------------------------------------
                   OLD IMAGE
                ----------------------------------------- */

                $oldImage =
                    $profileImage;


                /* -----------------------------------------
                   DATABASE UPDATE
                ----------------------------------------- */

                $stmt = $conn->prepare("
                    UPDATE users
                    SET profile_image = ?
                    WHERE id = ?
                ");

                if (!$stmt) {

                    @unlink($targetPath);

                    $errors[] =
                        "Unable to prepare image update.";

                } else {

                    $stmt->bind_param(
                        "si",
                        $databasePath,
                        $userId
                    );

                    if ($stmt->execute()) {

                        $profileImage =
                            $databasePath;

                        $profileImageUrl =
                            $databasePath;

                        $success =
                            "Your profile picture has been updated successfully.";


                        /* ---------------------------------
                           DELETE OLD LOCAL IMAGE
                        --------------------------------- */

                        if (
                            $oldImage !== "" &&
                            !preg_match(
                                '/^(https?:)?\/\//i',
                                $oldImage
                            )
                        ) {

                            $oldImagePath =
                                __DIR__ . "/" .
                                ltrim(
                                    $oldImage,
                                    "/"
                                );

                            if (
                                is_file(
                                    $oldImagePath
                                )
                            ) {

                                @unlink(
                                    $oldImagePath
                                );
                            }
                        }

                    } else {

                        @unlink($targetPath);

                        $errors[] =
                            "Unable to save your profile picture.";
                    }

                    $stmt->close();
                }

            } else {

                $errors[] =
                    "Unable to upload your profile picture.";
            }
        }
    }
}


/* =========================================================
   FORMAT ROLE
========================================================= */

$roleLabel =
    ucfirst(
        strtolower(
            $role
        )
    );


/* =========================================================
   FORMAT CREATED DATE
========================================================= */

$createdDate = "";

if (!empty($createdAt)) {

    $timestamp =
        strtotime($createdAt);

    if ($timestamp !== false) {

        $createdDate =
            date(
                "F d, Y",
                $timestamp
            );
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
        My Profile |
        Event Solutions by S.H.E.
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
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@300;400;500;600;700&display=swap"
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
         PROFILE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/profile.css"
    >

</head>


<body>


<?php include "includes/header.php"; ?>


<main class="profile-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="profile-hero">

        <div class="profile-decoration decoration-one"></div>

        <div class="profile-decoration decoration-two"></div>

        <div class="profile-hero-content">

            <span class="profile-label">
                MY ACCOUNT
            </span>

            <h1>
                My Profile
            </h1>

            <p>
                Manage your personal information
                and account settings.
            </p>

        </div>

    </section>



    <!-- =====================================================
         PROFILE SECTION
    ====================================================== -->

    <section class="profile-section">

        <div class="profile-container">


            <!-- =================================================
                 ALERTS
            ================================================== -->

            <?php if (!empty($success)): ?>

                <div class="profile-alert success">

                    <span class="alert-icon">
                        ✓
                    </span>

                    <span>
                        <?= htmlspecialchars(
                            $success
                        ) ?>
                    </span>

                    <button
                        type="button"
                        class="alert-close"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>


            <?php if (!empty($errors)): ?>

                <div class="profile-alert error">

                    <span class="alert-icon">
                        !
                    </span>

                    <div class="error-list">

                        <?php foreach (
                            $errors
                            as $error
                        ): ?>

                            <div>
                                <?= htmlspecialchars(
                                    $error
                                ) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button
                        type="button"
                        class="alert-close"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 MAIN GRID
            ================================================== -->

            <div class="profile-grid">


                <!-- =================================================
                     SIDEBAR
                ================================================== -->

                <aside class="profile-sidebar">


                    <!-- PROFILE CARD -->

                    <div class="profile-card">


                        <div class="profile-avatar-wrapper">

                            <?php if (
                                !empty($profileImageUrl)
                            ): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $profileImageUrl
                                    ) ?>"
                                    alt="Profile picture"
                                    class="profile-avatar-image"
                                >

                            <?php else: ?>

                                <div class="profile-avatar">

                                    <?= htmlspecialchars(
                                        $firstLetter
                                    ) ?>

                                </div>

                            <?php endif; ?>

                            <span class="avatar-status"></span>

                        </div>


                        <h2>
                            <?= htmlspecialchars(
                                $fullname
                            ) ?>
                        </h2>


                        <p class="profile-email">

                            <?= htmlspecialchars(
                                $email
                            ) ?>

                        </p>


                        <span class="profile-role">

                            <?= htmlspecialchars(
                                $roleLabel
                            ) ?>

                        </span>


                        <div class="profile-card-divider"></div>


                        <div class="profile-meta">

                            <div class="meta-item">

                                <span>
                                    ACCOUNT TYPE
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $roleLabel
                                    ) ?>
                                </strong>

                            </div>


                            <div class="meta-item">

                                <span>
                                    MEMBER SINCE
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $createdDate ?: "—"
                                    ) ?>
                                </strong>

                            </div>

                        </div>


                        <!-- =================================================
                             PHOTO UPLOAD
                        ================================================== -->

                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            class="photo-form"
                        >

                            <label
                                for="profile_image"
                                class="photo-upload-label"
                            >
                                Change Profile Photo
                            </label>


                            <input
                                type="file"
                                id="profile_image"
                                name="profile_image"
                                accept="image/jpeg,image/png,image/webp"
                                class="photo-input"
                            >


                            <button
                                type="submit"
                                name="upload_profile_image"
                                class="photo-button"
                            >
                                Upload Photo
                            </button>


                            <small>
                                JPG, PNG or WEBP · Max 5 MB
                            </small>

                        </form>

                    </div>



                    <!-- =================================================
                         QUICK LINKS
                    ================================================== -->

                    <div class="quick-card">

                        <span class="quick-label">
                            QUICK ACCESS
                        </span>


                        <a href="my-events.php">

                            <span>
                                My Events
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <a href="events.php">

                            <span>
                                Browse Events
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <a href="venues.php">

                            <span>
                                Explore Venues
                            </span>

                            <span>
                                →
                            </span>

                        </a>


                        <a href="contact.php">

                            <span>
                                Contact Us
                            </span>

                            <span>
                                →
                            </span>

                        </a>

                    </div>

                </aside>



                <!-- =================================================
                     CONTENT
                ================================================== -->

                <div class="profile-content">


                    <!-- =================================================
                         PERSONAL INFORMATION
                    ================================================== -->

                    <section class="settings-card">

                        <div class="settings-heading">

                            <div class="section-number">
                                01
                            </div>

                            <div>

                                <span>
                                    ACCOUNT DETAILS
                                </span>

                                <h2>
                                    Personal Information
                                </h2>

                                <p>
                                    Update your basic account information.
                                </p>

                            </div>

                        </div>


                        <form
                            method="POST"
                            class="profile-form"
                        >

                            <div class="form-row">


                                <div class="form-group">

                                    <label for="fullname">
                                        Full Name
                                    </label>

                                    <input
                                        type="text"
                                        id="fullname"
                                        name="fullname"
                                        value="<?= htmlspecialchars(
                                            $fullname
                                        ) ?>"
                                        maxlength="150"
                                        autocomplete="name"
                                        required
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="email">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars(
                                            $email
                                        ) ?>"
                                        maxlength="150"
                                        autocomplete="email"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="account-information">

                                <div>

                                    <span>
                                        ACCOUNT ROLE
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $roleLabel
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        MEMBER SINCE
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $createdDate ?: "—"
                                        ) ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="submit"
                                    name="update_profile"
                                    class="save-button"
                                >

                                    <span>
                                        Save Changes
                                    </span>

                                    <span>
                                        →
                                    </span>

                                </button>

                            </div>

                        </form>

                    </section>



                    <!-- =================================================
                         CHANGE PASSWORD
                    ================================================== -->

                    <section class="settings-card">

                        <div class="settings-heading">

                            <div class="section-number">
                                02
                            </div>

                            <div>

                                <span>
                                    SECURITY
                                </span>

                                <h2>
                                    Change Password
                                </h2>

                                <p>
                                    Update your password to keep
                                    your account secure.
                                </p>

                            </div>

                        </div>


                        <form
                            method="POST"
                            class="profile-form password-form"
                        >


                            <div class="form-group">

                                <label for="current_password">
                                    Current Password
                                </label>

                                <div class="password-wrapper">

                                    <input
                                        type="password"
                                        id="current_password"
                                        name="current_password"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        data-target="current_password"
                                    >
                                        Show
                                    </button>

                                </div>

                            </div>


                            <div class="form-row">


                                <div class="form-group">

                                    <label for="new_password">
                                        New Password
                                    </label>

                                    <div class="password-wrapper">

                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            minlength="8"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            data-target="new_password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="confirm_password">
                                        Confirm New Password
                                    </label>

                                    <div class="password-wrapper">

                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            minlength="8"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            data-target="confirm_password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>

                            </div>


                            <div class="password-requirements">

                                <span class="requirement-icon">
                                    ✓
                                </span>

                                <div>

                                    <strong>
                                        Password requirement
                                    </strong>

                                    <p>
                                        Your new password must contain
                                        at least 8 characters.
                                    </p>

                                </div>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="submit"
                                    name="change_password"
                                    class="save-button"
                                >

                                    <span>
                                        Change Password
                                    </span>

                                    <span>
                                        →
                                    </span>

                                </button>

                            </div>

                        </form>

                    </section>



                    <!-- =================================================
                         ACCOUNT STATUS
                    ================================================== -->

                    <section class="account-banner">

                        <div class="banner-icon">
                            ✓
                        </div>

                        <div>

                            <span>
                                ACCOUNT STATUS
                            </span>

                            <h3>
                                Your account is active.
                            </h3>

                            <p>
                                Thank you for being part of
                                Event Solutions by S.H.E.
                            </p>

                        </div>

                    </section>


                </div>

            </div>

        </div>

    </section>

</main>


<?php include "includes/footer.php"; ?>


<!-- =========================================================
     HEADER JS
========================================================= -->

<script src="js/header.js"></script>


<!-- =========================================================
     PROFILE JS
========================================================= -->

<script src="js/profile.js"></script>


</body>
</html>