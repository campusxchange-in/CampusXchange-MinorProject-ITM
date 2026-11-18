
import React, { useState, useEffect, useRef } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "./Navbar.css";
import "./userNavbarExtras.css";

import { useAuth } from "../../context/AuthContext";
import userAuthService from "../../services/userAuthService";
import { pingUserPresence } from "../../services/userChatService";
import {
    fetchUserNavbar,
    fetchUserNotifications,
    markUserNotificationRead,
    markAllUserNotificationsRead,
} from "../../services/userMarketService";
import { userInitial, userTimeAgo } from "../../utils/userFormat";

import {
    FaSearch,
    FaHeart,
    FaShoppingBag,
    FaBell,
    FaCommentDots,
    FaUser,
    FaCog,
    FaQuestionCircle,
    FaSignOutAlt,
    FaPlus,
    FaChevronDown,
    FaClipboardList,
    FaWallet,
} from "react-icons/fa";

function Navbar() {
    const navigate = useNavigate();
    const location = useLocation();

    const { signOut, user: authUser } = useAuth();

    // Live navbar data (name, avatar, unread counts) from the database
    const [navData, setNavData] = useState(null);

    useEffect(() => {
        let active = true;
        const load = () =>
            fetchUserNavbar()
                .then((d) => active && setNavData(d))
                .catch(() => {});
        load();
        const timer = setInterval(load, 30000);
        return () => {
            active = false;
            clearInterval(timer);
        };
    }, [location.pathname]);

    // ---- presence heartbeat: "this user has the app open" ----
    // Only while the tab is visible; stops by itself after logout (401).
    useEffect(() => {
        const beat = () => {
            if (document.visibilityState === "visible") {
                pingUserPresence().catch(() => {});
            }
        };
        beat();
        const timer = setInterval(beat, 20000);
        document.addEventListener("visibilitychange", beat);
        return () => {
            clearInterval(timer);
            document.removeEventListener("visibilitychange", beat);
        };
    }, []);

    // ---- notification bell (database) ----
    const [showNotifs, setShowNotifs] = useState(false);
    const [notifs, setNotifs] = useState([]);

    const loadNotifs = () =>
        fetchUserNotifications()
            .then((d) => setNotifs(d.notifications))
            .catch(() => {});

    const toggleNotifs = () => {
        const next = !showNotifs;
        setShowNotifs(next);
        if (next) loadNotifs();
    };

    const openNotif = async (n) => {
        setShowNotifs(false);
        if (!n.is_read) {
            try {
                await markUserNotificationRead(n.id);
                setNavData((d) =>
                    d ? { ...d, unread_notifications: Math.max(0, d.unread_notifications - 1) } : d
                );
            } catch (e) {}
        }
        if (n.link) navigate(n.link);
    };

    const readAllNotifs = async () => {
        try {
            await markAllUserNotificationsRead();
            setNotifs((list) => list.map((n) => ({ ...n, is_read: true })));
            setNavData((d) => (d ? { ...d, unread_notifications: 0 } : d));
        } catch (e) {}
    };

    const displayName = navData?.user?.full_name || authUser?.full_name || "";

    const [showProfileMenu, setShowProfileMenu] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);

    const profileRef = useRef(null);

    // Close profile dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                profileRef.current &&
                !profileRef.current.contains(event.target)
            ) {
                setShowProfileMenu(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);

        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, []);

    // Logout handler
    const handleLogout = async () => {
        if (loggingOut) return;

        setLoggingOut(true);

        try {
            // PHP revokes the remember token,
            // clears the remember cookie and destroys the session.
            await userAuthService.logout();

        } catch (error) {
            console.error("User logout request failed:", error);

        } finally {
            // Clear React authentication state
            signOut();

            setShowProfileMenu(false);
            setLoggingOut(false);

            // Return to homepage
            navigate("/", { replace: true });
        }
    };

    return (
        <header className="sell-topbar">

            {/* Logo */}

            <Link
                to="/dashboard"
                className="sell-brand"
            >
                <div className="sell-brand-icon">
                    <FaShoppingBag />
                </div>

                <div>
                    <h2>
                        Campus<span>X</span>change
                    </h2>

                    <p>
                        Buy. Sell. Exchange.
                    </p>
                </div>
            </Link>


            {/* Search */}

            <div className="sell-search">
                <FaSearch />

                <input
                    type="text"
                    placeholder="Search for items, categories or users..."
                    onKeyDown={(e) => {
                        if (e.key === "Enter" && e.target.value.trim()) {
                            navigate(
                                `/browseproducts?search=${encodeURIComponent(
                                    e.target.value.trim()
                                )}`
                            );
                        }
                    }}
                />
            </div>


            {/* Navbar Right */}

            <div className="sell-nav-right">

                {/* Sell / My Listings */}

                <button
                    className="top-my-listing-button"
                    onClick={() =>
                        location.pathname === "/sell-item"
                            ? navigate("/mylistings")
                            : navigate("/sell-item")
                    }
                >
                    {location.pathname === "/sell-item" ? (
                        <>
                            <FaClipboardList />
                            My Listings
                        </>
                    ) : (
                        <>
                            <FaPlus />
                            Sell items
                        </>
                    )}
                </button>


                {/* Messages */}

                <Link
                    to="/messages"
                    className="nav-message-btn"
                    aria-label="Messages"
                >
                    <FaCommentDots />
                    {navData?.unread_messages > 0 && (
                        <span className="user-nav-badge">
                            {navData.unread_messages > 99 ? "99+" : navData.unread_messages}
                        </span>
                    )}
                </Link>


                {/* Wishlist */}

                <Link
                    to="/wishlist"
                    className="nav-message-btn"
                    aria-label="Wishlist"
                >
                    <FaHeart />
                </Link>


                {/* Notifications */}

                <div className="user-notif-wrap">
                    <button
                        className="nav-message-btn"
                        aria-label="Notifications"
                        onClick={toggleNotifs}
                    >
                    <FaBell />
                    {navData?.unread_notifications > 0 && (
                        <span className="user-nav-badge">
                            {navData.unread_notifications > 99 ? "99+" : navData.unread_notifications}
                        </span>
                    )}
                </button>

                    {showNotifs && (
                        <div className="user-notif-panel">
                            <div className="user-notif-head">
                                <strong>Notifications</strong>
                                <button onClick={readAllNotifs}>Mark all read</button>
                            </div>

                            <div className="user-notif-list">
                                {notifs.length === 0 && (
                                    <div className="user-notif-empty">No notifications yet.</div>
                                )}

                                {notifs.map((n) => (
                                    <button
                                        key={n.id}
                                        className={`user-notif-item ${n.is_read ? "" : "unread"}`}
                                        onClick={() => openNotif(n)}
                                    >
                                        <strong>{n.title}</strong>
                                        <span>{n.message}</span>
                                        <small>{userTimeAgo(n.created_at)}</small>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>


                {/* Profile */}

                <div
                    className="profile-wrapper"
                    ref={profileRef}
                >

                    <button
                        className="profile-button"
                        aria-label="Profile menu"
                        aria-expanded={showProfileMenu}
                        onClick={() =>
                            setShowProfileMenu((prev) => !prev)
                        }
                    >
                        <div className="profile-initial">
                            {navData?.user?.profile_picture ? (
                                <img src={navData.user.profile_picture} alt={displayName} />
                            ) : (
                                userInitial(displayName)
                            )}
                        </div>

                        <FaChevronDown />
                    </button>


                    {/* Profile Dropdown */}

                    {showProfileMenu && (
                        <div className="profile-dropdown">

                            <Link
                                to="/profile"
                                onClick={() => setShowProfileMenu(false)}
                            >
                                <FaUser />
                                Profile
                            </Link>


                            <Link
                                to="/mylistings"
                                onClick={() => setShowProfileMenu(false)}
                            >
                                <FaClipboardList />
                                My Listings
                            </Link>


                            <Link
                                to="/profile"
                                onClick={() => setShowProfileMenu(false)}
                            >
                                <FaCog />
                                Settings
                            </Link>


                            {/* <Link
                                to="/help"
                                onClick={() => setShowProfileMenu(false)}
                            >
                                <FaQuestionCircle />
                                Help & Support
                            </Link> */}


                            <div className="dropdown-divider"></div>


                            {/* Logout */}

                            <button
                                onClick={handleLogout}
                                disabled={loggingOut}
                            >
                                <FaSignOutAlt />

                                {loggingOut
                                    ? "Logging out..."
                                    : "Logout"}
                            </button>

                        </div>
                    )}

                </div>

            </div>

        </header>
    );
}

export default Navbar;
