
import API_BASE_URL from "../config/config";

const USER_AUTH_BASE = `${API_BASE_URL}/backend/auth`;

async function request(endpoint, { method = "POST", body } = {}) {
    let response;

    try {
        response = await fetch(`${USER_AUTH_BASE}/${endpoint}`, {
            method,
            credentials: "include",
            headers: {
                Accept: "application/json",
                ...(body !== undefined
                    ? { "Content-Type": "application/json" }
                    : {}),
            },
            ...(body !== undefined
                ? { body: JSON.stringify(body) }
                : {}),
        });
    } catch (error) {
        console.error("User authentication connection error:", error);
        throw new Error("SERVICE_UNAVAILABLE");
    }

    let data;

    try {
        data = await response.json();
    } catch (error) {
        console.error("Invalid authentication response:", error);
        throw new Error("SERVICE_UNAVAILABLE");
    }

    if (!response.ok || data.success === false) {
        const error = new Error(data.message || "Request failed.");
        error.status = response.status;
        error.code = data.code;
        throw error;
    }

    return data;
}

const userAuthService = {
    signup: (details) =>
        request("signup.php", { body: details }),

    verifyEmail: (token) =>
        request(
            `verify-email.php?token=${encodeURIComponent(token)}`,
            { method: "GET" }
        ),

    login: (details) =>
        request("login.php", { body: details }),

    restoreSession: () =>
        request("remember-session.php", { method: "GET" }),

    logout: () =>
        request("logout.php", { body: {} }),

    forgotPassword: (email) =>
        request("forgot-password.php", { body: { email } }),

    resetPassword: (details) =>
        request("reset-password.php", { body: details }),
};

export const signup = userAuthService.signup;
export const verifyEmail = userAuthService.verifyEmail;
export const login = userAuthService.login;
export const restoreSession = userAuthService.restoreSession;
export const logout = userAuthService.logout;
export const forgotPassword = userAuthService.forgotPassword;
export const resetPassword = userAuthService.resetPassword;

export default userAuthService;
