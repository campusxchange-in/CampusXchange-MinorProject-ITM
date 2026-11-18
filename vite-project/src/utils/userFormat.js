// Small display helpers shared by the logged-in pages.

export const userFormatPrice = (n) =>
    `₹${Number(n || 0).toLocaleString("en-IN")}`;

// MySQL "2026-11-07 14:30:00" (server time, IST) -> Date
export function userParseDate(value) {
    if (!value) return null;
    if (value instanceof Date) return value;
    const d = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(d.getTime()) ? null : d;
}

export function userTimeAgo(value) {
    const d = userParseDate(value);
    if (!d) return "";
    const s = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
    if (s < 60) return "Just now";
    const m = Math.floor(s / 60);
    if (m < 60) return `${m} minute${m > 1 ? "s" : ""} ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h} hour${h > 1 ? "s" : ""} ago`;
    const days = Math.floor(h / 24);
    if (days < 30) return `${days} day${days > 1 ? "s" : ""} ago`;
    return userFormatDate(d);
}

export function userFormatDate(value) {
    const d = userParseDate(value);
    return d
        ? d.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" })
        : "";
}

export const userInitial = (name) =>
    (String(name || "").trim().charAt(0) || "U").toUpperCase();

export function userGreeting() {
    const h = new Date().getHours();
    return h < 12 ? "Good morning," : h < 17 ? "Good afternoon," : "Good evening,";
}
