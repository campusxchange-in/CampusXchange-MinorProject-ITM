// Calls the PUBLIC endpoints (no login needed) used by the landing page,
// login, signup and admin login screens. URL comes from .env -> VITE_API_BASE_URL.
import API_BASE_URL from "../config/config";

const PUBLIC_API_BASE = `${String(API_BASE_URL || "").replace(/\/+$/, "")}/backend/public`;

async function publicGet(endpoint, params) {
    let url = `${PUBLIC_API_BASE}/${endpoint}`;
    if (params) {
        const q = new URLSearchParams(params).toString();
        if (q) url += `?${q}`;
    }

    let response;
    try {
        response = await fetch(url, { headers: { Accept: "application/json" } });
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
        throw new Error(data.message || "Request failed.");
    }
    return data;
}

// One shared request: Login, Signup, Stats and AdminLogin all use the same
// numbers, so the page only asks the server once per short period.
let statsPromise = null;
let statsFetchedAt = 0;
const STATS_TTL_MS = 30 * 1000;

export function fetchPublicStats() {
    const now = Date.now();
    if (!statsPromise || now - statsFetchedAt > STATS_TTL_MS) {
        statsFetchedAt = now;
        statsPromise = publicGet("public_stats.php")
            .then((data) => data.stats)
            .catch((error) => {
                statsPromise = null; // allow a retry next time
                throw error;
            });
    }
    return statsPromise;
}

export const fetchFeaturedProducts = (limit = 6) =>
    publicGet("public_featured_products.php", { limit }).then((data) => data.products);
