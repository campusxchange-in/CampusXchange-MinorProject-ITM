import React, { useState, useCallback, useEffect, useRef } from "react";
import { BrowserMultiFormatReader } from "@zxing/browser";
import { DecodeHintType, BarcodeFormat } from "@zxing/library";

import {
  FaUser,
  FaGraduationCap,
  FaMapMarkerAlt,
  FaEnvelope,
  FaPhone,
  FaEdit,
  FaShieldAlt,
  FaCheckCircle,
  FaRegCircle,
  FaIdCard,
  FaUniversity,
  FaCloudUploadAlt,
  FaLock,
  FaInfoCircle,
  FaArrowRight,
  FaExclamationCircle,
  FaMobile,
  FaCamera,
  FaSyncAlt
  
} from "react-icons/fa";

import "./Profile.css";
import Navbar from "../components/Navbar";
import SettingsSidebar from "../components/SettingsSidebar";
import UserModal from "../components/UserModal";
import {
  fetchUserProfile,
  updateUserProfileSection,
  sendUserEmailOtp,
  verifyUserEmailOtp,
  submitUserVerification,
} from "../../services/userMarketService";
import { userInitial } from "../../utils/userFormat";


/* =====================================================
   ID CARD ROLL NUMBER SCAN (barcode first, text fallback)
===================================================== */

// "MCAN1CA 25038" -> "MCAN1CA25038"
const normalize = (value) =>
  String(value || "")
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, "");

const loadImage = (file) =>
  new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error("image"));
    };
    img.src = url;
  });

// Draws part of the image on a canvas (optionally enlarged + high contrast)
const makeCanvas = (
  img,
  { cropTop = 0, scale = 1, contrast = false, smooth = true }
) => {
  const sy = Math.floor(img.height * cropTop);
  const sh = img.height - sy;
  const maxW = 1800;
  const k = Math.min(scale, maxW / img.width);

  const canvas = document.createElement("canvas");
  canvas.width = Math.round(img.width * k);
  canvas.height = Math.round(sh * k);

  const ctx = canvas.getContext("2d", { willReadFrequently: true });
  ctx.imageSmoothingEnabled = smooth;
  ctx.imageSmoothingQuality = "high";
  ctx.drawImage(img, 0, sy, img.width, sh, 0, 0, canvas.width, canvas.height);

  if (contrast) {
    const data = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const px = data.data;
    for (let i = 0; i < px.length; i += 4) {
      const g = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
      const v = g < 140 ? 0 : 255;
      px[i] = px[i + 1] = px[i + 2] = v;
    }
    ctx.putImageData(data, 0, 0);
  }

  return canvas;
};

async function readBarcode(file) {
  let img;
  try {
    img = await loadImage(file);
  } catch {
    return null;
  }

  const hints = new Map();
  hints.set(DecodeHintType.TRY_HARDER, true);
  hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.CODE_128,
    BarcodeFormat.CODE_39,
    BarcodeFormat.CODE_93,
    BarcodeFormat.ITF,
    BarcodeFormat.EAN_13,
    BarcodeFormat.QR_CODE,
  ]);

  const reader = new BrowserMultiFormatReader(hints);

  // Several tries: small photos often need enlarging, and cropping to the
  // lower half removes the photo / text that confuses the reader.
  const attempts = [
    { scale: 1 },
    { scale: 2 },
    { scale: 3 },
    { scale: 2, cropTop: 0.5 },
    { scale: 3, cropTop: 0.5 },
    { scale: 2, contrast: true },
    { scale: 3, cropTop: 0.5, contrast: true },
    // crisp (nearest-neighbour) enlargement keeps thin bars sharp on small photos
    { scale: 4, cropTop: 0.5, smooth: false },
    { scale: 3, cropTop: 0.55, smooth: false, contrast: true },
  ];

  for (const options of attempts) {
    try {
      const result = reader.decodeFromCanvas(makeCanvas(img, options));
      const text = result.getText();
      if (text) return text;
    } catch {
      // not found in this attempt, try the next one
    }
  }

  return null;
}

/**
 * Checks the roll number typed by the user against the BARCODE on the
 * ID card photo.
 *
 * Only the barcode is trusted. Printed text on a card photo can be edited
 * easily, so it is never used to approve a roll number.
 *
 * Returns { matched, reason }
 *  - reason (when not matched): "mismatch" | "nobarcode" | "short"
 */
export async function verifyRollFromImage(file, rollNumber) {
  const wanted = normalize(rollNumber);

  if (wanted.length < 4) {
    return { matched: false, reason: "short" };
  }

  const barcodeText = await readBarcode(file);

  // Open the browser console (F12) to see what the barcode really contains
  console.log("[ID scan] barcode text:", barcodeText);

  if (!barcodeText) {
    return { matched: false, reason: "nobarcode", barcodeText: null };
  }

  // Exact match (a half-typed roll number must not pass)
  return normalize(barcodeText) === wanted
    ? { matched: true, barcodeText }
    : { matched: false, reason: "mismatch", barcodeText };
}

/* =====================================================
   LIVE PHOTO VERIFICATION CARD
===================================================== */

// Keep the wasm version equal to the installed @mediapipe/tasks-vision version.
// For production, download these files into /public and point to them instead.
const WASM_URL =
  "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm";
const MODEL_URL =
  "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task";

const LABELS = {
  blink: "Blink once",
  turn: "Turn your head to one side",
  center: "Look straight at the camera",
};

const shuffle = (list) => [...list].sort(() => Math.random() - 0.5);

