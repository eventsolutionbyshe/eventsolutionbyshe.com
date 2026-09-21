<?php

/* =====================================================
   ADMIN HOST MANAGEMENT
   Event Solutions by S.H.E.
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];


/*
    Keep admin access flexible with the existing
    session structure.
*/
$isAdmin =
    isset($_SESSION["admin_id"]) ||
    (
        isset($_SESSION["role"]) &&
        strtolower(trim((string) $_SESSION["role"])) === "admin"
    ) ||
    (
        isset($_SESSION["user_role"]) &&
        strtolower(trim((string) $_SESSION["user_role"])) === "admin"
    );

if (!$isAdmin) {
    header("Location: ../index.php");
    exit;
}


/* =====================================================
   DATABASE
===================================================== */

require_once "../config/database.php";


/* =====================================================
   PAGE SETTINGS
===================================================== */

$pageTitle = "Hosts";
$pageSection = "Administrator";
$pageHeading = "Hosts";
$pageAdminCss = "admin_host.css";
$pageAdminJs = "admin_host.js";


/* =====================================================
   UPLOAD SETTINGS
===================================================== */

$hostUploadDirectory = "../images/hosts/";
$hostDatabaseDirectory = "images/hosts/";


if (!is_dir($hostUploadDirectory)) {
    @mkdir($hostUploadDirectory, 0777, true);
}


/* =====================================================
   IMAGE HELPERS
===================================================== */

function createHostImageName($originalName)
{
    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    $extension = preg_replace(
        "/[^a-z0-9]/i",
        "",
        $extension
    );

    if ($extension === "") {
        $extension = "jpg";
    }

    return
        "host_" .
        date("YmdHis") .
        "_" .
        bin2hex(random_bytes(4)) .
        "." .
        $extension;
}


function deleteHostImage($imagePath)
{
    if (empty($imagePath)) {
        return;
    }

    /*
        Do not delete remote images.
    */
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return;
    }

    $filePath =
        "../" .
        ltrim($imagePath, "/");

    if (
        is_file($filePath) &&
        file_exists($filePath)
    ) {
        @unlink($filePath);
    }
}


function getHostImageUrl($imagePath)
{
    if (empty($imagePath)) {
        return "";
    }

    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    return
        "../" .
        ltrim($imagePath, "/");
}


/* =====================================================
   MESSAGE
===================================================== */

$message = "";
$messageType = "";

if (isset($_GET["success"])) {

    if ($_GET["success"] === "added") {

        $message =
            "Host added successfully.";

        $messageType =
            "success";

    } elseif ($_GET["success"] === "updated") {

        $message =
            "Host updated successfully.";

        $messageType =
            "success";
    }
}


/* =====================================================
   ADD HOST
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "add_host"
) {

    $hostName =
        trim($_POST["host_name"] ?? "");

    $hostType =
        trim($_POST["host_type"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $imagePath = "";


    try {

        if ($hostName === "") {
            throw new Exception(
                "Please enter the host name."
            );
        }

        if ($hostType === "") {
            throw new Exception(
                "Please select a host type."
            );
        }


        /* =============================================
           IMAGE UPLOAD
        ============================================= */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["image"]["error"] !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    "Unable to upload the host image."
                );
            }


            $allowedExtensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];

            $extension =
                strtolower(
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                throw new Exception(
                    "Only JPG, JPEG, PNG, and WEBP images are allowed."
                );
            }


            if (
                $_FILES["image"]["size"] >
                5 * 1024 * 1024
            ) {
                throw new Exception(
                    "The host image must not exceed 5MB."
                );
            }


            $imageName =
                createHostImageName(
                    $_FILES["image"]["name"]
                );


            $targetFile =
                $hostUploadDirectory .
                $imageName;


            if (
                !move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $targetFile
                )
            ) {
                throw new Exception(
                    "Unable to save the host image."
                );
            }


            $imagePath =
                $hostDatabaseDirectory .
                $imageName;
        }


        /* =============================================
           INSERT HOST
        ============================================= */

        $sql = "
            INSERT INTO hosts
            (
                host_name,
                host_type,
                email,
                phone,
                description,
                image
            )
            VALUES
            (?, ?, ?, ?, ?, ?)
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {
            throw new Exception(
                "Unable to prepare the add host query."
            );
        }


        $stmt->bind_param(
            "ssssss",
            $hostName,
            $hostType,
            $email,
            $phone,
            $description,
            $imagePath
        );


        if (!$stmt->execute()) {

            $stmt->close();

            if ($imagePath !== "") {
                deleteHostImage($imagePath);
            }

            throw new Exception(
                "Unable to add the host."
            );
        }


        $stmt->close();


        header(
            "Location: admin_host.php?success=added"
        );

        exit;


    } catch (Exception $e) {

        $message =
            $e->getMessage();

        $messageType =
            "error";
    }
}


