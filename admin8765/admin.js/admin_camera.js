/* =====================================================
   ADMIN CAMERA JAVASCRIPT
===================================================== */

"use strict";


/* =====================================================
   VERSION
===================================================== */

console.log(
    "ADMIN CAMERA JS LOADED - TOKEN FIX v4"
);


/* =====================================================
   CONFIGURATION
===================================================== */

const cameraConfig =
    window.CAMERA_CONFIG || {};

const confirmUrl =
    cameraConfig.confirmUrl ||
    "admin_wg.php?ajax=confirm_guest";

const guestListUrl =
    cameraConfig.guestListUrl ||
    "admin_wg.php";


/* =====================================================
   DOM ELEMENTS
===================================================== */

const qrReader =
    document.getElementById("qrReader");

const cameraOverlay =
    document.getElementById("cameraOverlay");

const cameraLoading =
    document.getElementById("cameraLoading");

const startScannerBtn =
    document.getElementById("startScanner");

const stopScannerBtn =
    document.getElementById("stopScanner");

const switchCameraBtn =
    document.getElementById("switchCamera");

const cameraStatus =
    document.getElementById("cameraStatus");

const cameraMessage =
    document.getElementById("cameraMessage");

const scanResult =
    document.getElementById("scanResult");

const scanGuest =
    document.getElementById("scanGuest");

const scanGuestName =
    document.getElementById("scanGuestName");

const scanGuestEmail =
    document.getElementById("scanGuestEmail");

const scanEvent =
    document.getElementById("scanEvent");

const scanEventName =
    document.getElementById("scanEventName");

const scanPhone =
    document.getElementById("scanPhone");

const scanGuestStatus =
    document.getElementById("scanGuestStatus");

const scanConfirmedAt =
    document.getElementById("scanConfirmedAt");

const openEventGuestList =
    document.getElementById("openEventGuestList");


/* =====================================================
   CONFIRMATION MODAL ELEMENTS
===================================================== */

const attendanceConfirmModal =
    document.getElementById(
        "attendanceConfirmModal"
    );

const attendanceConfirmBackdrop =
    document.getElementById(
        "attendanceConfirmBackdrop"
    );

const attendanceConfirmContinue =
    document.getElementById(
        "attendanceConfirmContinue"
    );

const attendanceConfirmGuest =
    document.getElementById(
        "attendanceConfirmGuest"
    );

const attendanceConfirmEvent =
    document.getElementById(
        "attendanceConfirmEvent"
    );

const attendanceConfirmStatus =
    document.getElementById(
        "attendanceConfirmStatus"
    );


/* =====================================================
   TOKEN EMPTY MODAL ELEMENTS
===================================================== */

const tokenEmptyModal =
    document.getElementById(
        "tokenEmptyModal"
    );

const tokenEmptyBackdrop =
    document.getElementById(
        "tokenEmptyBackdrop"
    );

const tokenEmptyContinue =
    document.getElementById(
        "tokenEmptyContinue"
    );


/* =====================================================
   CAMERA VARIABLES
===================================================== */

let html5QrCode = null;

let cameras = [];

let currentCameraIndex = 0;

let isScanning = false;

let lastScannedCode = "";

let scanLocked = false;

let isProcessingScan = false;

let scanUnlockTimer = null;


/* =====================================================
   LIBRARY CHECK
===================================================== */

if (
    typeof Html5Qrcode === "undefined"
) {

    console.error(
        "HTML5 QR Code library was not loaded."
    );

    updateCameraStatus(
        "Library Error",
        "error"
    );

    setCameraMessage(
        "QR scanner library could not be loaded."
    );
}


/* =====================================================
   CAMERA CONFIGURATION
===================================================== */

/*
 * IMPORTANT:
 *
 * There is NO qrbox here.
 *
 * The page already has:
 *
 * #cameraOverlay
 *
 * which provides the visible QR frame.
 *
 * Using qrbox would create another
 * position frame.
 */

function getScannerConfig() {

    return {

        fps: 15,

        disableFlip: false

    };

}


/* =====================================================
   CAMERA SELECTION
===================================================== */

function selectPreferredCamera() {

    if (
        !cameras.length
    ) {

        return null;

    }


    const preferredIndex =
        cameras.findIndex(
            camera => {

                const label =
                    (
                        camera.label ||
                        ""
                    ).toLowerCase();

                return (
                    label.includes("back") ||
                    label.includes("rear") ||
                    label.includes("environment")
                );

            }
        );


    if (
        preferredIndex >= 0 &&
        currentCameraIndex === 0
    ) {

        currentCameraIndex =
            preferredIndex;

    }


    if (
        currentCameraIndex >=
        cameras.length
    ) {

        currentCameraIndex = 0;

    }


    return cameras[
        currentCameraIndex
    ];

}


