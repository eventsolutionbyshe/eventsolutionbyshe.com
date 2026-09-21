/* =====================================================
   ADMIN DASHBOARD JAVASCRIPT

   Event Solutions by S.H.E.
===================================================== */

"use strict";


/* =====================================================
   CONFIGURATION
===================================================== */

const DASHBOARD_REFRESH_INTERVAL = 3000;


/* =====================================================
   STATE
===================================================== */

let dashboardRefreshInProgress = false;
let dashboardRefreshTimer = null;
let dashboardInitialized = false;


/* =====================================================
   DOM HELPER
===================================================== */

function getElement(id) {

    return document.getElementById(id);

}


/* =====================================================
   FORMAT NUMBER
===================================================== */

function formatNumber(value) {

    const number = Number(value);

    if (!Number.isFinite(number)) {

        return "0";

    }

    return number.toLocaleString("en-US");

}


/* =====================================================
   UPDATE NUMBER
===================================================== */

function updateNumber(id, value) {

    const element = getElement(id);

    if (!element) {

        return;

    }

    const newValue = formatNumber(value);

    const oldValue =
        element.textContent.trim();


    if (oldValue === newValue) {

        return;

    }


    element.classList.remove(
        "value-changed"
    );


    void element.offsetWidth;


    element.textContent =
        newValue;


    element.classList.add(
        "value-changed"
    );


    setTimeout(() => {

        element.classList.remove(
            "value-changed"
        );

    }, 700);

}


/* =====================================================
   SYSTEM STATUS
===================================================== */

function setSystemStatus(status) {

    const systemLive =
        getElement("systemLive");

    const statusText =
        getElement("systemStatusText");


    if (!systemLive || !statusText) {

        return;

    }


    systemLive.classList.remove(
        "syncing",
        "offline"
    );


    if (status === "syncing") {

        systemLive.classList.add(
            "syncing"
        );

        statusText.textContent =
            "Syncing...";

        return;

    }


    if (status === "offline") {

        systemLive.classList.add(
            "offline"
        );

        statusText.textContent =
            "Connection Error";

        return;

    }


    statusText.textContent =
        "System Live";

}


/* =====================================================
   UPDATE DASHBOARD COUNTS
===================================================== */