/* =====================================================
   UPDATE HOST
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update_host"
) {

    $hostId =
        (int) ($_POST["host_id"] ?? 0);

    $hostName =
        trim($_POST["host_name"] ?? "");

    $hostType =
        trim($_POST["host_type"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $description =
        trim($_POST["description"] ?? "");


    try {

        if ($hostId <= 0) {
            throw new Exception(
                "Invalid host ID."
            );
        }

        if ($hostName === "") {
            throw new Exception(
                "Please enter the host name."
            );
        }

        if ($hostType === "") {
            throw new Exception(
                "Please select a host type."
            );


        }


        /* =============================================
           GET CURRENT IMAGE
        ============================================= */

        $currentImage = "";


        $imageQuery = "
            SELECT image
            FROM hosts
            WHERE id = ?
            LIMIT 1
        ";


        $imageStmt =
            $conn->prepare($imageQuery);


        if (!$imageStmt) {
            throw new Exception(
                "Unable to verify the host."
            );
        }


        $imageStmt->bind_param(
            "i",
            $hostId
        );

        $imageStmt->execute();

        $imageResult =
            $imageStmt->get_result();


        if (
            $imageResult->num_rows === 0
        ) {

            $imageStmt->close();

            throw new Exception(
                "Host record was not found."
            );
        }


        $imageRow =
            $imageResult->fetch_assoc();


        $currentImage =
            $imageRow["image"] ?? "";


        $imageStmt->close();


        $newImagePath =
            $currentImage;


        /* =============================================
           NEW IMAGE
        ============================================= */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["image"]["error"] !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    "Unable to upload the new host image."
                );
            }


            $allowedExtensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];


            $extension =
                strtolower(
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                throw new Exception(
                    "Only JPG, JPEG, PNG, and WEBP images are allowed."
                );
            }


            if (
                $_FILES["image"]["size"] >
                5 * 1024 * 1024
            ) {
                throw new Exception(
                    "The host image must not exceed 5MB."
                );
            }


            $imageName =
                createHostImageName(
                    $_FILES["image"]["name"]
                );


            $targetFile =
                $hostUploadDirectory .
                $imageName;


            if (
                !move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $targetFile
                )
            ) {
                throw new Exception(
                    "Unable to save the new host image."
                );
            }


            $newImagePath =
                $hostDatabaseDirectory .
                $imageName;
        }


        /* =============================================
           UPDATE HOST
        ============================================= */

        $sql = "
            UPDATE hosts
            SET
                host_name = ?,
                host_type = ?,
                email = ?,
                phone = ?,
                description = ?,
                image = ?
            WHERE id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            if (
                $newImagePath !== $currentImage
            ) {
                deleteHostImage(
                    $newImagePath
                );
            }

            throw new Exception(
                "Unable to prepare the update host query."
            );
        }


        $stmt->bind_param(
            "ssssssi",
            $hostName,
            $hostType,
            $email,
            $phone,
            $description,
            $newImagePath,
            $hostId
        );


        if (!$stmt->execute()) {

            $stmt->close();

            if (
                $newImagePath !== $currentImage
            ) {
                deleteHostImage(
                    $newImagePath
                );
            }

            throw new Exception(
                "Unable to update the host."
            );
        }


        $stmt->close();


        /* =============================================
           DELETE OLD IMAGE AFTER SUCCESSFUL UPDATE
        ============================================= */

        if (
            $newImagePath !== $currentImage &&
            !empty($currentImage)
        ) {
            deleteHostImage(
                $currentImage
            );
        }


        header(
            "Location: admin_host.php?success=updated"
        );

        exit;


    } catch (Exception $e) {

        $message =
            $e->getMessage();

        $messageType =
            "error";
    }
}


