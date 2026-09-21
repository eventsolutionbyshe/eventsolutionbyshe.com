/* =====================================================
   ADMIN HOST MANAGEMENT
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /* =================================================
           ELEMENTS
        ================================================= */

        const addModal =
            document.getElementById(
                "addHostModal"
            );

        const editModal =
            document.getElementById(
                "editHostModal"
            );


        const addForm =
            document.getElementById(
                "addHostForm"
            );

        const editForm =
            document.getElementById(
                "editHostForm"
            );


        const openAddButton =
            document.getElementById(
                "btnOpenAddHost"
            );

        const openAddEmptyButton =
            document.getElementById(
                "btnOpenAddHostEmpty"
            );


        const closeButtons =
            document.querySelectorAll(
                "[data-close-host-modal]"
            );


        const editButtons =
            document.querySelectorAll(
                ".btn-edit-host"
            );


        const printButton =
            document.getElementById(
                "btnHostReport"
            );


        /* =================================================
           ADD FORM ELEMENTS
        ================================================= */

        const addName =
            document.getElementById(
                "addHostName"
            );

        const addType =
            document.getElementById(
                "addHostType"
            );

        const addEmail =
            document.getElementById(
                "addHostEmail"
            );

        const addPhone =
            document.getElementById(
                "addHostPhone"
            );

        const addDescription =
            document.getElementById(
                "addHostDescription"
            );

        const addImage =
            document.getElementById(
                "addHostImage"
            );

        const addPreview =
            document.getElementById(
                "addHostImagePreview"
            );

        const addPlaceholder =
            document.getElementById(
                "addHostPicturePlaceholder"
            );

        const addFileText =
            document.getElementById(
                "addHostFileText"
            );


        /* =================================================
           EDIT FORM ELEMENTS
        ================================================= */

        const editId =
            document.getElementById(
                "editHostId"
            );

        const editName =
            document.getElementById(
                "editHostName"
            );

        const editType =
            document.getElementById(
                "editHostType"
            );

        const editEmail =
            document.getElementById(
                "editHostEmail"
            );

        const editPhone =
            document.getElementById(
                "editHostPhone"
            );

        const editDescription =
            document.getElementById(
                "editHostDescription"
            );

        const editImage =
            document.getElementById(
                "editHostImage"
            );

        const editPreview =
            document.getElementById(
                "editHostImagePreview"
            );

        const editPlaceholder =
            document.getElementById(
                "editHostPicturePlaceholder"
            );

        const editFileText =
            document.getElementById(
                "editHostFileText"
            );


        /* =================================================
           BODY LOCK
        ================================================= */

        function lockBody() {

            document.body.classList.add(
                "host-modal-open"
            );

            document.documentElement.classList.add(
                "host-modal-active"
            );

        }


        function unlockBody() {

            document.body.classList.remove(
                "host-modal-open"
            );

            document.documentElement.classList.remove(
                "host-modal-active"
            );

        }


        /* =================================================
           OPEN MODAL
        ================================================= */

        function openModal(modal) {

            if (!modal) {
                return;
            }


            modal.classList.add(
                "show"
            );


            modal.setAttribute(
                "aria-hidden",
                "false"
            );


            lockBody();

        }


        /* =================================================
           CLOSE MODAL
        ================================================= */

        function closeModal(modal) {

            if (!modal) {
                return;
            }


            modal.classList.remove(
                "show"
            );


            modal.setAttribute(
                "aria-hidden",
                "true"
            );


            /*
                Only unlock the body if
                both modals are closed.
            */

            const anotherOpenModal =
                document.querySelector(
                    ".host-modal.show"
                );


            if (!anotherOpenModal) {

                unlockBody();

            }

        }


        /* =================================================
           CLOSE ALL MODALS
        ================================================= */

        function closeAllModals() {

            document.querySelectorAll(
                ".host-modal.show"
            ).forEach(
                function (modal) {

                    closeModal(
                        modal
                    );

                }
            );

        }


        /* =================================================
           RESET ADD FORM
        ================================================= */

        function resetAddForm() {

            if (!addForm) {
                return;
            }


            addForm.reset();


            if (addPreview) {

                addPreview.src = "";

                addPreview.classList.remove(
                    "show"
                );

            }


            if (addPlaceholder) {

                addPlaceholder.style.display =
                    "flex";

            }


            if (addFileText) {

                addFileText.textContent =
                    "Choose Image";

            }

        }


        /* =================================================
           OPEN ADD HOST
        ================================================= */

        function openAddHost() {

            resetAddForm();

            openModal(
                addModal
            );


            if (addName) {

                setTimeout(
                    function () {

                        addName.focus();

                    },
                    100
                );

            }

        }


        if (openAddButton) {

            openAddButton.addEventListener(
                "click",
                openAddHost
            );

        }


        if (openAddEmptyButton) {

            openAddEmptyButton.addEventListener(
                "click",
                openAddHost
            );

        }


        /* =================================================
           OPEN EDIT HOST
        ================================================= */

        editButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const id =
                            button.dataset.id || "";

                        const name =
                            button.dataset.name || "";

                        const type =
                            button.dataset.type || "";

                        const email =
                            button.dataset.email || "";

                        const phone =
                            button.dataset.phone || "";

                        const description =
                            button.dataset.description || "";

                        const image =
                            button.dataset.image || "";


                        /*
                            Fill fields.
                        */

                        if (editId) {

                            editId.value =
                                id;

                        }


                        if (editName) {

                            editName.value =
                                name;

                        }


                        if (editType) {

                            editType.value =
                                type;

                        }


                        if (editEmail) {

                            editEmail.value =
                                email;

                        }


                        if (editPhone) {

                            editPhone.value =
                                phone;

                        }


                        if (editDescription) {

                            editDescription.value =
                                description;

                        }


                        /*
                            Existing image.
                        */

                        if (
                            image !== "" &&
                            editPreview
                        ) {

                            editPreview.src =
                                image;

                            editPreview.classList.add(
                                "show"
                            );


                            if (
                                editPlaceholder
                            ) {

                                editPlaceholder.style.display =
                                    "none";

                            }

                        } else {

                            if (editPreview) {

                                editPreview.src = "";

                                editPreview.classList.remove(
                                    "show"
                                );

                            }


                            if (
                                editPlaceholder
                            ) {

                                editPlaceholder.style.display =
                                    "flex";

                            }

                        }


                        if (editFileText) {

                            editFileText.textContent =
                                "Change Image";

                        }


                        openModal(
                            editModal
                        );

                    }
                );

            }
        );


        /* =================================================
           CLOSE BUTTONS
        ================================================= */

        closeButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const modal =
                            button.closest(
                                ".host-modal"
                            );


                        closeModal(
                            modal
                        );

                    }
                );

            }
        );


        /* =================================================
           ESCAPE KEY
        ================================================= */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    closeAllModals();

                }

            }
        );


        /* =================================================
           ADD IMAGE PREVIEW
        ================================================= */

        if (addImage) {

            addImage.addEventListener(
                "change",
                function () {

                    const file =
                        addImage.files &&
                        addImage.files[0];


                    if (!file) {

                        if (addPreview) {

                            addPreview.src = "";

                            addPreview.classList.remove(
                                "show"
                            );

                        }


                        if (addPlaceholder) {

                            addPlaceholder.style.display =
                                "flex";

                        }


                        if (addFileText) {

                            addFileText.textContent =
                                "Choose Image";

                        }

                        return;

                    }


                    if (addFileText) {

                        addFileText.textContent =
                            file.name;

                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            if (addPreview) {

                                addPreview.src =
                                    event.target.result;

                                addPreview.classList.add(
                                    "show"
                                );

                            }


                            if (addPlaceholder) {

                                addPlaceholder.style.display =
                                    "none";

                            }

                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }


        /* =================================================
           EDIT IMAGE PREVIEW
        ================================================= */

        if (editImage) {

            editImage.addEventListener(
                "change",
                function () {

                    const file =
                        editImage.files &&
                        editImage.files[0];


                    if (!file) {

                        if (editFileText) {

                            editFileText.textContent =
                                "Change Image";

                        }

                        return;

                    }


                    if (editFileText) {

                        editFileText.textContent =
                            file.name;

                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            if (editPreview) {

                                editPreview.src =
                                    event.target.result;

                                editPreview.classList.add(
                                    "show"
                                );

                            }


                            if (editPlaceholder) {

                                editPlaceholder.style.display =
                                    "none";

                            }

                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }


        /* =================================================
           PREVENT DOUBLE SUBMIT
        ================================================= */

        if (addForm) {

            addForm.addEventListener(
                "submit",
                function () {

                    const submitButton =
                        addForm.querySelector(
                            ".btn-host-save"
                        );


                    if (submitButton) {

                        submitButton.disabled =
                            true;


                        submitButton.innerHTML =
                            `
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <span>Saving...</span>
                            `;

                    }

                }
            );

        }


        if (editForm) {

            editForm.addEventListener(
                "submit",
                function () {

                    const submitButton =
                        editForm.querySelector(
                            ".btn-host-save"
                        );


                    if (submitButton) {

                        submitButton.disabled =
                            true;


                        submitButton.innerHTML =
                            `
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <span>Updating...</span>
                            `;

                    }

                }
            );

        }


        /* =================================================
           PRINT REPORT
        ================================================= */

        if (printButton) {

            printButton.addEventListener(
                "click",
                function () {

                    /*
                        Close any open modal before
                        printing.
                    */

                    closeAllModals();


                    /*
                        Give the browser a moment to
                        apply the print CSS.
                    */

                    setTimeout(
                        function () {

                            window.print();

                        },
                        100
                    );

                }
            );

        }


        /* =================================================
           SUCCESS TOAST
        ================================================= */

        const toast =
            document.getElementById(
                "hostSuccessToast"
            );


        const toastClose =
            document.getElementById(
                "hostToastClose"
            );


        let toastTimer =
            null;


        function closeHostToast() {

            if (!toast) {
                return;
            }


            toast.classList.add(
                "hide"
            );


            if (toastTimer) {

                clearTimeout(
                    toastTimer
                );

            }


            setTimeout(
                function () {

                    if (toast) {

                        toast.remove();

                    }

                },
                230
            );

        }


        if (toastClose) {

            toastClose.addEventListener(
                "click",
                closeHostToast
            );

        }


        if (toast) {

            toastTimer =
                setTimeout(
                    closeHostToast,
                    4000
                );


            /*
                Remove success query from URL
                after the page has displayed it.

                Other search/filter parameters
                remain untouched.
            */

            if (
                window.history &&
                window.history.replaceState
            ) {

                try {

                    const currentUrl =
                        new URL(
                            window.location.href
                        );


                    currentUrl.searchParams.delete(
                        "success"
                    );


                    window.history.replaceState(
                        {},
                        document.title,
                        currentUrl.pathname +
                        (
                            currentUrl.search
                                ? currentUrl.search
                                : ""
                        ) +
                        (
                            currentUrl.hash
                                ? currentUrl.hash
                                : ""
                        )
                    );

                } catch (error) {

                    /*
                        Ignore URL cleanup errors.
                    */

                }

            }

        }

    }
);