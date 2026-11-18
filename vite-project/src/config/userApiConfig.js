// Single place where the logged-in (user) side builds URLs.
// Value comes from .env -> VITE_API_BASE_URL (see src/config/config.js).
import API_BASE_URL from "./config";

const trim = (v) => String(v || "").replace(/\/+$/, "");

export const USER_API_BASE = `${trim(API_BASE_URL)}/backend/user`;
export const UPLOADS_BASE = `${trim(API_BASE_URL)}/uploads`;

// Turns whatever the DB stored ("abc.jpg" or "uploads/products/abc.jpg" or a
// full URL) into a working URL. `folder` = products | user_profiles | ...
export function userUploadUrl(path, folder = "products") {
    if (!path) return "";
    if (/^https?:\/\//i.test(path)) return path;
    const clean = String(path).replace(/^\/+/, "").replace(/^uploads\//, "");
    return clean.includes("/")
        ? `${UPLOADS_BASE}/${clean}`
        : `${UPLOADS_BASE}/${folder}/${clean}`;
}