/* =====================================================
   START CAMERA
===================================================== */

async function startCamera() {

    if (
        typeof Html5Qrcode === "undefined"
    ) {

        setCameraMessage(
            "QR scanner library is unavailable."
        );

        return;

    }


    if (
        isScanning
    ) {

        return;

    }


    try {

        showCameraLoading(
            true,
            "Starting camera..."
        );


        updateCameraStatus(
            "Starting...",
            "starting"
        );


        if (
            !cameras.length
        ) {

            cameras =
                await Html5Qrcode.getCameras();

        }


        if (
            !cameras.length
        ) {

            throw new Error(
                "No camera was detected on this device."
            );

        }


        const selectedCamera =
            selectPreferredCamera();


        if (
            !selectedCamera
        ) {

            throw new Error(
                "No usable camera was found."
            );

        }


        if (
            !html5QrCode
        ) {

            html5QrCode =
                new Html5Qrcode(
                    "qrReader"
                );

        }


        const scannerConfig =
            getScannerConfig();


        console.log(
            "Starting camera:",
            selectedCamera
        );


        await html5QrCode.start(
            selectedCamera.id,
            scannerConfig,
            onScanSuccess,
            onScanFailure
        );


        isScanning = true;


        updateCameraStatus(
            "Camera Active",
            "active"
        );


        setCameraMessage(
            "Position the guest QR code inside the frame."
        );


        if (
            startScannerBtn
        ) {

            startScannerBtn.disabled =
                true;

        }


        if (
            stopScannerBtn
        ) {

            stopScannerBtn.disabled =
                false;

        }


        if (
            switchCameraBtn
        ) {

            switchCameraBtn.disabled =
                cameras.length <= 1;

        }


        if (
            cameraOverlay
        ) {

            cameraOverlay.classList.add(
                "active"
            );

        }

    }

    catch (error) {

        console.error(
            "Camera start error:",
            error
        );


        isScanning = false;


        updateCameraStatus(
            "Camera Error",
            "error"
        );


        let message =
            "Unable to start the camera.";


        if (
            error &&
            error.name ===
                "NotAllowedError"
        ) {

            message =
                "Camera permission was denied. Please allow camera access and try again.";

        }

        else if (
            error &&
            error.name ===
                "NotFoundError"
        ) {

            message =
                "No camera was found on this device.";

        }

        else if (
            error &&
            error.name ===
                "NotReadableError"
        ) {

            message =
                "The camera is already being used by another application.";

        }

        else if (
            error &&
            error.name ===
                "OverconstrainedError"
        ) {

            message =
                "The selected camera configuration is not supported.";

        }

        else if (
            location.protocol !==
                "https:" &&
            location.hostname !==
                "localhost"
        ) {

            message =
                "Camera access requires HTTPS or localhost.";

        }

        else if (
            error &&
            error.message
        ) {

            message =
                error.message;

        }


        setCameraMessage(
            message
        );


        if (
            startScannerBtn
        ) {

            startScannerBtn.disabled =
                false;

        }


        if (
            stopScannerBtn
        ) {

            stopScannerBtn.disabled =
                true;

        }


        if (
            switchCameraBtn
        ) {

            switchCameraBtn.disabled =
                true;

        }

    }

    finally {

        showCameraLoading(
            false
        );

    }

}


/* =====================================================
   EXTRACT TOKEN FROM QR CODE
===================================================== */

/*
 * Supports:
 *
 * 1. Raw guest token
 *
 *    ABC123456
 *
 *
 * 2. Full invitation URL
 *
 *    http://localhost/eventsolutions/invite/
 *    view_invitation.php?guest_token=ABC123456
 *
 *
 * 3. guest_token parameter
 *
 *    ?guest_token=ABC123456
 *
 *
 * 4. token parameter
 *
 *    ?token=ABC123456
 *
 *
 * IMPORTANT:
 *
 * The server ultimately decides whether the
 * token belongs to invitation_wedding.
 *
 * booking_wedding.invitation_token is NOT
 * used by this JavaScript to identify a guest.
 */

