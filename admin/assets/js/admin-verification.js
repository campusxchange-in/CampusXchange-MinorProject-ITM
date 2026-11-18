
document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       ADMIN INVITATION PASSWORD SETUP
    ===================================================== */

    const form = document.getElementById("adminSetupForm");

    if (!form) {
        return;
    }

    const password = document.getElementById("new_password");
    const confirmPassword = document.getElementById("confirm_password");

    const passwordError = document.getElementById("newPasswordError");
    const confirmError = document.getElementById("confirmPasswordError");

    const submitButton = document.getElementById("setupSubmit");


    /* =====================================================
       DISPLAY VALIDATION ERROR
    ===================================================== */

    function setError(input, errorElement, message) {

        errorElement.textContent = message;

        input.classList.toggle(
            "is-invalid",
            Boolean(message)
        );

        input.classList.toggle(
            "is-valid",
            !message && input.value.length > 0
        );

        input.setAttribute(
            "aria-invalid",
            message ? "true" : "false"
        );

    }


    /* =====================================================
       VALIDATE NEW PASSWORD
    ===================================================== */

    function validatePassword() {

        let message = "";

        if (password.value.length < 8) {

            message = "Password must contain at least 8 characters.";

        } else if (
            new TextEncoder().encode(password.value).length > 72
        ) {

            message = "Password is too long. Use no more than 72 bytes.";

        }

        setError(
            password,
            passwordError,
            message
        );

        return message === "";

    }


    /* =====================================================
       VALIDATE CONFIRM PASSWORD
    ===================================================== */

    function validateConfirmation() {

        let message = "";

        if (confirmPassword.value === "") {

            message = "Please confirm your password.";

        } else if (
            password.value !== confirmPassword.value
        ) {

            message = "The passwords do not match.";

        }

        setError(
            confirmPassword,
            confirmError,
            message
        );

        return message === "";

    }


    /* =====================================================
       LIVE PASSWORD VALIDATION
    ===================================================== */

    password.addEventListener("input", function () {

        validatePassword();

        if (confirmPassword.value !== "") {
            validateConfirmation();
        }

    });


    /* =====================================================
       LIVE CONFIRM PASSWORD VALIDATION
    ===================================================== */

    confirmPassword.addEventListener(
        "input",
        validateConfirmation
    );


    /* =====================================================
       SHOW / HIDE PASSWORD
    ===================================================== */

    document.querySelectorAll(
        ".verification-password-toggle"
    ).forEach(function (button) {

        button.addEventListener("click", function () {

            const targetId = button.dataset.target;

            const input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            const showPassword =
                input.type === "password";

            input.type = showPassword
                ? "text"
                : "password";


            /* Update eye icon */

            const icon = button.querySelector("i");

            if (icon) {

                icon.classList.toggle(
                    "fa-eye",
                    !showPassword
                );

                icon.classList.toggle(
                    "fa-eye-slash",
                    showPassword
                );

            }


            /* Update accessibility label */

            button.setAttribute(
                "aria-label",
                showPassword
                    ? "Hide password"
                    : "Show password"
            );

        });

    });


    /* =====================================================
       FORM SUBMISSION VALIDATION
    ===================================================== */

    form.addEventListener("submit", function (event) {

        const passwordValid = validatePassword();

        const confirmationValid =
            validateConfirmation();


        if (!passwordValid || !confirmationValid) {

            event.preventDefault();

            const firstInvalid =
                form.querySelector(".is-invalid");

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return;

        }


        /* Prevent accidental double-click submissions */

        submitButton.disabled = true;

        const buttonText =
            submitButton.querySelector("span");

        if (buttonText) {

            buttonText.textContent =
                "Creating your account...";

        }

    });

});
