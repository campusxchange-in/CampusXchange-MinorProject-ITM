
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { useEffect } from "react";
import AOS from "aos";
import "aos/dist/aos.css";

import LoadingBar from "./components/LoadingBar";
import ProtectedRoute from "./components/ProtectedRoute";
import { useAuth } from "./context/AuthContext";

// Main pages
import Home from "./pages/Home";

// User authentication
import Signup from "./pages/auth/Signup";
import Login from "./pages/auth/Login";
import ForgotPassword from "./pages/auth/Forgotpass";
import VerifyEmail from "./pages/auth/VerifyEmail";
import ResetPassword from "./pages/auth/ResetPassword";

// Admin authentication
import AdminLogin from "./pages/AdminLogin";
import AdminForgotPassword from "./pages/AdminForgotPassword";
import AdminResetPassword from "./pages/AdminResetPassword";

// User dashboard pages
import Dashboard from "./afterlogpages/Dashboard";
import SellItem from "./afterlogpages/SellItem";
import MyListings from "./afterlogpages/MyListings";
import Messages from "./afterlogpages/Messages/Messages";
import Myorders from "./afterlogpages/Myorders";
import Wishlist from "./afterlogpages/Wishlist";
import Profile from "./afterlogpages/settings/Profile";
import AccountSecurity from "./afterlogpages/settings/AccountSecurity";
import BrowseProducts from "./afterlogpages/Browse/BrowseProducts";
import BuyProduct from "./afterlogpages/Browse/BuyProduct";
import SellerWallet from "./afterlogpages/SellerWallet";


/* =========================================
   USER ROUTES
========================================= */

function AppRoutes() {
    const { user, loading } = useAuth();

    // Wait until PHP session / Remember Me check finishes.
    if (loading) {
        return (
            <div className="app-auth-loading">
                Checking your session...
            </div>
        );
    }

    return (
        <>
            <LoadingBar />

            <Routes>

                {/* =================================
                    PUBLIC HOME
                ================================= */}

                <Route
                    path="/"
                    element={
                        user
                            ? <Navigate to="/dashboard" replace />
                            : <Home />
                    }
                />


                {/* =================================
                    USER AUTHENTICATION
                ================================= */}

                <Route
                    path="/login"
                    element={
                        user
                            ? <Navigate to="/dashboard" replace />
                            : <Login />
                    }
                />

                <Route path="/signup" element={<Signup />} />

                <Route
                    path="/forgot-password"
                    element={<ForgotPassword />}
                />

                <Route
                    path="/verify-email"
                    element={<VerifyEmail />}
                />

                <Route
                    path="/reset-password"
                    element={<ResetPassword />}
                />


                {/* =================================
                    ADMIN AUTHENTICATION
                    INDEPENDENT FROM USER AUTH
                ================================= */}

                <Route
                    path="/admin-login"
                    element={<AdminLogin />}
                />

                <Route
                    path="/admin-forgot-password"
                    element={<AdminForgotPassword />}
                />

                <Route
                    path="/admin-reset-password"
                    element={<AdminResetPassword />}
                />


                {/* =================================
                    PROTECTED USER DASHBOARD ROUTES
                ================================= */}

                <Route element={<ProtectedRoute />}>

                    <Route
                        path="/dashboard"
                        element={<Dashboard />}
                    />

                    <Route
                        path="/sell-item"
                        element={<SellItem />}
                    />

                    <Route
                        path="/mylistings"
                        element={<MyListings />}
                    />

                    {/* Messages */}
                    <Route
                        path="/messages"
                        element={<Messages />}
                    />

                    <Route
                        path="/messages/:sellerId"
                        element={<Messages />}
                    />

                    {/* Account */}
                    <Route
                        path="/myorders"
                        element={<Myorders />}
                    />

                    <Route
                        path="/wishlist"
                        element={<Wishlist />}
                    />

                    <Route
                        path="/profile"
                        element={<Profile />}
                    />

                    <Route
                        path="/accountsecurity"
                        element={<AccountSecurity />}
                    />

                    {/* Products */}
                    <Route
                        path="/browseproducts"
                        element={<BrowseProducts />}
                    />

                    <Route
                        path="/buy/:productId"
                        element={<BuyProduct />}
                    />

                    <Route
                        path="/sellerwallet"
                        element={<SellerWallet />}
                    />

                </Route>


                {/* =================================
                    UNKNOWN ROUTES
                ================================= */}

                <Route
                    path="*"
                    element={<Navigate to="/" replace />}
                />

            </Routes>
        </>
    );
}


/* =========================================
   MAIN APP
========================================= */

function App() {

    useEffect(() => {
        AOS.init({
            duration: 1000,
            once: true,
            mirror: false,
            offset: 100,
            easing: "ease-in-out",
            delay: 100,
        });
    }, []);

    return (
        <BrowserRouter>
            <AppRoutes />
        </BrowserRouter>
    );
}

export default App;
