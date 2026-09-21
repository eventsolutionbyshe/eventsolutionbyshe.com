<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   REQUIRE ADMIN LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   PAGE SETTINGS
========================================================= */

$pageTitle = "QR Camera";

$pageSection = "Administrator";

$pageHeading = "QR Camera";


/* =========================================================
   PAGE CONTENT
========================================================= */

ob_start();

?>

<link
    rel="stylesheet"
    href="admin.css/admin_camera.css">


<div class="admin-camera-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="camera-page-header">

        <div>

            <span class="camera-page-eyebrow">
                Guest Attendance
            </span>

            <h1>
                QR Code Scanner
            </h1>

            <p>
                Scan a guest invitation QR code to verify and confirm attendance.
            </p>

        </div>


        <div class="camera-header-actions">

            <a
                href="admin_guestlist.php"
                class="camera-back-btn">

                <i class="fa-solid fa-arrow-left"></i>

                Guest List

            </a>

        </div>

    </div>


    <!-- =====================================================
         CAMERA LAYOUT
    ====================================================== -->

    <div class="camera-layout">


        <!-- =================================================
             CAMERA CARD
        ================================================== -->

        <div class="camera-card">


            <!-- =============================================
                 CAMERA HEADER
            ============================================== -->

            <div class="camera-card-header">

                <div>

                    <span class="camera-label">
                        Guest QR Scanner
                    </span>

                    <h2>
                        Scan Guest QR Code
                    </h2>

                    <p>
                        Scan the guest's individual invitation QR code using your webcam or mobile camera.
                    </p>

                </div>


                <div
                    id="cameraStatus"
                    class="camera-status waiting">

                    <span class="camera-status-dot"></span>

                    Camera Ready

                </div>

            </div>


            <!-- =================================================
                 CAMERA READER
            ================================================== -->

            <div class="camera-reader-wrapper">

                <div
                    id="qrReader"
                    class="camera-reader"></div>


                <!-- =============================================
                     CAMERA OVERLAY

                     IMPORTANT:
                     The camera scans the COMPLETE QR image.
                     The overlay is only a visual guide.
                     No event token is used here.
                ============================================== -->

                <div
                    id="cameraOverlay"
                    class="camera-overlay">

                    <div class="camera-overlay-frame">

                        <span class="corner top-left"></span>

                        <span class="corner top-right"></span>

                        <span class="corner bottom-left"></span>

                        <span class="corner bottom-right"></span>

                    </div>


                    <p>
                        Position the guest QR code inside the frame
                    </p>

                </div>


                <!-- =============================================
                     CAMERA LOADING
                ============================================== -->

                <div
                    id="cameraLoading"
                    class="camera-loading"
                    hidden>

                    <div class="camera-loading-spinner"></div>

                    <span>
                        Starting camera...
                    </span>

                </div>

            </div>


            <!-- =================================================
                 CAMERA CONTROLS
            ================================================== -->

            <div class="camera-controls">


                <!-- =============================================
                     START
                ============================================== -->

                <button
                    type="button"
                    id="startScanner"
                    class="camera-control-btn primary">

                    <i class="fa-solid fa-camera"></i>

                    Start Camera

                </button>


                <!-- =============================================
                     STOP
                ============================================== -->

                <button
                    type="button"
                    id="stopScanner"
                    class="camera-control-btn danger"
                    disabled>

                    <i class="fa-solid fa-stop"></i>

                    Stop

                </button>


                <!-- =============================================
                     SWITCH CAMERA
                ============================================== -->

                <button
                    type="button"
                    id="switchCamera"
                    class="camera-control-btn"
                    disabled>

                    <i class="fa-solid fa-camera-rotate"></i>

                    Switch Camera

                </button>

            </div>


            <!-- =================================================
                 CAMERA MESSAGE
            ================================================== -->

            <div
                id="cameraMessage"
                class="camera-message">

                Click <strong>Start Camera</strong> to begin scanning a guest QR code.

            </div>


            <!-- =================================================
                 CAMERA REQUIREMENTS
            ================================================== -->

            <div
                id="cameraRequirements"
                class="camera-requirements">

                <div class="camera-requirement-item">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>
                        Camera permission is required.
                    </span>

                </div>


                <div class="camera-requirement-item">

                    <i class="fa-solid fa-lock"></i>

                    <span>
                        Camera access requires HTTPS or localhost.
                    </span>

                </div>

            </div>


        </div>


        <!-- =====================================================
             LATEST SCAN CARD
        ====================================================== -->

        <div class="latest-scan-card">


            <!-- =================================================
                 LATEST SCAN HEADER
            ================================================== -->

            <div class="latest-scan-header">

                <div class="latest-scan-icon">

                    <i class="fa-solid fa-user-check"></i>

                </div>


                <div>

                    <span>
                        Latest Scan
                    </span>

                    <h2>
                        Guest Attendance
                    </h2>

                </div>

            </div>


            <!-- =================================================
                 EMPTY SCAN RESULT
            ================================================== -->

            <div
                id="scanResult"
                class="scan-result empty">

                <i class="fa-solid fa-qrcode"></i>

                <strong>
                    Waiting for QR scan
                </strong>

                <span>
                    The guest information will appear here after scanning.
                </span>

            </div>


            <!-- =================================================
                 GUEST INFORMATION
            ================================================== -->

            <div
                id="scanGuest"
                class="scan-guest"
                hidden>

                <div class="scan-guest-avatar">

                    <i class="fa-solid fa-user"></i>

                </div>


                <div class="scan-guest-info">

                    <strong id="scanGuestName">
                        —
                    </strong>

                    <span id="scanGuestEmail">
                        —
                    </span>

                </div>

            </div>


            <!-- =================================================
                 EVENT INFORMATION
            ================================================== -->

            <div
                id="scanEvent"
                class="scan-event"
                hidden>


                <!-- =============================================
                     EVENT
                ============================================== -->

                <div>

                    <span>
                        Event
                    </span>

                    <strong id="scanEventName">
                        —
                    </strong>

                </div>


                <!-- =============================================
                     PHONE
                ============================================== -->

                <div>

                    <span>
                        Phone
                    </span>

                    <strong id="scanPhone">
                        —
                    </strong>

                </div>


                <!-- =============================================
                     STATUS
                ============================================== -->

                <div>

                    <span>
                        Status
                    </span>

                    <strong id="scanGuestStatus">
                        —
                    </strong>

                </div>


                <!-- =============================================
                     CONFIRMED
                ============================================== -->

                <div>

                    <span>
                        Confirmed
                    </span>

                    <strong id="scanConfirmedAt">
                        —
                    </strong>

                </div>

            </div>


            <!-- =================================================
                 VIEW EVENT GUESTS
            ================================================== -->

            <a
                href="#"
                id="openEventGuestList"
                class="open-event-list-btn"
                hidden>

                <i class="fa-solid fa-users"></i>

                View Event Guests

            </a>


        </div>


    </div>


