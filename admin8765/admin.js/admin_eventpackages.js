"use strict";

(function () {

    /* =========================================================
       PREVENT DOUBLE INITIALIZATION
    ========================================================= */

    if (window.adminEventPackagesInitialized) {
        return;
    }

    window.adminEventPackagesInitialized = true;


    /* =========================================================
       HELPERS
    ========================================================= */

    function getElement(id) {
        return document.getElementById(id);
    }


    function closeAllPackageModals() {

        document.querySelectorAll(".package-modal").forEach(function (modal) {

            modal.classList.remove("show");

            modal.setAttribute("aria-hidden", "true");

            modal.style.display = "none";
            modal.style.visibility = "hidden";
            modal.style.opacity = "0";
            modal.style.pointerEvents = "none";

        });

        document.body.classList.remove("package-modal-open");
    }


    function showPackageModal(modal) {

        if (!modal) {
            return;
        }

        /* Close every other modal first */
        closeAllPackageModals();

        /* Reset inline styles */
        modal.style.display = "flex";
        modal.style.visibility = "visible";
        modal.style.opacity = "1";
        modal.style.pointerEvents = "auto";

        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");

        document.body.classList.add("package-modal-open");
    }


    function hidePackageModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove("show");

        modal.setAttribute("aria-hidden", "true");

        modal.style.display = "none";
        modal.style.visibility = "hidden";
        modal.style.opacity = "0";
        modal.style.pointerEvents = "none";

        document.body.classList.remove("package-modal-open");
    }


    /* =========================================================
       RESET ADD FORM
    ========================================================= */

    function resetAddForm() {

        const form = getElement("addPackageForm");

        if (!form) {
            return;
        }

        form.reset();

        const preview = getElement("addPackageImagePreview");
        const placeholder = getElement("addPackagePicturePlaceholder");
        const fileText = getElement("addPackageFileText");

        if (preview) {
            preview.src = "";
            preview.style.display = "none";
        }

        if (placeholder) {
            placeholder.style.display = "flex";
        }

        if (fileText) {
            fileText.textContent = "Choose Image";
        }
    }


    /* =========================================================
       OPEN ADD MODAL
    ========================================================= */

    function openAddPackageModal() {

        const modal = getElement("addPackageModal");

        if (!modal) {
            return;
        }

        resetAddForm();

        showPackageModal(modal);

        const nameInput = getElement("addPackageName");

        if (nameInput) {
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        }
    }


    /* =========================================================
       IMAGE URL
    ========================================================= */

    function getImageUrl(image) {

        if (!image) {
            return "";
        }

        image = String(image).trim();

        if (!image) {
            return "";
        }

        /* Already an absolute URL */
        if (/^https?:\/\//i.test(image)) {
            return image;
        }

        /* Root-relative path */
        if (image.charAt(0) === "/") {
            return image;
        }

        /*
         * Database stores:
         * images/example.jpg
         *
         * Admin page is inside /admin/
         */
        if (image.indexOf("images/") === 0) {
            return "../" + image;
        }

        if (image.indexOf("uploads/") === 0) {
            return "../" + image;
        }

        /* Filename only */
        return "../images/" + image;
    }


    /* =========================================================
       OPEN EDIT MODAL
    ========================================================= */

    function openEditPackageModal(button) {

        const modal = getElement("editPackageModal");

        if (!modal || !button) {
            return;
        }

        const id = button.dataset.id || "";
        const name = button.dataset.name || "";
        const eventType = button.dataset.eventType || "";
        const inclusions = button.dataset.inclusions || "";
        const image = button.dataset.image || "";

        const idInput = getElement("editPackageId");
        const nameInput = getElement("editPackageName");
        const categoryInput = getElement("editPackageCategory");
        const inclusionsInput = getElement("editPackageInclusions");

        if (idInput) {
            idInput.value = id;
        }

        if (nameInput) {
            nameInput.value = name;
        }

        if (categoryInput) {
            categoryInput.value = eventType;
        }

        if (inclusionsInput) {
            inclusionsInput.value = inclusions;
        }

        const preview = getElement("editPackageImagePreview");
        const placeholder = getElement("editPackagePicturePlaceholder");
        const fileText = getElement("editPackageFileText");

        if (image) {

            const imageUrl = getImageUrl(image);

            if (preview) {
                preview.src = imageUrl;
                preview.style.display = "block";

                preview.onerror = function () {

                    preview.style.display = "none";

                    if (placeholder) {
                        placeholder.style.display = "flex";
                    }
                };
            }

            if (placeholder) {
                placeholder.style.display = "none";
            }

            if (fileText) {
                fileText.textContent = "Change Image";
            }

        } else {

            if (preview) {
                preview.src = "";
                preview.style.display = "none";
            }

            if (placeholder) {
                placeholder.style.display = "flex";
            }

            if (fileText) {
                fileText.textContent = "Choose Image";
            }
        }

        showPackageModal(modal);

        if (nameInput) {
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        }
    }


    /* =========================================================
       IMAGE PREVIEW
    ========================================================= */

    function setupImagePreview(inputId, previewId, placeholderId, textId) {

        const input = getElement(inputId);

        if (!input) {
            return;
        }

        input.addEventListener("change", function () {

            const file = this.files && this.files[0];

            const preview = getElement(previewId);
            const placeholder = getElement(placeholderId);
            const fileText = getElement(textId);

            if (!file) {
                return;
            }

            if (fileText) {
                fileText.textContent = file.name;
            }

            if (!preview) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {

                preview.src = event.target.result;
                preview.style.display = "block";

                if (placeholder) {
                    placeholder.style.display = "none";
                }
            };

            reader.readAsDataURL(file);
        });
    }


    /* =========================================================
       DOM READY
    ========================================================= */

    function initialize() {

        const addModal = getElement("addPackageModal");
        const editModal = getElement("editPackageModal");

        /* Always start with all modals closed */
        closeAllPackageModals();


        /* =====================================================
           ADD BUTTONS
        ===================================================== */

        const addButton = getElement("btnOpenAddPackage");

        if (addButton) {

            addButton.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                openAddPackageModal();

            });
        }


        const addEmptyButton = getElement("btnOpenAddPackageEmpty");

        if (addEmptyButton) {

            addEmptyButton.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                openAddPackageModal();

            });
        }


        /* =====================================================
           EDIT BUTTONS
        ===================================================== */

        document.addEventListener("click", function (event) {

            const editButton = event.target.closest(".btn-edit-package");

            if (!editButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            openEditPackageModal(editButton);

        });


        /* =====================================================
           CLOSE BUTTONS
        ===================================================== */

        document.addEventListener("click", function (event) {

            const closeButton = event.target.closest(
                "[data-close-package-modal], .package-modal-close"
            );

            if (!closeButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const modal = closeButton.closest(".package-modal");

            if (modal) {
                hidePackageModal(modal);
            } else {
                closeAllPackageModals();
            }

        });


        /* =====================================================
           OVERLAY CLOSE
        ===================================================== */

        document.addEventListener("click", function (event) {

            const overlay = event.target.closest(".package-modal-overlay");

            if (!overlay) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const modal = overlay.closest(".package-modal");

            if (modal) {
                hidePackageModal(modal);
            }

        });


        /* =====================================================
           STOP CONTENT CLICK FROM CLOSING MODAL
        ===================================================== */

        document.addEventListener("click", function (event) {

            const content = event.target.closest(".package-modal-content");

            if (!content) {
                return;
            }

            event.stopPropagation();

        });


        /* =====================================================
           ESCAPE KEY
        ===================================================== */

        document.addEventListener("keydown", function (event) {

            if (event.key !== "Escape") {
                return;
            }

            const openModal = document.querySelector(
                ".package-modal.show"
            );

            if (openModal) {
                hidePackageModal(openModal);
            }

        });


        /* =====================================================
           IMAGE PREVIEWS
        ===================================================== */

        setupImagePreview(
            "addPackageImage",
            "addPackageImagePreview",
            "addPackagePicturePlaceholder",
            "addPackageFileText"
        );

        setupImagePreview(
            "editPackageImage",
            "editPackageImagePreview",
            "editPackagePicturePlaceholder",
            "editPackageFileText"
        );


        /* =====================================================
           FORM SUBMIT PROTECTION
        ===================================================== */

        document.querySelectorAll(
            "#addPackageForm, #editPackageForm"
        ).forEach(function (form) {

            form.addEventListener("submit", function () {

                const submitButton = form.querySelector(
                    'button[type="submit"]'
                );

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.style.pointerEvents = "none";
                    submitButton.style.opacity = "0.7";
                }

            });

        });


        /* =====================================================
           INITIAL MODAL STATE
        ===================================================== */

        if (addModal) {
            addModal.classList.remove("show");
            addModal.setAttribute("aria-hidden", "true");
        }

        if (editModal) {
            editModal.classList.remove("show");
            editModal.setAttribute("aria-hidden", "true");
        }
    }


    /* =========================================================
       START
    ========================================================= */

    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            initialize,
            { once: true }
        );

    } else {

        initialize();

    }

})();