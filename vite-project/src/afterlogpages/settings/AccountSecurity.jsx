import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  FaLock,
  FaShieldAlt,
  FaClock,
  FaEye,
  FaEyeSlash,
  FaCheckCircle,
  FaExclamationCircle,
  FaInfoCircle,
  FaSignOutAlt,
  FaTrashAlt,
  FaEnvelope,
} from "react-icons/fa";

import "./AccountSecurity.css";
import Navbar from "../components/Navbar";
import SettingsSidebar from "../components/SettingsSidebar";
import UserModal from "../components/UserModal";
import { useAuth } from "../../context/AuthContext";
import {
  fetchUserSecurity,
  fetchUserProfile,
  sendUserEmailOtp,
  verifyUserEmailOtp,
  changeUserPassword,
  logoutUserOtherDevices,
  deleteUserAccount,
} from "../../services/userMarketService";
import { userTimeAgo } from "../../utils/userFormat";

const AccountSecurity = () => {
  // =========================
  // PASSWORD STATES
  // =========================
  const [showCurrentPassword, setShowCurrentPassword] =
    useState(false);

  const [showNewPassword, setShowNewPassword] =
    useState(false);

  const [showConfirmPassword, setShowConfirmPassword] =
    useState(false);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const [passwordMessage, setPasswordMessage] = useState("");
  const [passwordSuccess, setPasswordSuccess] = useState(false);

  const navigate = useNavigate();
  const { signOut } = useAuth();

  // =========================
  // LOGIN ACTIVITY (from database)
  // =========================
  const [activities, setActivities] = useState([]);
  const [activityError, setActivityError] = useState("");
  const [sessionMessage, setSessionMessage] = useState("");
  const [logoutModal, setLogoutModal] = useState(false);
  const [logoutBusy, setLogoutBusy] = useState(false);
  const [logoutError, setLogoutError] = useState("");
  const [passwordBusy, setPasswordBusy] = useState(false);

  const loadActivity = () =>
    fetchUserSecurity()
      .then((d) => {
        setActivities(d.activities);
        setActivityError("");
      })
      .catch((e) => setActivityError(e.message));

  useEffect(() => {
    loadActivity();
  }, []);

  // =========================
  // CHANGE EMAIL (password + OTP sent to the NEW address)
  // The current email stays active until the OTP is verified.
  // =========================
  const [currentEmail, setCurrentEmail] = useState("");
  const [currentEmailVerified, setCurrentEmailVerified] = useState(false);
  const [pendingEmail, setPendingEmail] = useState("");
  const [newEmail, setNewEmail] = useState("");
  const [emailPassword, setEmailPassword] = useState("");
  const [showEmailPassword, setShowEmailPassword] = useState(false);
  const [emailStage, setEmailStage] = useState("form"); // "form" | "otp"
  const [emailOtp, setEmailOtp] = useState("");
  const [emailBusy, setEmailBusy] = useState(false);
  const [emailMessage, setEmailMessage] = useState("");
  const [emailSuccess, setEmailSuccess] = useState(false);

  const loadEmail = () =>
    fetchUserProfile()
      .then((d) => {
        setCurrentEmail(d.user.email || "");
        setCurrentEmailVerified(Boolean(d.user.email_verified));
        if (d.user.pending_email) {
          // an OTP was already sent earlier - let the user finish it
          setPendingEmail(d.user.pending_email);
          setNewEmail((prev) => prev || d.user.pending_email);
          setEmailStage("otp");
        } else {
          setPendingEmail("");
          setEmailStage("form");
        }
      })
      .catch((e) => {
        setEmailSuccess(false);
        setEmailMessage(e.message);
      });

  useEffect(() => {
    loadEmail();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleSendEmailOtp = async (e) => {
    if (e) e.preventDefault();

    setEmailMessage("");
    setEmailSuccess(false);

    const candidate = newEmail.trim().toLowerCase();

    if (!candidate || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(candidate)) {
      setEmailMessage("Please enter a valid email address.");
      return;
    }

    if (candidate === currentEmail.toLowerCase() && currentEmailVerified) {
      setEmailMessage("This is already your current verified email address.");
      return;
    }

    if (!emailPassword) {
      setEmailMessage("Please enter your current password.");
      return;
    }

    setEmailBusy(true);

    try {
      const res = await sendUserEmailOtp(candidate, emailPassword);
      setPendingEmail(res.email || candidate);
      setNewEmail(res.email || candidate);
      setEmailPassword("");
      setEmailOtp("");
      setEmailStage("otp");
      setEmailSuccess(true);
      setEmailMessage(res.message);
    } catch (err) {
      setEmailMessage(err.message);
    } finally {
      setEmailBusy(false);
    }
  };

  const handleVerifyEmailOtp = async (e) => {
    if (e) e.preventDefault();

    setEmailMessage("");
    setEmailSuccess(false);

    if (emailOtp.length !== 6) {
      setEmailMessage("Please enter the 6-digit OTP.");
      return;
    }

    setEmailBusy(true);

    try {
      await verifyUserEmailOtp(emailOtp);
      setEmailOtp("");
      setNewEmail("");
      await loadEmail();
      loadActivity();
      setEmailSuccess(true);
      setEmailMessage("Your email address was verified and updated.");
    } catch (err) {
      setEmailMessage(err.message);
    } finally {
      setEmailBusy(false);
    }
  };

  const handleChangeEmailAgain = () => {
    // back to the form (new email stays filled in) to resend the OTP
    setEmailStage("form");
    setEmailOtp("");
    setEmailMessage("");
    setEmailSuccess(false);
  };

  // =========================
  // DELETE ACCOUNT (modal, must type DELETE)
  // =========================
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteText, setDeleteText] = useState("");
  const [deleteBusy, setDeleteBusy] = useState(false);
  const [deleteError, setDeleteError] = useState("");

  // =========================
  // PASSWORD STRENGTH
  // =========================
  const getPasswordStrength = () => {
    if (!newPassword) {
      return {
        text: "",
        className: "",
      };
    }

    if (newPassword.length < 8) {
      return {
        text: "Weak",
        className: "weak",
      };
    }

    if (
      newPassword.length >= 8 &&
      /[A-Z]/.test(newPassword) &&
      /[0-9]/.test(newPassword)
    ) {
      return {
        text: "Strong",
        className: "strong",
      };
    }

    return {
      text: "Medium",
      className: "medium",
    };
  };

  const passwordStrength = getPasswordStrength();

  // =========================
  // CHANGE PASSWORD
  // =========================
  const handleChangePassword = async (e) => {
    e.preventDefault();

    setPasswordMessage("");
    setPasswordSuccess(false);

    if (!currentPassword.trim()) {
      setPasswordMessage("Please enter your current password.");
      return;
    }

    if (!newPassword.trim()) {
      setPasswordMessage("Please enter a new password.");
      return;
    }

    if (newPassword.length < 8) {
      setPasswordMessage("New password must contain at least 8 characters.");
      return;
    }

    if (newPassword !== confirmPassword) {
      setPasswordMessage("New password and confirm password do not match.");
      return;
    }

    if (currentPassword === newPassword) {
      setPasswordMessage("New password must be different from your current password.");
      return;
    }

    setPasswordBusy(true);

    try {
      const res = await changeUserPassword(currentPassword, newPassword, confirmPassword);
      setPasswordSuccess(true);
      setPasswordMessage(res.message);
      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      loadActivity();
    } catch (err) {
      setPasswordMessage(err.message);
    } finally {
      setPasswordBusy(false);
    }
  };

  // =========================
  // LOGOUT OTHER DEVICES
  // =========================
  const handleLogoutOtherDevices = async () => {
    setLogoutBusy(true);
    setLogoutError("");

    try {
      const res = await logoutUserOtherDevices();
      setLogoutModal(false);
      setSessionMessage(res.message);
      loadActivity();
      setTimeout(() => setSessionMessage(""), 4000);
    } catch (err) {
      setLogoutError(err.message);
    } finally {
      setLogoutBusy(false);
    }
  };

  // =========================
  // DELETE ACCOUNT
  // =========================
  const closeDeleteModal = () => {
    if (deleteBusy) return;
    setShowDeleteModal(false);
    setDeleteText("");
    setDeleteError("");
  };

  const handleDeleteAccount = async () => {
    if (deleteText !== "DELETE") return;

    setDeleteBusy(true);
    setDeleteError("");

    try {
      await deleteUserAccount(deleteText);
      signOut();
      navigate("/", { replace: true });
    } catch (err) {
      setDeleteError(err.message);
      setDeleteBusy(false);
    }
  };

  return (
  <>
    <Navbar />
    <SettingsSidebar activeItem="Account & Security" />

    <main className="account-security-page">

      {/* =========================
          PAGE HEADER
      ========================= */}
      <div className="security-page-header">

        <div className="security-header-icon">
          <FaShieldAlt />
        </div>

        <div>
          <h1>Account & Security</h1>

          <p>
            Manage your login credentials, security settings
            and account protection.
          </p>
        </div>

      </div>


      {/* =========================
          MAIN GRID
      ========================= */}
      <div className="security-grid">


        {/* =================================
            PASSWORD & LOGIN
        ================================= */}
        <section className="security-card password-card">

          <div className="security-card-heading">

            <div className="security-icon">
              <FaLock />
            </div>

            <div>
              <h2>Password & Login</h2>

              <p>
                Change your password to keep your
                account secure.
              </p>
            </div>

          </div>


          <form onSubmit={handleChangePassword}>

            {/* CURRENT PASSWORD */}
            <div className="security-field">

              <label>Current Password</label>

              <div className="password-input">

                <input
                  type={
                    showCurrentPassword
                      ? "text"
                      : "password"
                  }
                  placeholder="Enter current password"
                  value={currentPassword}
                  onChange={(e) =>
                    setCurrentPassword(e.target.value)
                  }
                />

                <button
                  type="button"
                  onClick={() =>
                    setShowCurrentPassword(
                      !showCurrentPassword
                    )
                  }
                >
                  {showCurrentPassword ? (
                    <FaEyeSlash />
                  ) : (
                    <FaEye />
                  )}
                </button>

              </div>

            </div>


            {/* NEW PASSWORD */}
            <div className="security-field">

              <label>New Password</label>

              <div className="password-input">

                <input
                  type={
                    showNewPassword
                      ? "text"
                      : "password"
                  }
                  placeholder="Enter new password"
                  value={newPassword}
                  onChange={(e) =>
                    setNewPassword(e.target.value)
                  }
                />

                <button
                  type="button"
                  onClick={() =>
                    setShowNewPassword(
                      !showNewPassword
                    )
                  }
                >
                  {showNewPassword ? (
                    <FaEyeSlash />
                  ) : (
                    <FaEye />
                  )}
                </button>

              </div>

              {passwordStrength.text && (
                <div
                  className={`password-strength ${passwordStrength.className}`}
                >
                  Password strength:
                  <strong>
                    {passwordStrength.text}
                  </strong>
                </div>
              )}

            </div>


            {/* CONFIRM PASSWORD */}
            <div className="security-field">

              <label>Confirm New Password</label>

              <div className="password-input">

                <input
                  type={
                    showConfirmPassword
                      ? "text"
                      : "password"
                  }
                  placeholder="Confirm new password"
                  value={confirmPassword}
                  onChange={(e) =>
                    setConfirmPassword(
                      e.target.value
                    )
                  }
                />

                <button
                  type="button"
                  onClick={() =>
                    setShowConfirmPassword(
                      !showConfirmPassword
                    )
                  }
                >
                  {showConfirmPassword ? (
                    <FaEyeSlash />
                  ) : (
                    <FaEye />
                  )}
                </button>

              </div>

            </div>


            <button
              type="submit"
              className="update-password-btn"
              disabled={passwordBusy}
            >
              {passwordBusy ? "Updating..." : "Update Password"}
            </button>


            {passwordMessage && (
              <div
                className={`password-message ${
                  passwordSuccess
                    ? "success"
                    : "error"
                }`}
              >
                {passwordSuccess ? (
                  <FaCheckCircle />
                ) : (
                  <FaExclamationCircle />
                )}

                <span>{passwordMessage}</span>
              </div>
            )}

          </form>


          <button
            className="forgot-password-btn"
            onClick={() => navigate("/forgot-password")}
          >
            Forgot your password? <span>Reset it</span>
          </button>

        </section>


        {/* =================================
            CHANGE EMAIL
        ================================= */}
        <section className="security-card email-card">

          <div className="security-card-heading">

            <div className="security-icon">
              <FaEnvelope />
            </div>

            <div>
              <h2>Change Email Address</h2>

              <p>
                Your email only changes after you verify the
                new address with an OTP.
              </p>
            </div>

          </div>


          <div className="email-info-note">
            <FaInfoCircle />
            <span>
              We send a 6-digit OTP to your new email address. Your
              current email stays active until it is verified.
            </span>
          </div>


          {emailStage === "otp" && pendingEmail && (
            <div className="pending-email-notice">
              <FaClock />
              <span>
                Verification pending for <strong>{pendingEmail}</strong>.
                Your current email is still <strong>{currentEmail}</strong>.
              </span>
            </div>
          )}


          {emailStage === "form" ? (

            <form onSubmit={handleSendEmailOtp}>

              <div className="security-field">

                <label>Current Email</label>

                <div className="password-input">
                  <input
                    type="email"
                    value={currentEmail}
                    readOnly
                    className="readonly-input"
                  />
                </div>

              </div>


              <div className="security-field">

                <label>New Email Address</label>

                <div className="password-input">
                  <input
                    type="email"
                    placeholder="Enter new email address"
                    value={newEmail}
                    autoComplete="email"
                    onChange={(e) => {
                      setNewEmail(e.target.value);
                      setEmailMessage("");
                    }}
                  />
                </div>

              </div>


              <div className="security-field">

                <label>Confirm Your Current Password</label>

                <div className="password-input">

                  <input
                    type={showEmailPassword ? "text" : "password"}
                    placeholder="Enter current password"
                    value={emailPassword}
                    autoComplete="current-password"
                    onChange={(e) => {
                      setEmailPassword(e.target.value);
                      setEmailMessage("");
                    }}
                  />

                  <button
                    type="button"
                    onClick={() => setShowEmailPassword(!showEmailPassword)}
                  >
                    {showEmailPassword ? <FaEyeSlash /> : <FaEye />}
                  </button>

                </div>

              </div>


              <button
                type="submit"
                className="update-password-btn"
                disabled={emailBusy}
              >
                {emailBusy ? "Sending OTP..." : "Send OTP to New Email"}
              </button>

            </form>

          ) : (

            <form onSubmit={handleVerifyEmailOtp}>

              <div className="security-field">

                <label>Enter the 6-digit OTP sent to {pendingEmail}</label>

                <div className="password-input">
                  <input
                    type="text"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    maxLength={6}
                    placeholder="Enter 6-digit OTP"
                    value={emailOtp}
                    onChange={(e) => {
                      setEmailOtp(e.target.value.replace(/\D/g, ""));
                      setEmailMessage("");
                    }}
                  />
                </div>

              </div>


              <button
                type="submit"
                className="update-password-btn"
                disabled={emailBusy}
              >
                {emailBusy ? "Verifying..." : "Verify & Update Email"}
              </button>


              <button
                type="button"
                className="forgot-password-btn"
                onClick={handleChangeEmailAgain}
              >
                Didn't get the OTP? <span>Resend or use a different email</span>
              </button>

            </form>

          )}


          {emailMessage && (
            <div
              className={`password-message ${emailSuccess ? "success" : "error"}`}
            >
              {emailSuccess ? <FaCheckCircle /> : <FaExclamationCircle />}

              <span>{emailMessage}</span>
            </div>
          )}

        </section>


        {/* =================================
            LOGIN ACTIVITY
        ================================= */}
        <section className="security-card login-activity-card">

          <div className="security-card-heading">

            <div className="security-icon">
              <FaClock />
            </div>

            <div>
              <h2>Login Activity</h2>

              <p>Recent sign-ins and security events on your account.</p>
            </div>

          </div>


          <div className="session-table">

            <div className="session-header">
              <span>Activity</span>
              <span>Details</span>
              <span>When</span>
            </div>

            {activityError && <p style={{ color: "#dc2626", padding: 8 }}>{activityError}</p>}
            {!activityError && activities.length === 0 && (
              <p style={{ padding: 8 }}>No activity recorded yet.</p>
            )}

            {activities.map((a, index) => (
              <div className="session-row" key={a.id}>
                <div className="session-device">
                  <div className="browser-circle">🔐</div>
                  <div>
                    <strong>{a.title}</strong>
                    <small>{a.type}</small>
                  </div>
                </div>

                <div className="session-location">{a.description}</div>

                <div className="session-active">
                  <span className={index === 0 ? "active-dot" : "inactive-dot"}></span>
                  {userTimeAgo(a.created_at)}
                </div>
              </div>
            ))}
          </div>


          <div className="session-actions">

            <button
              className="logout-devices-btn"
              onClick={() => {
                setLogoutError("");
                setLogoutModal(true);
              }}
            >
              <FaSignOutAlt />
              Log Out Other Devices
            </button>

          </div>


          {sessionMessage && (
            <div className="session-success">
              <FaCheckCircle />
              {sessionMessage}
            </div>
          )}

        </section>



        {/* =================================
            DANGER ZONE
        ================================= */}
        <section className="security-card danger-zone">

          <div className="danger-heading">

            <div className="danger-icon">
              <FaExclamationCircle />
            </div>

            <div>
              <h2>Danger Zone</h2>

              <p>
                These actions cannot be easily
                reversed.
              </p>
            </div>

          </div>


          <div className="delete-account-box">

            <div className="delete-icon">
              <FaTrashAlt />
            </div>

            <div className="delete-content">

              <strong>
                Delete Account
              </strong>

              <span>
                Permanently delete your
                CampusXchange account and
                associated data.
              </span>

            </div>

          </div>


          <button
            className="delete-account-btn"
            onClick={() => setShowDeleteModal(true)}
          >
            Delete Account
          </button>
        </section>

      </div>

        </main>

    <UserModal
      open={logoutModal}
      title="Log out other devices?"
      confirmText="Log out other devices"
      onConfirm={handleLogoutOtherDevices}
      onClose={() => setLogoutModal(false)}
      loading={logoutBusy}
      error={logoutError}
    >
      Every other browser or phone signed in to your account will be logged out. This
      device stays signed in.
    </UserModal>

    <UserModal
      open={showDeleteModal}
      title="Delete your account?"
      danger
      confirmText="Delete my account"
      onConfirm={handleDeleteAccount}
      onClose={closeDeleteModal}
      loading={deleteBusy}
      confirmDisabled={deleteText !== "DELETE"}
      error={deleteError}
    >
      <p style={{ margin: 0 }}>
        Your account will be disabled and your listings removed from the marketplace.
        Messages and order history are kept for safety and dispute records. You will be
        signed out immediately.
      </p>
      <p style={{ margin: "12px 0 0" }}>
        To confirm, type <strong>DELETE</strong> (in capital letters) below.
      </p>
      <input
        type="text"
        value={deleteText}
        autoComplete="off"
        placeholder="DELETE"
        onChange={(e) => setDeleteText(e.target.value)}
      />
    </UserModal>
  </>
  );
};

export default AccountSecurity;