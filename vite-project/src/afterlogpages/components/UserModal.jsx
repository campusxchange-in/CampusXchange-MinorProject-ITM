import React, { useEffect } from "react";
import "./UserModal.css";

/*
 * Reusable modal for every confirmation / notice on the logged-in side.
 * (No window.alert / window.confirm anywhere – use this instead.)
 *
 * Props:
 *  open, title, children
 *  confirmText  – label of the main button (omit for a notice-only modal)
 *  cancelText   – default "Cancel" (notice modal shows only "OK")
 *  onConfirm, onClose
 *  danger       – red confirm button
 *  loading      – disables buttons and shows "Please wait..."
 *  confirmDisabled – e.g. until the user types DELETE
 *  error        – message shown inside the modal
 */
function UserModal({
  open,
  title,
  children,
  confirmText,
  cancelText = "Cancel",
  onConfirm,
  onClose,
  danger = false,
  loading = false,
  confirmDisabled = false,
  error = "",
}) {
  useEffect(() => {
    if (!open) return undefined;
    const onKey = (e) => {
      if (e.key === "Escape" && !loading) onClose?.();
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open, loading, onClose]);

  if (!open) return null;

  const noticeOnly = !confirmText;

  return (
    <div
      className="user-modal-overlay"
      onMouseDown={() => !loading && onClose?.()}
    >
      <div
        className="user-modal"
        role="dialog"
        aria-modal="true"
        onMouseDown={(e) => e.stopPropagation()}
      >
        <h2 className={danger ? "user-modal-title danger" : "user-modal-title"}>
          {title}
        </h2>

        <div className="user-modal-body">{children}</div>

        {error && <p className="user-modal-error">{error}</p>}

        <div className="user-modal-actions">
          {!noticeOnly && (
            <button
              type="button"
              className="user-modal-btn secondary"
              onClick={onClose}
              disabled={loading}
            >
              {cancelText}
            </button>
          )}

          <button
            type="button"
            className={`user-modal-btn ${danger ? "danger" : "primary"}`}
            onClick={noticeOnly ? onClose : onConfirm}
            disabled={loading || (!noticeOnly && confirmDisabled)}
          >
            {loading ? "Please wait..." : noticeOnly ? "OK" : confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}

export default UserModal;