const LivePhotoVerification = ({ photo, onVerified, onReset }) => {
  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const landmarkerRef = useRef(null);
  const rafRef = useRef(null);

  // Logic state used inside the animation loop (no re-render per frame)
  const stepsRef = useRef([]);
  const indexRef = useRef(0);
  const eyesClosedRef = useRef(false);
  const centeredFramesRef = useRef(0);
  const hintRef = useRef("");
  const onVerifiedRef = useRef(onVerified);
  onVerifiedRef.current = onVerified;

  const [status, setStatus] = useState("idle"); // idle | loading | running
  const [steps, setSteps] = useState([]);
  const [doneCount, setDoneCount] = useState(0);
  const [hint, setHint] = useState("");
  const [error, setError] = useState("");

  const updateHint = (text) => {
    if (hintRef.current !== text) {
      hintRef.current = text;
      setHint(text);
    }
  };

  const stopCamera = useCallback(() => {
    if (rafRef.current) cancelAnimationFrame(rafRef.current);
    rafRef.current = null;

    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    }
  }, []);

  // Stop the camera if the user leaves the page
  useEffect(() => stopCamera, [stopCamera]);

  const capture = useCallback(() => {
    const video = videoRef.current;
    if (!video) return;

    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);

    const dataUrl = canvas.toDataURL("image/jpeg", 0.9);

    stopCamera();
    setStatus("idle");
    setSteps([]);
    updateHint("");
    onVerifiedRef.current(dataUrl);
  }, [stopCamera]);

  // Attach the camera to the <video> and run detection while status is "running"
  useEffect(() => {
    if (status !== "running") return undefined;

    const video = videoRef.current;
    if (!video || !streamRef.current) return undefined;

    video.srcObject = streamRef.current;
    video.play().catch(() => {});

    let lastTime = -1;

    const tick = () => {
      const landmarker = landmarkerRef.current;

      if (!landmarker || !streamRef.current) return;

      if (video.readyState >= 2 && video.currentTime !== lastTime) {
        lastTime = video.currentTime;

        const result = landmarker.detectForVideo(video, performance.now());
        const faces = result.faceLandmarks || [];

        if (faces.length === 0) {
          updateHint("No face found. Face the camera in good light.");
        } else if (faces.length > 1) {
          updateHint("Only one person should be in the frame.");
        } else {
          const lm = faces[0];

          // Face size in frame
          const xs = lm.map((p) => p.x);
          const faceWidth = Math.max(...xs) - Math.min(...xs);

          // Head turn: nose position between the two cheeks (0.5 = straight)
          const yaw = (lm[1].x - lm[234].x) / (lm[454].x - lm[234].x);

          // Blink score from blendshapes
          const shapes = result.faceBlendshapes?.[0]?.categories || [];
          const score = (name) =>
            shapes.find((s) => s.categoryName === name)?.score || 0;
          const blink = (score("eyeBlinkLeft") + score("eyeBlinkRight")) / 2;

          const current = stepsRef.current[indexRef.current];

          if (faceWidth < 0.22) {
            updateHint("Move a little closer.");
          } else {
            updateHint("");

            let passed = false;

            if (current === "blink") {
              if (blink > 0.5) eyesClosedRef.current = true;
              if (eyesClosedRef.current && blink < 0.3) passed = true;
            }

            if (current === "turn") {
              if (Math.abs(yaw - 0.5) > 0.17) passed = true;
            }

            if (current === "center") {
              const straight = Math.abs(yaw - 0.5) < 0.08 && blink < 0.3;
              centeredFramesRef.current = straight
                ? centeredFramesRef.current + 1
                : 0;

              if (centeredFramesRef.current >= 8) {
                capture();
                return;
              }
            }

            if (passed) {
              indexRef.current += 1;
              eyesClosedRef.current = false;
              centeredFramesRef.current = 0;
              setDoneCount(indexRef.current);
            }
          }
        }
      }

      rafRef.current = requestAnimationFrame(tick);
    };

    rafRef.current = requestAnimationFrame(tick);

    return () => {
      if (rafRef.current) cancelAnimationFrame(rafRef.current);
    };
  }, [status, capture]);

  const start = async () => {
    setError("");
    setStatus("loading");

    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error("unsupported");
      }

      streamRef.current = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: "user",
          width: { ideal: 640 },
          height: { ideal: 480 },
        },
        audio: false,
      });

      if (!landmarkerRef.current) {
        const { FaceLandmarker, FilesetResolver } = await import(
          "@mediapipe/tasks-vision"
        );
        const fileset = await FilesetResolver.forVisionTasks(WASM_URL);

        landmarkerRef.current = await FaceLandmarker.createFromOptions(
          fileset,
          {
            baseOptions: { modelAssetPath: MODEL_URL },
            runningMode: "VIDEO",
            numFaces: 2,
            outputFaceBlendshapes: true,
          }
        );
      }

      // Random order so a pre-recorded video cannot be replayed
      const queue = [...shuffle(["blink", "turn"]), "center"];

      stepsRef.current = queue;
      indexRef.current = 0;
      eyesClosedRef.current = false;
      centeredFramesRef.current = 0;

      setSteps(queue);
      setDoneCount(0);
      setStatus("running");
    } catch (err) {
      stopCamera();
      setStatus("idle");

      if (err?.name === "NotAllowedError") {
        setError("Camera permission was denied. Allow camera access and try again.");
      } else if (err?.name === "NotFoundError") {
        setError("No camera found on this device.");
      } else if (err?.message === "unsupported") {
        setError("Camera is not available. Open the site on HTTPS or localhost.");
      } else {
        setError("Could not start the camera check. Please try again.");
      }
    }
  };

  const cancel = () => {
    stopCamera();
    setStatus("idle");
    setSteps([]);
    updateHint("");
  };

  const currentStep = steps[doneCount];

  return (
    <div className="roll-number-card live-photo-card">
      <div className="roll-number-heading">
        <div className="roll-number-icon">
          <FaCamera />
        </div>

        <div>
          <h3>7. Live Photo Verification</h3>
          <p>Take a live photo so we know it's really you.</p>
        </div>
      </div>

      {/* Verified state */}
      {photo && status === "idle" && (
        <>
          <div className="live-photo-result">
            <img src={photo} alt="Your live verification" />
          </div>

          <div className="roll-verified-row">
            <div className="college-submitted-status">
              <FaCheckCircle />
              <span>Live photo verified</span>
            </div>

            <button
              type="button"
              className="roll-change-btn"
              onClick={() => {
                onReset();
                start();
              }}
            >
              <FaSyncAlt /> Retake
            </button>
          </div>
        </>
      )}

      {/* Not started */}
      {!photo && status === "idle" && (
        <>
          <ul className="live-photo-tips">
            <li>Use good light and keep your face fully visible.</li>
            <li>Remove cap, mask or sunglasses.</li>
            <li>You will be asked to blink and turn your head.</li>
          </ul>

          {error && (
            <div className="college-verification-error">
              <FaExclamationCircle />
              <span>{error}</span>
            </div>
          )}

          <button
            type="button"
            className="roll-number-submit-btn"
            onClick={start}
          >
            Start Live Check
          </button>
        </>
      )}

      {status === "loading" && (
        <p className="live-photo-loading">Starting camera and face check…</p>
      )}

      {/* Camera running */}
      {status === "running" && (
        <>
          <div className="live-photo-stage">
            <video ref={videoRef} muted playsInline />
            <div className="live-photo-guide" />

            <div className="live-photo-banner">
              {hint || (currentStep ? LABELS[currentStep] : "")}
            </div>
          </div>

          <ul className="live-photo-steps">
            {steps.map((step, i) => (
              <li key={step} className={i < doneCount ? "done" : ""}>
                {i < doneCount ? <FaCheckCircle /> : <FaRegCircle />}
                <span>{LABELS[step]}</span>
              </li>
            ))}
          </ul>

          <button type="button" className="roll-change-btn live-photo-cancel" onClick={cancel}>
            Cancel
          </button>
        </>
      )}
    </div>
  );
};

