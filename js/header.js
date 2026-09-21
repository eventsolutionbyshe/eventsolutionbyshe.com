document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | BASIC ELEMENTS
    |--------------------------------------------------------------------------
    */

    const html =
        document.documentElement;

    const siteHeader =
        document.getElementById("siteHeader");

    const themeToggle =
        document.getElementById("themeToggle");

    const menuToggle =
        document.getElementById("menuToggle");

    const mainNav =
        document.getElementById("mainNav");

    const userButton =
        document.getElementById("userButton");

    const userDropdown =
        document.getElementById("userDropdown");

    const notificationButton =
        document.getElementById("notificationButton");

    const notificationDropdown =
        document.getElementById(
            "notificationDropdown"
        );

    const notificationBadge =
        document.getElementById(
            "notificationBadge"
        );

    const notificationList =
        document.getElementById(
            "notificationList"
        );

    const notificationWrapper =
        document.querySelector(
            ".notification-wrapper"
        );


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION API URL
    |--------------------------------------------------------------------------
    */

    const notificationActionUrl =
        notificationWrapper
            ? notificationWrapper.dataset
                .notificationActionUrl
            : null;


    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    let notificationRefreshRunning =
        false;

    let notificationActionRunning =
        new Set();


    /*
    |--------------------------------------------------------------------------
    | THEME
    |--------------------------------------------------------------------------
    */

    const savedTheme =
        localStorage.getItem(
            "eventSolutionsTheme"
        );


    if (
        savedTheme === "dark"
    ) {

        html.classList.add(
            "dark-mode"
        );

    } else {

        html.classList.remove(
            "dark-mode"
        );
    }


    if (themeToggle) {

        themeToggle.addEventListener(
            "click",
            function () {

                const isDark =
                    html.classList.toggle(
                        "dark-mode"
                    );


                localStorage.setItem(
                    "eventSolutionsTheme",
                    isDark
                        ? "dark"
                        : "light"
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HEADER SCROLL
    |--------------------------------------------------------------------------
    */

    function updateHeaderScroll() {

        if (!siteHeader) {
            return;
        }


        siteHeader.classList.toggle(
            "scrolled",
            window.scrollY > 10
        );
    }


    updateHeaderScroll();


    window.addEventListener(
        "scroll",
        updateHeaderScroll,
        {
            passive: true
        }
    );


    /*
    |--------------------------------------------------------------------------
    | MOBILE MENU
    |--------------------------------------------------------------------------
    */

    function closeMobileMenu() {

        if (!mainNav) {
            return;
        }


        mainNav.classList.remove(
            "active"
        );


        if (menuToggle) {

            menuToggle.classList.remove(
                "active"
            );


            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DROPDOWN CONTROL
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | CLOSE USER DROPDOWN
    |--------------------------------------------------------------------------
    */

    function closeUserDropdown() {

        if (
            !userButton ||
            !userDropdown
        ) {

            return;
        }


        userDropdown.classList.remove(
            "active"
        );


        userButton.classList.remove(
            "active"
        );


        userButton.setAttribute(
            "aria-expanded",
            "false"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE NOTIFICATION DROPDOWN
    |--------------------------------------------------------------------------
    */

    function closeNotificationDropdown() {

        if (!notificationDropdown) {
            return;
        }


        notificationDropdown.classList.remove(
            "active"
        );


        notificationDropdown.setAttribute(
            "aria-hidden",
            "true"
        );


        if (notificationButton) {

            notificationButton.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE ALL DROPDOWNS
    |--------------------------------------------------------------------------
    */

    function closeAllDropdowns() {

        closeUserDropdown();

        closeNotificationDropdown();
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN / TOGGLE USER DROPDOWN
    |--------------------------------------------------------------------------
    */

    function toggleUserDropdown() {

        if (
            !userButton ||
            !userDropdown
        ) {

            return;
        }


        const isOpen =
            userDropdown.classList.contains(
                "active"
            );


        /*
        |----------------------------------------------------------------------
        | CLOSE IF ALREADY OPEN
        |----------------------------------------------------------------------
        */

        if (isOpen) {

            closeUserDropdown();

            return;
        }


        /*
        |----------------------------------------------------------------------
        | CLOSE NOTIFICATION FIRST
        |----------------------------------------------------------------------
        */

        closeNotificationDropdown();


        /*
        |----------------------------------------------------------------------
        | CLOSE MOBILE MENU
        |----------------------------------------------------------------------
        */

        closeMobileMenu();


        /*
        |----------------------------------------------------------------------
        | OPEN USER DROPDOWN
        |----------------------------------------------------------------------
        */

        userDropdown.classList.add(
            "active"
        );


        userButton.classList.add(
            "active"
        );


        userButton.setAttribute(
            "aria-expanded",
            "true"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER BUTTON
    |--------------------------------------------------------------------------
    */

    if (userButton) {

        userButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();

                toggleUserDropdown();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER DROPDOWN CLICK
    |--------------------------------------------------------------------------
    */

    if (userDropdown) {

        userDropdown.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN NOTIFICATION DROPDOWN
    |--------------------------------------------------------------------------
    */

    function openNotificationDropdown() {

        if (!notificationDropdown) {
            return;
        }


        /*
        |----------------------------------------------------------------------
        | CLOSE USER DROPDOWN FIRST
        |----------------------------------------------------------------------
        */

        closeUserDropdown();


        /*
        |----------------------------------------------------------------------
        | CLOSE MOBILE MENU
        |----------------------------------------------------------------------
        */

        closeMobileMenu();


        /*
        |----------------------------------------------------------------------
        | OPEN NOTIFICATION DROPDOWN
        |----------------------------------------------------------------------
        */

        notificationDropdown.classList.add(
            "active"
        );


        notificationDropdown.setAttribute(
            "aria-hidden",
            "false"
        );


        if (notificationButton) {

            notificationButton.setAttribute(
                "aria-expanded",
                "true"
            );
        }


        /*
        |----------------------------------------------------------------------
        | REFRESH NOTIFICATIONS
        |----------------------------------------------------------------------
        */

        refreshNotifications();
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION BUTTON
    |--------------------------------------------------------------------------
    */

    if (
        notificationButton &&
        notificationDropdown
    ) {

        notificationButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                const isOpen =
                    notificationDropdown.classList.contains(
                        "active"
                    );


                /*
                |------------------------------------------------------------------
                | CLOSE IF ALREADY OPEN
                |------------------------------------------------------------------
                */

                if (isOpen) {

                    closeNotificationDropdown();

                    return;
                }


                /*
                |------------------------------------------------------------------
                | OPEN NOTIFICATION DROPDOWN
                |------------------------------------------------------------------
                */

                openNotificationDropdown();

            }
        );


        /*
        |----------------------------------------------------------------------
        | PREVENT DROPDOWN CLICK FROM CLOSING
        |----------------------------------------------------------------------
        */

        notificationDropdown.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );


        /*
        |----------------------------------------------------------------------
        | PREVENT DRAG
        |----------------------------------------------------------------------
        */

        notificationButton.addEventListener(
            "dragstart",
            function (event) {

                event.preventDefault();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE MENU BUTTON
    |--------------------------------------------------------------------------
    */

    if (
        menuToggle &&
        mainNav
    ) {

        menuToggle.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                const active =
                    mainNav.classList.toggle(
                        "active"
                    );


                menuToggle.classList.toggle(
                    "active",
                    active
                );


                menuToggle.setAttribute(
                    "aria-expanded",
                    active
                        ? "true"
                        : "false"
                );


                /*
                |------------------------------------------------------------------
                | CLOSE DROPDOWNS WHEN MENU OPENS
                |------------------------------------------------------------------
                */

                if (active) {

                    closeAllDropdowns();

                }

            }
        );


        /*
        |----------------------------------------------------------------------
        | CLOSE MOBILE MENU WHEN LINK IS CLICKED
        |----------------------------------------------------------------------
        */

        mainNav
            .querySelectorAll("a")
            .forEach(
                function (link) {

                    link.addEventListener(
                        "click",
                        closeMobileMenu
                    );
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PARSE MYSQL DATE
    |--------------------------------------------------------------------------
    */

    function parseNotificationDate(
        createdAt
    ) {

        if (!createdAt) {
            return 0;
        }


        let normalized =
            String(createdAt)
                .trim()
                .replace(" ", "T");


        if (
            !normalized.endsWith("Z") &&
            !/[+-]\d{2}:\d{2}$/.test(
                normalized
            )
        ) {

            normalized +=
                "+08:00";
        }


        const timestamp =
            new Date(
                normalized
            ).getTime();


        return Number.isNaN(
            timestamp
        )
            ? 0
            : timestamp;
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT TIME
    |--------------------------------------------------------------------------
    */

    function formatNotificationTime(
        createdAt
    ) {

        const timestamp =
            parseNotificationDate(
                createdAt
            );


        if (!timestamp) {

            return createdAt || "";
        }


        const difference =
            Date.now() -
            timestamp;


        if (difference < 0) {

            return "Just now";
        }


        const seconds =
            Math.floor(
                difference / 1000
            );


        if (seconds < 10) {

            return "Just now";
        }


        if (seconds < 60) {

            return (
                seconds +
                (
                    seconds === 1
                        ? " second ago"
                        : " seconds ago"
                )
            );
        }


        const minutes =
            Math.floor(
                seconds / 60
            );


        if (minutes < 60) {

            return (
                minutes +
                (
                    minutes === 1
                        ? " minute ago"
                        : " minutes ago"
                )
            );
        }


        const hours =
            Math.floor(
                minutes / 60
            );


        if (hours < 24) {

            return (
                hours +
                (
                    hours === 1
                        ? " hour ago"
                        : " hours ago"
                )
            );
        }


        const days =
            Math.floor(
                hours / 24
            );


        if (days === 1) {

            return "Yesterday";
        }


        if (days < 7) {

            return days + " days ago";
        }


        const weeks =
            Math.floor(
                days / 7
            );


        if (weeks < 5) {

            return (
                weeks +
                (
                    weeks === 1
                        ? " week ago"
                        : " weeks ago"
                )
            );
        }


        const months =
            Math.floor(
                days / 30
            );


        if (months < 12) {

            return (
                months +
                (
                    months === 1
                        ? " month ago"
                        : " months ago"
                )
            );
        }


        const years =
            Math.floor(
                days / 365
            );


        return (
            years +
            (
                years === 1
                    ? " year ago"
                    : " years ago"
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ICON
    |--------------------------------------------------------------------------
    */

    function getNotificationIcon(
        type
    ) {

        switch (
            String(type || "")
                .toLowerCase()
        ) {

            case "booking":
                return "bi-calendar-check";

            case "event":
                return "bi-megaphone";

            case "message":
                return "bi-chat";

            case "welcome":
                return "bi-megaphone";

            case "venue":
                return "bi-building";

            case "host":
                return "bi-mic-fill";

            case "payment":
                return "bi-credit-card";

            case "success":
                return "bi-check-circle";

            case "warning":
                return "bi-exclamation-triangle";

            case "error":
                return "bi-x-circle";

            default:
                return "bi-bell";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE NOTIFICATION
    |--------------------------------------------------------------------------
    */

    function createNotificationElement(
        notification
    ) {

        const item =
            document.createElement(
                "div"
            );


        item.className =
            "notification-item";


        item.dataset.notificationId =
            String(
                notification.id
            );


        item.dataset.read =
            String(
                Number(
                    notification.is_read || 0
                )
            );


        item.dataset.status =
            notification.status ||
            "active";


        item.dataset.createdAt =
            notification.created_at ||
            "";


        if (
            Number(
                notification.is_read
            ) === 0
        ) {

            item.classList.add(
                "unread"
            );
        }


        /*
        |----------------------------------------------------------------------
        | ICON
        |----------------------------------------------------------------------
        */

        const icon =
            document.createElement(
                "div"
            );


        icon.className =
            "notification-icon " +
            (
                notification.type ||
                "general"
            );


        icon.innerHTML =
            `
                <span class="notification-icon-symbol">
                    <i class="bi ${getNotificationIcon(
                        notification.type
                    )}"></i>
                </span>
            `;


        /*
        |----------------------------------------------------------------------
        | CONTENT
        |----------------------------------------------------------------------
        */

        const content =
            document.createElement(
                "div"
            );


        content.className =
            "notification-content";


        const title =
            document.createElement(
                "strong"
            );


        title.textContent =
            notification.title ||
            "";


        const message =
            document.createElement(
                "p"
            );


        message.textContent =
            notification.message ||
            "";


        const time =
            document.createElement(
                "small"
            );


        time.textContent =
            formatNotificationTime(
                notification.created_at
            );


        content.appendChild(
            title
        );


        content.appendChild(
            message
        );


        content.appendChild(
            time
        );


        /*
        |----------------------------------------------------------------------
        | ARCHIVE BUTTON
        |----------------------------------------------------------------------
        */

        const deleteButton =
            document.createElement(
                "button"
            );


        deleteButton.type =
            "button";


        deleteButton.className =
            "notification-delete";


        deleteButton.dataset.notificationId =
            String(
                notification.id
            );


        deleteButton.setAttribute(
            "aria-label",
            "Archive notification"
        );


        deleteButton.setAttribute(
            "title",
            "Archive notification"
        );


        deleteButton.innerHTML =
            `<i class="bi bi-trash3"></i>`;


        /*
        |----------------------------------------------------------------------
        | UNREAD DOT
        |----------------------------------------------------------------------
        */

        if (
            Number(
                notification.is_read
            ) === 0
        ) {

            const dot =
                document.createElement(
                    "span"
                );


            dot.className =
                "notification-unread-dot";


            item.appendChild(
                dot
            );
        }


        item.appendChild(
            icon
        );


        item.appendChild(
            content
        );


        item.appendChild(
            deleteButton
        );


        return item;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE NOTIFICATION
    |--------------------------------------------------------------------------
    */

    function updateNotificationElement(
        item,
        notification
    ) {

        item.dataset.read =
            String(
                Number(
                    notification.is_read || 0
                )
            );


        item.dataset.status =
            notification.status ||
            "active";


        item.dataset.createdAt =
            notification.created_at ||
            "";


        /*
        |----------------------------------------------------------------------
        | READ STATE
        |----------------------------------------------------------------------
        */

        const unread =
            Number(
                notification.is_read
            ) === 0;


        item.classList.toggle(
            "unread",
            unread
        );


        /*
        |----------------------------------------------------------------------
        | TITLE
        |----------------------------------------------------------------------
        */

        const title =
            item.querySelector(
                ".notification-content strong"
            );


        if (title) {

            title.textContent =
                notification.title ||
                "";
        }


        /*
        |----------------------------------------------------------------------
        | MESSAGE
        |----------------------------------------------------------------------
        */

        const message =
            item.querySelector(
                ".notification-content p"
            );


        if (message) {

            message.textContent =
                notification.message ||
                "";
        }


        /*
        |----------------------------------------------------------------------
        | TIME
        |----------------------------------------------------------------------
        */

        const time =
            item.querySelector(
                ".notification-content small"
            );


        if (time) {

            time.textContent =
                formatNotificationTime(
                    notification.created_at
                );
        }


        /*
        |----------------------------------------------------------------------
        | ICON
        |----------------------------------------------------------------------
        */

        const icon =
            item.querySelector(
                ".notification-icon"
            );


        if (icon) {

            icon.className =
                "notification-icon " +
                (
                    notification.type ||
                    "general"
                );


            const iconElement =
                icon.querySelector(
                    "i"
                );


            if (iconElement) {

                iconElement.className =
                    "bi " +
                    getNotificationIcon(
                        notification.type
                    );
            }
        }


        /*
        |----------------------------------------------------------------------
        | UNREAD DOT
        |----------------------------------------------------------------------
        */

        const existingDot =
            item.querySelector(
                ".notification-unread-dot"
            );


        if (unread) {

            if (!existingDot) {

                const dot =
                    document.createElement(
                        "span"
                    );


                dot.className =
                    "notification-unread-dot";


                const deleteButton =
                    item.querySelector(
                        ".notification-delete"
                    );


                if (deleteButton) {

                    item.insertBefore(
                        dot,
                        deleteButton
                    );

                } else {

                    item.appendChild(
                        dot
                    );
                }
            }

        } else {

            if (existingDot) {

                existingDot.remove();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE BADGE
    |--------------------------------------------------------------------------
    */

    function updateNotificationCount() {

        if (!notificationList) {
            return;
        }


        const items =
            notificationList.querySelectorAll(
                ".notification-item"
            );


        let unreadCount = 0;


        items.forEach(
            function (item) {

                if (
                    item.dataset.status ===
                    "active" &&
                    item.dataset.read !==
                    "1"
                ) {

                    unreadCount++;
                }
            }
        );


        const badge =
            document.getElementById(
                "notificationBadge"
            );


        if (!badge) {
            return;
        }


        badge.dataset.notificationCount =
            String(
                unreadCount
            );


        if (
            unreadCount > 0
        ) {

            badge.textContent =
                unreadCount > 9
                    ? "9+"
                    : String(
                        unreadCount
                    );


            badge.style.display =
                "flex";

        } else {

            badge.textContent =
                "";


            badge.style.display =
                "none";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    function updateNotificationEmptyState() {

        if (!notificationList) {
            return;
        }


        const items =
            notificationList.querySelectorAll(
                ".notification-item"
            );


        const empty =
            notificationList.querySelector(
                ".notifications-empty"
            );


        if (
            items.length === 0
        ) {

            if (!empty) {

                const element =
                    document.createElement(
                        "div"
                    );


                element.className =
                    "notifications-empty";


                element.innerHTML =
                    `
                        <div class="empty-notification-icon">
                            <span>
                                <i class="bi bi-bell"></i>
                            </span>
                        </div>

                        <strong>
                            No notifications
                        </strong>

                        <p>
                            You're all caught up.
                        </p>
                    `;


                notificationList.appendChild(
                    element
                );
            }

        } else {

            if (empty) {

                empty.remove();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE TIMES
    |--------------------------------------------------------------------------
    */

    function updateNotificationTimes() {

        if (!notificationList) {
            return;
        }


        notificationList
            .querySelectorAll(
                ".notification-item"
            )
            .forEach(
                function (item) {

                    const time =
                        item.querySelector(
                            ".notification-content small"
                        );


                    if (
                        time &&
                        item.dataset.createdAt
                    ) {

                        time.textContent =
                            formatNotificationTime(
                                item.dataset.createdAt
                            );
                    }
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | API REQUEST
    |--------------------------------------------------------------------------
    */

    async function sendNotificationAction(
        action,
        notificationId = 0
    ) {

        if (
            !notificationActionUrl
        ) {

            console.error(
                "Notification API URL is missing."
            );


            return {
                success: false,
                message:
                    "Notification API URL is missing."
            };
        }


        const key =
            action +
            ":" +
            String(
                notificationId || 0
            );


        if (
            notificationActionRunning.has(
                key
            )
        ) {

            return {
                success: false,
                message:
                    "Request already running."
            };
        }


        notificationActionRunning.add(
            key
        );


        try {

            const formData =
                new FormData();


            formData.append(
                "notification_action",
                action
            );


            if (
                Number(
                    notificationId
                ) > 0
            ) {

                formData.append(
                    "notification_id",
                    String(
                        notificationId
                    )
                );
            }


            const separator =
                notificationActionUrl.includes(
                    "?"
                )
                    ? "&"
                    : "?";


            const url =
                notificationActionUrl +
                separator +
                "_=" +
                Date.now();


            const response =
                await fetch(
                    url,
                    {
                        method: "POST",

                        body: formData,

                        credentials:
                            "same-origin",

                        cache:
                            "no-store",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest",

                            "Cache-Control":
                                "no-cache",

                            "Pragma":
                                "no-cache"
                        }
                    }
                );


            const text =
                await response.text();


            if (!response.ok) {

                console.error(
                    "Notification API HTTP error:",
                    response.status,
                    text
                );


                throw new Error(
                    "HTTP " +
                    response.status
                );
            }


            let data;


            try {

                data =
                    JSON.parse(
                        text
                    );

            } catch (error) {

                console.error(
                    "Notification API returned non-JSON:",
                    text
                );


                throw new Error(
                    "Invalid JSON response."
                );
            }


            if (
                !data ||
                data.success !== true
            ) {

                console.error(
                    "Notification API error:",
                    data
                );
            }


            return data;


        } catch (error) {

            console.error(
                "Notification request failed:",
                error
            );


            return {
                success: false,
                message:
                    error.message
            };


        } finally {

            notificationActionRunning.delete(
                key
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | APPLY SERVER NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    function applyServerNotifications(
        serverNotifications
    ) {

        if (!notificationList) {
            return;
        }


        const serverIds =
            new Set(
                serverNotifications.map(
                    function (
                        notification
                    ) {

                        return String(
                            notification.id
                        );
                    }
                )
            );


        /*
        |----------------------------------------------------------------------
        | REMOVE ARCHIVED / MISSING ITEMS
        |----------------------------------------------------------------------
        */

        notificationList
            .querySelectorAll(
                ".notification-item"
            )
            .forEach(
                function (item) {

                    const id =
                        String(
                            item.dataset
                                .notificationId
                        );


                    if (
                        !serverIds.has(id)
                    ) {

                        item.remove();
                    }
                }
            );


        /*
        |----------------------------------------------------------------------
        | ADD / UPDATE
        |----------------------------------------------------------------------
        */

        serverNotifications.forEach(
            function (notification) {

                const id =
                    String(
                        notification.id
                    );


                let item =
                    notificationList.querySelector(
                        `.notification-item[data-notification-id="${CSS.escape(id)}"]`
                    );


                if (item) {

                    updateNotificationElement(
                        item,
                        notification
                    );

                } else {

                    item =
                        createNotificationElement(
                            notification
                        );


                    const first =
                        notificationList.querySelector(
                            ".notification-item"
                        );


                    if (first) {

                        notificationList.insertBefore(
                            item,
                            first
                        );

                    } else {

                        notificationList.appendChild(
                            item
                        );
                    }
                }
            }
        );


        /*
        |----------------------------------------------------------------------
        | SORT
        |----------------------------------------------------------------------
        */

        const items =
            Array.from(
                notificationList.querySelectorAll(
                    ".notification-item"
                )
            );


        items.sort(
            function (a, b) {

                return (
                    parseNotificationDate(
                        b.dataset.createdAt
                    ) -
                    parseNotificationDate(
                        a.dataset.createdAt
                    )
                );
            }
        );


        items.forEach(
            function (item) {

                notificationList.appendChild(
                    item
                );
            }
        );


        updateNotificationCount();

        updateNotificationEmptyState();

        updateNotificationTimes();
    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    async function refreshNotifications() {

        if (
            !notificationList ||
            !notificationActionUrl
        ) {

            return;
        }


        if (
            notificationRefreshRunning
        ) {

            return;
        }


        notificationRefreshRunning =
            true;


        try {

            const data =
                await sendNotificationAction(
                    "fetch_notifications"
                );


            if (
                !data ||
                data.success !== true
            ) {

                console.error(
                    "Unable to refresh notifications:",
                    data
                );


                return;
            }


            if (
                !Array.isArray(
                    data.notifications
                )
            ) {

                console.error(
                    "Notification response does not contain notifications array.",
                    data
                );


                return;
            }


            applyServerNotifications(
                data.notifications
            );


        } catch (error) {

            console.error(
                "Notification refresh error:",
                error
            );

        } finally {

            notificationRefreshRunning =
                false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ONE READ
    |--------------------------------------------------------------------------
    */

    async function markNotificationAsRead(
        notificationId
    ) {

        if (
            !notificationId
        ) {

            return;
        }


        const item =
            notificationList
                ? notificationList.querySelector(
                    `.notification-item[data-notification-id="${CSS.escape(String(notificationId))}"]`
                )
                : null;


        if (
            item &&
            item.dataset.read ===
            "1"
        ) {

            return;
        }


        const data =
            await sendNotificationAction(
                "mark_read",
                notificationId
            );


        if (
            !data ||
            data.success !== true
        ) {

            console.error(
                "Mark read failed:",
                data
            );


            return;
        }


        /*
        |----------------------------------------------------------------------
        | Use the server response.
        | This makes the database the source of truth.
        |----------------------------------------------------------------------
        */

        if (
            Array.isArray(
                data.notifications
            )
        ) {

            applyServerNotifications(
                data.notifications
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ALL READ
    |--------------------------------------------------------------------------
    */

    async function markAllNotificationsAsRead() {

        const data =
            await sendNotificationAction(
                "mark_all_read"
            );


        if (
            !data ||
            data.success !== true
        ) {

            console.error(
                "Mark all read failed:",
                data
            );


            return;
        }


        if (
            Array.isArray(
                data.notifications
            )
        ) {

            applyServerNotifications(
                data.notifications
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE
    |--------------------------------------------------------------------------
    */

    async function archiveNotification(
        notificationId
    ) {

        if (
            !notificationId
        ) {

            return;
        }


        const item =
            notificationList
                ? notificationList.querySelector(
                    `.notification-item[data-notification-id="${CSS.escape(String(notificationId))}"]`
                )
                : null;


        if (item) {

            item.style.pointerEvents =
                "none";

            item.style.opacity =
                "0.5";
        }


        const data =
            await sendNotificationAction(
                "archive",
                notificationId
            );


        if (
            !data ||
            data.success !== true
        ) {

            if (item) {

                item.style.pointerEvents =
                    "";

                item.style.opacity =
                    "";
            }


            console.error(
                "Archive notification failed:",
                data
            );


            return;
        }


        /*
        |----------------------------------------------------------------------
        | Immediately remove it from the screen.
        |----------------------------------------------------------------------
        */

        if (item) {

            item.remove();
        }


        updateNotificationCount();

        updateNotificationEmptyState();


        /*
        |----------------------------------------------------------------------
        | Synchronize with database.
        |----------------------------------------------------------------------
        */

        if (
            Array.isArray(
                data.notifications
            )
        ) {

            applyServerNotifications(
                data.notifications
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION CLICK DELEGATION
    |--------------------------------------------------------------------------
    */

    if (notificationList) {

        notificationList.addEventListener(
            "click",
            function (event) {

                /*
                |------------------------------------------------------------------
                | DELETE / ARCHIVE
                |------------------------------------------------------------------
                */

                const deleteButton =
                    event.target.closest(
                        ".notification-delete"
                    );


                if (deleteButton) {

                    event.preventDefault();

                    event.stopPropagation();


                    const id =
                        deleteButton.dataset
                            .notificationId;


                    archiveNotification(
                        id
                    );


                    return;
                }


                /*
                |------------------------------------------------------------------
                | NOTIFICATION ITEM
                |------------------------------------------------------------------
                */

                const item =
                    event.target.closest(
                        ".notification-item"
                    );


                if (!item) {
                    return;
                }


                /*
                |------------------------------------------------------------------
                | IGNORE BUTTONS
                |------------------------------------------------------------------
                */

                if (
                    event.target.closest(
                        "button"
                    )
                ) {

                    return;
                }


                const id =
                    item.dataset
                        .notificationId;


                if (
                    item.dataset.read !==
                    "1"
                ) {

                    markNotificationAsRead(
                        id
                    );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ALL READ
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    "#markAllRead"
                );


            if (!button) {
                return;
            }


            event.preventDefault();

            event.stopPropagation();


            markAllNotificationsAsRead();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | OUTSIDE CLICK
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const clickedInsideUser =
                userButton &&
                userButton.contains(
                    event.target
                );


            const clickedInsideUserDropdown =
                userDropdown &&
                userDropdown.contains(
                    event.target
                );


            const clickedInsideNotification =
                notificationButton &&
                notificationButton.contains(
                    event.target
                );


            const clickedInsideNotificationDropdown =
                notificationDropdown &&
                notificationDropdown.contains(
                    event.target
                );


            /*
            |------------------------------------------------------------------
            | CLOSE USER DROPDOWN
            |------------------------------------------------------------------
            */

            if (
                !clickedInsideUser &&
                !clickedInsideUserDropdown
            ) {

                closeUserDropdown();
            }


            /*
            |------------------------------------------------------------------
            | CLOSE NOTIFICATION DROPDOWN
            |------------------------------------------------------------------
            */

            if (
                !clickedInsideNotification &&
                !clickedInsideNotificationDropdown
            ) {

                closeNotificationDropdown();
            }


            /*
            |------------------------------------------------------------------
            | CLOSE MOBILE MENU
            |------------------------------------------------------------------
            */

            const clickedInsideMenu =
                mainNav &&
                mainNav.contains(
                    event.target
                );


            const clickedMenuButton =
                menuToggle &&
                menuToggle.contains(
                    event.target
                );


            if (
                !clickedInsideMenu &&
                !clickedMenuButton
            ) {

                closeMobileMenu();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key ===
                "Escape"
            ) {

                closeAllDropdowns();

                closeMobileMenu();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | UPDATE TIME EVERY 30 SECONDS
    |--------------------------------------------------------------------------
    */

    setInterval(
        function () {

            updateNotificationTimes();

        },
        30000
    );


    /*
    |--------------------------------------------------------------------------
    | REAL-TIME POLLING
    |--------------------------------------------------------------------------
    |
    | Checks the database every 3 seconds.
    |--------------------------------------------------------------------------
    */

    setInterval(
        function () {

            if (
                document.visibilityState ===
                "visible"
            ) {

                refreshNotifications();
            }

        },
        3000
    );


    /*
    |--------------------------------------------------------------------------
    | TAB BECOMES ACTIVE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "visibilitychange",
        function () {

            if (
                document.visibilityState ===
                "visible"
            ) {

                refreshNotifications();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL LOAD
    |--------------------------------------------------------------------------
    */

    updateNotificationCount();

    updateNotificationEmptyState();

    updateNotificationTimes();

    refreshNotifications();

});