function extractQrToken(
    qrText
) {

    if (
        qrText === null ||
        qrText === undefined
    ) {

        return "";

    }


    const value =
        String(
            qrText
        ).trim();


    if (
        !value
    ) {

        return "";

    }


    console.log(
        "Original QR payload:",
        value
    );


    /* =================================================
       TRY FULL URL
    ================================================= */

    try {

        const url =
            new URL(
                value
            );


        const guestToken =
            url.searchParams.get(
                "guest_token"
            );


        const token =
            url.searchParams.get(
                "token"
            );


        /*
         * Prefer guest_token when available.
         */

        const extractedToken =
            guestToken ||
            token;


        if (
            extractedToken
        ) {

            const decodedToken =
                extractedToken.trim();


            console.log(
                "Token extracted from URL."
            );


            console.log(
                "Token parameter:",
                guestToken
                    ? "guest_token"
                    : "token"
            );


            return decodedToken;

        }

    }

    catch (error) {

        /*
         * QR payload is not a complete URL.
         *
         * Continue with manual extraction.
         */

    }


    /* =================================================
       MANUAL TOKEN EXTRACTION
    ================================================= */

    const match =
        value.match(
            /(?:\?|&)(?:guest_token|token)=([^&#\s]+)/i
        );


    if (
        match &&
        match[1]
    ) {

        try {

            const decodedToken =
                decodeURIComponent(
                    match[1]
                ).trim();


            console.log(
                "Token extracted manually."
            );


            return decodedToken;

        }

        catch (error) {

            return match[1].trim();

        }

    }


    /* =================================================
       RAW TOKEN
    ================================================= */

    console.log(
        "QR payload treated as raw token."
    );


    return value;

}


/* =====================================================
   QR SUCCESS
===================================================== */

function onScanSuccess(
    decodedText,
    decodedResult
) {

    console.log(
        "QR detected."
    );


    if (
        scanLocked ||
        isProcessingScan
    ) {

        return;

    }


    if (
        decodedText === null ||
        decodedText === undefined
    ) {

        console.warn(
            "QR scanner returned an empty value."
        );


        showTokenEmptyModal();


        return;

    }


    const scannedToken =
        extractQrToken(
            decodedText
        );


    /* =================================================
       TOKEN EMPTY
    ================================================= */

    if (
        !scannedToken
    ) {

        console.warn(
            "QR code contains no usable token."
        );


        setCameraMessage(
            "The QR code does not contain a valid invitation token."
        );


        showTokenEmptyModal();


        return;

    }


    console.log(
        "Invitation token detected. Length:",
        scannedToken.length
    );


    /* =================================================
       DUPLICATE SCAN
    ================================================= */

    if (
        scannedToken ===
        lastScannedCode
    ) {

        return;

    }


    lastScannedCode =
        scannedToken;


    scanLocked = true;

    isProcessingScan = true;


    setCameraMessage(
        "QR code detected. Checking guest invitation..."
    );


    processQRCode(
        scannedToken
    );

}


/* =====================================================
   QR FAILURE
===================================================== */

function onScanFailure(
    errorMessage
) {

    /*
     * html5-qrcode continuously calls this
     * while searching for a QR code.
     *
     * These are normal scanning events.
     */

}


/* =====================================================
   PROCESS QR CODE
===================================================== */

async function processQRCode(
    token
) {

    try {

        const cleanToken =
            String(
                token || ""
            ).trim();


        if (
            !cleanToken
        ) {

            showTokenEmptyModal();


            throw new Error(
                "The QR code does not contain a valid invitation token."
            );

        }


        console.log(
            "Sending invitation token to server."
        );


        console.log(
            "Confirmation URL:",
            confirmUrl
        );


        console.log(
            "Token length:",
            cleanToken.length
        );


        /* =================================================
           SEND TOKEN
        ================================================= */

        const response =
            await fetch(
                confirmUrl,
                {

                    method: "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {

                        "Content-Type":
                            "application/x-www-form-urlencoded; charset=UTF-8",

                        "X-Requested-With":
                            "XMLHttpRequest"

                    },

                    body:
                        "token=" +
                        encodeURIComponent(
                            cleanToken
                        )

                }
            );


        const responseText =
            await response.text();


        console.log(
            "Server HTTP status:",
            response.status
        );


        console.log(
            "Server response:",
            responseText
        );


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        }

        catch (jsonError) {

            console.error(
                "JSON parsing error:",
                jsonError
            );


            throw new Error(
                "The server returned an invalid response. Check the browser console for the server response."
            );

        }


        /* =================================================
           SERVER HTTP ERROR
        ================================================= */

        if (
            !response.ok
        ) {

            throw new Error(
                data.message ||
                "The server returned an error."
            );

        }


        /* =================================================
           EVENT QR DETECTED
        ================================================= */

        if (
            data.status ===
            "event_token"
        ) {

            setCameraMessage(
                "This is an event QR code. Please scan the guest's individual invitation QR code."
            );


            showTokenEmptyModal(
                "Event QR Code Detected",
                "This QR code belongs to the event, not an individual guest. Please scan the guest's individual invitation QR code."
            );


            return;

        }


        /* =================================================
           SUCCESS
        ================================================= */

        if (
            data.success
        ) {

            displayScanResult(
                data
            );


            showAttendanceConfirmation(
                data
            );


            setCameraMessage(
                "Attendance confirmed successfully."
            );


            return;

        }


        /* =================================================
           SERVER ERROR
        ================================================= */

        throw new Error(
            data.message ||
            "This QR code could not be confirmed."
        );

    }

    catch (error) {

        console.error(
            "QR processing error:",
            error
        );


        setCameraMessage(
            error.message ||
            "Unable to confirm attendance."
        );


        /*
         * If the token-empty modal is already open,
         * do not immediately unlock the scanner.
         */

        if (
            !tokenEmptyModal ||
            tokenEmptyModal.hidden
        ) {

            unlockScanner(
                1800
            );

        }

    }

}