function updateDashboardCounts(data) {

    if (!data) {

        return;

    }


    updateNumber(
        "upcomingEventsCount",
        data.upcomingEvents
    );


    updateNumber(
        "customersCount",
        data.customers
    );


    updateNumber(
        "pendingBookingsCount",
        data.pendingBookings
    );


    updateNumber(
        "confirmedBookingsCount",
        data.confirmedBookings
    );


    updateNumber(
        "totalBookingsCount",
        data.totalBookings
    );


    updateNumber(
        "venuesCount",
        data.venues
    );


    updateNumber(
        "hostsCount",
        data.hosts
    );


    updateNumber(
        "packagesCount",
        data.packages
    );


    updateNumber(
        "regularEventsCount",
        data.regularEvents
    );


    updateNumber(
        "weddingEventsCount",
        data.weddingEvents
    );

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
   FORMAT EVENT DATE
===================================================== */

function formatEventDate(dateString) {

    if (!dateString) {

        return {
            month: "---",
            day: "--"
        };

    }


    const date =
        new Date(
            dateString + "T00:00:00"
        );


    if (Number.isNaN(date.getTime())) {

        return {
            month: "---",
            day: "--"
        };

    }


    return {

        month:
            date.toLocaleDateString(
                "en-US",
                {
                    month: "short"
                }
            ),

        day:
            date.toLocaleDateString(
                "en-US",
                {
                    day: "2-digit"
                }
            )

    };

}


/* =====================================================
   FORMAT EVENT TIME
===================================================== */

function formatEventTime(timeString) {

    if (!timeString) {

        return "Time not specified";

    }


    const parts =
        String(timeString).split(":");


    if (parts.length < 2) {

        return timeString;

    }


    let hours =
        parseInt(
            parts[0],
            10
        );


    const minutes =
        parts[1];


    if (Number.isNaN(hours)) {

        return timeString;

    }


    const suffix =
        hours >= 12
            ? "PM"
            : "AM";


    hours =
        hours % 12 || 12;


    return (
        hours +
        ":" +
        minutes +
        " " +
        suffix
    );

}


/* =====================================================
   UPDATE UPCOMING EVENTS
===================================================== */

function updateUpcomingEvents(events) {

    const container =
        getElement(
            "upcomingEventsList"
        );


    if (!container) {

        return;

    }


    if (
        !Array.isArray(events) ||
        events.length === 0
    ) {

        const emptyHtml = `
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                <strong>
                    No upcoming events
                </strong>
                <span>
                    Confirmed events will appear here.
                </span>
            </div>
        `;


        if (
            container.dataset.lastHtml ===
            emptyHtml
        ) {

            return;

        }


        container.dataset.lastHtml =
            emptyHtml;


        container.innerHTML =
            emptyHtml;


        return;

    }


    const html =
        events.map(event => {

            const date =
                formatEventDate(
                    event.event_date
                );


            const time =
                formatEventTime(
                    event.start_time
                );


            const eventName =
                escapeHtml(
                    event.event_name ||
                    "Event"
                );


            const venue =
                escapeHtml(
                    event.venue_name ||
                    "Venue not specified"
                );


            const eventType =
                escapeHtml(
                    event.event_type ||
                    "Event"
                );


            const guestCount =
                formatNumber(
                    event.guest_count || 0
                );


            return `
                <div class="event-row">

                    <div class="event-date">

                        <span>
                            ${escapeHtml(date.month)}
                        </span>

                        <strong>
                            ${escapeHtml(date.day)}
                        </strong>

                    </div>


                    <div class="event-details">

                        <strong>
                            ${eventName}
                        </strong>


                        <span>
                            <i class="fa-regular fa-clock"></i>
                            ${escapeHtml(time)}
                        </span>


                        <span>
                            <i class="fa-solid fa-location-dot"></i>
                            ${venue}
                        </span>

                    </div>


                    <div class="event-meta">

                        <span class="event-type">
                            ${eventType}
                        </span>


                        <span class="guest-count">
                            <i class="fa-solid fa-users"></i>
                            ${guestCount}
                        </span>

                    </div>

                </div>
            `;

        }).join("");


    if (
        container.dataset.lastHtml === html
    ) {

        return;

    }


    container.dataset.lastHtml =
        html;


    container.innerHTML =
        html;

}


/* =====================================================
   PROFILE IMAGE URL
===================================================== */

function getProfileImageUrl(profileImage) {

    const value =
        String(
            profileImage || ""
        ).trim();


    if (!value) {

        return "";

    }


    /*
       External URL
    */

    if (
        /^https?:\/\//i.test(value)
    ) {

        return value;

    }


    /*
       Root-relative URL
    */

    if (
        value.startsWith("/")
    ) {

        return value;

    }


    /*
       Normalize slashes
    */

    const normalized =
        value
            .replace(/\\/g, "/")
            .replace(/^\/+/, "");


    /*
       Get project root.

       Example:
       /eventsolutions/admin8765/admin_dashboard.php

       becomes:

       /eventsolutions
    */

    const path =
        window.location.pathname;


    const adminIndex =
        path.indexOf(
            "/admin8765/"
        );


    const projectRoot =
        adminIndex !== -1
            ? path.substring(
                0,
                adminIndex
            )
            : "";


    /*
       Already images/
    */

    if (
        normalized.startsWith(
            "images/"
        )
    ) {

        return (
            projectRoot +
            "/" +
            normalized
        );

    }


    /*
       Already profiles/
    */

    if (
        normalized.startsWith(
            "profiles/"
        )
    ) {

        return (
            projectRoot +
            "/images/" +
            normalized
        );

    }


    /*
       Already contains images/profiles/
    */

    if (
        normalized.startsWith(
            "images/profiles/"
        )
    ) {

        return (
            projectRoot +
            "/" +
            normalized
        );

    }


    /*
       Filename only

       Example:

       profile_1_1788969884.png

       becomes:

       /eventsolutions/images/profiles/profile_1_1788969884.png
    */

    return (
        projectRoot +
        "/images/profiles/" +
        normalized.split("/").pop()
    );

}


/* =====================================================
   UPDATE RECENT BOOKINGS
===================================================== */

function updateRecentBookings(bookings) {

    const wrapper =
        getElement(
            "recentBookingsWrapper"
        );


    if (!wrapper) {

        return;

    }


    if (
        !Array.isArray(bookings) ||
        bookings.length === 0
    ) {

        const emptyHtml = `
            <div class="empty-state">

                <i class="fa-regular fa-folder-open"></i>

                <strong>
                    No bookings yet
                </strong>

                <span>
                    New bookings will appear here.
                </span>

            </div>
        `;


        if (
            wrapper.dataset.lastHtml ===
            emptyHtml
        ) {

            return;

        }


        wrapper.dataset.lastHtml =
            emptyHtml;


        wrapper.innerHTML =
            emptyHtml;


        return;

    }


    const rows =
        bookings.map(booking => {

            const customer =
                booking.customer_name ||
                "Customer";


            const email =
                booking.customer_email ||
                "";


            const event =
                booking.event_name ||
                "Event";


            const eventType =
                booking.event_type ||
                "Event";


            const packageName =
                booking.package_name ||
                "No package";


            const status =
                String(
                    booking.status ||
                    "pending"
                )
                .trim()
                .toLowerCase();


            /*
               CUSTOMER INITIAL

               Always use the first letter
               when the profile image is empty.
            */

            const customerName =
                String(customer).trim();


            const initial =
                escapeHtml(
                    customerName
                        .charAt(0)
                        .toUpperCase() ||
                    "C"
                );


            /*
               PROFILE IMAGE

               If the profile image is NULL,
               empty, or invalid, the initial
               will be displayed.
            */

            const profileImage =
                getProfileImageUrl(
                    booking.profile_image
                );


            let formattedDate =
                "No date";


            if (booking.event_date) {

                const date =
                    new Date(
                        booking.event_date +
                        "T00:00:00"
                    );


                if (
                    !Number.isNaN(
                        date.getTime()
                    )
                ) {

                    formattedDate =
                        date.toLocaleDateString(
                            "en-US",
                            {
                                month: "short",
                                day: "2-digit",
                                year: "numeric"
                            }
                        );

                }

            }


            /*
               AVATAR HTML
            */

            let avatarHtml;


            /*
               PROFILE IMAGE EXISTS
            */

            if (
                profileImage !== ""
            ) {

                avatarHtml = `

                    <img
                        src="${escapeHtml(profileImage)}"
                        alt="${escapeHtml(customer)}"
                        onerror="
                            this.style.display='none';
                            this.nextElementSibling.style.display='flex';
                        "
                    >

                    <span
                        class="customer-avatar-fallback"
                        style="
                            display:none;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        ${initial}
                    </span>

                `;

            } else {

                /*
                   NO PROFILE IMAGE

                   SHOW FIRST LETTER
                */

                avatarHtml = `

                    <span
                        class="customer-avatar-fallback"
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        ${initial}
                    </span>

                `;

            }


            return `

                <tr>

                    <td>

                        <div class="customer-cell">

                            <div class="customer-avatar">

                                ${avatarHtml}

                            </div>


                            <div>

                                <strong>
                                    ${escapeHtml(customer)}
                                </strong>

                                <span>
                                    ${escapeHtml(email)}
                                </span>

                            </div>

                        </div>

                    </td>


                    <td>

                        <strong>
                            ${escapeHtml(event)}
                        </strong>

                        <span class="table-event-type">
                            ${escapeHtml(eventType)}
                        </span>

                    </td>


                    <td>

                        ${escapeHtml(formattedDate)}

                    </td>


                    <td>

                        ${escapeHtml(packageName)}

                    </td>


                    <td>

                        <span
                            class="status-badge status-${escapeHtml(status)}"
                        >

                            ${escapeHtml(
                                status.charAt(0).toUpperCase() +
                                status.slice(1)
                            )}

                        </span>

                    </td>

                </tr>

            `;

        }).join("");


    const html = `

        <table class="recent-table">

            <thead>

                <tr>

                    <th>
                        Customer
                    </th>

                    <th>
                        Event
                    </th>

                    <th>
                        Date
                    </th>

                    <th>
                        Package
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody id="recentBookingsBody">

                ${rows}

            </tbody>

        </table>

    `;


    if (
        wrapper.dataset.lastHtml === html
    ) {

        return;

    }


    wrapper.dataset.lastHtml =
        html;


    wrapper.innerHTML =
        html;

}


/* =====================================================
   UPDATE POPULAR PACKAGES
===================================================== */

function updatePopularPackages(packages) {

    const container =
        getElement(
            "popularPackagesList"
        );


    if (!container) {

        return;

    }


    if (
        !Array.isArray(packages) ||
        packages.length === 0
    ) {

        const emptyHtml = `
            <div class="empty-state">

                <i class="fa-solid fa-box-open"></i>

                <strong>
                    No package data
                </strong>

                <span>
                    Package bookings will appear here.
                </span>

            </div>
        `;


        if (
            container.dataset.lastHtml ===
            emptyHtml
        ) {

            return;

        }


        container.dataset.lastHtml =
            emptyHtml;


        container.innerHTML =
            emptyHtml;


        return;

    }


    const html =
        packages.map(
            (item, index) => {

                return `

                    <div class="popular-package">

                        <div class="package-rank">
                            ${index + 1}
                        </div>


                        <div class="package-info">

                            <strong>

                                ${escapeHtml(
                                    item.package_name ||
                                    "Package"
                                )}

                            </strong>


                            <span>

                                ${formatNumber(
                                    item.booking_count || 0
                                )}

                                bookings

                            </span>

                        </div>


                        <div class="package-arrow">

                            <i class="fa-solid fa-chevron-right"></i>

                        </div>

                    </div>

                `;

            }

        ).join("");


    if (
        container.dataset.lastHtml === html
    ) {

        return;

    }


    container.dataset.lastHtml =
        html;


    container.innerHTML =
        html;

}


/* =====================================================
   GET DASHBOARD URL
===================================================== */

function getDashboardUrl() {

    const url =
        new URL(
            window.location.href
        );


    url.search = "";
    url.hash = "";


    url.searchParams.set(
        "action",
        "live_dashboard"
    );


    url.searchParams.set(
        "_",
        Date.now().toString()
    );


    return url.toString();

}


/* =====================================================
   REFRESH DASHBOARD
===================================================== */

async function refreshDashboard() {

    if (dashboardRefreshInProgress) {

        return;

    }


    if (
        document.visibilityState !==
        "visible"
    ) {

        return;

    }


    dashboardRefreshInProgress =
        true;


    setSystemStatus(
        "syncing"
    );


    try {

        const url =
            getDashboardUrl();


        console.log(
            "Dashboard refreshing:",
            url
        );


        const response =
            await fetch(
                url,
                {
                    method: "GET",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {

                        "Accept":
                            "application/json",

                        "X-Requested-With":
                            "XMLHttpRequest"

                    }

                }
            );


        if (!response.ok) {

            throw new Error(
                "HTTP Error: " +
                response.status
            );

        }


        const text =
            await response.text();


        let result;


        try {

            result =
                JSON.parse(text);

        } catch (error) {

            console.error(
                "Dashboard returned invalid JSON:"
            );

            console.error(text);


            throw new Error(
                "Server did not return valid JSON"
            );

        }


        if (
            !result ||
            result.success !== true ||
            !result.data
        ) {

            console.error(
                "Invalid dashboard response:",
                result
            );


            throw new Error(
                "Invalid dashboard response"
            );

        }


        const data =
            result.data;


        updateDashboardCounts(
            data
        );


        updateUpcomingEvents(
            data.upcomingEventList
        );


        updateRecentBookings(
            data.recentBookings
        );


        updatePopularPackages(
            data.popularPackages
        );


        window.adminDashboardData =
            data;


        setSystemStatus(
            "live"
        );


        console.log(
            "Dashboard updated successfully"
        );


    } catch (error) {

        console.error(
            "Dashboard auto-update error:",
            error
        );


        setSystemStatus(
            "offline"
        );


    } finally {

        dashboardRefreshInProgress =
            false;

    }

}


/* =====================================================
   AUTO REFRESH LOOP
===================================================== */

function scheduleDashboardRefresh() {

    if (dashboardRefreshTimer) {

        clearTimeout(
            dashboardRefreshTimer
        );

    }


    dashboardRefreshTimer =
        setTimeout(
            async () => {

                if (
                    document.visibilityState ===
                    "visible"
                ) {

                    await refreshDashboard();

                }


                scheduleDashboardRefresh();

            },

            DASHBOARD_REFRESH_INTERVAL

        );

}


/* =====================================================
   PAGE VISIBILITY
===================================================== */

document.addEventListener(
    "visibilitychange",
    () => {

        if (
            document.visibilityState ===
            "visible"
        ) {

            refreshDashboard();

        }

    }
);


/* =====================================================
   INITIALIZE DASHBOARD
===================================================== */

function initializeDashboard() {

    if (dashboardInitialized) {

        return;

    }


    dashboardInitialized =
        true;


    console.log(
        "Admin Dashboard initialized"
    );


    /*
       FIRST LIVE UPDATE
    */

    refreshDashboard();


    /*
       START AUTO REFRESH
    */

    scheduleDashboardRefresh();

}


/* =====================================================
   WAIT FOR DOM
===================================================== */

if (
    document.readyState === "loading"
) {

    document.addEventListener(
        "DOMContentLoaded",
        initializeDashboard
    );

} else {

    initializeDashboard();

}