import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import {
  FaUser,
  FaEnvelope,
  FaLock,
  FaEye,
  FaEyeSlash,
  FaExclamationCircle,
  FaCheckCircle,
} from "react-icons/fa";
import userAuthService from "../../services/userAuthService";
import { useUserPublicStats, formatUserStat } from "../../hooks/userPublicStats";
import "./Signup.css";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;

const rules = {
  name: (v) => {
    const value = v.trim();
    if (!value) return "Enter your full name.";
    if (value.length < 3) return "Name must be at least 3 characters.";
    return "";
  },
  email: (v) => {
    const value = v.trim();
    if (!value) return "Enter your email address.";
    if (!EMAIL_RE.test(value)) return "Enter a valid email address.";
    return "";
  },
  role: (v) => (!["Student", "Faculty"].includes(v) ? "Choose Student or Faculty." : ""),
  password: (v) => {
    if (!v) return "Enter a password.";
    if (v.length < 8) return "Password must be at least 8 characters.";
    if (!/[A-Z]/.test(v)) return "Add at least one uppercase letter.";
    if (!/[a-z]/.test(v)) return "Add at least one lowercase letter.";
    if (!/[0-9]/.test(v)) return "Add at least one number.";
    if (!/[^A-Za-z0-9]/.test(v)) return "Add at least one symbol.";
    return "";
  },
  confirmPassword: (v, values) =>
    !v ? "Re-enter your password." : v !== values.password ? "Passwords do not match." : "",
  terms: (v) => (v ? "" : "Accept the Terms & Conditions to continue."),
};

const EMPTY = {
  name: "",
  email: "",
  role: "",
  password: "",
  confirmPassword: "",
  terms: false,
};

function strengthOf(password) {
  if (!password) return 0;
  let score = 0;
  if (password.length >= 8) score++;
  if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;
  return score;
}
const STRENGTH_LABEL = ["", "Weak", "Fair", "Good", "Strong"];

