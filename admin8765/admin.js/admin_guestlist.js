document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* =====================================================
       CONFIG
    ===================================================== */

    const config =
        window.GUEST_LIST_CONFIG || {};


    const ajaxUrl =
        config.ajaxUrl ||
        "admin_guestlist.php";


    const token =
        config.token || "";


    const pollingInterval =
        Number(config.pollingInterval) || 1000;


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById("guestSearch");


    const statusSelect =
        document.getElementById("guestStatus");


    const refreshButton =
        document.getElementById("refreshGuestList");


    const tableBody =
        document.getElementById("guestTableBody");


    const guestCountLabel =
        document.getElementById("guestCountLabel");


    const totalGuests =
        document.getElementById("totalGuests");


    const confirmedGuests =
        document.getElementById("confirmedGuests");


    const pendingGuests =
        document.getElementById("pendingGuests");


    const declinedGuests =
        document.getElementById("declinedGuests");


    const attendancePercent =
        document.getElementById("attendancePercent");


    const attendanceProgress =
        document.getElementById("attendanceProgress");


    const realtimeBar =
        document.getElementById("guestRealtimeBar");


    const realtimeText =
        document.getElementById("guestRealtimeText");


    const lastUpdate =
        document.getElementById("guestLastUpdate");


    /* =====================================================
       STATE
    ===================================================== */

    let requestRunning = false;

    let pollingTimer = null;

    let destroyed = false;

    let lastDataSignature = "";


    /* =====================================================
       IF NO TOKEN
    ===================================================== */

    if (!token) {
        return;
    }


    /* =====================================================
       ESCAPE HTML
    ===================================================== */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return "";
        }


        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }


    /* =====================================================
       UPDATE REALTIME STATUS
    ===================================================== */

    function setRealtimeStatus(
        state,
        message
    ) {

        if (realtimeBar) {

            realtimeBar.classList.remove(
                "is-online",
                "is-loading",
                "is-error"
            );


            if (state === "online") {

                realtimeBar.classList.add(
                    "is-online"
                );

            } else if (state === "loading") {

                realtimeBar.classList.add(
                    "is-loading"
                );

            } else if (state === "error") {

                realtimeBar.classList.add(
                    "is-error"
                );
            }
        }


        if (realtimeText) {

            realtimeText.textContent =
                message;
        }
    }


    /* =====================================================
       TIME DISPLAY
    ===================================================== */

    function updateLastUpdate() {

        if (!lastUpdate) {
            return;
        }


        const now =
            new Date();


        lastUpdate.textContent =
            "Updated " +
            now.toLocaleTimeString();
    }


    /* =====================================================
       STATUS HTML
    ===================================================== */

    function statusHtml(status) {

        const normalized =
            String(status || "pending")
                .toLowerCase();


        if (normalized === "confirmed") {

            return `
                <span class="guest-status confirmed">
                    <i class="fa-solid fa-circle-check"></i>
                    Confirmed
                </span>
            `;
        }


        if (normalized === "declined") {

            return `
                <span class="guest-status declined">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Declined
                </span>
            `;
        }


        return `
            <span class="guest-status pending">
                <i class="fa-solid fa-clock"></i>
                Pending
            </span>
        `;
    }


    /* =====================================================
       FORMAT CONFIRMED DATE
    ===================================================== */

    function confirmedDate(value) {

        if (!value) {
            return "—";
        }


        return escapeHtml(value);
    }


    /* =====================================================
       RENDER TABLE
    ===================================================== */

    function renderGuests(guests) {

        if (!tableBody) {
            return;
        }


        if (
            !Array.isArray(guests) ||
            guests.length === 0
        ) {

            tableBody.innerHTML = `
                <tr>
                    <td
                        colspan="7"
                        class="guest-empty"
                    >
                        <i class="fa-solid fa-users-slash"></i>

                        <strong>
                            No guests found
                        </strong>

                        <span>
                            No guest records match the current filters.
                        </span>
                    </td>
                </tr>
            `;

            return;
        }


        const rows =
            guests.map(function (guest) {

                const guestName =
                    escapeHtml(
                        guest.guest_name || "Unknown Guest"
                    );


                const email =
                    escapeHtml(
                        guest.guest_email || ""
                    );


                const eventName =
                    escapeHtml(
                        guest.event_name || "—"
                    );


                const packageName =
                    escapeHtml(
                        guest.package_name || ""
                    );


                const eventDate =
                    escapeHtml(
                        guest.event_date || "—"
                    );


                const phone =
                    escapeHtml(
                        guest.phone || "—"
                    );


                const venue =
                    escapeHtml(
                        guest.venue_name || "—"
                    );


                return `
                    <tr>

                        <td>

                            <div class="guest-name-cell">

                                <div class="guest-avatar">

                                    <i class="fa-solid fa-user"></i>

                                </div>

                                <div>

                                    <strong>
                                        ${guestName}
                                    </strong>

                                    ${
                                        email
                                            ? `
                                                <small>
                                                    ${email}
                                                </small>
                                            `
                                            : ""
                                    }

                                </div>

                            </div>

                        </td>


                        <td>

                            <div class="guest-event-cell">

                                <strong>
                                    ${eventName}
                                </strong>

                                ${
                                    packageName
                                        ? `
                                            <small>
                                                ${packageName}
                                            </small>
                                        `
                                        : ""
                                }

                            </div>

                        </td>


                        <td>
                            ${eventDate}
                        </td>


                        <td>
                            ${phone}
                        </td>


                        <td>
                            ${venue}
                        </td>


                        <td>
                            ${statusHtml(
                                guest.status
                            )}
                        </td>


                        <td>
                            ${confirmedDate(
                                guest.confirmed_at
                            )}
                        </td>

                    </tr>
                `;
            });


        tableBody.innerHTML =
            rows.join("");
    }


    /* =====================================================
   UPDATE STATISTICS
===================================================== */

