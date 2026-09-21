/* =====================================================
   ADMIN HOST & HOST PACKAGE MANAGEMENT
   Event Solutions by S.H.E.
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    /* =================================================
       HELPERS
    ================================================= */

    function getElement(id) {
        return document.getElementById(id);
    }


    function openModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.add("active");
        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "hostpackage-modal-open"
        );
    }


    function closeModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove("active");
        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        if (
            !document.querySelector(
                ".hostpackage-modal.active"
            )
        ) {
            document.body.classList.remove(
                "hostpackage-modal-open"
            );
        }
    }


    function closeAllModals() {

        document
            .querySelectorAll(
                ".hostpackage-modal.active"
            )
            .forEach(function (modal) {

                modal.classList.remove(
                    "active"
                );

                modal.setAttribute(
                    "aria-hidden",
                    "true"
                );
            });

        document.body.classList.remove(
            "hostpackage-modal-open"
        );
    }


    /* =================================================
       MODALS
    ================================================= */

    const addHostModal =
        getElement("addHostModal");

    const editHostModal =
        getElement("editHostModal");

    const addPackageModal =
        getElement("addHostPackageModal");

    const editPackageModal =
        getElement("editHostPackageModal");


    /* =================================================
       ADD HOST
    ================================================= */

    function openAddHostModal() {

        const form =
            getElement("addHostForm");

        if (form) {
            form.reset();
        }

        const preview =
            getElement(
                "addHostImagePreview"
            );

        const placeholder =
            getElement(
                "addHostPicturePlaceholder"
            );

        const fileText =
            getElement(
                "addHostFileText"
            );

        if (preview) {

            preview.src = "";
            preview.classList.remove(
                "visible"
            );
        }

        if (placeholder) {
            placeholder.style.display =
                "flex";
        }

        if (fileText) {
            fileText.textContent =
                "Choose Image";
        }

        openModal(addHostModal);
    }


    const btnOpenAddHost =
        getElement("btnOpenAddHost");

    if (btnOpenAddHost) {

        btnOpenAddHost.addEventListener(
            "click",
            openAddHostModal
        );
    }


    const btnOpenAddHostEmpty =
        getElement(
            "btnOpenAddHostEmpty"
        );

    if (btnOpenAddHostEmpty) {

        btnOpenAddHostEmpty.addEventListener(
            "click",
            openAddHostModal
        );
    }


    /* =================================================
       EDIT HOST
    ================================================= */

    document
        .querySelectorAll(
            ".btn-edit-host"
        )
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const id =
                        this.dataset.id || "";

                    const name =
                        this.dataset.name || "";

                    const type =
                        this.dataset.type || "";

                    const email =
                        this.dataset.email || "";

                    const phone =
                        this.dataset.phone || "";

                    const description =
                        this.dataset.description || "";

                    const image =
                        this.dataset.image || "";


                    const idInput =
                        getElement(
                            "editHostId"
                        );

                    const nameInput =
                        getElement(
                            "editHostName"
                        );

                    const typeInput =
                        getElement(
                            "editHostType"
                        );

                    const emailInput =
                        getElement(
                            "editHostEmail"
                        );

                    const phoneInput =
                        getElement(
                            "editHostPhone"
                        );

                    const descriptionInput =
                        getElement(
                            "editHostDescription"
                        );

                    const preview =
                        getElement(
                            "editHostImagePreview"
                        );

                    const placeholder =
                        getElement(
                            "editHostPicturePlaceholder"
                        );

                    const fileText =
                        getElement(
                            "editHostFileText"
                        );


                    if (idInput) {
                        idInput.value = id;
                    }

                    if (nameInput) {
                        nameInput.value = name;
                    }

                    if (typeInput) {
                        typeInput.value = type;
                    }

                    if (emailInput) {
                        emailInput.value = email;
                    }

                    if (phoneInput) {
                        phoneInput.value = phone;
                    }

                    if (descriptionInput) {
                        descriptionInput.value =
                            description;
                    }


                    if (
                        preview &&
                        placeholder
                    ) {

                        if (image !== "") {

                            preview.src =
                                image;

                            preview.classList.add(
                                "visible"
                            );

                            placeholder.style.display =
                                "none";

                        } else {

                            preview.src = "";

                            preview.classList.remove(
                                "visible"
                            );

                            placeholder.style.display =
                                "flex";
                        }
                    }


                    if (fileText) {

                        fileText.textContent =
                            image !== ""
                                ? "Change Image"
                                : "Choose Image";
                    }


                    openModal(
                        editHostModal
                    );
                }
            );
        });


    /* =================================================
       ADD PACKAGE
    ================================================= */

    function openAddPackageModal() {

        const form =
            getElement(
                "addHostPackageForm"
            );

        if (form) {
            form.reset();
        }

        openModal(
            addPackageModal
        );
    }


    const btnOpenAddPackage =
        getElement(
            "btnOpenAddHostPackage"
        );

    if (btnOpenAddPackage) {

        btnOpenAddPackage.addEventListener(
            "click",
            openAddPackageModal
        );
    }


    const btnOpenAddPackageEmpty =
        getElement(
            "btnOpenAddHostPackageEmpty"
        );

    if (btnOpenAddPackageEmpty) {

        btnOpenAddPackageEmpty.addEventListener(
            "click",
            openAddPackageModal
        );
    }


    /* =================================================
       EDIT PACKAGE
    ================================================= */

    document
        .querySelectorAll(
            ".btn-edit-host-package"
        )
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const id =
                        this.dataset.id || "";

                    const hostId =
                        this.dataset.hostId || "";

                    const packageName =
                        this.dataset.packageName || "";

                    const inclusions =
                        this.dataset.inclusions || "";

                    const price =
                        this.dataset.price || "";


                    const idInput =
                        getElement(
                            "editPackageId"
                        );

                    const hostInput =
                        getElement(
                            "editPackageHost"
                        );

                    const nameInput =
                        getElement(
                            "editPackageName"
                        );

                    const inclusionsInput =
                        getElement(
                            "editPackageInclusions"
                        );

                    const priceInput =
                        getElement(
                            "editPackagePrice"
                        );


                    if (idInput) {
                        idInput.value = id;
                    }

                    if (hostInput) {
                        hostInput.value =
                            hostId;
                    }

                    if (nameInput) {
                        nameInput.value =
                            packageName;
                    }

                    if (inclusionsInput) {
                        inclusionsInput.value =
                            inclusions;
                    }

                    if (priceInput) {
                        priceInput.value =
                            price;
                    }


                    openModal(
                        editPackageModal
                    );
                }
            );
        });


    /* =================================================
       CLOSE HOST MODALS
    ================================================= */

    document
        .querySelectorAll(
            "[data-close-host-modal]"
        )
        .forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    closeModal(
                        addHostModal
                    );

                    closeModal(
                        editHostModal
                    );
                }
            );
        });


    /* =================================================
       CLOSE PACKAGE MODALS
    ================================================= */

    document
        .querySelectorAll(
            "[data-close-package-modal]"
        )
        .forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    closeModal(
                        addPackageModal
                    );

                    closeModal(
                        editPackageModal
                    );
                }
            );
        });


    /* =================================================
       IMAGE PREVIEW - ADD HOST
    ================================================= */

    const addHostImage =
        getElement(
            "addHostImage"
        );

    if (addHostImage) {

        addHostImage.addEventListener(
            "change",
            function () {

                const file =
                    this.files &&
                    this.files[0];

                const preview =
                    getElement(
                        "addHostImagePreview"
                    );

                const placeholder =
                    getElement(
                        "addHostPicturePlaceholder"
                    );

                const fileText =
                    getElement(
                        "addHostFileText"
                    );


                if (!file) {

                    if (preview) {
                        preview.src = "";
                        preview.classList.remove(
                            "visible"
                        );
                    }

                    if (placeholder) {
                        placeholder.style.display =
                            "flex";
                    }

                    if (fileText) {
                        fileText.textContent =
                            "Choose Image";
                    }

                    return;
                }


                if (fileText) {
                    fileText.textContent =
                        file.name;
                }


                const reader =
                    new FileReader();

                reader.onload =
                    function (event) {

                        if (preview) {

                            preview.src =
                                event.target.result;

                            preview.classList.add(
                                "visible"
                            );
                        }

                        if (placeholder) {

                            placeholder.style.display =
                                "none";
                        }
                    };

                reader.readAsDataURL(file);
            }
        );
    }


    /* =================================================
       IMAGE PREVIEW - EDIT HOST
    ================================================= */

    const editHostImage =
        getElement(
            "editHostImage"
        );

    if (editHostImage) {

        editHostImage.addEventListener(
            "change",
            function () {

                const file =
                    this.files &&
                    this.files[0];

                const preview =
                    getElement(
                        "editHostImagePreview"
                    );

                const placeholder =
                    getElement(
                        "editHostPicturePlaceholder"
                    );

                const fileText =
                    getElement(
                        "editHostFileText"
                    );


                if (!file) {
                    return;
                }


                if (fileText) {

                    fileText.textContent =
                        file.name;
                }


                const reader =
                    new FileReader();

                reader.onload =
                    function (event) {

                        if (preview) {

                            preview.src =
                                event.target.result;

                            preview.classList.add(
                                "visible"
                            );
                        }

                        if (placeholder) {

                            placeholder.style.display =
                                "none";
                        }
                    };

                reader.readAsDataURL(file);
            }
        );
    }


    /* =================================================
       CLOSE WITH ESCAPE
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
       SUCCESS TOAST
    ================================================= */

    const successToast =
        getElement(
            "hostPackagesSuccessToast"
        );

    const toastClose =
        getElement(
            "hostPackagesToastClose"
        );


    if (toastClose) {

        toastClose.addEventListener(
            "click",
            function () {

                if (successToast) {

                    successToast.style.opacity =
                        "0";

                    successToast.style.transform =
                        "translateY(-10px)";

                    successToast.style.transition =
                        "all .2s ease";

                    setTimeout(
                        function () {

                            if (successToast) {
                                successToast.remove();
                            }

                        },
                        220
                    );
                }
            }
        );
    }


    if (successToast) {

        setTimeout(
            function () {

                if (
                    document.body.contains(
                        successToast
                    )
                ) {

                    successToast.style.opacity =
                        "0";

                    successToast.style.transform =
                        "translateY(-10px)";

                    successToast.style.transition =
                        "all .2s ease";

                    setTimeout(
                        function () {

                            if (
                                document.body.contains(
                                    successToast
                                )
                            ) {
                                successToast.remove();
                            }

                        },
                        220
                    );
                }

            },
            4000
        );
    }


    /* =================================================
       PRINT REPORT
    ================================================= */

    const printButton =
        getElement(
            "btnHostPackagesReport"
        );

    if (printButton) {

        printButton.addEventListener(
            "click",
            function () {

                window.print();
            }
        );
    }

});