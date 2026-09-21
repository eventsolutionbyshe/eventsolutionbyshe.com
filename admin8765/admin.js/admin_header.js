/* =====================================================
   ADMIN HEADER JAVASCRIPT
===================================================== */

"use strict";


/* =====================================================
   DOM ELEMENTS
===================================================== */

const body =
    document.body;

const sidebar =
    document.getElementById("adminSidebar");

const sidebarToggleBtn =
    document.getElementById("sidebarToggleBtn");

const sidebarToggleIcon =
    document.getElementById("sidebarToggleIcon");

const mobileMenuBtn =
    document.getElementById("mobileMenuBtn");

const sidebarOverlay =
    document.getElementById("sidebarOverlay");

const profileWrapper =
    document.getElementById("profileWrapper");

const profileBtn =
    document.getElementById("profileBtn");

const profileArrow =
    document.getElementById("profileArrow");

const profileDropdown =
    document.getElementById("profileDropdown");

const headerThemeButton =
    document.getElementById("headerThemeButton");

const headerThemeIcon =
    document.getElementById("headerThemeIcon");

const themeToggle =
    document.getElementById("themeToggle");

const themeIcon =
    document.getElementById("themeIcon");

const themeText =
    document.getElementById("themeText");


/* =====================================================
   SIDEBAR STORAGE KEY
===================================================== */

const SIDEBAR_STORAGE_KEY =
    "adminSidebarCollapsed";


/* =====================================================
   THEME STORAGE KEY
===================================================== */

const THEME_STORAGE_KEY =
    "adminTheme";


/* =====================================================
   CLOSE PROFILE DROPDOWN
===================================================== */

function closeProfileDropdown() {

    if (!profileWrapper) {
        return;
    }

    profileWrapper.classList.remove("open");

    if (profileBtn) {
        profileBtn.setAttribute(
            "aria-expanded",
            "false"
        );
    }
}


/* =====================================================
   OPEN PROFILE DROPDOWN
===================================================== */

function toggleProfileDropdown() {

    if (!profileWrapper) {
        return;
    }

    const isOpen =
        profileWrapper.classList.contains(
            "open"
        );

    if (isOpen) {

        closeProfileDropdown();

    } else {

        profileWrapper.classList.add(
            "open"
        );

        if (profileBtn) {
            profileBtn.setAttribute(
                "aria-expanded",
                "true"
            );
        }
    }
}


/* =====================================================
   PROFILE BUTTON
===================================================== */

if (profileBtn) {

    profileBtn.addEventListener(
        "click",
        function (event) {

            event.stopPropagation();

            toggleProfileDropdown();

        }
    );
}


/* =====================================================
   PREVENT DROPDOWN CLICK FROM CLOSING
===================================================== */

if (profileDropdown) {

    profileDropdown.addEventListener(
        "click",
        function (event) {

            event.stopPropagation();

        }
    );
}


/* =====================================================
   CLOSE DROPDOWN WHEN CLICKING OUTSIDE
===================================================== */

document.addEventListener(
    "click",
    function (event) {

        if (!profileWrapper) {
            return;
        }

        if (
            !profileWrapper.contains(
                event.target
            )
        ) {

            closeProfileDropdown();

        }

    }
);


/* =====================================================
   CLOSE DROPDOWN WITH ESCAPE
===================================================== */

document.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Escape") {

            closeProfileDropdown();

            closeMobileSidebar();

        }

    }
);


/* =====================================================
   SIDEBAR COLLAPSE
===================================================== */

function updateSidebarIcon() {

    if (
        !sidebarToggleIcon ||
        !sidebarToggleBtn
    ) {
        return;
    }

    const collapsed =
        body.classList.contains(
            "sidebar-collapsed"
        );

    if (collapsed) {

        sidebarToggleIcon.classList.remove(
            "fa-angles-left"
        );

        sidebarToggleIcon.classList.add(
            "fa-angles-right"
        );

        sidebarToggleBtn.setAttribute(
            "aria-label",
            "Expand sidebar"
        );

        sidebarToggleBtn.setAttribute(
            "title",
            "Expand sidebar"
        );

    } else {

        sidebarToggleIcon.classList.remove(
            "fa-angles-right"
        );

        sidebarToggleIcon.classList.add(
            "fa-angles-left"
        );

        sidebarToggleBtn.setAttribute(
            "aria-label",
            "Collapse sidebar"
        );

        sidebarToggleBtn.setAttribute(
            "title",
            "Collapse sidebar"
        );
    }
}


