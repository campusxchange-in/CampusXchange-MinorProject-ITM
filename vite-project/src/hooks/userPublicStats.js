import { useEffect, useState } from "react";
import { fetchPublicStats } from "../services/userPublicService";

// Gives a component the live numbers from the database.
//   const { stats, loading, error } = useUserPublicStats();
// `stats` is null until the numbers arrive (or if the server cannot be reached).
export function useUserPublicStats() {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;
        fetchPublicStats()
            .then((data) => { if (active) setStats(data); })
            .catch((e) => { if (active) setError(e.message || "Unable to load statistics."); })
            .finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, []);

    return { stats, loading, error };
}

// 120 -> "120", 1250 -> "1.3K", 25000 -> "25K"; null/undefined -> "-"
export function formatUserStat(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return "-";
    const n = Number(value);
    if (n < 1000) return String(n);
    const k = n / 1000;
    return `${k >= 10 ? Math.round(k) : Math.round(k * 10) / 10}K`;
}
