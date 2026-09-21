/* =========================================================
   EVENTS PAGE JAVASCRIPT
   EVENT SOLUTIONS BY S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("packageSearch");

    const eventTypeFilter = document.getElementById("eventTypeFilter");

    const packagesGrid = document.getElementById("packagesGrid");

    const packageCount = document.getElementById("packageCount");

    const noSearchResults = document.getElementById("noSearchResults");

    const pagination = document.getElementById("eventsPagination");

    if (!packagesGrid || !searchInput || !eventTypeFilter || !pagination) {
        return;
    }

    /* =====================================================
       SETTINGS
    ===================================================== */

    /*
       2 columns × 2 rows = 4 cards per page
    */

    const ITEMS_PER_PAGE = 4;

    let currentPage = 1;

    /*
       Get all package cards
    */

    const allPackages = Array.from(packagesGrid.querySelectorAll(".package-card"));

    let filteredPackages = [...allPackages];

    /* =====================================================
       UPDATE PACKAGE COUNT
    ===================================================== */

    function updatePackageCount(count) {
        if (!packageCount) {
            return;
        }

        packageCount.textContent = count + (count === 1 ? " Package" : " Packages");
    }

    /* =====================================================
       FILTER PACKAGES
    ===================================================== */

    function filterPackages() {
        const searchValue = searchInput.value.trim().toLowerCase();

        const selectedType = eventTypeFilter.value;

        filteredPackages = allPackages.filter(function (card) {
            const packageName = card.dataset.name || "";

            const eventType = card.dataset.eventType || "";

            const matchesSearch = packageName.includes(searchValue);

            const matchesType = selectedType === "all" || eventType === selectedType;

            return matchesSearch && matchesType;
        });

        /*
        =====================================================
        RESET TO PAGE 1
        =====================================================
        */

        currentPage = 1;

        updatePackageCount(filteredPackages.length);

        renderPagination();

        renderPage();
    }

    /* =====================================================
       RENDER CURRENT PAGE
    ===================================================== */

    function renderPage() {
        /*
           Hide every package first
        */

        allPackages.forEach(function (card) {
            card.style.display = "none";
        });

        /*
           If there are no results
        */

        if (filteredPackages.length === 0) {
            packagesGrid.style.display = "none";

            noSearchResults.style.display = "block";

            pagination.innerHTML = "";

            return;
        }

        /*
           Show package grid
        */

        packagesGrid.style.display = "grid";

        noSearchResults.style.display = "none";

        /*
           Calculate page range
        */

        const start = (currentPage - 1) * ITEMS_PER_PAGE;

        const end = start + ITEMS_PER_PAGE;

        const pagePackages = filteredPackages.slice(start, end);

        /*
           Display current page
        */

        pagePackages.forEach(function (card) {
            card.style.display = "grid";
        });

        /*
           Scroll back to package section
           when page changes.
        */
    }

    /* =====================================================
       CREATE PAGINATION
    ===================================================== */

    function renderPagination() {
        pagination.innerHTML = "";

        const totalPages = Math.ceil(filteredPackages.length / ITEMS_PER_PAGE);

        /*
           No pagination needed
           for one page.
        */

        if (totalPages <= 1) {
            return;
        }

        /* =================================================
           PREVIOUS BUTTON
        ================================================= */

        const previousButton = document.createElement("button");

        previousButton.type = "button";

        previousButton.className = "pagination-button arrow";

        previousButton.innerHTML = "&#8249;";

        previousButton.setAttribute("aria-label", "Previous page");

        previousButton.disabled = currentPage === 1;

        previousButton.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage--;

                renderPage();

                renderPagination();

                scrollToPackages();
            }
        });

        pagination.appendChild(previousButton);

        /* =================================================
           PAGE NUMBERS
        ================================================= */

        const pages = getVisiblePages(currentPage, totalPages);

        pages.forEach(function (page) {
            /*
               Ellipsis
            */

            if (page === "...") {
                const ellipsis = document.createElement("span");

                ellipsis.className = "pagination-ellipsis";

                ellipsis.textContent = "...";

                pagination.appendChild(ellipsis);

                return;
            }

            /*
               Number button
            */

            const pageButton = document.createElement("button");

            pageButton.type = "button";

            pageButton.className = "pagination-button";

            if (page === currentPage) {
                pageButton.classList.add("active");

                pageButton.setAttribute("aria-current", "page");
            }

            pageButton.textContent = page;

            pageButton.addEventListener("click", function () {
                currentPage = page;

                renderPage();

                renderPagination();

                scrollToPackages();
            });

            pagination.appendChild(pageButton);
        });

        /* =================================================
           NEXT BUTTON
        ================================================= */

        const nextButton = document.createElement("button");

        nextButton.type = "button";

        nextButton.className = "pagination-button arrow";

        nextButton.innerHTML = "&#8250;";

        nextButton.setAttribute("aria-label", "Next page");

        nextButton.disabled = currentPage === totalPages;

        nextButton.addEventListener("click", function () {
            if (currentPage < totalPages) {
                currentPage++;

                renderPage();

                renderPagination();

                scrollToPackages();
            }
        });

        pagination.appendChild(nextButton);
    }

    /* =====================================================
       VISIBLE PAGE NUMBERS
    ===================================================== */

    function getVisiblePages(current, total) {
        /*
           For small number of pages,
           show everything.

           Example:
           1 2 3 4 5
        */

        if (total <= 5) {
            return Array.from(
                {
                    length: total
                },
                function (_, index) {
                    return index + 1;
                }
            );
        }

        /*
           Beginning

           1 2 3 ... 10
        */

        if (current <= 3) {
            return [1, 2, 3, "...", total];
        }

        /*
           Ending

           1 ... 8 9 10
        */

        if (current >= total - 2) {
            return [1, "...", total - 2, total - 1, total];
        }

        /*
           Middle

           1 ... 4 5 6 ... 10
        */

        return [1, "...", current - 1, current, current + 1, "...", total];
    }

    /* =====================================================
       SCROLL TO PACKAGES
    ===================================================== */

    function scrollToPackages() {
        const gridTop = packagesGrid.getBoundingClientRect().top + window.scrollY - 130;

        window.scrollTo({
            top: gridTop,

            behavior: "smooth"
        });
    }

    /* =====================================================
       SEARCH
    ===================================================== */

    searchInput.addEventListener("input", filterPackages);

    /* =====================================================
       EVENT TYPE FILTER
    ===================================================== */

    eventTypeFilter.addEventListener("change", filterPackages);

    /* =====================================================
       INITIAL LOAD
    ===================================================== */

    updatePackageCount(filteredPackages.length);

    renderPagination();

    renderPage();
});
