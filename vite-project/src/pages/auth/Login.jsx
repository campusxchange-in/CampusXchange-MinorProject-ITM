import { useEffect, useRef, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import {
  FaEnvelope, FaLock, FaEye, FaEyeSlash, FaExclamationCircle,
} from "react-icons/fa";
import "./Auth.css";
import userAuthService from "../../services/userAuthService";
import { useAuth } from "../../context/AuthContext";
import { useUserPublicStats, formatUserStat } from "../../hooks/userPublicStats";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
const EMPTY = { email: "", password: "", remember: false };

export default function Login() {
  const [values, setValues] = useState(EMPTY);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const refs = { email: useRef(null), password: useRef(null) };
  const { signIn, isAuthenticated, loading } = useAuth();
  const { stats } = useUserPublicStats();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (!loading && isAuthenticated) {
        navigate("/dashboard", { replace: true });
    }
}, [loading, isAuthenticated, navigate]);

  const rules = {
    email: (v) => !v.trim() ? "Enter your college email." : !EMAIL_RE.test(v.trim()) ? "Enter a valid email address." : "",
    password: (v) => !v ? "Enter your password." : "",
  };

  const handleChange = (e) => {
    const { name, type, value, checked } = e.target;
    const next = { ...values, [name]: type === "checkbox" ? checked : value };
    setValues(next);
    if (errors.form) setErrors((prev) => ({ ...prev, form: "" }));
    if (touched[name] && rules[name]) setErrors((prev) => ({ ...prev, [name]: rules[name](next[name]) }));
  };

  const handleBlur = (e) => {
    const { name } = e.target;
    setTouched((prev) => ({ ...prev, [name]: true }));
    setErrors((prev) => ({ ...prev, [name]: rules[name](values[name]) }));
  };

  const handleSubmit = async (e) => {
    if (loading || isAuthenticated) {
        return;
    }
    e.preventDefault();
    const nextErrors = {};
    Object.keys(rules).forEach((key) => {
      const message = rules[key](values[key]);
      if (message) nextErrors[key] = message;
    });
    setErrors(nextErrors);
    setTouched({ email: true, password: true });
    const firstBad = Object.keys(rules).find((key) => nextErrors[key]);
    if (firstBad) {
      refs[firstBad]?.current?.focus();
      return;
    }

    setSubmitting(true);
    try {
      const result = await userAuthService.login({
        email: values.email.trim(),
        password: values.password,
        remember_me: values.remember,
      });
      signIn(result.user);
      navigate(location.state?.from?.pathname || "/dashboard", { replace: true });
    } catch (error) {
        console.error("User login failed:", error);

        let message = "Unable to sign in right now. Please try again later.";

        if (error.status === 401) {
            message = "Invalid email or password.";
        } else if (error.code === "EMAIL_NOT_VERIFIED") {
            message = "Please verify your email address before logging in.";
        } else if (error.status === 403) {
            message = error.message;
        } else if (error.status === 422) {
            message = error.message;
        }

        setErrors({ form: message });
        refs.password.current?.focus();
    } finally {
      setSubmitting(false);
    }
  };

  const bad = (field) => Boolean(touched[field] && errors[field]);
  const ErrorText = ({ field }) => bad(field)
    ? <p className="auth-error" role="alert"><FaExclamationCircle /> {errors[field]}</p>
    : <div className="auth-error-slot" />;

  return (
    <section className="auth-page">
      <div className="auth-left">
        <div className="auth-overlay" />
        <div className="auth-left-content">
          <span className="auth-logo">Campus<span>X</span>change</span>
          <h1>Welcome<br />Back.</h1>
          <p>Buy, Sell and Exchange products inside your campus safely with verified students.</p>
          <div className="auth-stats">
            <div className="auth-stat-card"><h3>{formatUserStat(stats?.students)}</h3><p>Students</p></div>
            <div className="auth-stat-card"><h3>{formatUserStat(stats?.products_listed)}</h3><p>Products</p></div>
            <div className="auth-stat-card"><h3>{stats ? `${stats.verified_percent}%` : "-"}</h3><p>Verified</p></div>
          </div>
        </div>
      </div>

      <div className="auth-right">
        <div className="auth-card">
          <h2>Sign In</h2>
          <p className="auth-subtitle">Welcome back! Please login to continue.</p>

          <form onSubmit={handleSubmit} noValidate>
            <div className={`auth-input-box${bad("email") ? " auth-has-error" : ""}`}>
              <FaEnvelope className="auth-icon" />
              <input ref={refs.email} type="email" name="email" placeholder="College Email"
                value={values.email} onChange={handleChange} onBlur={handleBlur}
                className={bad("email") ? "auth-input-error" : ""} autoComplete="email" />
            </div>
            <ErrorText field="email" />

            <div className={`auth-input-box${bad("password") ? " auth-has-error" : ""}`}>
              <FaLock className="auth-icon" />
              <input ref={refs.password} type={showPassword ? "text" : "password"}
                name="password" placeholder="Password" value={values.password}
                onChange={handleChange} onBlur={handleBlur}
                className={bad("password") ? "auth-input-error" : ""}
                autoComplete="current-password" />
              <span className="auth-eye" role="button" tabIndex={0}
                aria-label={showPassword ? "Hide password" : "Show password"}
                onClick={() => setShowPassword((v) => !v)}
                onKeyDown={(e) => e.key === "Enter" && setShowPassword((v) => !v)}>
                {showPassword ? <FaEyeSlash /> : <FaEye />}
              </span>
            </div>
            <ErrorText field="password" />

            <div className="auth-remember">
              <label>
                <input type="checkbox" name="remember" checked={values.remember} onChange={handleChange} />
                Remember Me
              </label>
              <Link to="/forgot-password">Forgot Password?</Link>
            </div>

            <button type="submit" className="auth-login-btn" disabled={submitting}>
              {submitting ? "Signing in..." : "Sign In"}
            </button>

            {errors.form && <p className="auth-error" role="alert"><FaExclamationCircle /> {errors.form}</p>}
          </form>

          <div className="auth-divider"><span>OR</span></div>
          <p className="auth-bottom-text">Don't have an account? <Link to="/signup">Sign Up</Link></p>

          <div className="bottom-text">

            Return to Homepage?{" "}

            <a href="/">

                CampusXchange

            </a>

          </div>
        </div>
      </div>
    </section>
  );
}