const Profile = () => {
  // ---------- everything below is loaded from / saved to the database ----------
  const [profileImage, setProfileImage] = useState("");
  const [isEditing, setIsEditing] = useState(false);

  const [emailOtp, setEmailOtp] = useState("");
  const [emailOtpSent, setEmailOtpSent] = useState(false);
  const [emailMessage, setEmailMessage] = useState("");
  const [email, setEmail] = useState("");
  const [currentEmail, setCurrentEmail] = useState("");
  const [currentEmailVerified, setCurrentEmailVerified] = useState(false);

  const [phone, setPhone] = useState("");
  const [phoneSaved, setPhoneSaved] = useState(false);
  const [phoneMessage, setPhoneMessage] = useState("");

  const [rollNumber, setRollNumber] = useState("");
  const [rollSubmitted, setRollSubmitted] = useState(false);
  const [rollNumberMessage, setRollNumberMessage] = useState("");
  const [rollProofFile, setRollProofFile] = useState(null);
  const [rollProofUrl, setRollProofUrl] = useState("");
  const [rollScanning, setRollScanning] = useState(false);
  const [rollScanInfo, setRollScanInfo] = useState("");

  const [idFront, setIdFront] = useState(null);
  const [idBack, setIdBack] = useState(null);

  const [livePhoto, setLivePhoto] = useState(null);

  const [addressSubmitted, setAddressSubmitted] = useState(false);
  const [addressErrors, setAddressErrors] = useState({});
  const [addressData, setAddressData] = useState({ address: "", city: "", state: "", pincode: "" });

  const [collegeSubmitted, setCollegeSubmitted] = useState(false);
  const [collegeError, setCollegeError] = useState("");
  const [collegeName, setCollegeName] = useState("");

  const [finalVerificationSubmitted, setFinalVerificationSubmitted] = useState(false);
  const [finalVerificationError, setFinalVerificationError] = useState("");

  const [profileData, setProfileData] = useState({
    name: "", college: "", course: "", year: "", address: "", bio: "",
  });
  const [isDetailsEditing, setIsDetailsEditing] = useState(false);

  // server state
  const [serverData, setServerData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [notice, setNotice] = useState("");
  const [passwordModal, setPasswordModal] = useState(false);
  const [otpPassword, setOtpPassword] = useState("");
  const [otpBusy, setOtpBusy] = useState(false);
  const [otpModalError, setOtpModalError] = useState("");
  const [savingHeader, setSavingHeader] = useState(false);

  const verificationStatus = serverData?.verification.status || "Not Submitted";
  const locked = Boolean(serverData?.verification.locked);   // Pending / Approved
  const approved = verificationStatus === "Approved";
  const percent = serverData?.completion.percent ?? 0;
  const steps = serverData?.completion.steps || [];

  // ----- put server data into the page -----
  const applyData = (d) => {
    setServerData(d);

    setProfileData({
      name: d.user.full_name || "",
      college: d.profile.college || "",
      course: d.profile.course || "",
      year: d.profile.graduation_year || "",
      address: d.profile.location || "",
      bio: d.profile.bio || "",
    });

    setCurrentEmail(d.user.email);
    setCurrentEmailVerified(d.user.email_verified);
    setEmail((prev) => (emailOtpSent ? prev : d.user.email));

    setPhone(d.profile.phone || "");
    setPhoneSaved(Boolean(d.profile.phone));

    setRollNumber(d.identity.roll_number || "");
    setRollSubmitted(Boolean(d.identity.roll_number));

    setIdFront(d.identity.front_image);
    setIdBack(d.identity.back_image);

    setLivePhoto(d.profile.live_photo);
    setProfileImage(d.profile.avatar || "");

    setAddressData({
      address: d.profile.address_line || "",
      city: d.profile.city || "",
      state: d.profile.state || "",
      pincode: d.profile.pincode || "",
    });
    setAddressSubmitted(
      Boolean(d.profile.address_line && d.profile.city && d.profile.state && d.profile.pincode)
    );

    setCollegeName(d.profile.college || "");
    setCollegeSubmitted(Boolean(d.profile.college));

    setFinalVerificationSubmitted(["Pending", "Approved"].includes(d.verification.status));
    setFinalVerificationError(
      d.verification.status === "Rejected"
        ? `Your last request was rejected${d.verification.rejection_reason ? `: ${d.verification.rejection_reason}` : "."} Update your details and submit again.`
        : ""
    );
  };

  useEffect(() => {
    fetchUserProfile()
      .then(applyData)
      .catch((e) => setNotice(e.message))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // one helper that saves one card
  const saveSection = async (section, fields = {}, files = {}) => {
    const body = new FormData();
    body.append("section", section);
    Object.entries(fields).forEach(([k, v]) => body.append(k, v ?? ""));
    Object.entries(files).forEach(([k, f]) => f && body.append(k, f));
    const d = await updateUserProfileSection(body);
    applyData(d);
    return d;
  };

  // ----- header (name / college / course / year / location / bio) -----
  const handleProfileChange = (field, value) => {
    // identity fields are locked once the request is pending/approved
    if (locked && field !== "bio") return;
    setProfileData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSaveHeader = async () => {
    setSavingHeader(true);
    try {
      if (!locked) {
        await saveSection("basic", {
          full_name: profileData.name,
          college: profileData.college,
          course: profileData.course,
          graduation_year: profileData.year,
          location: profileData.address,
        });
      }
      await saveSection("bio", { bio: profileData.bio });
      setIsEditing(false);
    } catch (e) {
      setNotice(e.message);
    } finally {
      setSavingHeader(false);
    }
  };

  const handleCancelHeader = () => {
    if (serverData) applyData(serverData);
    setIsEditing(false);
  };

  const handleDetailsChange = () => {};

  // ----- avatar -----
  const handleProfileImageChange = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    if (!file.type.startsWith("image/") || file.size > 5 * 1024 * 1024) {
      setNotice("Please choose an image of 5MB or less.");
      return;
    }
    setProfileImage(URL.createObjectURL(file));
    try {
      await saveSection("avatar", {}, { profile_picture: file });
    } catch (err) {
      setNotice(err.message);
    }
  };

  // ----- 1. email (OTP through the mailer) -----
  const handleSendEmailOtp = () => {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!email.trim()) {
      setEmailMessage("Please enter your email address.");
      setEmailOtpSent(false);
      return;
    }
    if (!emailPattern.test(email)) {
      setEmailMessage("Please enter a valid email address.");
      setEmailOtpSent(false);
      return;
    }
    if (email.trim().toLowerCase() === currentEmail.toLowerCase() && currentEmailVerified) {
      setEmailMessage("This email address is already verified.");
      return;
    }

    // ask for the password in a modal first (security)
    setOtpPassword("");
    setOtpModalError("");
    setPasswordModal(true);
  };

  const confirmSendOtp = async () => {
    if (!otpPassword) {
      setOtpModalError("Please enter your current password.");
      return;
    }
    setOtpBusy(true);
    setOtpModalError("");
    try {
      const res = await sendUserEmailOtp(email.trim(), otpPassword);
      setPasswordModal(false);
      setOtpPassword("");
      setEmailOtp("");
      setEmailOtpSent(true);
      setEmailMessage(res.message);
    } catch (err) {
      setOtpModalError(err.message);
    } finally {
      setOtpBusy(false);
    }
  };

  const handleEmailVerification = async () => {
    if (emailOtp.length !== 6) {
      setEmailMessage("Please enter a valid 6-digit OTP.");
      return;
    }
    try {
      const res = await verifyUserEmailOtp(emailOtp);
      setEmailOtpSent(false);
      setEmailOtp("");
      setEmail(res.email);
      setCurrentEmail(res.email);
      setCurrentEmailVerified(true);
      setEmailMessage("Email verified successfully.");
      const d = await fetchUserProfile();
      applyData(d);
    } catch (err) {
      setEmailMessage(err.message);
    }
  };

  // the badge: verified only while the box still shows the verified account email
  const emailVerified =
    currentEmailVerified && email.trim().toLowerCase() === currentEmail.toLowerCase();

  // ----- 2. phone -----
  const handlePhoneSave = async () => {
    if (phone.length !== 10) {
      setPhoneMessage("Please enter a valid 10-digit phone number.");
      setPhoneSaved(false);
      return;
    }
    try {
      await saveSection("phone", { phone });
      setPhoneSaved(true);
      setPhoneMessage("Phone number saved successfully.");
    } catch (err) {
      setPhoneSaved(false);
      setPhoneMessage(err.message);
    }
  };

  // ----- 3. roll number (barcode check in the browser, then saved) -----
  const handleRollProofChange = (e) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      setRollNumberMessage("Image size must be 5MB or less.");
      return;
    }
    if (rollProofUrl) URL.revokeObjectURL(rollProofUrl);
    setRollProofFile(file);
    setRollProofUrl(URL.createObjectURL(file));
    setRollNumberMessage("");
    setRollScanInfo("");
  };

  const handleRollVerify = async () => {
    const roll = rollNumber.trim();

    if (!roll) {
      setRollNumberMessage("Please enter your college roll number.");
      return;
    }
    if (!/^[A-Za-z0-9-]+$/.test(roll)) {
      setRollNumberMessage("Please enter a valid roll number.");
      return;
    }
    if (!rollProofFile) {
      setRollNumberMessage("Upload a photo of your ID card to verify the roll number.");
      return;
    }

    setRollScanning(true);
    setRollNumberMessage("");

    try {
      const result = await verifyRollFromImage(rollProofFile, roll);
      setRollScanInfo(result.barcodeText || "not readable");

      if (result.matched) {
        await saveSection("roll", { roll_number: roll });
        setRollSubmitted(true);
      } else if (result.reason === "mismatch") {
        setRollNumberMessage(
          "The barcode on your ID card does not match the roll number you entered."
        );
      } else if (result.reason === "nobarcode") {
        setRollNumberMessage(
          "Barcode could not be read. Upload a clear photo with the whole barcode visible, card flat and well lit."
        );
      } else {
        setRollNumberMessage("Please enter a valid roll number.");
      }
    } catch (err) {
      setRollNumberMessage(err.message || "Could not read the photo. Please try again.");
    } finally {
      setRollScanning(false);
    }
  };

  const handleRollReset = () => {
    if (locked) return;
    if (rollProofUrl) URL.revokeObjectURL(rollProofUrl);
    setRollSubmitted(false);
    setRollNumberMessage("");
    setRollScanInfo("");
    setRollProofFile(null);
    setRollProofUrl("");
  };

  // ----- 4. ID card front / back (uploaded as soon as chosen) -----
  const handleIdPick = async (side, file) => {
    if (locked) return;
    if (!file.type.startsWith("image/")) {
      setNotice("Please choose a JPG or PNG image.");
      return;
    }
    const preview = URL.createObjectURL(file);
    if (side === "front") setIdFront(preview);
    else setIdBack(preview);

    try {
      await saveSection("id_cards", {}, side === "front" ? { id_front: file } : { id_back: file });
    } catch (err) {
      setNotice(err.message);
      if (serverData) {
        setIdFront(serverData.identity.front_image);
        setIdBack(serverData.identity.back_image);
      }
    }
  };

  // ----- 5. address -----
  const handleAddressChange = (field, value) => {
    if (locked) return;
    setAddressData((prev) => ({ ...prev, [field]: value }));
    setAddressErrors((prev) => ({ ...prev, [field]: "" }));
    setAddressSubmitted(false);
  };

  const handleAddressSubmit = async () => {
    const errors = {};
    if (!addressData.address.trim()) errors.address = "Address is required.";
    if (!addressData.city.trim()) errors.city = "City is required.";
    if (!addressData.state.trim()) errors.state = "State is required.";
    if (!/^[0-9]{6}$/.test(addressData.pincode)) errors.pincode = "Enter a valid 6-digit PIN code.";

    setAddressErrors(errors);
    if (Object.keys(errors).length > 0) return;

    try {
      await saveSection("address", {
        address_line: addressData.address,
        city: addressData.city,
        state: addressData.state,
        pincode: addressData.pincode,
      });
    } catch (err) {
      setAddressErrors({ address: err.message });
    }
  };

  // ----- 6. college -----
  const handleCollegeSubmit = async () => {
    if (!collegeName.trim()) {
      setCollegeError("Please enter your college or university name.");
      setCollegeSubmitted(false);
      return;
    }
    setCollegeError("");
    try {
      await saveSection("basic", {
        full_name: profileData.name,
        college: collegeName.trim(),
        course: profileData.course,
        graduation_year: profileData.year,
        location: profileData.address,
      });
    } catch (err) {
      setCollegeError(err.message);
    }
  };

  // ----- 7. live photo -----
  const handleLiveVerified = async (dataUrl) => {
    setLivePhoto(dataUrl);
    try {
      const blob = await (await fetch(dataUrl)).blob();
      const file = new File([blob], "live.jpg", { type: blob.type || "image/jpeg" });
      await saveSection("live_photo", {}, { live_photo: file });
    } catch (err) {
      setLivePhoto(null);
      setNotice(err.message || "Could not save your live photo.");
    }
  };

  const handleLiveReset = async () => {
    if (locked) return;
    setLivePhoto(null);
    try {
      await saveSection("live_photo", { clear: "1" });
    } catch (err) {
      setNotice(err.message);
    }
  };

  // ----- final: send to admin -----
  const handleFinalSubmit = async () => {
    setFinalVerificationError("");
    try {
      const d = await submitUserVerification();
      applyData(d);
    } catch (err) {
      setFinalVerificationError(err.message);
    }
  };

  return (
  <>
 <div className="profile-layout">
  <Navbar />

  <div className="profile-body">
    <SettingsSidebar activeItem="Profile Settings" />

    <main className="profile-page">

      {/* =========================
          PROFILE HEADER
      ========================= */}

      <section className="profile-header">

        <div className="profile-main-info">

          <div className="profile-avatar-wrapper">
                {profileImage ? (
                  <img
                    src={profileImage}
                    alt="Profile"
                    className="profile-avatar"
                  />
                ) : (
                  <div
                    className="profile-avatar"
                    style={{
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      fontSize: 44,
                      fontWeight: 700,
                      color: "#6637e8",
                      background: "#ede9fe",
                    }}
                  >
                    {userInitial(profileData.name)}
                  </div>
                )}

                <label
                  htmlFor="profile-photo-input"
                  className="profile-camera-btn"
                >
                  📷
                </label>

                <input
                  id="profile-photo-input"
                  type="file"
                  accept="image/*"
                  onChange={handleProfileImageChange}
                  style={{ display: "none" }}
                />
          </div>

          {/* <div className="profile-user-info">

            <div className="profile-name-row">
              <h1>Anuj Kushwaha</h1>

              <FaCheckCircle className="verified-name-icon" />
            </div>

            <div className="profile-info-row">
              <FaGraduationCap />
              <span>ITM University Gwalior</span>
            </div>

            <div className="profile-info-row">
              <FaGraduationCap />
              <span>MCA&nbsp; • &nbsp;2027</span>
            </div>

            <div className="profile-info-row">
              <FaMapMarkerAlt />
              <span>Gwalior, Madhya Pradesh</span>
            </div>

            <p className="profile-bio">
              Tech enthusiast | Always looking for great deals and
              useful connections on campus.
            </p>
          </div> */}
                  <div className="profile-user-info">

                                                    <div className="profile-name-row">
                                                      {isEditing ? (
                                                        <input
                                                          type="text"
                                                          value={profileData.name}
                                                          onChange={(e) =>
                                                            handleProfileChange("name", e.target.value)
                                                          }
                                                          className="profile-header-input profile-name-input"
                                                        />
                                                      ) : (
                                                        <h1>{profileData.name}</h1>
                                                      )}

                                                      {approved && <FaCheckCircle className="verified-name-icon" />}
                                                    </div>

                                                    <div className="profile-info-row">
                                                      <FaGraduationCap />

                                                      {isEditing ? (
                                                        <input
                                                          type="text"
                                                          value={profileData.college}
                                                          onChange={(e) =>
                                                            handleProfileChange("college", e.target.value)
                                                          }
                                                          className="profile-header-input"
                                                        />
                                                      ) : (
                                                        <span>{profileData.college}</span>
                                                      )}
                                                    </div>

                                                    <div className="profile-info-row">
                                                      <FaGraduationCap />

                                                      {isEditing ? (
                                                        <>
                                                          <input
                                                            type="text"
                                                            value={profileData.course}
                                                            onChange={(e) =>
                                                              handleProfileChange("course", e.target.value)
                                                            }
                                                            className="profile-header-input profile-course-input"
                                                          />

                                                          <span>•</span>

                                                          <input
                                                            type="text"
                                                            value={profileData.year}
                                                            onChange={(e) =>
                                                              handleProfileChange("year", e.target.value)
                                                            }
                                                            className="profile-header-input profile-year-input"
                                                          />
                                                        </>
                                                      ) : (
                                                        <span>
                                                          {profileData.course}&nbsp; • &nbsp;{profileData.year}
                                                        </span>
                                                      )}
                                                    </div>

                                                    <div className="profile-info-row">
                                                      <FaMapMarkerAlt />

                                                      {isEditing ? (
                                                        <input
                                                          type="text"
                                                          value={profileData.address}
                                                          onChange={(e) =>
                                                            handleProfileChange("address", e.target.value)
                                                          }
                                                          className="profile-header-input"
                                                        />
                                                      ) : (
                                                        <span>{profileData.address}</span>
                                                      )}
                                                    </div>

                                                    <p className="profile-bio">
                                                      {isEditing ? (
                                                        <textarea
                                                          value={profileData.bio || ""}
                                                          onChange={(e) =>
                                                            handleProfileChange("bio", e.target.value)
                                                          }
                                                          className="profile-header-input profile-bio-input"
                                                        />
                                                      ) : (
                                                        profileData.bio ||
                                                        "Tech enthusiast | Always looking for great deals and useful connections on campus."
                                                      )}
                                                    </p>

                                                  </div>

                                                  

        </div>

                      {!isEditing ? (
                  <button
                    className="edit-profile-btn"
                    onClick={() => setIsEditing(true)}
                  >
                    <FaEdit />
                    Edit Profile
                  </button>
                ) : (
                  <div className="edit-profile-actions">
                    <button
  className="cancel-profile-btn"
  onClick={handleCancelHeader}
                    >
                      Cancel
                    </button>

                    <button
  className="save-profile-btn"
  disabled={savingHeader}
  onClick={handleSaveHeader}
  >
  {savingHeader ? "Saving..." : "Save Changes"}
                    </button>
                  </div>
                )}

      </section>


      {/* =========================
          MAIN PROFILE GRID
      ========================= */}

      <div className="profile-content-grid">

        {/* =====================
            LEFT / CENTER CONTENT
        ===================== */}

        <section className="verification-section">

          <div className="verification-heading">
            <h2>Student Verification</h2>

            <p>
              Complete all the steps below to get 100% verified
              and unlock all features.
            </p>
          </div>


          <div className="verification-grid">

            {/* =====================
                1. EMAIL VERIFICATION
            ===================== */}

                <div className="verification-column">

<div className="email-verification-card">

                            <div className="email-verification-card-heading">

                              <div className="email-verification-icon">
                                <FaEnvelope />
                              </div>

                              <div>
                                <h3>1. Email Verification</h3>
                                <p>Verify your student email address with OTP.</p>
                              </div>

                            </div>

                            <div className="email-verification-input-row">

                             <input
                                  type="email"
                                  placeholder="Enter your email address"
                                  value={email}
                                  autoComplete="email"
                                  className={emailMessage && !emailVerified ? "input-error" : ""}
                                  onChange={(e) => {
                                    setEmail(e.target.value);
                                    setEmailMessage("");
                                  }}
                                />

                              {emailVerified ? (
                                <span className="email-verified-badge">
                                  <FaCheckCircle />
                                  Verified
                                </span>
                              ) : (
                                <span className="pending-badge">
                                  <FaRegCircle />
                                  Not Verified
                                </span>
                              )}

                            </div>

                            {!emailVerified && !emailOtpSent && (
                              <>
                                <button
                                  className="send-otp-btn"
                                  onClick={handleSendEmailOtp}
                                >
                                  Send OTP
                                </button>

                                {emailMessage && (
                                  <div className="email-error-message">
                                    <FaInfoCircle />
                                    <span>{emailMessage}</span>
                                  </div>
                                )}
                              </>
                            )}

                           {!emailVerified && emailOtpSent && (
                              <>
                                <div className="otp-row">
                                  <input
                                    type="text"
                                    placeholder="Enter 6-digit OTP"
                                    value={emailOtp}
                                    maxLength={6}
                                    autoComplete="one-time-code"
                                    inputMode="numeric"
                                    onChange={(e) => {
                                      const value = e.target.value.replace(/\D/g, "");
                                      setEmailOtp(value);
                                      setEmailMessage("");
                                    }}
                                  />

                                  <button
                                    className="verify-otp-btn"
                                    onClick={handleEmailVerification}
                                  >
                                    Verify OTP
                                  </button>
                                </div>

                                {emailMessage && (
                                  <p
                                    className={
                                      emailVerified
                                        ? "otp-success-message"
                                        : "otp-error-message"
                                    }
                                  >
                                    {emailMessage}
                                  </p>
                                )}

                                <p className="resend-text">
                                  Didn't receive the OTP?
                                  <span onClick={handleSendEmailOtp}> Resend OTP</span>
                                </p>
                              </>
                            )}

                            {emailVerified && (
                              <div className="email-verified-message">
                                <FaCheckCircle />
                                <span>Your email has been successfully verified.</span>
                              </div>
                            )}

                  </div>


{/* =================       ROLL NUMBER VERIFICAITON ================================= */}


<div className="roll-number-card">

  {/* Heading */}
  <div className="roll-number-heading">

    <div className="roll-number-icon">
      <FaIdCard />
    </div>

    <div>
      <h3>3. Student Roll Number</h3>
      <p>Enter your roll number and upload your ID card photo to confirm it.</p>
    </div>

  </div>


  {/* Input */}
  <div className="roll-number-input-wrapper">

    <input
      type="text"
      placeholder="Enter your college roll number"
      value={rollNumber}
      disabled={rollSubmitted || rollScanning}
      className={
        rollNumberMessage && !rollSubmitted
          ? "roll-number-input-error"
          : ""
      }
      onChange={(e) => {
        setRollNumber(e.target.value);
        setRollNumberMessage("");
        setRollSubmitted(false);
      }}
    />

    {rollSubmitted && (
      <span className="roll-number-check">
        <FaCheckCircle />
      </span>
    )}

  </div>


  {/* ID photo used to confirm the roll number */}
  <div className="roll-proof-box">

    {rollProofUrl ? (
      <div className="roll-proof-preview">
        <img src={rollProofUrl} alt="ID card used for roll number check" />
      </div>
    ) : (
      <div className="roll-proof-empty">
        <FaCloudUploadAlt />
        <span>Photo of the ID side that shows your roll number or barcode</span>
      </div>
    )}

    {!rollSubmitted && (
      <>
        <input
          id="roll-proof-upload"
          type="file"
          accept="image/jpeg,image/png"
          hidden
          onChange={handleRollProofChange}
        />

        <label
          htmlFor="roll-proof-upload"
          className="student-id-upload-btn"
        >
          {rollProofUrl ? "Change Photo" : "Upload ID Photo"}
        </label>
      </>
    )}

    <small>JPG, PNG • Max 5MB • Keep the card flat and well lit</small>

    {rollScanInfo && (
      <small className="roll-scan-debug">
        Scan v3 · barcode read: <b>{rollScanInfo}</b>
      </small>
    )}

  </div>


  {rollNumberMessage && !rollSubmitted && (
    <div className="college-verification-error">
      <FaExclamationCircle />
      <span>{rollNumberMessage}</span>
    </div>
  )}


  {rollSubmitted ? (
    <div className="roll-verified-row">
      <div className="college-submitted-status">
        <FaCheckCircle />
        <span>Roll number matched with ID card</span>
      </div>

      <button
        type="button"
        className="roll-change-btn"
        onClick={handleRollReset}
      >
        Change
      </button>
    </div>
  ) : (
    <button
      type="button"
      className="roll-number-submit-btn"
      disabled={rollScanning}
      onClick={handleRollVerify}
    >
      {rollScanning ? "Reading ID card..." : "Verify Roll Number"}
    </button>
  )}

</div>

{/* ========================== ADDRESS VERIFICATION============================= */}

<div className="address-verification-card">

                              <div className="address-verification-heading">

                                <div className="address-verification-icon">
                                  <FaMapMarkerAlt />
                                </div>

                                <div>
                                  <h3>5. Address Verification</h3>
                                  <p>Enter your current residential address for verification.</p>
                                </div>

                                {addressSubmitted && (
                                  <div className="address-verification-check">
                                    <FaCheckCircle />
                                  </div>
                                )}

                              </div>


                              <div className="address-verification-form">

                                {/* FULL ADDRESS */}

                                <div className="address-verification-field address-verification-full">
                                  <label>Full Address</label>

                                  <textarea
                                    value={addressData.address}
                                    onChange={(e) =>
                                      handleAddressChange("address", e.target.value)
                                    }
                                    placeholder="House no., street, area, locality..."
                                    className={addressErrors.address ? "address-verification-error" : ""}
                                  />

                                  {addressErrors.address && (
                                    <small>{addressErrors.address}</small>
                                  )}
                                </div>


                                {/* CITY */}

                                <div className="address-verification-field">
                                  <label>City</label>

                                  <input
                                    type="text"
                                    value={addressData.city}
                                    onChange={(e) =>
                                      handleAddressChange("city", e.target.value)
                                    }
                                    placeholder="Enter city"
                                    className={addressErrors.city ? "address-verification-error" : ""}
                                  />

                                  {addressErrors.city && (
                                    <small>{addressErrors.city}</small>
                                  )}
                                </div>


                                {/* STATE */}

                                <div className="address-verification-field">
                                  <label>State</label>

                                  <input
                                    type="text"
                                    value={addressData.state}
                                    onChange={(e) =>
                                      handleAddressChange("state", e.target.value)
                                    }
                                    placeholder="Enter state"
                                    className={addressErrors.state ? "address-verification-error" : ""}
                                  />

                                  {addressErrors.state && (
                                    <small>{addressErrors.state}</small>
                                  )}
                                </div>


                                {/* PIN CODE */}

                                <div className="address-verification-field">
                                  <label>PIN Code</label>

                                  <input
                                    type="text"
                                    inputMode="numeric"
                                    maxLength="6"
                                    value={addressData.pincode}
                                    onChange={(e) =>
                                      handleAddressChange(
                                        "pincode",
                                        e.target.value.replace(/\D/g, "")
                                      )
                                    }
                                    placeholder="6-digit PIN"
                                    className={addressErrors.pincode ? "address-verification-error" : ""}
                                  />

                                  {addressErrors.pincode && (
                                    <small>{addressErrors.pincode}</small>
                                  )}
                                </div>

                              </div>


                              <div className="address-verification-footer">

                                <p>
                                  <FaShieldAlt />
                                  Your address will be reviewed by the CampusXchange admin.
                                </p>

                                <button
                                  type="button"
                                  className="address-verification-save-btn"
                                  onClick={handleAddressSubmit}
                                >
                                  {addressSubmitted ? "Address Submitted" : "Save Address"}
                                </button>

                              </div>
                            </div>

</div>


{/* ========================  PHONE VERIFICATION ================================= */}
<div className="verification-column">

<div className="email-verification-card">

                            <div className="email-verification-card-heading">

                              <div className="email-verification-icon">
                                <FaMobile />
                              </div>

                              <div>
                                <h3>2. Phone Number</h3>
                                <p>Enter your mobile number.</p>
                              </div>

                            </div>

                            <div className="email-verification-input-row">

                              <input
                                  type="tel"
                                  placeholder="Enter your phone number"
                                  value={phone}
                                  maxLength={10}
                                  className={phoneMessage && !phoneSaved ? "input-error" : ""}
                                  onChange={(e) => {
                                    const value = e.target.value.replace(/\D/g, "");
                                    setPhone(value);
                                    setPhoneSaved(false);
                                    setPhoneMessage("");
                                  }}
                                />

                              {phoneSaved && (
                                <span className="email-verified-badge">
                                  <FaCheckCircle />
                                  Saved
                                </span>
                              )}

                            </div>

                            <button
                              className="save-phone-btn"
                              onClick={handlePhoneSave}
                            >
                              Save Phone Number
                            </button>

                            {phoneMessage && (
                              <div
                                className={`phone-message ${
                                  phoneSaved ? "phone-success" : "phone-error"
                                }`}
                              >
                                <FaCheckCircle />
                                <span>{phoneMessage}</span>
                              </div>
                            )}

                          </div>



{/* ================================ STUDENT ID CARD VERIFICATION ============================ */}


<div className="student-id-card">

                      <div className="student-id-heading">

                        <div className="student-id-icon">
                          <FaIdCard />
                        </div>

                        <div>
                          <h3>4. Student ID Card</h3>
                          <p>Upload both sides of your valid college ID card.</p>
                        </div>

                      </div>


                      <div className="student-id-upload-grid">

                        {/* FRONT SIDE */}
                                
                                  <div className="student-id-upload-box">

                                    <h4>Front Side</h4>

                                    {idFront ? (
                                      <div className="student-id-image-preview">
                                        <img src={idFront} alt="Student ID front" />
                                      </div>
                                    ) : (
                                      <div className="student-id-preview">
                                        <FaCloudUploadAlt />
                                        <span>Upload front side</span>
                                      </div>
                                    )}

                                    <input
                                      id="front-id-upload"
                                      type="file"
                                      accept="image/jpeg,image/png"
                                      hidden
                                      onChange={(e) => {
                                        const file = e.target.files?.[0];

                                        if (!file) return;

                                        if (file.size > 5 * 1024 * 1024) {
                                          setNotice("Image size must be 5MB or less.");
                                          return;
                                        }

                                        handleIdPick("front", file);
                                      }}
                                    />

                                    <label
                                      htmlFor="front-id-upload"
                                      className="student-id-upload-btn"
                                    >
                                      {idFront ? "Change Image" : "Upload Image"}
                                    </label>

                                    <small>JPG, PNG • Max 5MB</small>

                                  </div>


                        {/* BACK SIDE */}
                          {/* BACK SIDE */}

                            <div className="student-id-upload-box">

                              <h4>Back Side</h4>

                              {idBack ? (
                                <div className="student-id-image-preview">
                                  <img src={idBack} alt="Student ID back" />
                                </div>
                              ) : (
                                <div className="student-id-preview">
                                  <FaCloudUploadAlt />
                                  <span>Upload back side</span>
                                </div>
                              )}

                              <input
                                id="back-id-upload"
                                type="file"
                                accept="image/jpeg,image/png"
                                hidden
                                onChange={(e) => {
                                  const file = e.target.files?.[0];

                                  if (!file) return;

                                  if (file.size > 5 * 1024 * 1024) {
                                    setNotice("Image size must be 5MB or less.");
                                    return;
                                  }

                                  handleIdPick("back", file);
                                }}
                              />

                              <label
                                htmlFor="back-id-upload"
                                className="student-id-upload-btn"
                              >
                                {idBack ? "Change Image" : "Upload Image"}
                              </label>

                              <small>JPG, PNG • Max 5MB</small>

                            </div>

                            </div>

                      </div>


{/* --------------------college verification----------------------- */}

<div className="college-verification-card">

  <div className="college-verification-heading">
    <div className="college-verification-icon">
      <FaUniversity />
    </div>

    <div>
      <h3>6. College / University</h3>
      <p>
        Enter the official name of your college or university.
      </p>
    </div>
  </div>


  <div className="college-verification-field">

    <label>College / University Name</label>

    <div className="college-input-wrapper">

      <input
        type="text"
        value={collegeName}
        disabled={collegeSubmitted}
        placeholder="Enter your college or university name"
        className={collegeError ? "college-input-error" : ""}
        onChange={(e) => {
          setCollegeName(e.target.value);
          setCollegeError("");
          setCollegeSubmitted(false);
        }}
      />

      {collegeSubmitted && collegeName.trim() && (
        <span className="college-verification-check">
          <FaCheckCircle />
        </span>
      )}

    </div>


    {collegeError && (
      <div className="college-verification-error">
        <FaExclamationCircle />
        <span>{collegeError}</span>
      </div>
    )}

  </div>


  <div className="college-verification-footer">

    <p>
      <FaShieldAlt />
      Your details will be reviewed by the CampusXchange admin.
    </p>


    {!collegeSubmitted && (
      <button
        type="button"
        className="college-verification-submit-btn"
        onClick={(e) => {
          e.preventDefault();
          handleCollegeSubmit();
        }}
      >
        Submit College
      </button>
    )}


    {collegeSubmitted && collegeName.trim() && (
      <div className="college-submitted-status">
        <FaCheckCircle />
        <span>College submitted</span>
      </div>
    )}

  </div>

</div>

{/* -------------------- live photo verification -------------------- */}

<LivePhotoVerification
  photo={livePhoto}
  onVerified={handleLiveVerified}
  onReset={handleLiveReset}
/>

</div>


          </div>

{/* SECURITY MESSAGE */}




          {/* SECURITY MESSAGE */}

          <div className="verification-security-message">

            <FaLock />

            <span>
              Your information is secure and will only be used
              for verification purposes.
            </span>

          </div>

        {/* =========================
    FINAL VERIFICATION
========================= */}
 
<div className="final-verification-card">

  <div className="final-verification-content">

    <div className="final-verification-icon">
      <FaShieldAlt />
    </div>

    <div>
      <h3>Ready for Final Verification?</h3>

      <p>
        Submit all your details to the CampusXchange admin
        for final student verification.
      </p>
    </div>

  </div>


  {finalVerificationError && (
    <div className="final-verification-error">
      <FaExclamationCircle />
      <span>{finalVerificationError}</span>
    </div>
  )}


  {!finalVerificationSubmitted ? (
  <button
  type="button"
  className="final-verification-btn"
  onClick={(e) => {
    e.preventDefault();
    e.stopPropagation();
    handleFinalSubmit();
  }}
>
  Proceed to Final Verification
</button>
) : (
  <div className="final-verification-success">

    <div className="final-success-icon">
      <FaCheckCircle />
    </div>

    <div className="final-success-text">
      <strong>Details Sent for Verification</strong>

      <span>
        Your student details have been successfully submitted.
        Please wait while the CampusXchange admin reviews your
        profile.
      </span>
    </div>

    <span className="final-pending-badge">
      {verificationStatus === "Approved" ? "Verified" : "Pending Admin Review"}
    </span>

  </div>
)}

</div>

        </section>



        {/* =========================
            RIGHT SIDE
        ========================= */}

        <aside className="profile-right-column">

          {/* =====================
              PROFILE VERIFICATION
          ===================== */}

          <div className="profile-verification-card">

            <div className="verification-top">

              <div className="verification-shield">
                <FaShieldAlt />
              </div>

              <div>
                <h2>Profile Verification</h2>

                <p>
                  Complete your details to unlock full access
                  to buy, sell and chat on CampusXchange.
                </p>
              </div>

            </div>


            <div className="verification-progress">

              <div
                className="progress-circle"
                style={{
                  background: `radial-gradient(circle, white 58%, transparent 59%), conic-gradient(#6637e8 ${percent}%, #e8e1ff ${percent}%)`,
                }}
              >
                <span>{percent}%</span>
              </div>


              <div className="progress-list">
                {steps.map((step) => (
                  <div
                    key={step.key}
                    className={`progress-item ${step.done ? "completed" : ""}`}
                  >
                    {step.done ? <FaCheckCircle /> : <FaRegCircle />}
                    <span>{step.label}</span>
                  </div>
                ))}
              </div>

            </div>

          </div>


          {/* =====================
              PROFILE DETAILS
          ===================== */}

   <div className="profile-details-card">

  <div className="right-card-heading">
    <h2>Profile Details</h2>

    {/* {!isDetailsEditing ? (
      <button
        type="button"
        onClick={() => setIsDetailsEditing(true)}
      >
        Edit
      </button>
    ) : (
      <div className="profile-details-actions">

        <button
          type="button"
          className="details-cancel-btn"
          onClick={() => setIsDetailsEditing(false)}
        >
          Cancel
        </button>

        <button
          type="button"
          className="details-save-btn"
          onClick={() => setIsDetailsEditing(false)}
        >
          Save
        </button>

      </div>
    )} */}
  </div>


  {/* FULL NAME */}
  <div className="profile-detail-item">
    <FaUser />

    <div className="profile-detail-content">
      <span>Full Name</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={profileData.name}
          onChange={(e) =>
            handleProfileChange("name", e.target.value)
          }
        />
      ) : (
        <strong>{profileData.name}</strong>
      )}
    </div>
  </div>


  {/* EMAIL */}
  <div className="profile-detail-item">
    <FaEnvelope />

    <div className="profile-detail-content">
      <span>Email</span>

      {isDetailsEditing ? (
        <input
  type="email"
  value={email}
  onChange={(e) => {
    setEmail(e.target.value);
    setEmailMessage("");
  }}
  placeholder="Enter your email address"
  required
/>
      ) : (
        <strong>
          {currentEmail || ""}
        </strong>
      )}
    </div>

    {/* {!isDetailsEditing && (
      <b className="detail-verified">
        ✓ Verified
      </b>
    )} */}
  </div>


  {/* MOBILE */}
  <div className="profile-detail-item">
    <FaPhone />

    <div className="profile-detail-content">
      <span>Mobile Number</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
      ) : (
        <strong>
          {phone || ""}
        </strong>
      )}
    </div>

    {/* {!isDetailsEditing && (
      <b className="detail-verified">
        ✓ Verified
      </b>
    )} */}
  </div>


  {/* ROLL NUMBER */}
  <div className="profile-detail-item">
    <FaIdCard />

    <div className="profile-detail-content">
      <span>Roll Number</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={rollNumber}
          onChange={(e) => setRollNumber(e.target.value)}
        />
      ) : (
        <strong>
          {rollNumber || ""}
        </strong>
      )}
    </div>

    {/* {!isDetailsEditing && (
      <b className="detail-verified">
        ✓ Verified
      </b>
    )} */}
  </div>


  {/* COLLEGE */}
  <div className="profile-detail-item">
    <FaGraduationCap />

    <div className="profile-detail-content">
      <span>College / University</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={profileData.college}
          onChange={(e) =>
            handleProfileChange("college", e.target.value)
          }
        />
      ) : (
        <strong>{profileData.college}</strong>
      )}
    </div>

    {/* {!isDetailsEditing && (
      <b className="detail-verified">
        ✓ Verified
      </b>
    )} */}
  </div>


  {/* COURSE */}
  <div className="profile-detail-item">
    <FaGraduationCap />

    <div className="profile-detail-content">
      <span>Course</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={profileData.course}
          onChange={(e) =>
            handleProfileChange("course", e.target.value)
          }
        />
      ) : (
        <strong>{profileData.course}</strong>
      )}
    </div>
  </div>


  {/* GRADUATION YEAR */}
  <div className="profile-detail-item">
    <FaGraduationCap />

    <div className="profile-detail-content">
      <span>Graduation Year</span>

      {isDetailsEditing ? (
        <input
          type="text"
          value={profileData.year}
          onChange={(e) =>
            handleProfileChange("year", e.target.value)
          }
        />
      ) : (
        <strong>{profileData.year}</strong>
      )}
    </div>
  </div>


  {/* ADDRESS */}
  <div className="profile-detail-item">
    <FaMapMarkerAlt />

    <div className="profile-detail-content">
      <span>Address</span>

      {isDetailsEditing ? (
        <textarea
          value={profileData.address}
          onChange={(e) =>
            handleProfileChange("address", e.target.value)
          }
        />
      ) : (
        <strong>{profileData.address}</strong>
      )}
    </div>
  </div>