/* =====================================================
   SAVE SIDEBAR STATE
===================================================== */

function saveSidebarState() {

    const collapsed =
        body.classList.contains(
            "sidebar-collapsed"
        );

    try {

        localStorage.setItem(
            SIDEBAR_STORAGE_KEY,
            collapsed
                ? "true"
                : "false"
        );

    } catch (error) {

        console.warn(
            "Unable to save sidebar state.",
            error
        );
    }
}


/* =====================================================
   LOAD SIDEBAR STATE
===================================================== */

function loadSidebarState() {

    try {

        const saved =
            localStorage.getItem(
                SIDEBAR_STORAGE_KEY
            );

        if (
            saved === "true" &&
            window.innerWidth > 900
        ) {

            body.classList.add(
                "sidebar-collapsed"
            );
        }

    } catch (error) {

        console.warn(
            "Unable to load sidebar state.",
            error
        );
    }

    updateSidebarIcon();
}


/* =====================================================
   DESKTOP SIDEBAR TOGGLE
===================================================== */

if (sidebarToggleBtn) {

    sidebarToggleBtn.addEventListener(
        "click",
        function () {

            if (
                window.innerWidth <= 900
            ) {
                return;
            }

            body.classList.toggle(
                "sidebar-collapsed"
            );

            saveSidebarState();

            updateSidebarIcon();

        }
    );
}


/* =====================================================
   OPEN MOBILE SIDEBAR
===================================================== */

function openMobileSidebar() {

    if (!sidebar) {
        return;
    }

    sidebar.classList.add(
        "mobile-open"
    );

    if (sidebarOverlay) {

        sidebarOverlay.classList.add(
            "active"
        );
    }
}


/* =====================================================
   CLOSE MOBILE SIDEBAR
===================================================== */

function closeMobileSidebar() {

    if (!sidebar) {
        return;
    }

    sidebar.classList.remove(
        "mobile-open"
    );

    if (sidebarOverlay) {

        sidebarOverlay.classList.remove(
            "active"
        );
    }
}


/* =====================================================
   MOBILE MENU
===================================================== */

if (mobileMenuBtn) {

    mobileMenuBtn.addEventListener(
        "click",
        function () {

            const isOpen =
                sidebar &&
                sidebar.classList.contains(
                    "mobile-open"
                );

            if (isOpen) {

                closeMobileSidebar();

            } else {

                openMobileSidebar();

            }

        }
    );
}


/* =====================================================
   SIDEBAR OVERLAY
===================================================== */

if (sidebarOverlay) {

    sidebarOverlay.addEventListener(
        "click",
        function () {

            closeMobileSidebar();

        }
    );
}


/* =====================================================
   CLOSE MOBILE SIDEBAR AFTER NAVIGATION
===================================================== */

if (sidebar) {

    const navLinks =
        sidebar.querySelectorAll(
            "a"
        );

    navLinks.forEach(
        function (link) {

            link.addEventListener(
                "click",
                function () {

                    if (
                        window.innerWidth <= 900
                    ) {

                        closeMobileSidebar();

                    }

                }
            );

        }
    );
}


/* =====================================================
   THEME ICON
===================================================== */

function updateThemeUI() {

    const dark =
        body.classList.contains(
            "dark-theme"
        );


    /* =================================================
       HEADER THEME BUTTON
    ================================================== */

    if (headerThemeIcon) {

        headerThemeIcon.classList.toggle(
            "fa-moon",
            !dark
        );

        headerThemeIcon.classList.toggle(
            "fa-sun",
            dark
        );
    }


    /* =================================================
       DROPDOWN THEME BUTTON
    ================================================== */

    if (themeIcon) {

        themeIcon.classList.toggle(
            "fa-moon",
            !dark
        );

        themeIcon.classList.toggle(
            "fa-sun",
            dark
        );
    }


    if (themeText) {

        themeText.textContent =
            dark
                ? "Light Mode"
                : "Dark Mode";
    }
}


