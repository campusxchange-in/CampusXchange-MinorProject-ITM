document.addEventListener("DOMContentLoaded", function () {

    /* PASSWORD VISIBILITY */
    document.querySelectorAll(".password-toggle").forEach(function (button) {
        button.addEventListener("click", function () {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;

            const showing = input.type === "password";
            input.type = showing ? "text" : "password";

            const icon = button.querySelector("i");
            if (icon) icon.className = showing ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";

            button.setAttribute("aria-label", showing ? "Hide password" : "Show password");
        });
    });

    /* EMAIL CHANGE VALIDATION */
    const emailForm = document.getElementById("emailChangeForm");
    if (emailForm) {
        emailForm.addEventListener("submit", function (event) {
            const email = document.getElementById("new_email");
            const password = document.getElementById("email_current_password");
            const error = document.getElementById("newEmailError");
            let invalid = false;

            email.classList.remove("input-error");
            password.classList.remove("input-error");
            error.textContent = "";
            error.classList.remove("show");

            if (!email.value.trim() || !email.validity.valid) {
                event.preventDefault();
                email.classList.add("input-error");
                error.textContent = "Please enter a valid email address.";
                error.classList.add("show");
                invalid = true;
            }

            if (!password.value) {
                event.preventDefault();
                password.classList.add("input-error");
                invalid = true;
            }

            if (invalid) {
                (email.classList.contains("input-error") ? email : password).focus();
                return;
            }

            const button = document.getElementById("emailChangeSubmit");
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            }
        });
    }

    /* CHANGE PASSWORD VALIDATION */
    const passwordForm = document.getElementById("passwordChangeForm");
    if (passwordForm) {
        passwordForm.addEventListener("submit", function (event) {
            const current = document.getElementById("current_password");
            const next = document.getElementById("new_password");
            const confirm = document.getElementById("confirm_password");

            let message = "";
            let focusTarget = null;

            [current, next, confirm].forEach(input => input && input.classList.remove("input-error"));

            if (!current.value) {
                message = "Please enter your current password.";
                focusTarget = current;
            } else if (next.value.length < 8 || next.value.length > 20) {
                message = "New password must contain 8–20 characters.";
                focusTarget = next;
            } else if (current.value === next.value) {
                message = "Your new password must be different from your current password.";
                focusTarget = next;
            } else if (!confirm.value) {
                message = "Please confirm your new password.";
                focusTarget = confirm;
            } else if (next.value !== confirm.value) {
                message = "The new password and confirmation do not match.";
                focusTarget = confirm;
            }

            const oldError = passwordForm.querySelector(".password-form-error");
            if (oldError) oldError.remove();

            if (message) {
                event.preventDefault();
                focusTarget.classList.add("input-error");
                const box = document.createElement("div");
                box.className = "password-form-error";
                const icon = document.createElement("i");
                icon.className = "fa-solid fa-circle-exclamation";
                const text = document.createElement("span");
                text.textContent = message;
                box.append(icon, text);
                passwordForm.prepend(box);
                focusTarget.focus();
            }
        });
    }

    /* ADMIN ALERTS */
    document.querySelectorAll(".admin-alert").forEach(function (alert) {
        const close = alert.querySelector(".alert-close");
        if (close) close.addEventListener("click", function () { closeAlert(alert); });
        setTimeout(function () { closeAlert(alert); }, 10000);
    });

    function closeAlert(alert) {
        if (!alert || !alert.isConnected) return;
        alert.classList.add("alert-hiding");
        setTimeout(function () { alert.remove(); }, 300);
    }
});
