import API_BASE_URL from "../config/config";

export async function adminLogin(email, password) {

    const response = await fetch(
        `${API_BASE_URL}/admin/login`,
        {
            method: "POST",

            headers: {
                "Content-Type": "application/json",
            },

            credentials: "include",

            body: JSON.stringify({
                email: email.trim(),
                password: password,
            }),
        }
    );

    let data;

    try {
        data = await response.json();
    } catch (error) {
        throw new Error("Invalid server response.");
    }

    // Return authentication failures normally.
    // HTTP 401 and 403 should not be treated as network errors.

    if (!response.ok && !data.message) {
        throw new Error("Server error. Please try again later.");
    }

    return data;
}


export async function requestAdminPasswordReset(email) {
    const response = await fetch(`${API_BASE_URL}/admin/forgot_password`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ email: email.trim() }),
    });
    return await response.json();
}

export async function resetAdminPassword(token, password, confirmPassword) {
    const response = await fetch(`${API_BASE_URL}/admin/reset_password`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
            token,
            password,
            confirm_password: confirmPassword,
        }),
    });
    return await response.json();
}