function updateStats(stats) {

    if (!stats) {
        return;
    }


    /*
     * IMPORTANT:
     *
     * PHP returns the booking guest number as:
     *
     * stats.total
     *
     * This comes from:
     *
     * booking_wedding.guest_count
     *
     * Do NOT use invitation_guest_count here.
     */

    const total =
        Number(
            stats.total || 0
        );


    const confirmed =
        Number(
            stats.confirmed || 0
        );


    const pending =
        Number(
            stats.pending || 0
        );


    const declined =
        Number(
            stats.declined || 0
        );


    let percentage =
        Number(
            stats.attendance_percent || 0
        );


    if (!Number.isFinite(percentage)) {
        percentage = 0;
    }


    percentage =
        Math.max(
            0,
            Math.min(
                100,
                percentage
            )
        );


    /* =================================================
       GUEST NUMBER
    ================================================= */

    if (totalGuests) {

        animateNumber(
            totalGuests,
            total
        );
    }


    /* =================================================
       CONFIRMED
    ================================================= */

    if (confirmedGuests) {

        animateNumber(
            confirmedGuests,
            confirmed
        );
    }


    /* =================================================
       PENDING
    ================================================= */

    if (pendingGuests) {

        animateNumber(
            pendingGuests,
            pending
        );
    }


    /* =================================================
       DECLINED
    ================================================= */

    if (declinedGuests) {

        animateNumber(
            declinedGuests,
            declined
        );
    }


    /* =================================================
       ATTENDANCE PERCENTAGE
    ================================================= */

    if (attendancePercent) {

        attendancePercent.textContent =
            percentage + "%";
    }


    if (attendanceProgress) {

        attendanceProgress.style.width =
            percentage + "%";
    }
}


    /* =====================================================
       NUMBER UPDATE
    ===================================================== */

    function animateNumber(
        element,
        value
    ) {

        const newValue =
            Number(value) || 0;


        const oldValue =
            Number(
                element.textContent
            ) || 0;


        if (oldValue === newValue) {
            return;
        }


        element.classList.remove(
            "guest-stat-updated"
        );


        void element.offsetWidth;


        element.textContent =
            newValue;


        element.classList.add(
            "guest-stat-updated"
        );


        setTimeout(function () {

            element.classList.remove(
                "guest-stat-updated"
            );

        }, 600);
    }


    /* =====================================================
       UPDATE GUEST COUNT
    ===================================================== */

    function updateGuestCount(
        guests
    ) {

        if (!guestCountLabel) {
            return;
        }


        const count =
            Array.isArray(guests)
                ? guests.length
                : 0;


        guestCountLabel.textContent =
            count === 1
                ? "1 guest"
                : count + " guests";
    }


    /* =====================================================
       DATA SIGNATURE
       Avoid unnecessary DOM rebuilding.
    ===================================================== */

    function createSignature(data) {

        return JSON.stringify({
            event: data.event || null,
            stats: data.stats || null,
            guests: data.guests || []
        });
    }


    /* =====================================================
       LOAD GUEST LIST
    ===================================================== */

    async function loadGuestList(
        manual = false
    ) {

        if (
            destroyed ||
            requestRunning ||
            !token
        ) {
            return;
        }


        requestRunning = true;


        if (manual) {

            setRealtimeStatus(
                "loading",
                "Refreshing guest list..."
            );

        }


        const search =
            searchInput
                ? searchInput.value.trim()
                : "";


        const status =
            statusSelect
                ? statusSelect.value
                : "";


        const url =
            new URL(
                ajaxUrl,
                window.location.href
            );


        url.searchParams.set(
            "ajax",
            "guest_list"
        );


        url.searchParams.set(
            "token",
            token
        );


        url.searchParams.set(
            "search",
            search
        );


        url.searchParams.set(
            "status",
            status
        );


        /* =================================================
           CACHE BUSTER
        ================================================= */

        url.searchParams.set(
            "_t",
            Date.now()
        );


        try {

            const response =
                await fetch(
                    url.toString(),
                    {
                        method: "GET",

                        cache: "no-store",

                        headers: {
                            "Accept":
                                "application/json",

                            "Cache-Control":
                                "no-cache",

                            "Pragma":
                                "no-cache"
                        }
                    }
                );


            if (!response.ok) {

                throw new Error(
                    "Server returned HTTP " +
                    response.status
                );
            }


            const data =
                await response.json();


            if (!data.success) {

                throw new Error(
                    data.message ||
                    "Unable to load guest list."
                );
            }


            const signature =
                createSignature(data);


            /* =================================================
               ONLY REBUILD IF DATA CHANGED
            ================================================= */

            if (
                signature !==
                lastDataSignature
            ) {

                lastDataSignature =
                    signature;


                renderGuests(
                    data.guests || []
                );


                updateStats(
                    data.stats || {}
                );


                updateGuestCount(
                    data.guests || []
                );
            }


            setRealtimeStatus(
                "online",
                "Live • Updating every 1 second"
            );


            updateLastUpdate();


        } catch (error) {

            console.error(
                "Guest list realtime update error:",
                error
            );


            setRealtimeStatus(
                "error",
                "Realtime connection error • Retrying..."
            );


        } finally {

            requestRunning =
                false;
        }
    }


    /* =====================================================
       START POLLING
    ===================================================== */

    function startPolling() {

        if (
            pollingTimer ||
            destroyed
        ) {
            return;
        }


        pollingTimer =
            setInterval(
                function () {

                    if (
                        document.visibilityState ===
                        "visible"
                    ) {

                        loadGuestList(
                            false
                        );
                    }

                },
                pollingInterval
            );
    }


    /* =====================================================
       STOP POLLING
    ===================================================== */

    function stopPolling() {

        if (pollingTimer) {

            clearInterval(
                pollingTimer
            );

            pollingTimer = null;
        }
    }


    /* =====================================================
       SEARCH
    ===================================================== */

    let searchTimer = null;


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                clearTimeout(
                    searchTimer
                );


                searchTimer =
                    setTimeout(
                        function () {

                            lastDataSignature =
                                "";

                            loadGuestList(
                                true
                            );

                        },
                        250
                    );
            }
        );
    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (statusSelect) {

        statusSelect.addEventListener(
            "change",
            function () {

                lastDataSignature =
                    "";

                loadGuestList(
                    true
                );

            }
        );
    }


    /* =====================================================
       MANUAL REFRESH
    ===================================================== */

    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            function () {

                lastDataSignature =
                    "";


                refreshButton.classList.add(
                    "is-refreshing"
                );


                loadGuestList(
                    true
                );


                setTimeout(
                    function () {

                        refreshButton.classList.remove(
                            "is-refreshing"
                        );

                    },
                    700
                );
            }
        );
    }


    /* =====================================================
       PAUSE WHEN TAB IS HIDDEN
    ===================================================== */

    document.addEventListener(
        "visibilitychange",
        function () {

            if (
                document.visibilityState ===
                "visible"
            ) {

                loadGuestList(
                    true
                );

                startPolling();

            } else {

                stopPolling();

            }
        }
    );


    /* =====================================================
       INITIAL LOAD
    ===================================================== */

    loadGuestList(
        true
    );


    /* =====================================================
       START 1-SECOND REALTIME POLLING
    ===================================================== */

    startPolling();


    /* =====================================================
       CLEANUP
    ===================================================== */

    window.addEventListener(
        "beforeunload",
        function () {

            destroyed =
                true;

            stopPolling();

        }
    );

});