/* =====================================================
   SAVE THEME
===================================================== */

function saveTheme() {

    const dark =
        body.classList.contains(
            "dark-theme"
        );

    try {

        localStorage.setItem(
            THEME_STORAGE_KEY,
            dark
                ? "dark"
                : "light"
        );

    } catch (error) {

        console.warn(
            "Unable to save theme.",
            error
        );
    }
}


/* =====================================================
   TOGGLE THEME
===================================================== */

function toggleTheme() {

    body.classList.toggle(
        "dark-theme"
    );

    saveTheme();

    updateThemeUI();

}


/* =====================================================
   HEADER THEME BUTTON
===================================================== */

if (headerThemeButton) {

    headerThemeButton.addEventListener(
        "click",
        function () {

            toggleTheme();

        }
    );
}


/* =====================================================
   DROPDOWN THEME BUTTON
===================================================== */

if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        function () {

            toggleTheme();

        }
    );
}


/* =====================================================
   LOAD THEME
===================================================== */

function loadTheme() {

    try {

        const savedTheme =
            localStorage.getItem(
                THEME_STORAGE_KEY
            );

        if (
            savedTheme === "dark"
        ) {

            body.classList.add(
                "dark-theme"
            );

        }

    } catch (error) {

        console.warn(
            "Unable to load theme.",
            error
        );
    }

    updateThemeUI();
}


/* =====================================================
   PROFILE IMAGE ERROR HANDLER
===================================================== */

function setupProfileImages() {

    /*
     * These selectors match the exact
     * classes used in admin_header.php.
     */

    const profileImages =
        document.querySelectorAll(
            ".profile-avatar img, .dropdown-avatar img"
        );


    profileImages.forEach(
        function (image) {

            /* =================================================
               IMAGE ERROR
            ================================================== */

            image.addEventListener(
                "error",
                function () {

                    /*
                     * Hide broken image.
                     */

                    image.style.display =
                        "none";


                    /*
                     * Find parent container.
                     */

                    const parent =
                        image.parentElement;

                    if (!parent) {
                        return;
                    }


                    /*
                     * Find the correct fallback.
                     */

                    const fallback =
                        parent.querySelector(
                            ".profile-avatar-fallback, .dropdown-avatar-fallback"
                        );


                    /*
                     * Show fallback letter.
                     */

                    if (fallback) {

                        fallback.style.display =
                            "flex";
                    }

                }
            );


            /* =================================================
               IMAGE SUCCESS
            ================================================== */

            image.addEventListener(
                "load",
                function () {

                    /*
                     * Make image visible.
                     */

                    image.style.display =
                        "block";


                    /*
                     * Find parent container.
                     */

                    const parent =
                        image.parentElement;

                    if (!parent) {
                        return;
                    }


                    /*
                     * Hide fallback.
                     */

                    const fallback =
                        parent.querySelector(
                            ".profile-avatar-fallback, .dropdown-avatar-fallback"
                        );


                    if (fallback) {

                        fallback.style.display =
                            "none";
                    }

                }
            );


            /*
             * Check if image has already
             * finished loading before the
             * event listener was attached.
             */

            if (image.complete) {

                if (image.naturalWidth === 0) {

                    image.dispatchEvent(
                        new Event("error")
                    );

                } else {

                    image.dispatchEvent(
                        new Event("load")
                    );

                }
            }

        }
    );
}


/* =====================================================
   WINDOW RESIZE
===================================================== */

window.addEventListener(
    "resize",
    function () {

        if (
            window.innerWidth > 900
        ) {

            closeMobileSidebar();

        }

    }
);


/* =====================================================
   INITIALIZE HEADER
===================================================== */

function initializeAdminHeader() {

    loadSidebarState();

    loadTheme();

    setupProfileImages();

}


/* =====================================================
   DOM READY
===================================================== */

if (
    document.readyState === "loading"
) {

    document.addEventListener(
        "DOMContentLoaded",
        initializeAdminHeader
    );

} else {

    initializeAdminHeader();

}