</div>


<!-- =========================================================
     CONFIRMATION MODAL
========================================================= -->

<div
    id="attendanceConfirmModal"
    class="attendance-confirm-modal"
    hidden>

    <div
        class="attendance-confirm-backdrop"
        id="attendanceConfirmBackdrop"></div>


    <div
        class="attendance-confirm-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="attendanceConfirmTitle">


        <!-- =================================================
             SUCCESS ICON
        ================================================== -->

        <div class="attendance-confirm-icon">

            <div class="attendance-confirm-check">

                <i class="fa-solid fa-check"></i>

            </div>

        </div>


        <!-- =================================================
             TITLE
        ================================================== -->

        <div class="attendance-confirm-content">

            <span class="attendance-confirm-label">
                Attendance Confirmed
            </span>

            <h2 id="attendanceConfirmTitle">
                Guest Confirmed
            </h2>

            <p>
                The guest has been successfully marked as confirmed.
            </p>


            <!-- =============================================
                 GUEST
            ============================================== -->

            <div class="attendance-confirm-details">

                <div>

                    <span>
                        Guest
                    </span>

                    <strong id="attendanceConfirmGuest">
                        —
                    </strong>

                </div>


                <!-- =============================================
                     EVENT
                ============================================== -->

                <div>

                    <span>
                        Event
                    </span>

                    <strong id="attendanceConfirmEvent">
                        —
                    </strong>

                </div>


                <!-- =============================================
                     STATUS
                ============================================== -->

                <div>

                    <span>
                        Status
                    </span>

                    <strong
                        id="attendanceConfirmStatus"
                        class="confirmed">
                        Confirmed
                    </strong>

                </div>

            </div>


            <!-- =============================================
                 CONTINUE BUTTON
            ============================================== -->

            <button
                type="button"
                id="attendanceConfirmContinue"
                class="attendance-confirm-continue">

                <i class="fa-solid fa-check"></i>

                Continue

            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     INVALID / EVENT QR MODAL
========================================================= -->

<div
    id="tokenEmptyModal"
    class="token-empty-modal"
    hidden>

    <div
        id="tokenEmptyBackdrop"
        class="token-empty-backdrop"></div>


    <div
        class="token-empty-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tokenEmptyTitle">


        <!-- =================================================
             WARNING ICON
        ================================================== -->

        <div class="token-empty-icon">

            <i class="fa-solid fa-triangle-exclamation"></i>

        </div>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="token-empty-content">

            <span class="token-empty-label">
                Invalid Guest QR Code
            </span>

            <h2 id="tokenEmptyTitle">
                Guest QR Code Required
            </h2>

            <p id="tokenEmptyMessage">
                Please scan the guest's individual invitation QR code.
            </p>

            <p class="token-empty-help">
                The event QR code cannot be used for attendance confirmation.
            </p>

        </div>


        <!-- =================================================
             CONTINUE
        ================================================== -->

        <button
            type="button"
            id="tokenEmptyContinue"
            class="token-empty-continue">

            <i class="fa-solid fa-check"></i>

            OK

        </button>

    </div>

</div>


<!-- =========================================================
     CAMERA CONFIGURATION
========================================================= -->

<script>
    window.CAMERA_CONFIG = {

        /*
         * IMPORTANT:
         *
         * This endpoint expects:
         *
         * invitation_wedding.invitation_token
         *
         * It does NOT expect:
         *
         * booking_wedding.invitation_token
         *
         */

        confirmUrl: "admin_guestlist.php?ajax=confirm_guest",


        /*
         * This is only used after a guest has
         * successfully been identified.
         *
         * The camera does NOT use this URL
         * to identify the guest.
         */

        guestListUrl: "admin_guestlist.php",


        /*
         * The camera uses the custom visual
         * overlay only.
         */

        useCustomOverlay: true,


        /*
         * Guest QR only.
         */

        guestOnly: true

    };
</script>


<!-- =========================================================
     HTML5 QR CODE LIBRARY
========================================================= -->

<script
    src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>


<!-- =========================================================
     CAMERA JAVASCRIPT
========================================================= -->

<script
    src="admin.js/admin_camera.js?v=4"></script>


<?php

$pageContent = ob_get_clean();

require_once __DIR__ . "/admin_include/admin_header.php";

?>
