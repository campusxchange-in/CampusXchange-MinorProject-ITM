import { useState } from "react";
import { Link, useSearchParams, useNavigate } from "react-router-dom";
import { FaLock, FaExclamationCircle, FaCheckCircle } from "react-icons/fa";
import userAuthService from "../../services/userAuthService";
import "./Auth.css";
import "./AuthExtra.css";

export default function ResetPassword() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const token = params.get("token") || "";
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    if (!token) return setError("The password reset token is missing.");
    if (password.length < 8) return setError("Password must be at least 8 characters.");
    if (password !== confirmPassword) return setError("Passwords do not match.");

    setLoading(true);
    try {
      const result = await userAuthService.resetPassword({
        token,
        password,
        confirm_password: confirmPassword,
      });
      setSuccess(result.message);
      setTimeout(() => navigate("/login", { replace: true }), 1800);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="auth-page auth-message-page">
      <div className="auth-card auth-message-card">
        <h2>Choose a New Password</h2>
        <p className="auth-subtitle">Enter and confirm your new password.</p>
        {success ? (
          <p className="auth-success" role="status"><FaCheckCircle /> {success}</p>
        ) : (
          <form onSubmit={handleSubmit}>
            <div className="auth-input-box">
              <FaLock className="auth-icon" />
              <input type="password" placeholder="New password" value={password}
                onChange={(e) => setPassword(e.target.value)} autoComplete="new-password" required />
            </div>
            <div className="auth-input-box">
              <FaLock className="auth-icon" />
              <input type="password" placeholder="Confirm new password" value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)} autoComplete="new-password" required />
            </div>
            {error && <p className="auth-error" role="alert"><FaExclamationCircle /> {error}</p>}
            <button className="auth-login-btn" type="submit" disabled={loading}>
              {loading ? "Updating..." : "Reset Password"}
            </button>
          </form>
        )}
        <p className="auth-bottom-text"><Link to="/login">Back to Sign In</Link></p>
      </div>
    </section>
  );
}
