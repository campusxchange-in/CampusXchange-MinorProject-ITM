document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       PASSWORD VISIBILITY TOGGLE
    ===================================================== */

    const passwordToggles =
        document.querySelectorAll(".password-toggle");

    passwordToggles.forEach(function (button) {

        button.addEventListener("click", function () {

            const targetId =
                button.getAttribute("data-target");

            const input =
                document.getElementById(targetId);

            if (!input) {
                return;
            }

            const icon =
                button.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                if (icon) {
                    icon.className =
                        "fa-solid fa-eye-slash";
                }

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                input.type = "password";

                if (icon) {
                    icon.className =
                        "fa-solid fa-eye";
                }

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        });

    });


    /* =====================================================
       CHANGE PASSWORD FORM VALIDATION
    ===================================================== */

    const passwordForm =
        document.querySelector(
            'form[action="account_password_update"]'
        );

    if (passwordForm) {

        passwordForm.addEventListener(
            "submit",
            function (event) {

                const currentPassword =
                    document.getElementById(
                        "current_password"
                    );

                const newPassword =
                    document.getElementById(
                        "new_password"
                    );

                const confirmPassword =
                    document.getElementById(
                        "confirm_password"
                    );


                if (
                    !currentPassword ||
                    !newPassword ||
                    !confirmPassword
                ) {
                    return;
                }


                /* Remove previous errors */

                document
                    .querySelectorAll(
                        ".password-form-error"
                    )
                    .forEach(function (error) {
                        error.remove();
                    });


                /* Clear previous error states */

                [
                    currentPassword,
                    newPassword,
                    confirmPassword
                ].forEach(function (input) {

                    input.classList.remove(
                        "input-error"
                    );

                });


                let errorMessage = "";


                /* Current password */

                if (
                    currentPassword.value.trim() === ""
                ) {

                    errorMessage =
                        "Please enter your current password.";

                    currentPassword.classList.add(
                        "input-error"
                    );

                }


                /* New password */

                else if (
                    newPassword.value.trim() === ""
                ) {

                    errorMessage =
                        "Please enter a new password.";

                    newPassword.classList.add(
                        "input-error"
                    );

                }


                /* Minimum length */

                else if (
                    newPassword.value.length < 8
                ) {

                    errorMessage =
                        "New password must contain at least 8 characters.";

                    newPassword.classList.add(
                        "input-error"
                    );

                }

                /* Maximum length */

                else if (
                    newPassword.value.length > 20
                ) {

                    errorMessage =
                        "New password cannot exceed 20 characters.";

                    newPassword.classList.add(
                        "input-error"
                    );

                }


                /* Same password */

                else if (
                    currentPassword.value ===
                    newPassword.value
                ) {

                    errorMessage =
                        "New password must be different from your current password.";

                    newPassword.classList.add(
                        "input-error"
                    );

                }


                /* Confirm password */

                else if (
                    confirmPassword.value.trim() === ""
                ) {

                    errorMessage =
                        "Please confirm your new password.";

                    confirmPassword.classList.add(
                        "input-error"
                    );

                }

                else if (
                    confirmPassword.value.length > 20
                ) {

                    errorMessage =
                        "Confirmation password cannot exceed 20 characters.";

                    confirmPassword.classList.add(
                        "input-error"
                    );

                }


                /* Password mismatch */

                else if (
                    newPassword.value !==
                    confirmPassword.value
                ) {

                    errorMessage =
                        "New password and confirmation password do not match.";

                    newPassword.classList.add(
                        "input-error"
                    );

                    confirmPassword.classList.add(
                        "input-error"
                    );

                }


                /* Show error */

                if (errorMessage !== "") {

                    event.preventDefault();

                    const error =
                        document.createElement("div");

                    error.className =
                        "password-form-error";

                    error.innerHTML =
                        '<i class="fa-solid fa-circle-exclamation"></i>' +
                        "<span>" +
                        errorMessage +
                        "</span>";

                    passwordForm.prepend(error);

                }

            }
        );

    }



    /* =====================================================
    ADMIN ALERTS
    ===================================================== */

    const adminAlerts =
        document.querySelectorAll(".admin-alert");


    adminAlerts.forEach(function (alert) {


        /* =============================================
        CLOSE BUTTON
        ============================================== */

        const closeButton =
            alert.querySelector(".alert-close");


        if (closeButton) {

            closeButton.addEventListener(
                "click",
                function () {

                    closeAlert(alert);

                }
            );

        }


        /* =============================================
        AUTO DISMISS AFTER 10 SECONDS
        ============================================== */

        setTimeout(function () {

            closeAlert(alert);

        }, 10000);

    });



    /* =====================================================
    CLOSE ALERT FUNCTION
    ===================================================== */

    function closeAlert(alert) {

        if (!alert) {
            return;
        }


        alert.classList.add("alert-hiding");


        setTimeout(function () {

            alert.remove();

        }, 300);

    }

});