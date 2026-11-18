import React, { useState, useEffect } from "react";
import { useNavigate, useParams } from "react-router-dom";

import {
  FaArrowLeft,
  FaMapMarkerAlt,
  FaClock,
  FaStar,
  FaShieldAlt,
  FaPaperPlane,
  FaUserCircle,
  FaTag,
  FaQuoteLeft,
  FaHandshake,
  FaCalendarAlt,
  FaComments,
  FaFlag,
} from "react-icons/fa";

import { userGet } from "../../services/userApiClient";
import { startUserChat } from "../../services/userChatService";
import {
  userFormatPrice,
  userTimeAgo,
  userFormatDate,
  userInitial,
} from "../../utils/userFormat";

import "./BuyProduct.css";

import Navbar from "../components/Navbar";
import Sidebar from "../components/Sidebar";
import UserModal from "../components/UserModal";
import UserReportModal from "../components/UserReportModal";




// ================================
// MAIN COMPONENT
// ================================

function BuyProduct() {

  const navigate = useNavigate();
  const { productId } = useParams();

  // ---- live data from the database ----
  const [product, setProduct] = useState(null);
  const [buyProductSeller, setBuyProductSeller] = useState(null);
  const [reviews, setReviews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState("");
  const [activeImage, setActiveImage] = useState(0);
  const [starting, setStarting] = useState(false);

  // report this product (screenshot evidence required)
  const [reportOpen, setReportOpen] = useState(false);
  const [reportNotice, setReportNotice] = useState("");

  // P2P deal: buyer chats with seller, agrees price + pickup there.
  const [buyProductBuyerMessage, setBuyProductBuyerMessage] = useState("");

  useEffect(() => {
    let active = true;
    setLoading(true);
    setLoadError("");

    userGet("product_details.php", { product_id: productId })
      .then((d) => {
        if (!active) return;
        setProduct(d.product);
        setBuyProductSeller(d.seller);
        setReviews(d.reviews);
        setActiveImage(0);
        setBuyProductBuyerMessage(
          `Hi, I'm interested in your "${d.product.name}". Is it still available?`
        );
      })
      .catch((e) => active && setLoadError(e.message))
      .finally(() => active && setLoading(false));

    return () => {
      active = false;
    };
  }, [productId]);

  const [buyProductOfferPrice, setBuyProductOfferPrice] = useState("");
  const [buyProductPickupSpot, setBuyProductPickupSpot] = useState("");
  const [buyProductPickupDate, setBuyProductPickupDate] = useState("");
  const [buyProductPickupTime, setBuyProductPickupTime] = useState("");
  const [buyProductError, setBuyProductError] = useState("");


  // ================================
  // START CHAT WITH SELLER
  // ================================

  const handleBuyProductStartChat = async () => {

    if (starting) return;

    if (!buyProductBuyerMessage.trim()) {
      setBuyProductError("Please write a message to the seller.");
      return;
    }

    const offer = Number(buyProductOfferPrice);

    if (buyProductOfferPrice && (!Number.isFinite(offer) || offer <= 0)) {
      setBuyProductError("Please enter a valid offer price.");
      return;
    }

    setBuyProductError("");

    let fullMessage = buyProductBuyerMessage.trim();

    if (buyProductOfferPrice) {
      fullMessage += `\n\nMy offer: ${userFormatPrice(offer)} (listed at ${userFormatPrice(product.price)})`;
    }

    if (buyProductPickupSpot || buyProductPickupDate || buyProductPickupTime) {
      fullMessage +=
        `\n\nProposed pickup: ${buyProductPickupSpot || "campus (spot TBD)"}` +
        `${buyProductPickupDate ? `, ${buyProductPickupDate}` : ""}` +
        `${buyProductPickupTime ? `, ${buyProductPickupTime}` : ""}`;
    }

    setStarting(true);

    try {
      const res = await startUserChat(product.id, fullMessage);
      navigate(`/messages/${res.chat_id}`);
    } catch (err) {
      setBuyProductError(err.message);
    } finally {
      setStarting(false);
    }
  };


  // ================================
  // PRODUCT NOT FOUND
  // ================================

  if (loading || !product) {
    return (
      <>
        <Navbar />
        <Sidebar />
        <main className="buy-product-page">
          <section className="buy-product-content">
            <div className="buy-product-not-found">
              <h2>{loading ? "Loading..." : "Product not found"}</h2>
              <p>
                {loading
                  ? "Please wait while we load this listing."
                  : loadError || "This listing may have been removed or the link is incorrect."}
              </p>
              <button onClick={() => navigate("/browseproducts")}>
                <FaArrowLeft />
                Back to Browse
              </button>
            </div>
          </section>
        </main>
      </>
    );
  }


  // ================================
  // MAIN RENDER
  // ================================

  return (
    <>
    <Navbar/>
    <Sidebar/>

    <main className="buy-product-page">

      <section className="buy-product-content">

        <button
          className="buy-product-back-link"
          onClick={() => navigate(-1)}
        >
          <FaArrowLeft />
          Back to results
        </button>

        <div className="buy-product-layout">

          <div className="buy-product-details-column">

            <div className="buy-product-image-wrap">

              <img
                src={product.images?.[activeImage] || product.image}
                alt={product.name}
              />

              <span className="buy-product-condition-badge">
                {product.condition}
              </span>
            </div>

            {product.images?.length > 1 && (
              <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                {product.images.map((img, i) => (
                  <img
                    key={img}
                    src={img}
                    alt={`${product.name} ${i + 1}`}
                    onClick={() => setActiveImage(i)}
                    style={{
                      width: 64,
                      height: 64,
                      objectFit: "cover",
                      borderRadius: 8,
                      cursor: "pointer",
                      border: i === activeImage ? "2px solid #2563eb" : "2px solid transparent",
                    }}
                  />
                ))}
              </div>
            )}


            <div className="buy-product-info-card">

              <span className="buy-product-category-tag">
                <FaTag />
                {product.category}
              </span>

              <h1>
                {product.name}
              </h1>

              <div className="buy-product-price">
                {userFormatPrice(product.price)}
              </div>

              <div className="buy-product-meta-row">

                <span>
                  <FaMapMarkerAlt />
                  {product.location}
                </span>

                <span>
                  <FaClock />
                  Posted {userTimeAgo(product.posted_at)}
                </span>

              </div>

              <p className="buy-product-description">
                {product.description || "The seller has not added a description."}
              </p>

            </div>


            {/* =================================
                SELLER CARD
            ================================= */}

            <div className="buy-product-seller-card">

              <div className="buy-product-seller-avatar">
                {userInitial(buyProductSeller.name)}
              </div>

              <div className="buy-product-seller-info">

                <h3>
                  {buyProductSeller.name}
                </h3>

                <div className="buy-product-seller-rating">

                  <FaStar />
                  {buyProductSeller.rating ?? "New"}
                  <span>
                    · {buyProductSeller.total_deals} deals
                  </span>

                </div>

                <p>
                  Member since {userFormatDate(buyProductSeller.member_since)}
                </p>

              </div>

              <FaUserCircle
                className="buy-product-seller-icon"
              />

            </div>


            {/* =================================
                SELLER REVIEWS
            ================================= */}

            <div className="buy-product-reviews-card">

              <h2>
                Seller Reviews
              </h2>

              {reviews.length === 0 && (
                <p className="buy-product-message-subtext">No reviews yet.</p>
              )}

              {reviews.map((review) => (

                <div
                  className="buy-product-review-item"
                  key={review.id}
                >

                  <FaQuoteLeft className="buy-product-review-quote-icon" />

                  <div className="buy-product-review-body">

                    <div className="buy-product-review-top">

                      <span className="buy-product-review-name">
                        {review.reviewer}
                      </span>

                      <span className="buy-product-review-stars">
                        <FaStar />
                        {review.rating}
                      </span>

                    </div>

                    <p>
                      {review.text}
                    </p>

                  </div>

                </div>

              ))}

            </div>

          </div>


          {/* =================================
              RIGHT: CHAT / DEAL PANEL (P2P)
          ================================= */}

          <aside className="buy-product-side-column">

            {/* Price + how it works */}

            <div className="buy-product-summary-card">

              <h2>Deal with Seller</h2>

              <div className="buy-product-summary-row">
                <span>Asking price</span>
                <span>{userFormatPrice(product.price)}</span>
              </div>

              <div className="buy-product-summary-row">
                <span>Platform fee</span>
                <span>₹0</span>
              </div>

              <p className="buy-product-peer-note">
                <FaComments />
                Chat with the seller, agree on the price and
                meeting spot, then pay the seller directly when
                you meet and inspect the item.
              </p>

              <div className="buy-product-safety-note">
                <FaShieldAlt />
                <span>
                  Always meet on campus and inspect the item
                  before paying. Don't pay in advance.
                </span>
              </div>

            </div>


            {/* Optional pickup proposal (sent with first message) */}

            <div className="buy-product-message-card">

              <h2>
                <FaHandshake style={{ marginRight: 6 }} />
                Propose Pickup (optional)
              </h2>

              <p className="buy-product-message-subtext">
                You can also decide this later in the chat.
              </p>

              <label className="buy-product-pickup-label">Meeting Location</label>

              <select
                className="buy-product-pickup-input"
                value={buyProductPickupSpot}
                onChange={(e) => setBuyProductPickupSpot(e.target.value)}
              >
                <option value="">Select campus location</option>
                <option value="Main Gate">Main Gate</option>
                <option value="College Library">College Library</option>
                <option value="College Canteen">College Canteen</option>
                <option value="Admin Block">Admin Block</option>
                <option value="Hostel Gate">Hostel Gate</option>
                <option value="Parking Area">Parking Area</option>
                <option value="Department Building">Department Building</option>
                <option value="Other">Other</option>
              </select>

              <label className="buy-product-pickup-label">Pickup Date</label>

              <div className="buy-product-date-wrapper">
                <FaCalendarAlt className="buy-product-date-icon" />
                <input
                  type="date"
                  className="buy-product-pickup-input buy-product-date-input"
                  value={buyProductPickupDate}
                  onChange={(e) => setBuyProductPickupDate(e.target.value)}
                />
              </div>

              <label className="buy-product-pickup-label">Pickup Time</label>

              <div className="buy-product-time-wrapper">
                <FaClock className="buy-product-time-icon" />
                <input
                  type="time"
                  className="buy-product-pickup-input buy-product-time-input"
                  value={buyProductPickupTime}
                  onChange={(e) => setBuyProductPickupTime(e.target.value)}
                />
              </div>

            </div>


            {/* Chat with seller */}

            <div className="buy-product-message-card">

              <h2>Chat with Seller</h2>

              <p className="buy-product-message-subtext">
                Ask {buyProductSeller.name.split(" ")[0]} about
                this item or make an offer.
              </p>

              <label className="buy-product-pickup-label">
                Your Offer (optional)
              </label>

              <input
                type="number"
                min="1"
                className="buy-product-pickup-input"
                value={buyProductOfferPrice}
                onChange={(e) => {
                  setBuyProductOfferPrice(e.target.value);
                  setBuyProductError("");
                }}
                placeholder={`Listed at ${userFormatPrice(product.price)}`}
              />

              <textarea
                rows={4}
                value={buyProductBuyerMessage}
                onChange={(e) => {
                  setBuyProductBuyerMessage(e.target.value);
                  setBuyProductError("");
                }}
                placeholder="Type your message..."
              />

              {buyProductError && (
                <p className="buy-product-payment-message" style={{ marginTop: 10 }}>
                  {buyProductError}
                </p>
              )}

              <button
                className="buy-product-confirm-button"
                style={{ marginTop: 12 }}
                onClick={handleBuyProductStartChat}
                disabled={starting || product.is_own || product.status !== "Active"}
              >
                <FaPaperPlane style={{ marginRight: 8 }} />
                {product.is_own
                  ? "This is your listing"
                  : product.status !== "Active"
                  ? "No longer available"
                  : starting
                  ? "Starting..."
                  : product.chat_id
                  ? "Send Message"
                  : "Start Chat"}
              </button>

            </div>


            {/* Report this listing */}

            {!product.is_own && (
              <button
                type="button"
                className="buy-product-report-button"
                onClick={() => setReportOpen(true)}
              >
                <FaFlag />
                Report this listing
              </button>
            )}

          </aside>

        </div>

      </section>

    </main>

    <UserReportModal
      open={reportOpen}
      product={product}
      onClose={() => setReportOpen(false)}
      onDone={(msg) => {
        setReportOpen(false);
        setReportNotice(msg);
      }}
    />

    <UserModal
      open={Boolean(reportNotice)}
      title="Report sent"
      onClose={() => setReportNotice("")}
    >
      {reportNotice}
    </UserModal>
    </>
  );

}

export default BuyProduct;