/* =====================================================
   DISPLAY SCAN RESULT
===================================================== */

function displayScanResult(
    data
) {

    const guest =
        data.guest || {};

    const event =
        data.event || {};


    /* =================================================
       SHOW RESULT
    ================================================= */

    if (
        scanResult
    ) {

        scanResult.classList.remove(
            "empty"
        );


        scanResult.innerHTML = `

            <i class="fa-solid fa-circle-check"></i>

            <strong>
                Attendance Confirmed
            </strong>

            <span>
                Guest attendance has been successfully confirmed.
            </span>

        `;

    }


    /* =================================================
       GUEST
    ================================================= */

    if (
        scanGuest
    ) {

        scanGuest.hidden =
            false;

    }


    if (
        scanGuestName
    ) {

        scanGuestName.textContent =
            guest.name ||
            guest.guest_name ||
            "—";

    }


    if (
        scanGuestEmail
    ) {

        scanGuestEmail.textContent =
            guest.email ||
            guest.guest_email ||
            "—";

    }


    /* =================================================
       EVENT
    ================================================= */

    if (
        scanEvent
    ) {

        scanEvent.hidden =
            false;

    }


    if (
        scanEventName
    ) {

        scanEventName.textContent =
            event.name ||
            event.event_name ||
            "—";

    }


    /* =================================================
       PHONE
    ================================================= */

    if (
        scanPhone
    ) {

        scanPhone.textContent =
            guest.phone ||
            guest.contact ||
            guest.contact_number ||
            event.phone ||
            "—";

    }


    /* =================================================
       STATUS
    ================================================= */

    if (
        scanGuestStatus
    ) {

        scanGuestStatus.textContent =
            guest.status ||
            guest.guest_status ||
            data.status ||
            "Confirmed";


        scanGuestStatus.classList.add(
            "confirmed"
        );

    }


    /* =================================================
       CONFIRMED TIME
    ================================================= */

    if (
        scanConfirmedAt
    ) {

        scanConfirmedAt.textContent =
            guest.confirmed_at ||
            data.confirmed_at ||
            "Just now";

    }


    /* =================================================
       EVENT GUEST LIST
    ================================================= */

    if (
        openEventGuestList
    ) {

        /*
         * IMPORTANT:
         *
         * This token is ONLY used for opening
         * the event guest list.
         *
         * It is NOT used to confirm the guest.
         */

        const eventToken =
            event.token ||
            event.invitation_token ||
            data.event_token ||
            "";


        if (
            eventToken
        ) {

            openEventGuestList.href =
                guestListUrl +
                "?token=" +
                encodeURIComponent(
                    eventToken
                );

        }

        else {

            openEventGuestList.href =
                guestListUrl;

        }


        openEventGuestList.hidden =
            false;

    }

}


/* =====================================================
   SHOW ATTENDANCE CONFIRMATION
===================================================== */