export default function Signup() {
  const { stats } = useUserPublicStats();
  const [values, setValues] = useState(EMPTY);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [resultMessage, setResultMessage] = useState("");
  const [resultError, setResultError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const refs = {
    name: useRef(null),
    email: useRef(null),
    role: useRef(null),
    password: useRef(null),
    confirmPassword: useRef(null),
    terms: useRef(null),
  };

  const validateAll = (vals) => {
    const next = {};
    Object.keys(rules).forEach((field) => {
      const message = rules[field](vals[field], vals);
      if (message) next[field] = message;
    });
    return next;
  };

  const handleChange = (e) => {
    const { name, type, value, checked } = e.target;
    const nextValues = {
      ...values,
      [name]: type === "checkbox" ? checked : value,
    };
    setValues(nextValues);
    setResultError("");

    if (touched[name] && rules[name]) {
      setErrors((prev) => ({
        ...prev,
        [name]: rules[name](nextValues[name], nextValues),
      }));
    }
    if (name === "password" && touched.confirmPassword) {
      setErrors((prev) => ({
        ...prev,
        confirmPassword: rules.confirmPassword(nextValues.confirmPassword, nextValues),
      }));
    }
  };

  const handleBlur = (e) => {
    const { name } = e.target;
    setTouched((prev) => ({ ...prev, [name]: true }));
    setErrors((prev) => ({
      ...prev,
      [name]: rules[name](values[name], values),
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setResultError("");
    setResultMessage("");

    const nextErrors = validateAll(values);
    setErrors(nextErrors);
    setTouched(Object.fromEntries(Object.keys(rules).map((key) => [key, true])));

    const firstBad = Object.keys(rules).find((field) => nextErrors[field]);
    if (firstBad) {
      refs[firstBad]?.current?.focus();
      return;
    }

    setSubmitting(true);
    try {
      const response = await userAuthService.signup({
        full_name: values.name.trim(),
        email: values.email.trim(),
        role: values.role,
        password: values.password,
        confirm_password: values.confirmPassword,
      });
      setResultMessage(response.message);
      setValues(EMPTY);
      setTouched({});
      setErrors({});
    } catch (error) {
      setResultError(error.message);
    } finally {
      setSubmitting(false);
    }
  };

  const bad = (field) => Boolean(touched[field] && errors[field]);
  const strength = strengthOf(values.password);

  const ErrorText = ({ field }) =>
    bad(field) ? (
      <p className="signup-error" role="alert">
        <FaExclamationCircle /> {errors[field]}
      </p>
    ) : (
      <div className="signup-error-slot" />
    );

  return (
    <section className="signup-reverse">
      <div className="signup-right">
        <div className="signup-card">
          <h2>Create Account</h2>
          <p className="signup-subtitle">
            Join CampusXchange and start buying &amp; selling with verified students.
          </p>

          {resultMessage && (
            <p className="signup-success" role="status">
              <FaCheckCircle /> {resultMessage}
            </p>
          )}
          {resultError && <p className="signup-error" role="alert"><FaExclamationCircle /> {resultError}</p>}

          <form onSubmit={handleSubmit} noValidate>
            <div className="signup-input-box">
              <FaUser className="signup-icon" />
              <input
                ref={refs.name}
                name="name"
                type="text"
                placeholder="Full Name"
                value={values.name}
                onChange={handleChange}
                onBlur={handleBlur}
                className={bad("name") ? "signup-input-error" : ""}
                autoComplete="name"
              />
            </div>
            <ErrorText field="name" />

            <div className="signup-input-box">
              <FaEnvelope className="signup-icon" />
              <input
                ref={refs.email}
                name="email"
                type="email"
                placeholder="College Email"
                value={values.email}
                onChange={handleChange}
                onBlur={handleBlur}
                className={bad("email") ? "signup-input-error" : ""}
                autoComplete="email"
              />
            </div>
            <ErrorText field="email" />

            <div className="signup-input-box">
              <FaUser className="signup-icon" />
              <select
                ref={refs.role}
                name="role"
                value={values.role}
                onChange={handleChange}
                onBlur={handleBlur}
                className={bad("role") ? "signup-input-error" : ""}
                aria-label="Account type"
              >
                <option value="">Select account type</option>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
              </select>
            </div>
            <ErrorText field="role" />

            <div className="signup-input-box">
              <FaLock className="signup-icon" />
              <input
                ref={refs.password}
                name="password"
                type={showPassword ? "text" : "password"}
                placeholder="Password"
                value={values.password}
                onChange={handleChange}
                onBlur={handleBlur}
                className={bad("password") ? "signup-input-error" : ""}
                autoComplete="new-password"
              />
              <span className="signup-eye" role="button" tabIndex={0}
                aria-label={showPassword ? "Hide password" : "Show password"}
                onClick={() => setShowPassword((v) => !v)}
                onKeyDown={(e) => e.key === "Enter" && setShowPassword((v) => !v)}>
                {showPassword ? <FaEyeSlash /> : <FaEye />}
              </span>
            </div>
            {values.password && (
              <div className="signup-strength" data-level={strength}>
                <div className="signup-strength-track"><span /><span /><span /><span /></div>
                <p className="signup-strength-label">Password strength: {STRENGTH_LABEL[strength]}</p>
              </div>
            )}
            <ErrorText field="password" />

            <div className="signup-input-box">
              <FaLock className="signup-icon" />
              <input
                ref={refs.confirmPassword}
                name="confirmPassword"
                type={showConfirmPassword ? "text" : "password"}
                placeholder="Confirm Password"
                value={values.confirmPassword}
                onChange={handleChange}
                onBlur={handleBlur}
                className={bad("confirmPassword") ? "signup-input-error" : ""}
                autoComplete="new-password"
              />
              <span className="signup-eye" role="button" tabIndex={0}
                aria-label={showConfirmPassword ? "Hide password" : "Show password"}
                onClick={() => setShowConfirmPassword((v) => !v)}
                onKeyDown={(e) => e.key === "Enter" && setShowConfirmPassword((v) => !v)}>
                {showConfirmPassword ? <FaEyeSlash /> : <FaEye />}
              </span>
            </div>
            <ErrorText field="confirmPassword" />

            <div className={`signup-remember${bad("terms") ? " signup-has-error" : ""}`}>
              <label>
                <input ref={refs.terms} type="checkbox" name="terms" checked={values.terms}
                  onChange={handleChange} onBlur={handleBlur} />
                I agree to Terms &amp; Conditions
              </label>
            </div>
            <ErrorText field="terms" />

            <button type="submit" className="signup-login-btn" disabled={submitting}>
              {submitting ? "Creating account..." : "Create Account"}
            </button>
          </form>

          <div className="signup-divider"><span>OR</span></div>
          <p className="signup-bottom-text">
            Already have an account? <Link to="/login">Sign In</Link>
          </p>

          <div className="bottom-text">

            Return to Homepage?{" "}

            <a href="/">

                CampusXchange

            </a>

          </div>
        </div>
      </div>

      <div className="signup-left">
        <div className="signup-overlay" />
        <div className="signup-left-content">
          <span className="signup-logo">Campus<span>X</span>change</span>
          <h1>Join <br />CampusXchange</h1>
          <p>Create your account and start buying, selling and exchanging products safely within your college community.</p>
          <div className="signup-stats">
            <div className="signup-stat-card"><h3>{formatUserStat(stats?.students)}</h3><p>Students</p></div>
            <div className="signup-stat-card"><h3>{formatUserStat(stats?.products_listed)}</h3><p>Products</p></div>
            <div className="signup-stat-card"><h3>{stats ? `${stats.verified_percent}%` : "-"}</h3><p>Verified</p></div>
          </div>
        </div>
      </div>
    </section>
  );
}
