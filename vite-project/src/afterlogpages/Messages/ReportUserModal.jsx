import React, { useEffect, useState } from "react";
import UserModal from "../components/UserModal";
import { reportUserFromChat } from "../../services/userChatService";

const CATEGORIES = [
  "Harassment",
  "Scam/Fraud",
  "Inappropriate Behavior",
  "Spam",
  "Other",
];

const MAX_SHOTS = 2;
const MAX_BYTES = 5 * 1024 * 1024;

/* Report the person you are chatting with. 1 or 2 screenshots are required. */
function ReportUserModal({ open, chatId, userName, onClose, onDone }) {
  const [category, setCategory] = useState("");
  const [reason, setReason] = useState("");
  const [description, setDescription] = useState("");
  const [shots, setShots] = useState([]); // [{ file, url }]
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const clearShots = () => {
    setShots((prev) => {
      prev.forEach((s) => URL.revokeObjectURL(s.url));
      return [];
    });
  };

  const reset = () => {
    setCategory("");
    setReason("");
    setDescription("");
    clearShots();
    setError("");
  };

  // free the preview URLs when the modal goes away
  useEffect(() => () => shots.forEach((s) => URL.revokeObjectURL(s.url)), []); // eslint-disable-line

  const close = () => {
    if (busy) return;
    reset();
    onClose();
  };

  const onPick = (e) => {
    const picked = Array.from(e.target.files || []);
    e.target.value = "";
    if (!picked.length) return;

    if (picked.some((f) => !["image/jpeg", "image/png", "image/webp"].includes(f.type))) {
      setError("Screenshots must be JPG, PNG or WebP images.");
      return;
    }
    if (picked.some((f) => f.size > MAX_BYTES)) {
      setError("Each screenshot must be 5MB or less.");
      return;
    }
    if (shots.length + picked.length > MAX_SHOTS) {
      setError(`You can attach up to ${MAX_SHOTS} screenshots.`);
      return;
    }

    setError("");
    setShots((prev) => [
      ...prev,
      ...picked.map((file) => ({ file, url: URL.createObjectURL(file) })),
    ]);
  };

  const removeShot = (index) => {
    setShots((prev) => {
      URL.revokeObjectURL(prev[index].url);
      return prev.filter((_, i) => i !== index);
    });
  };

  const submit = async () => {
    if (!category) return setError("Choose a report category.");
    if (!reason.trim()) return setError("Please give a short reason.");
    if (shots.length === 0) return setError("Attach at least one screenshot as evidence.");

    const body = new FormData();
    body.append("chat_id", chatId);
    body.append("report_category", category);
    body.append("reason", reason.trim());
    body.append("description", description.trim());
    shots.forEach((s) => body.append("evidence[]", s.file));

    setBusy(true);
    setError("");
    try {
      const res = await reportUserFromChat(body);
      reset();
      onDone(res.message);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <UserModal
      open={open}
      title={`Report ${userName || "this user"}`}
      confirmText="Submit report"
      onConfirm={submit}
      onClose={close}
      loading={busy}
      error={error}
    >
      <p className="cx-report-intro">
        Tell us what happened. Our team reviews every report. Please attach 1 or 2
        screenshots of the problem.
      </p>

      <label className="cx-report-label">Category</label>
      <select
        className="cx-report-input"
        value={category}
        onChange={(e) => setCategory(e.target.value)}
      >
        <option value="">Select a category</option>
        {CATEGORIES.map((c) => (
          <option key={c}>{c}</option>
        ))}
      </select>

      <label className="cx-report-label">Short reason</label>
      <input
        type="text"
        className="cx-report-input"
        maxLength={255}
        placeholder="e.g. Asked me to pay outside the app"
        value={reason}
        onChange={(e) => setReason(e.target.value)}
      />

      <label className="cx-report-label">More details (optional)</label>
      <textarea
        className="cx-report-input"
        rows={3}
        maxLength={3000}
        placeholder="Describe what happened"
        value={description}
        onChange={(e) => setDescription(e.target.value)}
      />

      <label className="cx-report-label">
        Screenshots (required, 1 to {MAX_SHOTS})
      </label>

      <div className="cx-report-shots">
        {shots.map((s, i) => (
          <div className="cx-report-shot" key={s.url}>
            <img src={s.url} alt={`Screenshot ${i + 1}`} />
            <button type="button" onClick={() => removeShot(i)} aria-label="Remove screenshot">
              ×
            </button>
          </div>
        ))}

        {shots.length < MAX_SHOTS && (
          <label className="cx-report-add">
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              multiple={MAX_SHOTS - shots.length > 1}
              onChange={onPick}
              hidden
            />
            <span>+</span>
            <small>Add screenshot</small>
          </label>
        )}
      </div>
      <small className="cx-report-hint">JPG, PNG or WebP, up to 5MB each.</small>
    </UserModal>
  );
}

export default ReportUserModal;
