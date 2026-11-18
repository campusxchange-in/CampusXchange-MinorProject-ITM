document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       SIDEBAR - MOBILE TOGGLE
    ====================================================== */

    const sidebar =
        document.querySelector(".admin-sidebar");

    const toggleButton =
        document.getElementById("sidebarToggle");


    if (sidebar && toggleButton) {

        toggleButton.addEventListener("click", function () {

            sidebar.classList.toggle("open");

        });

    }


    /* =====================================================
       SIDEBAR - DESKTOP COLLAPSE
    ====================================================== */

    const collapseButton =
        document.getElementById("sidebarCollapseBtn");


    if (sidebar && collapseButton) {

        collapseButton.addEventListener("click", function () {

            sidebar.classList.toggle("collapsed");

            document.body.classList.toggle(
                "sidebar-collapsed"
            );

        });

    }


    /* =====================================================
       ADMIN PROFILE DROPDOWN
    ====================================================== */

    const profileButton =
        document.getElementById("adminProfileButton");

    const dropdown =
        document.getElementById("adminDropdown");


    if (profileButton && dropdown) {

        profileButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                dropdown.classList.toggle("show");

                profileButton.classList.toggle(
                    "active"
                );

            }
        );


        /* =============================================
           CLOSE DROPDOWN WHEN CLICKING OUTSIDE
        ============================================== */

        document.addEventListener(
            "click",
            function (event) {

                if (
                    !profileButton.contains(event.target) &&
                    !dropdown.contains(event.target)
                ) {

                    dropdown.classList.remove("show");

                    profileButton.classList.remove(
                        "active"
                    );

                }

            }
        );

    }


    /* =====================================================
       DARK / LIGHT THEME
    ====================================================== */

    const themeToggle =
        document.getElementById("themeToggle");


    /*
       Apply saved theme when page loads
    */

    const savedTheme =
        localStorage.getItem("adminTheme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-mode");

    }


    /*
       Update theme button appearance
    */

    function updateThemeButton() {

        if (!themeToggle) {
            return;
        }


        const icon =
            themeToggle.querySelector("i");

        const text =
            themeToggle.querySelector("span");


        if (
            document.body.classList.contains(
                "dark-mode"
            )
        ) {

            if (icon) {

                icon.className =
                    "fa-solid fa-sun";

            }

            if (text) {

                text.textContent =
                    "Light Mode";

            }

        } else {

            if (icon) {

                icon.className =
                    "fa-solid fa-moon";

            }

            if (text) {

                text.textContent =
                    "Dark Mode";

            }

        }

    }


    /*
       Set initial button state
    */

    updateThemeButton();


    /*
       Toggle theme
    */

    if (themeToggle) {

        themeToggle.addEventListener(
            "click",
            function () {

                document.body.classList.toggle(
                    "dark-mode"
                );


                const isDark =
                    document.body.classList.contains(
                        "dark-mode"
                    );


                /*
                   Save preference
                */

                localStorage.setItem(
                    "adminTheme",
                    isDark ? "dark" : "light"
                );


                /*
                   Update button
                */

                updateThemeButton();

            }
        );

    }



        /* =====================================================
       NOTIFICATION DROPDOWN
    ===================================================== */

    const notificationTrigger =
        document.getElementById('notificationTrigger');

    const notificationModal =
        document.getElementById('notificationModal');

    const notificationClose =
        document.getElementById('notificationClose');


    /* =====================================================
       OPEN / CLOSE NOTIFICATIONS
    ===================================================== */

    if (notificationTrigger && notificationModal) {

        notificationTrigger.addEventListener('click', function (event) {

            event.stopPropagation();

            const isOpen =
                notificationModal.classList.contains('show');

            if (isOpen) {

                notificationModal.classList.remove('show');

                notificationTrigger.setAttribute(
                    'aria-expanded',
                    'false'
                );

                notificationModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            } else {

                notificationModal.classList.add('show');

                notificationTrigger.setAttribute(
                    'aria-expanded',
                    'true'
                );

                notificationModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

            }

        });


        /* =================================================
           CLOSE BUTTON
        ================================================= */

        if (notificationClose) {

            notificationClose.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    notificationModal.classList.remove('show');

                    notificationTrigger.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                    notificationModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                }
            );

        }


        /* =================================================
           CLOSE WHEN CLICKING OUTSIDE
        ================================================= */

        document.addEventListener(
            'click',
            function (event) {

                const notificationWrapper =
                    notificationTrigger.closest(
                        '.notification-wrapper'
                    );

                if (
                    notificationWrapper &&
                    !notificationWrapper.contains(event.target)
                ) {

                    notificationModal.classList.remove('show');

                    notificationTrigger.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                    notificationModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                }

            }
        );


        /* =================================================
           ESCAPE KEY
        ================================================= */

        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {

                    notificationModal.classList.remove('show');

                    notificationTrigger.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                    notificationModal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    notificationTrigger.focus();

                }

            }
        );

    }


});