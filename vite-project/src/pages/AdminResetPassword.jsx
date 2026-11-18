import { useMemo, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { FaLock, FaEye, FaEyeSlash, FaArrowLeft, FaShieldAlt } from "react-icons/fa";
import { resetAdminPassword } from "../services/authService";
import "./AdminPasswordRecovery.css";

function AdminResetPassword() {
    const [searchParams] = useSearchParams();
    const token = useMemo(() => searchParams.get("token") || "", [searchParams]);
    const [password, setPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [errorMessage, setErrorMessage] = useState("");
    const [isLoading, setIsLoading] = useState(false);
    const [completed, setCompleted] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrorMessage("");

        if (!/^[a-f0-9]{64}$/.test(token)) {
            setErrorMessage("This password reset link is invalid. Please request a new link.");
            return;
        }
        if (password.length < 8 || password.length > 20) {
            setErrorMessage("Your password must contain 8–20 characters.");
            return;
        }
        if (new TextEncoder().encode(password).length > 72) {
            setErrorMessage("Your password is too long. Please use a shorter password.");
            return;
        }
        if (password !== confirmPassword) {
            setErrorMessage("The passwords do not match.");
            return;
        }
        if (isLoading) return;

        setIsLoading(true);
        try {
            const result = await resetAdminPassword(token, password, confirmPassword);
            if (result.success) setCompleted(true);
            else setErrorMessage(result.message || "This reset link could not be used.");
        } catch (error) {
            console.error("Admin reset password error:", error);
            setErrorMessage("Unable to connect to the server. Please try again.");
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <section className="admin-recovery-page">
            <div className="admin-recovery-left">
                <div className="admin-recovery-overlay" />
                <div className="admin-recovery-brand-content">
                    <span className="admin-recovery-logo">Campus<span>X</span>change</span>
                    <h1>Secure Your<br />Admin Account.</h1>
                    <p>Choose a new password to restore secure access to the CampusXchange admin panel.</p>
                    <div className="admin-recovery-security-note"><FaShieldAlt /><span>Use a unique password that you don't use on other websites.</span></div>
                </div>
            </div>

            <div className="admin-recovery-right">
                <div className="admin-recovery-card">
                    {!completed ? (
                        <>
                            <div className="admin-recovery-eyebrow">ADMINISTRATOR SECURITY</div>
                            <h2>Reset Password</h2>
                            <p className="admin-recovery-subtitle">Create a new password for your administrator account. Your reset link is valid for one hour and can only be used once.</p>

                            <form onSubmit={handleSubmit} noValidate>
                                <label className="admin-recovery-label" htmlFor="admin-new-password">New Password</label>
                                <div className="admin-recovery-input-wrap">
                                    <FaLock className="admin-recovery-input-icon" />
                                    <input id="admin-new-password" type={showPassword ? "text" : "password"} value={password} onChange={(event) => setPassword(event.target.value)} placeholder="Enter new password" autoComplete="new-password" maxLength={20} required />
                                    <button type="button" className="admin-recovery-eye" onClick={() => setShowPassword(value => !value)} aria-label={showPassword ? "Hide password" : "Show password"}>{showPassword ? <FaEyeSlash /> : <FaEye />}</button>
                                </div>

                                <label className="admin-recovery-label" htmlFor="admin-confirm-password">Confirm New Password</label>
                                <div className="admin-recovery-input-wrap">
                                    <FaLock className="admin-recovery-input-icon" />
                                    <input id="admin-confirm-password" type={showConfirm ? "text" : "password"} value={confirmPassword} onChange={(event) => setConfirmPassword(event.target.value)} placeholder="Confirm new password" autoComplete="new-password" maxLength={20} required />
                                    <button type="button" className="admin-recovery-eye" onClick={() => setShowConfirm(value => !value)} aria-label={showConfirm ? "Hide password" : "Show password"}>{showConfirm ? <FaEyeSlash /> : <FaEye />}</button>
                                </div>

                                <p className="admin-recovery-hint">Password must contain 8–20 characters.</p>
                                {errorMessage && <div className="admin-recovery-error" role="alert">{errorMessage}</div>}
                                <button className="admin-recovery-button" type="submit" disabled={isLoading}>{isLoading ? "Updating Password..." : "Reset Password"}</button>
                            </form>

                            <div className="admin-recovery-back"><Link to="/admin-login"><FaArrowLeft /> Back to Admin Sign In</Link></div>
                        </>
                    ) : (
                        <div className="admin-recovery-success">
                            <div className="admin-recovery-success-icon"><FaLock /></div>
                            <div className="admin-recovery-eyebrow">PASSWORD UPDATED</div>
                            <h2>You're All Set</h2>
                            <p>Your administrator password has been changed successfully. You can now sign in using your new password.</p>
                            <Link to="/admin-login" className="admin-recovery-button admin-recovery-button-link">Go to Admin Sign In</Link>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

export default AdminResetPassword;
