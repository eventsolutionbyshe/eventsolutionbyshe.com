document.addEventListener("DOMContentLoaded", function () {

    const grid =
        document.getElementById("venuesGrid");

    const searchInput =
        document.getElementById("venueSearch");

    const typeFilter =
        document.getElementById("venueType");

    const resultInfo =
        document.getElementById("venueResultInfo");

    const noResults =
        document.getElementById("noVenueResults");

    const pagination =
        document.getElementById("venuesPagination");


    /* =========================================================
       STOP IF VENUE GRID DOES NOT EXIST
    ========================================================== */

    if (!grid) {
        return;
    }


    /* =========================================================
       VENUE CARDS
    ========================================================== */

    const cards =
        Array.from(
            grid.querySelectorAll(".venue-card")
        );


    let currentPage = 1;


    /* =========================================================
       PAGE SIZE

       Desktop:
       2 columns × 2 rows = 4 venues

       Mobile:
       1 column × 2 rows = 2 venues
    ========================================================== */

    function getPageSize() {

        if (window.innerWidth <= 600) {
            return 2;
        }

        return 4;
    }


    /* =========================================================
       FILTER VENUES
    ========================================================== */

    function getFilteredCards() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : "";


        const selectedType =
            typeFilter
                ? typeFilter.value
                    .trim()
                    .toLowerCase()
                : "all";


        return cards.filter(function (card) {

            const name =
                card.dataset.name || "";


            const location =
                card.dataset.location || "";


            const type =
                card.dataset.type || "";


            const matchesSearch =
                name.includes(searchValue) ||
                location.includes(searchValue) ||
                type.includes(searchValue);


            const matchesType =
                selectedType === "all" ||
                type === selectedType;


            return (
                matchesSearch &&
                matchesType
            );

        });

    }


    /* =========================================================
       UPDATE RESULT COUNT
    ========================================================== */

    function updateResultCount(count) {

        if (!resultInfo) {
            return;
        }


        if (count === 1) {

            resultInfo.textContent =
                "1 venue available";

        } else {

            resultInfo.textContent =
                count + " venues available";

        }

    }


    /* =========================================================
       TOTAL PAGES
    ========================================================== */

    function getTotalPages() {

        const filteredCards =
            getFilteredCards();


        return Math.max(
            1,
            Math.ceil(
                filteredCards.length /
                getPageSize()
            )
        );

    }


    /* =========================================================
       CREATE PAGINATION BUTTON
    ========================================================== */

    function createButton(
        text,
        page,
        className = ""
    ) {

        const button =
            document.createElement("button");


        button.type = "button";


        button.className =
            "pagination-button " +
            className;


        button.textContent =
            text;


        if (page === currentPage) {

            button.classList.add(
                "active"
            );

        }


        button.addEventListener(
            "click",
            function () {

                if (
                    page < 1 ||
                    page > getTotalPages()
                ) {

                    return;

                }


                currentPage =
                    page;


                render();


                const gridTop =
                    grid.getBoundingClientRect().top +
                    window.scrollY -
                    110;


                window.scrollTo({

                    top: gridTop,

                    behavior: "smooth"

                });

            }
        );


        return button;

    }


    /* =========================================================
       PAGINATION
    ========================================================== */

    function renderPagination(
        totalPages
    ) {

        if (!pagination) {
            return;
        }


        pagination.innerHTML =
            "";


        if (totalPages <= 1) {
            return;
        }


        /* =====================================================
           PREVIOUS
        ====================================================== */

        const previous =
            createButton(
                "‹",
                currentPage - 1,
                "arrow"
            );


        previous.disabled =
            currentPage === 1;


        pagination.appendChild(
            previous
        );


        /* =====================================================
           PAGE NUMBERS
        ====================================================== */

        const pages = [];


        if (totalPages <= 7) {

            for (
                let i = 1;
                i <= totalPages;
                i++
            ) {

                pages.push(i);

            }

        } else {

            pages.push(1);


            if (currentPage > 3) {

                pages.push("...");

            }


            const start =
                Math.max(
                    2,
                    currentPage - 1
                );


            const end =
                Math.min(
                    totalPages - 1,
                    currentPage + 1
                );


            for (
                let i = start;
                i <= end;
                i++
            ) {

                pages.push(i);

            }


            if (
                currentPage <
                totalPages - 2
            ) {

                pages.push("...");

            }


            pages.push(
                totalPages
            );

        }


        /* =====================================================
           ADD PAGE BUTTONS
        ====================================================== */

        pages.forEach(function (page) {

            if (page === "...") {

                const ellipsis =
                    document.createElement(
                        "span"
                    );


                ellipsis.className =
                    "pagination-ellipsis";


                ellipsis.textContent =
                    "…";


                pagination.appendChild(
                    ellipsis
                );


                return;

            }


            pagination.appendChild(

                createButton(
                    String(page),
                    page
                )

            );

        });


        /* =====================================================
           NEXT
        ====================================================== */

        const next =
            createButton(
                "›",
                currentPage + 1,
                "arrow"
            );


        next.disabled =
            currentPage === totalPages;


        pagination.appendChild(
            next
        );

    }


    /* =========================================================
       MAIN RENDER
    ========================================================== */

    function render() {

        const filteredCards =
            getFilteredCards();


        const pageSize =
            getPageSize();


        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    filteredCards.length /
                    pageSize
                )
            );


        /* =====================================================
           PREVENT INVALID PAGE
        ====================================================== */

        if (
            currentPage >
            totalPages
        ) {

            currentPage =
                totalPages;

        }


        /* =====================================================
           HIDE ALL CARDS
        ====================================================== */

        cards.forEach(function (card) {

            card.classList.add(
                "is-hidden"
            );

        });


        /* =====================================================
           SHOW CURRENT PAGE
        ====================================================== */

        const start =
            (currentPage - 1) *
            pageSize;


        const end =
            start +
            pageSize;


        filteredCards
            .slice(start, end)
            .forEach(function (card) {

                card.classList.remove(
                    "is-hidden"
                );

            });


        /* =====================================================
           UPDATE RESULT COUNT
        ====================================================== */

        updateResultCount(
            filteredCards.length
        );


        /* =====================================================
           NO RESULTS
        ====================================================== */

        if (noResults) {

            if (
                filteredCards.length === 0
            ) {

                noResults.style.display =
                    "block";

            } else {

                noResults.style.display =
                    "none";

            }

        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        renderPagination(
            totalPages
        );

    }


    /* =========================================================
       SEARCH
    ========================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                currentPage = 1;

                render();

            }
        );

    }


    /* =========================================================
       FILTER
    ========================================================== */

    if (typeFilter) {

        typeFilter.addEventListener(
            "change",
            function () {

                currentPage = 1;

                render();

            }
        );

    }


    /* =========================================================
       RESIZE
    ========================================================== */

    let resizeTimer;


    window.addEventListener(
        "resize",
        function () {

            clearTimeout(
                resizeTimer
            );


            resizeTimer =
                setTimeout(
                    function () {

                        currentPage = 1;

                        render();

                    },
                    150
                );

        }
    );


    /* =========================================================
       VIEW VENUE / LOGIN BUTTON
    ========================================================== */

    document
        .querySelectorAll(".venue-button")
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    const isLoggedIn =
                        button.dataset.loggedIn === "true";


                    /*
                     * If the user is NOT logged in,
                     * prevent the venue details page
                     * from opening.
                     */

                    if (!isLoggedIn) {

                        event.preventDefault();


                        /*
                         * Directly send the user
                         * to the login page.
                         */

                        window.location.href =
                            "auth/login.php";


                        return;

                    }


                    /*
                     * If logged in:
                     *
                     * Do nothing.
                     *
                     * The normal href will open:
                     *
                     * view_venue.php?id=VENUE_ID
                     */

                }
            );

        });


    /* =========================================================
       INITIAL LOAD
    ========================================================== */

    render();

});