function showAttendanceConfirmation(
    data
) {

    if (
        !attendanceConfirmModal
    ) {

        return;

    }


    const guest =
        data.guest || {};

    const event =
        data.event || {};


    const guestName =
        guest.name ||
        guest.guest_name ||
        data.guest_name ||
        "Guest";


    const eventName =
        event.name ||
        event.event_name ||
        data.event_name ||
        "Event";


    /* =================================================
       UPDATE MODAL
    ================================================= */

    if (
        attendanceConfirmGuest
    ) {

        attendanceConfirmGuest.textContent =
            guestName;

    }


    if (
        attendanceConfirmEvent
    ) {

        attendanceConfirmEvent.textContent =
            eventName;

    }


    if (
        attendanceConfirmStatus
    ) {

        attendanceConfirmStatus.textContent =
            "Confirmed";

    }


    /* =================================================
       UPDATE LATEST SCAN STATUS
    ================================================= */

    if (
        scanGuestStatus
    ) {

        scanGuestStatus.textContent =
            "Confirmed";


        scanGuestStatus.classList.add(
            "confirmed"
        );

    }


    /* =================================================
       SHOW MODAL
    ================================================= */

    attendanceConfirmModal.hidden =
        false;


    document.body.classList.add(
        "attendance-modal-open"
    );


    setTimeout(
        function () {

            if (
                attendanceConfirmContinue
            ) {

                attendanceConfirmContinue.focus();

            }

        },
        100
    );

}


/* =====================================================
   CLOSE ATTENDANCE CONFIRMATION
===================================================== */

function closeAttendanceConfirmation() {

    if (
        !attendanceConfirmModal
    ) {

        return;

    }


    attendanceConfirmModal.hidden =
        true;


    document.body.classList.remove(
        "attendance-modal-open"
    );


    lastScannedCode =
        "";

    isProcessingScan =
        false;

    scanLocked =
        false;


    setCameraMessage(
        "Position the next QR code inside the frame."
    );

}


/* =====================================================
   SHOW TOKEN EMPTY MODAL
===================================================== */

function showTokenEmptyModal(
    title,
    message
) {

    if (
        !tokenEmptyModal
    ) {

        return;

    }


    /*
     * Update title/message when supplied.
     */

    const modalTitle =
        document.getElementById(
            "tokenEmptyTitle"
        );

    const modalMessage =
        document.getElementById(
            "tokenEmptyMessage"
        );


    if (
        modalTitle
    ) {

        modalTitle.textContent =
            title ||
            "Guest QR Code Required";

    }


    if (
        modalMessage
    ) {

        modalMessage.textContent =
            message ||
            "Please scan the guest's individual invitation QR code.";

    }


    /*
     * Lock scanning while the
     * popup is visible.
     */

    scanLocked =
        true;

    isProcessingScan =
        false;


    tokenEmptyModal.hidden =
        false;


    document.body.classList.add(
        "token-empty-modal-open"
    );


    setTimeout(
        function () {

            if (
                tokenEmptyContinue
            ) {

                tokenEmptyContinue.focus();

            }

        },
        100
    );

}


/* =====================================================
   CLOSE TOKEN EMPTY MODAL
===================================================== */

function closeTokenEmptyModal() {

    if (
        !tokenEmptyModal
    ) {

        return;

    }


    tokenEmptyModal.hidden =
        true;


    document.body.classList.remove(
        "token-empty-modal-open"
    );


    lastScannedCode =
        "";

    isProcessingScan =
        false;

    scanLocked =
        false;


    setCameraMessage(
        "Position the next QR code inside the frame."
    );

}


/* =====================================================
   UNLOCK SCANNER
===================================================== */

function unlockScanner(
    delay = 1800
) {

    if (
        scanUnlockTimer
    ) {

        clearTimeout(
            scanUnlockTimer
        );

    }


    scanUnlockTimer =
        setTimeout(
            function () {

                scanLocked =
                    false;

                isProcessingScan =
                    false;

                lastScannedCode =
                    "";

            },
            delay
        );

}


/* =====================================================
   STOP CAMERA
===================================================== */

async function stopCamera() {

    if (
        !html5QrCode ||
        !isScanning
    ) {

        return;

    }


    try {

        await html5QrCode.stop();

    }

    catch (error) {

        console.error(
            "Camera stop error:",
            error
        );

    }


    isScanning =
        false;


    updateCameraStatus(
        "Camera Stopped",
        "waiting"
    );


    setCameraMessage(
        "Click Start Camera to begin scanning."
    );


    if (
        startScannerBtn
    ) {

        startScannerBtn.disabled =
            false;

    }


    if (
        stopScannerBtn
    ) {

        stopScannerBtn.disabled =
            true;

    }


    if (
        switchCameraBtn
    ) {

        switchCameraBtn.disabled =
            true;

    }


    if (
        cameraOverlay
    ) {

        cameraOverlay.classList.remove(
            "active"
        );

    }

}


