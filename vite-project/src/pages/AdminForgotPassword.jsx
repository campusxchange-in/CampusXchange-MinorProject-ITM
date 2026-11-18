import { useState } from "react";
import { Link } from "react-router-dom";
import { FaEnvelope, FaArrowLeft, FaShieldAlt } from "react-icons/fa";
import { requestAdminPasswordReset } from "../services/authService";
import "./AdminPasswordRecovery.css";

function AdminForgotPassword() {
    const [email, setEmail] = useState("");
    const [errorMessage, setErrorMessage] = useState("");
    const [isLoading, setIsLoading] = useState(false);
    const [submitted, setSubmitted] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrorMessage("");

        const normalizedEmail = email.trim();
        if (!normalizedEmail) {
            setErrorMessage("Please enter your administrator email address.");
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(normalizedEmail)) {
            setErrorMessage("Please enter a valid email address.");
            return;
        }

        if (isLoading) return;
        setIsLoading(true);

        try {
            const result = await requestAdminPasswordReset(normalizedEmail);
            if (result.success) {
                setSubmitted(true);
            } else {
                setErrorMessage(result.message || "Unable to process your request. Please try again.");
            }
        } catch (error) {
            console.error("Admin forgot password error:", error);
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
                    <h1>Admin Account<br />Recovery.</h1>
                    <p>Securely restore access to your CampusXchange administration account.</p>
                    <div className="admin-recovery-security-note">
                        <FaShieldAlt />
                        <span>Password reset links expire after one hour and can only be used once.</span>
                    </div>
                </div>
            </div>

            <div className="admin-recovery-right">
                <div className="admin-recovery-card">
                    {!submitted ? (
                        <>
                            <div className="admin-recovery-eyebrow">ADMINISTRATOR SECURITY</div>
                            <h2>Forgot Password?</h2>
                            <p className="admin-recovery-subtitle">Enter the email address associated with your administrator account. If an active account exists, we'll send a secure reset link.</p>

                            <form onSubmit={handleSubmit} noValidate>
                                <label className="admin-recovery-label" htmlFor="admin-recovery-email">Administrator Email</label>
                                <div className="admin-recovery-input-wrap">
                                    <FaEnvelope className="admin-recovery-input-icon" />
                                    <input
                                        id="admin-recovery-email"
                                        type="email"
                                        value={email}
                                        onChange={(event) => setEmail(event.target.value)}
                                        placeholder="Enter your admin email"
                                        autoComplete="email"
                                        maxLength={150}
                                        required
                                    />
                                </div>

                                {errorMessage && <div className="admin-recovery-error" role="alert">{errorMessage}</div>}

                                <button className="admin-recovery-button" type="submit" disabled={isLoading}>
                                    {isLoading ? "Sending Request..." : "Send Password Reset Link"}
                                </button>
                            </form>

                            <div className="admin-recovery-back">
                                <Link to="/admin-login"><FaArrowLeft /> Back to Admin Sign In</Link>
                            </div>
                        </>
                    ) : (
                        <div className="admin-recovery-success">
                            <div className="admin-recovery-success-icon"><FaEnvelope /></div>
                            <div className="admin-recovery-eyebrow">REQUEST RECEIVED</div>
                            <h2>Check Your Email</h2>
                            <p>If an active administrator account exists for <strong>{email.trim()}</strong>, a password reset link will be sent shortly. Please also check your spam folder.</p>
                            <Link to="/admin-login" className="admin-recovery-button admin-recovery-button-link">Back to Admin Sign In</Link>
                            <button type="button" className="admin-recovery-resend" onClick={() => setSubmitted(false)}>Try another email address</button>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

export default AdminForgotPassword;