</div>

          {/* =====================
              GUIDELINES
          ===================== */}

          <div className="guidelines-card">

            <div className="guidelines-icon">
              <FaInfoCircle />
            </div>

            <div>
              <h3>Verification Guidelines</h3>

              <p>
                Know what documents are required and how
                to complete the verification process.
              </p>
            </div>

            <FaArrowRight className="guidelines-arrow" />

          </div>


          {/* =====================
              PROFILE LOCK CARD
          ===================== */}

          <div className="profile-lock-card">

            <div className="lock-icon">
              <FaLock />
            </div>

            <div>
              <h3>Complete Your Profile to Unlock</h3>

              <p>
                You won't be able to buy, sell or chat with sellers
                until your profile is 100% verified.
              </p>
            </div>

          </div>

        </aside>

      </div>

    </main>
    
    </div>
    </div>
    
    <UserModal
      open={Boolean(notice)}
      title="CampusXchange"
      onClose={() => setNotice("")}
    >
      {notice}
    </UserModal>

    <UserModal
      open={passwordModal}
      title="Confirm it's you"
      confirmText="Send OTP"
      onConfirm={confirmSendOtp}
      onClose={() => !otpBusy && setPasswordModal(false)}
      loading={otpBusy}
      error={otpModalError}
    >
      <p style={{ margin: 0 }}>
        Enter your current password. We will send a 6-digit OTP to{" "}
        <strong>{email}</strong> to verify the new email address.
      </p>
      <input
        type="password"
        value={otpPassword}
        autoComplete="current-password"
        placeholder="Current password"
        onChange={(e) => setOtpPassword(e.target.value)}
        onKeyDown={(e) => e.key === "Enter" && confirmSendOtp()}
      />
    </UserModal>
    </>
  );
};

export default Profile;