/* =====================================================
   SWITCH CAMERA
===================================================== */

async function switchCamera() {

    if (
        cameras.length <= 1 ||
        !isScanning
    ) {

        return;

    }


    try {

        await html5QrCode.stop();

    }

    catch (error) {

        console.error(
            "Camera switch stop error:",
            error
        );

    }


    isScanning =
        false;


    currentCameraIndex =
        (
            currentCameraIndex + 1
        ) %
        cameras.length;


    console.log(
        "Switching to camera:",
        cameras[
            currentCameraIndex
        ]
    );


    await startCamera();

}


/* =====================================================
   CAMERA STATUS
===================================================== */

function updateCameraStatus(
    text,
    state
) {

    if (
        !cameraStatus
    ) {

        return;

    }


    cameraStatus.className =
        "camera-status " +
        (
            state ||
            "waiting"
        );


    /*
     * Keep the existing status dot.
     */

    let statusText =
        cameraStatus.querySelector(
            ".camera-status-text"
        );


    if (
        !statusText
    ) {

        statusText =
            document.createElement(
                "span"
            );


        statusText.className =
            "camera-status-text";


        cameraStatus.appendChild(
            statusText
        );

    }


    statusText.textContent =
        text;

}


/* =====================================================
   CAMERA MESSAGE
===================================================== */

function setCameraMessage(
    message
) {

    if (
        !cameraMessage
    ) {

        return;

    }


    cameraMessage.innerHTML =
        escapeHtml(
            message
        );

}


/* =====================================================
   CAMERA LOADING
===================================================== */

function showCameraLoading(
    show,
    message
) {

    if (
        !cameraLoading
    ) {

        return;

    }


    cameraLoading.hidden =
        !show;


    if (
        show &&
        message
    ) {

        const loadingText =
            cameraLoading.querySelector(
                "span"
            );


        if (
            loadingText
        ) {

            loadingText.textContent =
                message;

        }

    }

}


/* =====================================================
   ESCAPE HTML
===================================================== */

function escapeHtml(
    value
) {

    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        value == null
            ? ""
            : String(value);


    return div.innerHTML;

}


/* =====================================================
   BUTTON EVENTS
===================================================== */

if (
    startScannerBtn
) {

    startScannerBtn.addEventListener(
        "click",
        startCamera
    );

}


if (
    stopScannerBtn
) {

    stopScannerBtn.addEventListener(
        "click",
        stopCamera
    );

}


if (
    switchCameraBtn
) {

    switchCameraBtn.addEventListener(
        "click",
        switchCamera
    );

}


/* =====================================================
   CONFIRMATION MODAL EVENTS
===================================================== */

if (
    attendanceConfirmContinue
) {

    attendanceConfirmContinue.addEventListener(
        "click",
        closeAttendanceConfirmation
    );

}


if (
    attendanceConfirmBackdrop
) {

    attendanceConfirmBackdrop.addEventListener(
        "click",
        function () {

            /*
             * Keep the confirmation modal open
             * until Continue is clicked.
             */

        }
    );

}


/* =====================================================
   TOKEN EMPTY MODAL EVENTS
===================================================== */

if (
    tokenEmptyContinue
) {

    tokenEmptyContinue.addEventListener(
        "click",
        closeTokenEmptyModal
    );

}


if (
    tokenEmptyBackdrop
) {

    tokenEmptyBackdrop.addEventListener(
        "click",
        closeTokenEmptyModal
    );

}


/* =====================================================
   ESC KEY
===================================================== */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key !== "Escape"
        ) {

            return;

        }


        if (
            attendanceConfirmModal &&
            !attendanceConfirmModal.hidden
        ) {

            closeAttendanceConfirmation();

            return;

        }


        if (
            tokenEmptyModal &&
            !tokenEmptyModal.hidden
        ) {

            closeTokenEmptyModal();

        }

    }
);


/* =====================================================
   CLEANUP
===================================================== */

window.addEventListener(
    "beforeunload",
    function () {

        if (
            html5QrCode &&
            isScanning
        ) {

            html5QrCode
                .stop()
                .catch(
                    function () {}
                );

        }

    }
);