/* =====================================================
   SEARCH / FILTER
===================================================== */

$search =
    trim($_GET["search"] ?? "");

$typeFilter =
    trim($_GET["host_type"] ?? "");


/* =====================================================
   HOST TYPES
===================================================== */

$hostTypes = [];


$typeSql = "
    SELECT DISTINCT host_type
    FROM hosts
    WHERE host_type IS NOT NULL
    AND host_type <> ''
    ORDER BY host_type ASC
";


$typeResult =
    $conn->query($typeSql);


if ($typeResult) {

    while (
        $typeRow =
        $typeResult->fetch_assoc()
    ) {

        $hostTypes[] =
            $typeRow["host_type"];
    }
}


/* =====================================================
   HOST QUERY
===================================================== */

$hosts = [];


$sql = "
    SELECT
        id,
        host_name,
        host_type,
        email,
        phone,
        description,
        image,
        created_at,
        updated_at
    FROM hosts
    WHERE 1 = 1
";


$params = [];
$types = "";


if ($search !== "") {

    $sql .= "
        AND
        (
            host_name LIKE ?
            OR host_type LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR description LIKE ?
        )
    ";

    $searchValue =
        "%" .
        $search .
        "%";

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $types .= "sssss";
}


if ($typeFilter !== "") {

    $sql .= "
        AND host_type = ?
    ";

    $params[] =
        $typeFilter;

    $types .= "s";
}


$sql .= "
    ORDER BY host_name ASC
";


$stmt =
    $conn->prepare($sql);


if ($stmt) {

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
        $result->fetch_assoc()
    ) {

        $hosts[] =
            $row;
    }


    $stmt->close();
}


/* =====================================================
   STATISTICS
===================================================== */

$totalHosts = 0;
$displayedHosts =
    count($hosts);

$totalHostTypes =
    count($hostTypes);


$countSql = "
    SELECT COUNT(*) AS total
    FROM hosts
";


$countResult =
    $conn->query($countSql);


if ($countResult) {

    $countRow =
        $countResult->fetch_assoc();

    $totalHosts =
        (int) $countRow["total"];
}


/* =====================================================
   OUTPUT BUFFER
===================================================== */

ob_start();

?>

