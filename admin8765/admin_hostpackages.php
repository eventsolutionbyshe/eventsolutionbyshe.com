<?php

/* =====================================================
   ADMIN HOST & HOST PACKAGE MANAGEMENT
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

$pageTitle = "Host Packages";
$pageSection = "Administrator";
$pageHeading = "Host Packages";
$pageAdminCss = "admin_hostpackages.css";
$pageAdminJs = "admin_hostpackages.js";


/* =====================================================
   UPLOAD SETTINGS
===================================================== */

$hostUploadDirectory = "../images/hosts/";
$hostDatabaseDirectory = "images/hosts/";

if (!is_dir($hostUploadDirectory)) {
    @mkdir($hostUploadDirectory, 0777, true);
}


/* =====================================================
   HELPER
===================================================== */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =====================================================
   IMAGE HELPERS
===================================================== */

function createHostImageName($originalName)
{
    $extension = strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
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

    if (
        preg_match(
            '/^https?:\/\//i',
            $imagePath
        )
    ) {
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
    $imagePath = trim(
        (string) $imagePath
    );

    if ($imagePath === "") {
        return "";
    }

    if (
        preg_match(
            '/^https?:\/\//i',
            $imagePath
        )
    ) {
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

    } elseif ($_GET["success"] === "package_added") {

        $message =
            "Host package added successfully.";

        $messageType =
            "success";

    } elseif ($_GET["success"] === "package_updated") {

        $message =
            "Host package updated successfully.";

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

            if ($imagePath !== "") {
                deleteHostImage($imagePath);
            }

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
            "Location: admin_hostpackages.php?success=added"
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
           DELETE OLD IMAGE
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
            "Location: admin_hostpackages.php?success=updated"
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
   ADD HOST PACKAGE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "add_host_package"
) {

    $packageHostId =
        (int) ($_POST["host_id"] ?? 0);

    $packageName =
        trim($_POST["package_name"] ?? "");

    $inclusions =
        trim($_POST["inclusions"] ?? "");

    $price =
        (float) ($_POST["price"] ?? 0);

    try {

        if ($packageHostId <= 0) {
            throw new Exception(
                "Please select a host."
            );
        }

        if ($packageName === "") {
            throw new Exception(
                "Please enter the package name."
            );
        }

        if ($price < 0) {
            throw new Exception(
                "Package price cannot be negative."
            );
        }


        $sql = "
            INSERT INTO host_packages
            (
                host_id,
                package_name,
                inclusions,
                price
            )
            VALUES
            (?, ?, ?, ?)
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare the add package query."
            );
        }

        $stmt->bind_param(
            "issd",
            $packageHostId,
            $packageName,
            $inclusions,
            $price
        );

        if (!$stmt->execute()) {

            $stmt->close();

            throw new Exception(
                "Unable to add the host package."
            );
        }

        $stmt->close();

        header(
            "Location: admin_hostpackages.php?success=package_added"
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
   UPDATE HOST PACKAGE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update_host_package"
) {

    $packageId =
        (int) ($_POST["package_id"] ?? 0);

    $packageHostId =
        (int) ($_POST["host_id"] ?? 0);

    $packageName =
        trim($_POST["package_name"] ?? "");

    $inclusions =
        trim($_POST["inclusions"] ?? "");

    $price =
        (float) ($_POST["price"] ?? 0);

    try {

        if ($packageId <= 0) {
            throw new Exception(
                "Invalid package ID."
            );
        }

        if ($packageHostId <= 0) {
            throw new Exception(
                "Please select a host."
            );
        }

        if ($packageName === "") {
            throw new Exception(
                "Please enter the package name."
            );
        }

        if ($price < 0) {
            throw new Exception(
                "Package price cannot be negative."
            );


        }


        $sql = "
            UPDATE host_packages
            SET
                host_id = ?,
                package_name = ?,
                inclusions = ?,
                price = ?
            WHERE id = ?
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare the update package query."
            );
        }

        $stmt->bind_param(
            "issdi",
            $packageHostId,
            $packageName,
            $inclusions,
            $price,
            $packageId
        );

        if (!$stmt->execute()) {

            $stmt->close();

            throw new Exception(
                "Unable to update the host package."
            );
        }

        $stmt->close();

        header(
            "Location: admin_hostpackages.php?success=package_updated"
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

$hostFilter =
    (int) ($_GET["host_id"] ?? 0);


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
   NO STATUS COLUMN
===================================================== */

$hosts = [];

$hostSql = "
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

$hostParams = [];
$hostTypesBind = "";

if ($search !== "") {

    $hostSql .= "
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

    $hostParams[] = $searchValue;
    $hostParams[] = $searchValue;
    $hostParams[] = $searchValue;
    $hostParams[] = $searchValue;
    $hostParams[] = $searchValue;

    $hostTypesBind .= "sssss";
}

if ($hostFilter > 0) {

    $hostSql .= "
        AND id = ?
    ";

    $hostParams[] =
        $hostFilter;

    $hostTypesBind .= "i";
}

$hostSql .= "
    ORDER BY host_name ASC
";

$hostStmt =
    $conn->prepare($hostSql);

if ($hostStmt) {

    if (!empty($hostParams)) {

        $hostStmt->bind_param(
            $hostTypesBind,
            ...$hostParams
        );
    }

    $hostStmt->execute();

    $hostResult =
        $hostStmt->get_result();

    while (
        $row =
        $hostResult->fetch_assoc()
    ) {

        $hosts[] =
            $row;
    }

    $hostStmt->close();
}


/* =====================================================
   HOST PACKAGE QUERY
===================================================== */

$hostPackages = [];

$packageSql = "
    SELECT
        hp.id,
        hp.host_id,
        hp.package_name,
        hp.inclusions,
        hp.price,
        hp.created_at,
        hp.updated_at,
        h.host_name,
        h.host_type,
        h.image AS host_image
    FROM host_packages hp
    INNER JOIN hosts h
        ON h.id = hp.host_id
    WHERE 1 = 1
";

$packageParams = [];
$packageTypes = "";

if ($search !== "") {

    $packageSql .= "
        AND
        (
            hp.package_name LIKE ?
            OR hp.inclusions LIKE ?
            OR h.host_name LIKE ?
            OR h.host_type LIKE ?
        )
    ";

    $searchValue =
        "%" .
        $search .
        "%";

    $packageParams[] = $searchValue;
    $packageParams[] = $searchValue;
    $packageParams[] = $searchValue;
    $packageParams[] = $searchValue;

    $packageTypes .= "ssss";
}

if ($hostFilter > 0) {

    $packageSql .= "
        AND hp.host_id = ?
    ";

    $packageParams[] =
        $hostFilter;

    $packageTypes .= "i";
}

$packageSql .= "
    ORDER BY hp.id DESC
";

$packageStmt =
    $conn->prepare($packageSql);

if ($packageStmt) {

    if (!empty($packageParams)) {

        $packageStmt->bind_param(
            $packageTypes,
            ...$packageParams
        );
    }

    $packageStmt->execute();

    $packageResult =
        $packageStmt->get_result();

    while (
        $row =
        $packageResult->fetch_assoc()
    ) {

        $hostPackages[] =
            $row;
    }

    $packageStmt->close();
}


/* =====================================================
   STATISTICS
===================================================== */

$totalHosts = 0;
$totalPackages = 0;
$totalHostTypes = count($hostTypes);

$countResult =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM hosts
    ");

if ($countResult) {

    $countRow =
        $countResult->fetch_assoc();

    $totalHosts =
        (int) $countRow["total"];
}


$packageCountResult =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM host_packages
    ");

if ($packageCountResult) {

    $packageCountRow =
        $packageCountResult->fetch_assoc();

    $totalPackages =
        (int) $packageCountRow["total"];
}

$displayedPackages =
    count($hostPackages);


/* =====================================================
   OUTPUT BUFFER
===================================================== */

ob_start();

?>

<div class="admin-hostpackages">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="hostpackages-header">

        <div class="hostpackages-header-left">

            <div class="hostpackages-title-icon">
                <i class="fa-solid fa-microphone-lines"></i>
            </div>

            <div>

                <span class="hostpackages-page-label">
                    <?= e($pageSection) ?>
                </span>

                <h2>
                    <?= e($pageHeading) ?>
                </h2>

                <p>
                    Manage hosts and singing host packages.
                </p>

            </div>

        </div>


        <div class="hostpackages-header-actions">

            <button
                type="button"
                class="btn-hostpackages-report"
                id="btnHostPackagesReport"
            >
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>


            <button
                type="button"
                class="btn-add-hostpackages"
                id="btnOpenAddHost"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add Host</span>
            </button>


            <button
                type="button"
                class="btn-add-hostpackages btn-add-package"
                id="btnOpenAddHostPackage"
            >
                <i class="fa-solid fa-box"></i>
                <span>Add Package</span>
            </button>

        </div>

    </div>


    <!-- =================================================
         ERROR
    ================================================== -->

    <?php if (
        $message !== "" &&
        $messageType === "error"
    ): ?>

        <div class="hostpackages-alert error">

            <div>
                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= e($message) ?>
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
            class="hostpackages-toast"
            id="hostPackagesSuccessToast"
            role="status"
            aria-live="polite"
        >

            <div class="hostpackages-toast-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div class="hostpackages-toast-content">

                <strong>
                    Success
                </strong>

                <span>
                    <?= e($message) ?>
                </span>

            </div>

            <button
                type="button"
                class="hostpackages-toast-close"
                id="hostPackagesToastClose"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="hostpackages-toast-progress"></div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="hostpackages-stat-grid">

        <div class="hostpackages-stat-card">

            <div class="hostpackages-stat-icon">
                <i class="fa-solid fa-microphone"></i>
            </div>

            <div class="hostpackages-stat-content">

                <span>
                    Total Hosts
                </span>

                <strong>
                    <?= number_format($totalHosts) ?>
                </strong>

                <small>
                    All registered hosts
                </small>

            </div>

        </div>


        <div class="hostpackages-stat-card">

            <div class="hostpackages-stat-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <div class="hostpackages-stat-content">

                <span>
                    Total Packages
                </span>

                <strong>
                    <?= number_format($totalPackages) ?>
                </strong>

                <small>
                    Available host packages
                </small>

            </div>

        </div>


        <div class="hostpackages-stat-card">

            <div class="hostpackages-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <div class="hostpackages-stat-content">

                <span>
                    Host Types
                </span>

                <strong>
                    <?= number_format($totalHostTypes) ?>
                </strong>

                <small>
                    Available host categories
                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         HOST MANAGEMENT
    ================================================== -->

    <div class="hostpackages-panel">

        <div class="hostpackages-panel-header">

            <div>

                <span class="hostpackages-panel-label">
                    HOST MANAGEMENT
                </span>

                <h3>
                    Registered Hosts
                </h3>

                <p>
                    Manage event hosts and singing hosts.
                </p>

            </div>

            <span class="hostpackages-total">
                <?= number_format(count($hosts)) ?> Hosts
            </span>

        </div>


        <!-- FILTER -->

        <form
            method="GET"
            class="hostpackages-filter-bar"
        >

            <div class="hostpackages-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search host name, type, email, phone..."
                >

            </div>


            <div class="hostpackages-filter">

                <i class="fa-solid fa-filter"></i>

                <select name="host_id">

                    <option value="">
                        All Hosts
                    </option>

                    <?php foreach ($hosts as $hostOption): ?>

                        <option
                            value="<?= (int) $hostOption["id"] ?>"
                            <?= $hostFilter === (int) $hostOption["id"]
                                ? "selected"
                                : "" ?>
                        >
                            <?= e($hostOption["host_name"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn-hostpackages-search"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search</span>
            </button>


            <a
                href="admin_hostpackages.php"
                class="btn-hostpackages-reset"
            >
                <i class="fa-solid fa-rotate-left"></i>
                <span>Reset</span>
            </a>

        </form>


        <!-- HOST TABLE -->

        <div class="hostpackages-table-container">

            <?php if (!empty($hosts)): ?>

                <table class="hostpackages-table">

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

                        <?php foreach ($hosts as $host): ?>

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
                                getHostImageUrl($image);

                            $initial =
                                strtoupper(
                                    substr(
                                        trim($hostName),
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

                                            <?php if ($imageUrl !== ""): ?>

                                                <img
                                                    src="<?= e($imageUrl) ?>"
                                                    alt="<?= e($hostName) ?>"
                                                >

                                            <?php else: ?>

                                                <span class="host-avatar-placeholder">
                                                    <?= e($initial) ?>
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="host-information">

                                            <strong>
                                                <?= e($hostName) ?>
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

                                        <?= e($hostType) ?>

                                    </span>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <div class="host-contact">

                                        <?php if ($email !== ""): ?>

                                            <span
                                                title="<?= e($email) ?>"
                                            >

                                                <i class="fa-regular fa-envelope"></i>

                                                <?= e($email) ?>

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

                                        <?php if ($phone !== ""): ?>

                                            <span
                                                title="<?= e($phone) ?>"
                                            >

                                                <i class="fa-solid fa-phone"></i>

                                                <?= e($phone) ?>

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

                                    <?php if ($description !== ""): ?>

                                        <div
                                            class="host-description"
                                            title="<?= e($description) ?>"
                                        >
                                            <?= e($description) ?>
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
                                            data-name="<?= e($hostName) ?>"
                                            data-type="<?= e($hostType) ?>"
                                            data-email="<?= e($email) ?>"
                                            data-phone="<?= e($phone) ?>"
                                            data-description="<?= e($description) ?>"
                                            data-image="<?= e($imageUrl) ?>"
                                        >

                                            <i class="fa-solid fa-pen-to-square"></i>

                                            <span>
                                                Edit
                                            </span>

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="hostpackages-empty">

                    <div class="hostpackages-empty-icon">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>

                    <h3>
                        No Hosts Found
                    </h3>

                    <p>
                        No hosts match your current search or filter.
                    </p>

                    <button
                        type="button"
                        class="hostpackages-empty-button"
                        id="btnOpenAddHostEmpty"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add First Host
                    </button>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         HOST PACKAGES
    ================================================== -->

    <div class="hostpackages-panel host-package-panel">

        <div class="hostpackages-panel-header">

            <div>

                <span class="hostpackages-panel-label">
                    PACKAGE MANAGEMENT
                </span>

                <h3>
                    Host Packages
                </h3>

                <p>
                    Manage packages offered by registered hosts.
                </p>

            </div>

            <span class="hostpackages-total">
                <?= number_format($displayedPackages) ?> Packages
            </span>

        </div>


        <div class="hostpackages-filter-bar">

            <div class="hostpackages-filter-info">

                <i class="fa-solid fa-box-open"></i>

                <span>
                    Host service packages
                </span>

            </div>

        </div>


        <div class="hostpackages-table-container">

            <?php if (!empty($hostPackages)): ?>

                <table class="hostpackages-table package-table">

                    <thead>

                        <tr>

                            <th>
                                Package
                            </th>

                            <th>
                                Host
                            </th>

                            <th>
                                Inclusions
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Created
                            </th>

                            <th class="no-print">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($hostPackages as $package): ?>

                            <?php

                            $packageId =
                                (int) $package["id"];

                            $packageHostId =
                                (int) $package["host_id"];

                            $packageName =
                                $package["package_name"] ?? "";

                            $inclusions =
                                $package["inclusions"] ?? "";

                            $price =
                                (float) $package["price"];

                            $packageHostName =
                                $package["host_name"] ?? "";

                            $packageHostType =
                                $package["host_type"] ?? "";

                            $packageHostImage =
                                getHostImageUrl(
                                    $package["host_image"] ?? ""
                                );

                            ?>

                            <tr>

                                <!-- PACKAGE -->

                                <td>

                                    <div class="package-information">

                                        <strong>
                                            <?= e($packageName) ?>
                                        </strong>

                                        <span>
                                            Package ID:
                                            #<?= $packageId ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- HOST -->

                                <td>

                                    <div class="package-host-info">

                                        <div class="package-host-avatar">

                                            <?php if ($packageHostImage !== ""): ?>

                                                <img
                                                    src="<?= e($packageHostImage) ?>"
                                                    alt="<?= e($packageHostName) ?>"
                                                >

                                            <?php else: ?>

                                                <span>
                                                    <?= e(
                                                        strtoupper(
                                                            substr(
                                                                trim($packageHostName),
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div>

                                            <strong>
                                                <?= e($packageHostName) ?>
                                            </strong>

                                            <small>
                                                <?= e($packageHostType) ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- INCLUSIONS -->

                                <td>

                                    <?php if ($inclusions !== ""): ?>

                                        <div
                                            class="package-inclusions"
                                            title="<?= e($inclusions) ?>"
                                        >
                                            <?= e($inclusions) ?>
                                        </div>

                                    <?php else: ?>

                                        <span class="muted">
                                            No inclusions
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    <strong class="package-price">
                                        ₱<?= number_format(
                                            $price,
                                            2
                                        ) ?>
                                    </strong>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <span class="package-date">

                                        <i class="fa-regular fa-calendar"></i>

                                        <?= !empty($package["created_at"])
                                            ? date(
                                                "M d, Y",
                                                strtotime(
                                                    $package["created_at"]
                                                )
                                            )
                                            : "—" ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td class="no-print">

                                    <div class="package-actions">

                                        <button
                                            type="button"
                                            class="btn-edit-host-package"
                                            data-id="<?= $packageId ?>"
                                            data-host-id="<?= $packageHostId ?>"
                                            data-package-name="<?= e($packageName) ?>"
                                            data-inclusions="<?= e($inclusions) ?>"
                                            data-price="<?= e($price) ?>"
                                        >

                                            <i class="fa-solid fa-pen-to-square"></i>

                                            <span>
                                                Edit
                                            </span>

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="hostpackages-empty">

                    <div class="hostpackages-empty-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <h3>
                        No Host Packages Found
                    </h3>

                    <p>
                        No host packages match your current search or filter.
                    </p>

                    <button
                        type="button"
                        class="hostpackages-empty-button"
                        id="btnOpenAddHostPackageEmpty"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add First Package
                    </button>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =====================================================
     ADD HOST MODAL
===================================================== -->

<div
    class="hostpackage-modal"
    id="addHostModal"
    aria-hidden="true"
>

    <div
        class="hostpackage-modal-overlay"
        data-close-host-modal
    ></div>


    <div
        class="hostpackage-modal-box host-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addHostModalTitle"
    >

        <div class="hostpackage-modal-header">

            <div class="hostpackage-modal-heading">

                <div class="hostpackage-modal-icon">
                    <i class="fa-solid fa-microphone-lines"></i>
                </div>

                <div>

                    <span class="hostpackage-modal-label">
                        HOST MANAGEMENT
                    </span>

                    <h2 id="addHostModalTitle">
                        Add Host
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="hostpackage-modal-close"
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


            <div class="hostpackage-modal-body">

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

                            <i class="fa-solid fa-user"></i>

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

                                <?php foreach ($hostTypes as $type): ?>

                                    <option value="<?= e($type) ?>">
                                        <?= e($type) ?>
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


            <div class="hostpackage-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-host-modal
                >

                    <i class="fa-solid fa-xmark"></i>

                    <span>
                        Cancel
                    </span>

                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        Save Host
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT HOST MODAL
===================================================== -->

<div
    class="hostpackage-modal"
    id="editHostModal"
    aria-hidden="true"
>

    <div
        class="hostpackage-modal-overlay"
        data-close-host-modal
    ></div>


    <div
        class="hostpackage-modal-box host-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editHostModalTitle"
    >

        <div class="hostpackage-modal-header">

            <div class="hostpackage-modal-heading">

                <div class="hostpackage-modal-icon">
                    <i class="fa-solid fa-user-pen"></i>
                </div>

                <div>

                    <span class="hostpackage-modal-label">
                        HOST MANAGEMENT
                    </span>

                    <h2 id="editHostModalTitle">
                        Edit Host
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="hostpackage-modal-close"
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


            <div class="hostpackage-modal-body">

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

                            <i class="fa-solid fa-user"></i>

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

                                <?php foreach ($hostTypes as $type): ?>

                                    <option value="<?= e($type) ?>">
                                        <?= e($type) ?>
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


            <div class="hostpackage-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-host-modal
                >

                    <i class="fa-solid fa-xmark"></i>

                    <span>
                        Cancel
                    </span>

                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        Update Host
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     ADD HOST PACKAGE MODAL
===================================================== -->

<div
    class="hostpackage-modal"
    id="addHostPackageModal"
    aria-hidden="true"
>

    <div
        class="hostpackage-modal-overlay"
        data-close-package-modal
    ></div>


    <div
        class="hostpackage-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addHostPackageModalTitle"
    >

        <div class="hostpackage-modal-header">

            <div class="hostpackage-modal-heading">

                <div class="hostpackage-modal-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <div>

                    <span class="hostpackage-modal-label">
                        PACKAGE MANAGEMENT
                    </span>

                    <h2 id="addHostPackageModalTitle">
                        Add Host Package
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="hostpackage-modal-close"
                data-close-package-modal
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            id="addHostPackageForm"
        >

            <input
                type="hidden"
                name="action"
                value="add_host_package"
            >


            <div class="hostpackage-modal-body">

                <div class="package-form-grid">

                    <div class="package-form-group package-form-full">

                        <label for="addPackageHost">
                            Host
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-microphone-lines"></i>

                            <select
                                name="host_id"
                                id="addPackageHost"
                                required
                            >

                                <option value="">
                                    Select host
                                </option>

                                <?php foreach ($hosts as $host): ?>

                                    <option
                                        value="<?= (int) $host["id"] ?>"
                                    >
                                        <?= e($host["host_name"]) ?>
                                        —
                                        <?= e($host["host_type"]) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <div class="package-form-group">

                        <label for="addPackageName">
                            Package Name
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-box"></i>

                            <input
                                type="text"
                                name="package_name"
                                id="addPackageName"
                                maxlength="150"
                                placeholder="Enter package name"
                                required
                            >

                        </div>

                    </div>


                    <div class="package-form-group">

                        <label for="addPackagePrice">
                            Price
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-peso-sign"></i>

                            <input
                                type="number"
                                name="price"
                                id="addPackagePrice"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                required
                            >

                        </div>

                    </div>


                    <div class="package-form-group package-form-full">

                        <label for="addPackageInclusions">
                            Inclusions
                        </label>

                        <div class="package-textarea-wrapper">

                            <i class="fa-regular fa-file-lines"></i>

                            <textarea
                                name="inclusions"
                                id="addPackageInclusions"
                                rows="6"
                                placeholder="Enter package inclusions..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="hostpackage-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-package-modal
                >

                    <i class="fa-solid fa-xmark"></i>

                    <span>
                        Cancel
                    </span>

                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        Save Package
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT HOST PACKAGE MODAL
===================================================== -->

<div
    class="hostpackage-modal"
    id="editHostPackageModal"
    aria-hidden="true"
>

    <div
        class="hostpackage-modal-overlay"
        data-close-package-modal
    ></div>


    <div
        class="hostpackage-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editHostPackageModalTitle"
    >

        <div class="hostpackage-modal-header">

            <div class="hostpackage-modal-heading">

                <div class="hostpackage-modal-icon">
                    <i class="fa-solid fa-box"></i>
                </div>

                <div>

                    <span class="hostpackage-modal-label">
                        PACKAGE MANAGEMENT
                    </span>

                    <h2 id="editHostPackageModalTitle">
                        Edit Host Package
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="hostpackage-modal-close"
                data-close-package-modal
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            id="editHostPackageForm"
        >

            <input
                type="hidden"
                name="action"
                value="update_host_package"
            >

            <input
                type="hidden"
                name="package_id"
                id="editPackageId"
                value=""
            >


            <div class="hostpackage-modal-body">

                <div class="package-form-grid">

                    <div class="package-form-group package-form-full">

                        <label for="editPackageHost">
                            Host
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-microphone-lines"></i>

                            <select
                                name="host_id"
                                id="editPackageHost"
                                required
                            >

                                <option value="">
                                    Select host
                                </option>

                                <?php foreach ($hosts as $host): ?>

                                    <option
                                        value="<?= (int) $host["id"] ?>"
                                    >
                                        <?= e($host["host_name"]) ?>
                                        —
                                        <?= e($host["host_type"]) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <div class="package-form-group">

                        <label for="editPackageName">
                            Package Name
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-box"></i>

                            <input
                                type="text"
                                name="package_name"
                                id="editPackageName"
                                maxlength="150"
                                placeholder="Enter package name"
                                required
                            >

                        </div>

                    </div>


                    <div class="package-form-group">

                        <label for="editPackagePrice">
                            Price
                            <span>*</span>
                        </label>

                        <div class="package-input-wrapper">

                            <i class="fa-solid fa-peso-sign"></i>

                            <input
                                type="number"
                                name="price"
                                id="editPackagePrice"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                required
                            >

                        </div>

                    </div>


                    <div class="package-form-group package-form-full">

                        <label for="editPackageInclusions">
                            Inclusions
                        </label>

                        <div class="package-textarea-wrapper">

                            <i class="fa-regular fa-file-lines"></i>

                            <textarea
                                name="inclusions"
                                id="editPackageInclusions"
                                rows="6"
                                placeholder="Enter package inclusions..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="hostpackage-modal-footer">

                <button
                    type="button"
                    class="btn-host-cancel"
                    data-close-package-modal
                >

                    <i class="fa-solid fa-xmark"></i>

                    <span>
                        Cancel
                    </span>

                </button>


                <button
                    type="submit"
                    class="btn-host-save"
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        Update Package
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<script src="admin_js/admin_hostpackages.js"></script>

<?php

$pageContent =
    ob_get_clean();

require_once
    __DIR__ .
    "/admin_include/admin_header.php";

?>