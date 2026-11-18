// Shared fetch helper for every logged-in page. Sends the PHP session cookie
// and always expects the JSON shape { success, message, ...data }.
import { USER_API_BASE } from "../config/userApiConfig";

async function userRequest(endpoint, { method = "GET", params, body, form } = {}) {
    let url = `${USER_API_BASE}/${endpoint}`;

    if (params) {
        const q = new URLSearchParams();
        Object.entries(params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== "") q.append(k, v);
        });
        const qs = q.toString();
        if (qs) url += `?${qs}`;
    }

    const options = {
        method,
        credentials: "include",
        headers: { Accept: "application/json" },
    };

    if (form) {
        options.body = form; // FormData – browser sets the boundary
    } else if (body !== undefined) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (e) {
        const err = new Error("Unable to reach the server.");
        err.code = "SERVICE_UNAVAILABLE";
        throw err;
    }

    let data;
    try {
        data = await response.json();
    } catch (e) {
        const err = new Error("Invalid server response.");
        err.code = "SERVICE_UNAVAILABLE";
        throw err;
    }

    if (!response.ok || data.success === false) {
        const err = new Error(data.message || "Request failed.");
        err.status = response.status;
        err.code = data.code;
        throw err;
    }
    return data;
}

export const userGet = (endpoint, params) => userRequest(endpoint, { params });
export const userPost = (endpoint, body) => userRequest(endpoint, { method: "POST", body });
export const userPostForm = (endpoint, form) => userRequest(endpoint, { method: "POST", form });

export default userRequest;