<div class="admin-hosts">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="hosts-header">

        <div class="hosts-header-left">

            <div class="hosts-title-icon">
                <i class="fa-solid fa-microphone-lines"></i>
            </div>

            <div>

                <span class="hosts-page-label">
                    <?= htmlspecialchars(
                        $pageSection,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $pageHeading,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </h2>

                <p>
                    Manage event hosts and singing hosts.
                </p>

            </div>

        </div>


        <div class="hosts-header-actions">

            <button
                type="button"
                class="btn-host-report"
                id="btnHostReport"
            >
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>


            <button
                type="button"
                class="btn-add-host"
                id="btnOpenAddHost"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add Host</span>
            </button>

        </div>

    </div>


    <!-- =================================================
         ERROR MESSAGE
    ================================================== -->

    <?php if (
        $message !== "" &&
        $messageType === "error"
    ): ?>

        <div class="host-alert error">

            <div>

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SUCCESS TOAST
    ================================================== -->

    <?php if (
        $message !== "" &&
        $messageType === "success"
    ): ?>

        <div
            class="host-toast host-toast-success"
            id="hostSuccessToast"
            role="status"
            aria-live="polite"
        >

            <div class="host-toast-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>


            <div class="host-toast-content">

                <strong>
                    Success
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>

            </div>


            <button
                type="button"
                class="host-toast-close"
                id="hostToastClose"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>


            <div class="host-toast-progress"></div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="host-stat-grid">

        <div class="host-stat-card">

            <div class="host-stat-icon">
                <i class="fa-solid fa-microphone"></i>
            </div>

            <div class="host-stat-content">

                <span>
                    Total Hosts
                </span>

                <strong>
                    <?= number_format(
                        $totalHosts
                    ) ?>
                </strong>

                <small>
                    All registered hosts
                </small>

            </div>

        </div>


        <div class="host-stat-card">

            <div class="host-stat-icon">

                <i class="fa-solid fa-list"></i>

            </div>

            <div class="host-stat-content">

                <span>
                    Displayed
                </span>

                <strong>
                    <?= number_format(
                        $displayedHosts
                    ) ?>
                </strong>

                <small>
                    Current filtered results
                </small>

            </div>

        </div>


        <div class="host-stat-card">

            <div class="host-stat-icon">

                <i class="fa-solid fa-layer-group"></i>

            </div>

            <div class="host-stat-content">

                <span>
                    Host Types
                </span>

                <strong>
                    <?= number_format(
                        $totalHostTypes
                    ) ?>
                </strong>

                <small>
                    Available host categories
                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         HOST REPORT
    ================================================== -->

    <div
        id="hostReport"
        class="hosts-report"
    >

        <!-- =================================================
             REPORT HEADER
        ================================================== -->

        <div class="report-header">

            <div>

                <span class="report-label">
                    HOST MANAGEMENT
                </span>

                <h3>
                    Registered Hosts
                </h3>

                <p>
                    List of hosts available for event services.
                </p>

            </div>

        </div>


        <!-- =================================================
             FILTER BAR
        ================================================== -->

        <form
            method="GET"
            class="host-filter-bar"
        >

            <div class="host-search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    placeholder="Search host name, type, email, phone..."
                >

            </div>


            <div class="host-filter-select">

                <i class="fa-solid fa-filter"></i>

                <select
                    name="host_type"
                >

                    <option value="">
                        All Host Types
                    </option>

                    <?php foreach (
                        $hostTypes
                        as $hostType
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $hostType,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            <?= $typeFilter === $hostType
                                ? "selected"
                                : "" ?>
                        >
                            <?= htmlspecialchars(
                                $hostType,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn-host-search"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search</span>
            </button>


            <a
                href="admin_host.php"
                class="btn-host-reset"
            >
                <i class="fa-solid fa-rotate-left"></i>
                <span>Reset</span>
            </a>

        </form>


        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="hosts-table-container">

            <?php if (
                !empty($hosts)
            ): ?>

                <table class="hosts-table">

                    <thead>

                        <tr>

                            <th>
                                Host
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Description
                            </th>

                            <th class="no-print">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach (
                            $hosts
                            as $host
                        ): ?>

                            <?php

                            $hostId =
                                (int) $host["id"];

                            $hostName =
                                $host["host_name"] ?? "";

                            $hostType =
                                $host["host_type"] ?? "";

                            $email =
                                $host["email"] ?? "";

                            $phone =
                                $host["phone"] ?? "";

                            $description =
                                $host["description"] ?? "";

                            $image =
                                $host["image"] ?? "";

                            $imageUrl =
                                getHostImageUrl(
                                    $image
                                );

                            $initial =
                                strtoupper(
                                    substr(
                                        trim(
                                            $hostName
                                        ),
                                        0,
                                        1
                                    )
                                );

                            ?>

                            <tr>

                                <!-- HOST -->

                                <td>

                                    <div class="host-info">

                                        <div class="host-avatar">

                                            <?php if (
                                                $imageUrl !== ""
                                            ): ?>

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $imageUrl,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $hostName,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                >

                                            <?php else: ?>

                                                <span class="host-avatar-placeholder">
                                                    <?= htmlspecialchars(
                                                        $initial,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="host-information">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $hostName,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>

                                            <span>
                                                Host ID:
                                                #<?= $hostId ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <span class="host-type-badge">

                                        <i class="fa-solid fa-microphone-lines"></i>

                                        <?= htmlspecialchars(
                                            $hostType,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <div class="host-contact">

                                        <?php if (
                                            $email !== ""
                                        ): ?>

                                            <span
                                                title="<?= htmlspecialchars(
                                                    $email,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                            >
                                                <i class="fa-regular fa-envelope"></i>
                                                <?= htmlspecialchars(
                                                    $email,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="muted">
                                                No email
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <div class="host-contact">

                                        <?php if (
                                            $phone !== ""
                                        ): ?>

                                            <span
                                                title="<?= htmlspecialchars(
                                                    $phone,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                            >
                                                <i class="fa-solid fa-phone"></i>
                                                <?= htmlspecialchars(
                                                    $phone,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="muted">
                                                No phone
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- DESCRIPTION -->

                                <td>

                                    <?php if (
                                        $description !== ""
                                    ): ?>

                                        <div
                                            class="host-description"
                                            title="<?= htmlspecialchars(
                                                $description,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $description,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </div>

                                    <?php else: ?>

                                        <span class="muted">
                                            No description
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td class="no-print">

                                    <div class="host-actions">

                                        <button
                                            type="button"
                                            class="btn-edit-host"
                                            data-id="<?= $hostId ?>"
                                            data-name="<?= htmlspecialchars(
                                                $hostName,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-type="<?= htmlspecialchars(
                                                $hostType,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-email="<?= htmlspecialchars(
                                                $email,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-phone="<?= htmlspecialchars(
                                                $phone,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-description="<?= htmlspecialchars(
                                                $description,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-image="<?= htmlspecialchars(
                                                $imageUrl,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span>Edit</span>
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="hosts-empty">

                    <div class="hosts-empty-icon">

                        <i class="fa-solid fa-microphone-lines"></i>

                    </div>

                    <h3>
                        No Hosts Found
                    </h3>

                    <p>
                        No hosts match your current search or filter.
                    </p>

                    <?php if (
                        $search !== "" ||
                        $typeFilter !== ""
                    ): ?>

                        <a
                            href="admin_host.php"
                            class="hosts-empty-button"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset Filters
                        </a>

                    <?php else: ?>

                        <button
                            type="button"
                            class="hosts-empty-button"
                            id="btnOpenAddHostEmpty"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Add First Host
                        </button>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =====================================================
     ADD HOST MODAL
===================================================== -->

<div
    class="host-modal"
    id="addHostModal"
    aria-hidden="true"
>

    <div
        class="host-modal-overlay"
        data-close-host-modal
    ></div>


    <div
        class="host-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addHostModalTitle"
    >

        <div class="host-modal-header">

            <div class="host-modal-heading">

                <div class="host-modal-icon">

                    <i class="fa-solid fa-microphone-lines"></i>

                </div>

                <div>

                    <span class="host-modal-label">
                        HOST MANAGEMENT
                    </span>

                    <h2 id="addHostModalTitle">
                        Add Host
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="host-modal-close"
                data-close-host-modal
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="addHostForm"
        >

            <input
                type="hidden"
                name="action"
                value="add_host"
            >


            <div class="host-modal-body">

                <!-- IMAGE -->

                <div class="host-image-upload">

                    <div class="host-image-preview-box">

                        <img
                            id="addHostImagePreview"
                            src=""
                            alt="Host Preview"
                            class="host-preview-image"
                        >

                        <div
                            class="host-picture-placeholder"
                            id="addHostPicturePlaceholder"
                        >

                            <i class="fa-solid fa-user-microphone"></i>

                            <span>
                                Host Photo
                            </span>

                            <small>
                                JPG, PNG, WEBP
                            </small>

                        </div>

                    </div>


                    <label
                        for="addHostImage"
                        class="host-file-label"
                    >

                        <i class="fa-solid fa-upload"></i>

                        <span id="addHostFileText">
                            Choose Image
                        </span>

                    </label>


                    <input
                        type="file"
                        name="image"
                        id="addHostImage"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        hidden
                    >

                </div>


                <!-- FORM -->

                <div class="host-form-grid">

                    <div class="host-form-group">

                        <label for="addHostName">
                            Host Name
                            <span>*</span>
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                name="host_name"
                                id="addHostName"
                                maxlength="150"
                                placeholder="Enter host name"
                                required
                            >

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="addHostType">
                            Host Type
                            <span>*</span>
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-microphone-lines"></i>

                            <select
                                name="host_type"
                                id="addHostType"
                                required
                            >

                                <option value="">
                                    Select host type
                                </option>

                                <?php foreach (
                                    $hostTypes
                                    as $type
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                                <option value="Singing Host">
                                    Singing Host
                                </option>

                                <option value="Event Host">
                                    Event Host
                                </option>

                                <option value="Professional Host">
                                    Professional Host
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="addHostEmail">
                            Email
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                name="email"
                                id="addHostEmail"
                                maxlength="150"
                                placeholder="Enter email address"
                            >

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="addHostPhone">
                            Phone
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="text"
                                name="phone"
                                id="addHostPhone"
                                maxlength="50"
                                placeholder="Enter phone number"
                            >

                        </div>

                    </div>


                    <div class="host-form-group host-form-full">

                        <label for="addHostDescription">
                            Description
                        </label>

                        <div class="host-textarea-wrapper">

                            <i class="fa-regular fa-file-lines"></i>

                            <textarea
                                name="description"
                                id="addHostDescription"
                                rows="4"
                                maxlength="1000"
                                placeholder="Enter a short description about the host..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="host-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-host-modal
                >
                    <i class="fa-solid fa-xmark"></i>
                    <span>Cancel</span>
                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Save Host</span>
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT HOST MODAL
===================================================== -->

<div
    class="host-modal"
    id="editHostModal"
    aria-hidden="true"
>

    <div
        class="host-modal-overlay"
        data-close-host-modal
    ></div>


    <div
        class="host-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editHostModalTitle"
    >

        <div class="host-modal-header">

            <div class="host-modal-heading">

                <div class="host-modal-icon">

                    <i class="fa-solid fa-user-pen"></i>

                </div>

                <div>

                    <span class="host-modal-label">
                        HOST MANAGEMENT
                    </span>

                    <h2 id="editHostModalTitle">
                        Edit Host
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="host-modal-close"
                data-close-host-modal
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="editHostForm"
        >

            <input
                type="hidden"
                name="action"
                value="update_host"
            >

            <input
                type="hidden"
                name="host_id"
                id="editHostId"
                value=""
            >


            <div class="host-modal-body">

                <!-- IMAGE -->

                <div class="host-image-upload">

                    <div class="host-image-preview-box">

                        <img
                            id="editHostImagePreview"
                            src=""
                            alt="Host Preview"
                            class="host-preview-image"
                        >

                        <div
                            class="host-picture-placeholder"
                            id="editHostPicturePlaceholder"
                        >

                            <i class="fa-solid fa-user-microphone"></i>

                            <span>
                                Host Photo
                            </span>

                            <small>
                                JPG, PNG, WEBP
                            </small>

                        </div>

                    </div>


                    <label
                        for="editHostImage"
                        class="host-file-label"
                    >

                        <i class="fa-solid fa-upload"></i>

                        <span id="editHostFileText">
                            Change Image
                        </span>

                    </label>


                    <input
                        type="file"
                        name="image"
                        id="editHostImage"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        hidden
                    >

                </div>


                <!-- FORM -->

                <div class="host-form-grid">

                    <div class="host-form-group">

                        <label for="editHostName">
                            Host Name
                            <span>*</span>
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                name="host_name"
                                id="editHostName"
                                maxlength="150"
                                placeholder="Enter host name"
                                required
                            >

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="editHostType">
                            Host Type
                            <span>*</span>
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-microphone-lines"></i>

                            <select
                                name="host_type"
                                id="editHostType"
                                required
                            >

                                <option value="">
                                    Select host type
                                </option>

                                <?php foreach (
                                    $hostTypes
                                    as $type
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $type,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                                <option value="Singing Host">
                                    Singing Host
                                </option>

                                <option value="Event Host">
                                    Event Host
                                </option>

                                <option value="Professional Host">
                                    Professional Host
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="editHostEmail">
                            Email
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                name="email"
                                id="editHostEmail"
                                maxlength="150"
                                placeholder="Enter email address"
                            >

                        </div>

                    </div>


                    <div class="host-form-group">

                        <label for="editHostPhone">
                            Phone
                        </label>

                        <div class="host-input-wrapper">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="text"
                                name="phone"
                                id="editHostPhone"
                                maxlength="50"
                                placeholder="Enter phone number"
                            >

                        </div>

                    </div>


                    <div class="host-form-group host-form-full">

                        <label for="editHostDescription">
                            Description
                        </label>

                        <div class="host-textarea-wrapper">

                            <i class="fa-regular fa-file-lines"></i>

                            <textarea
                                name="description"
                                id="editHostDescription"
                                rows="4"
                                maxlength="1000"
                                placeholder="Enter a short description about the host..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="host-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-host-modal
                >
                    <i class="fa-solid fa-xmark"></i>
                    <span>Cancel</span>
                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Update Host</span>
                </button>

            </div>

        </form>

    </div>

</div>


<script src="admin.js/admin_host.js"></script>

<?php

$pageContent =
    ob_get_clean();


require_once
    __DIR__ .
    "/admin_include/admin_header.php";

?>