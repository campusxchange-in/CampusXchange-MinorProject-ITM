/* =====================================================
   PASSWORD CHANGE VALIDATION
====================================================== */

const passwordForm =
    document.getElementById("passwordChangeForm");

const currentPassword =
    document.getElementById("current_password");

const newPassword =
    document.getElementById("new_password");

const confirmPassword =
    document.getElementById("confirm_password");


const currentPasswordError =
    document.getElementById("currentPasswordError");

const newPasswordError =
    document.getElementById("newPasswordError");

const confirmPasswordError =
    document.getElementById("confirmPasswordError");


/* =====================================================
   SHOW ERROR
====================================================== */

function showPasswordError(input, errorElement, message) {

    if (input) {
        input.classList.add("input-error");
    }

    if (errorElement) {

        errorElement.textContent = message;

        errorElement.classList.add("show");

    }

}


/* =====================================================
   CLEAR ERROR
====================================================== */

function clearPasswordError(input, errorElement) {

    if (input) {
        input.classList.remove("input-error");
    }

    if (errorElement) {

        errorElement.textContent = "";

        errorElement.classList.remove("show");

    }

}


/* =====================================================
   VALIDATE PASSWORD FORM
====================================================== */

if (passwordForm) {

    passwordForm.addEventListener(
        "submit",
        function (event) {

            let valid = true;


            /* -----------------------------------------
               CLEAR PREVIOUS ERRORS
            ------------------------------------------ */

            clearPasswordError(
                currentPassword,
                currentPasswordError
            );

            clearPasswordError(
                newPassword,
                newPasswordError
            );

            clearPasswordError(
                confirmPassword,
                confirmPasswordError
            );


            /* -----------------------------------------
               CURRENT PASSWORD
            ------------------------------------------ */

            if (
                !currentPassword.value.trim()
            ) {

                showPasswordError(
                    currentPassword,
                    currentPasswordError,
                    "Please enter your current password."
                );

                valid = false;

            }


            /* -----------------------------------------
               NEW PASSWORD
            ------------------------------------------ */

            if (!newPassword.value) {

                showPasswordError(
                    newPassword,
                    newPasswordError,
                    "Please enter a new password."
                );

                valid = false;

            }

            else if (
                newPassword.value.length < 8
            ) {

                showPasswordError(
                    newPassword,
                    newPasswordError,
                    "New password must contain at least 8 characters."
                );

                valid = false;

            }


            /* -----------------------------------------
               CONFIRM PASSWORD
            ------------------------------------------ */

            if (!confirmPassword.value) {

                showPasswordError(
                    confirmPassword,
                    confirmPasswordError,
                    "Please confirm your new password."
                );

                valid = false;

            }

            else if (
                newPassword.value !==
                confirmPassword.value
            ) {

                showPasswordError(
                    confirmPassword,
                    confirmPasswordError,
                    "Passwords do not match."
                );

                valid = false;

            }


            /* -----------------------------------------
               SAME PASSWORD CHECK
            ------------------------------------------ */

            if (
                currentPassword.value &&
                newPassword.value &&
                currentPassword.value ===
                newPassword.value
            ) {

                showPasswordError(
                    newPassword,
                    newPasswordError,
                    "New password must be different from your current password."
                );

                valid = false;

            }


            /* -----------------------------------------
               STOP SUBMISSION
            ------------------------------------------ */

            if (!valid) {

                event.preventDefault();

            }

        }
    );

}

/* =====================================================
   PASSWORD VISIBILITY TOGGLE
====================================================== */

const passwordToggleButtons =
    document.querySelectorAll(".password-toggle");


passwordToggleButtons.forEach(function (button) {

    button.addEventListener(
        "click",
        function () {

            const targetId =
                button.getAttribute("data-target");

            const passwordInput =
                document.getElementById(targetId);

            const icon =
                button.querySelector("i");


            if (!passwordInput) {
                return;
            }


            if (
                passwordInput.type === "password"
            ) {

                passwordInput.type = "text";

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

                if (icon) {

                    icon.className =
                        "fa-solid fa-eye-slash";

                }

            }

            else {

                passwordInput.type = "password";

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

                if (icon) {

                    icon.className =
                        "fa-solid fa-eye";

                }

            }

        }
    );

});