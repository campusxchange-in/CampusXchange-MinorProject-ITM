import { useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { FaCheckCircle, FaExclamationCircle } from "react-icons/fa";
import userAuthService from "../../services/userAuthService";
import "./Auth.css";
import "./AuthExtra.css";

export default function VerifyEmail() {
  const [params] = useSearchParams();
  const token = params.get("token") || "";
  const [state, setState] = useState({ loading: true, success: false, message: "Verifying your email..." });

  useEffect(() => {
    let active = true;
    if (!token) {
      setState({ loading: false, success: false, message: "The verification link is missing its token." });
      return () => { active = false; };
    }

    userAuthService.verifyEmail(token)
      .then((result) => {
        if (active) setState({ loading: false, success: true, message: result.message });
      })
      .catch((error) => {
        if (active) setState({ loading: false, success: false, message: error.message });
      });

    return () => { active = false; };
  }, [token]);

  return (
    <section className="auth-page auth-message-page">
      <div className="auth-card auth-message-card">
        <h2>Email Verification</h2>
        <p className="auth-subtitle">{state.message}</p>
        {!state.loading && (
          <p className={state.success ? "auth-success" : "auth-error"} role="status">
            {state.success ? <FaCheckCircle /> : <FaExclamationCircle />}
            {state.message}
          </p>
        )}
        {state.success && <Link className="auth-login-btn auth-message-link" to="/login">Go to Sign In</Link>}
        {!state.success && !state.loading && <Link className="auth-bottom-text" to="/signup">Back to Sign Up</Link>}
      </div>
    </section>
  );
}
