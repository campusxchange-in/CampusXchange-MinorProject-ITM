import React, { useEffect, useState } from "react";
import UserModal from "./UserModal";
import { createUserProductReport } from "../../services/userMarketService";
import "./UserReportModal.css";

const CATEGORIES = [
  "Scam/Fraud",
  "Fake/Misleading Listing",
  "Prohibited Product",
  "Inappropriate Behavior",
  "Spam",
  "Other",
];

// one-tap reasons (fills the "short reason" box; the user can still edit it)
const QUICK_REASONS = [
  "Looks like a stolen item",
  "Inappropriate or offensive listing",
  "Fake or misleading photos",
  "Prohibited item for sale",
  "Seller asked for advance payment",
];

const MAX_SHOTS = 5;
const MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED = ["image/jpeg", "image/png", "image/webp"];

/* Report a product listing. 1 to 5 screenshots are required as evidence. */
function UserReportModal({ open, product, onClose, onDone }) {
  const [category, setCategory] = useState("");
  const [reason, setReason] = useState("");
  const [description, setDescription] = useState("");
  const [shots, setShots] = useState([]); // [{ file, url }]
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const reset = () => {
    setCategory("");
    setReason("");
    setDescription("");
    setShots((prev) => {
      prev.forEach((s) => URL.revokeObjectURL(s.url));
      return [];
    });
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

    if (picked.some((f) => !ALLOWED.includes(f.type))) {
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
    body.append("product_id", product.id);
    body.append("report_category", category);
    body.append("reason", reason.trim());
    body.append("description", description.trim());
    shots.forEach((s) => body.append("evidence[]", s.file));

    setBusy(true);
    setError("");
    try {
      const res = await createUserProductReport(body);
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
      title="Report this listing"
      confirmText="Submit report"
      onConfirm={submit}
      onClose={close}
      loading={busy}
      error={error}
    >
      <p className="pr-report-intro">
        Reporting <strong>{product?.name || "this product"}</strong>. Tell us
        what is wrong and attach screenshots as proof. Our admin team reviews
        every report.
      </p>

      <label className="pr-report-label">Category</label>
      <select
        className="pr-report-input"
        value={category}
        onChange={(e) => setCategory(e.target.value)}
      >
        <option value="">Select a category</option>
        {CATEGORIES.map((c) => (
          <option key={c}>{c}</option>
        ))}
      </select>

      <label className="pr-report-label">Short reason</label>
      <div className="pr-report-chips">
        {QUICK_REASONS.map((q) => (
          <button
            type="button"
            key={q}
            className={reason === q ? "pr-report-chip active" : "pr-report-chip"}
            onClick={() => setReason(q)}
          >
            {q}
          </button>
        ))}
      </div>
      <input
        type="text"
        className="pr-report-input"
        maxLength={255}
        placeholder="Or write your own reason"
        value={reason}
        onChange={(e) => setReason(e.target.value)}
      />

      <label className="pr-report-label">More details (optional)</label>
      <textarea
        className="pr-report-input"
        rows={3}
        maxLength={3000}
        placeholder="Describe what is wrong with this listing"
        value={description}
        onChange={(e) => setDescription(e.target.value)}
      />

      <label className="pr-report-label">
        Screenshots (required, 1 to {MAX_SHOTS})
      </label>

      <div className="pr-report-shots">
        {shots.map((s, i) => (
          <div className="pr-report-shot" key={s.url}>
            <img src={s.url} alt={`Screenshot ${i + 1}`} />
            <button
              type="button"
              onClick={() => removeShot(i)}
              aria-label="Remove screenshot"
            >
              ×
            </button>
          </div>
        ))}

        {shots.length < MAX_SHOTS && (
          <label className="pr-report-add">
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              multiple
              onChange={onPick}
              hidden
            />
            <span>+</span>
            <small>Add screenshot</small>
          </label>
        )}
      </div>
      <small className="pr-report-hint">JPG, PNG or WebP, up to 5MB each.</small>
    </UserModal>
  );
}

export default UserReportModal;
