import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import Navbar from "./components/Navbar";
import Sidebar from "./components/Sidebar";
import {
  FaShoppingBag,
  FaClock,
  FaCheck,
  FaTimes,
  FaBoxOpen,
  FaChevronDown,
} from "react-icons/fa";

import "./Myorders.css";
import {
  fetchUserOrders,
  createUserReview,
} from "../services/userMarketService";
import { userFormatPrice, userFormatDate } from "../utils/userFormat";


const Myorders = () => {
  const [activeTab, setActiveTab] = useState("All Orders");
  const [sortBy, setSortBy] = useState("Latest");

  const navigate = useNavigate();

  // ---- live data from the database ----
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // inline review form
  const [reviewingId, setReviewingId] = useState(null);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewText, setReviewText] = useState("");
  const [reviewBusy, setReviewBusy] = useState(false);

  const loadOrders = async () => {
    try {
      const d = await fetchUserOrders();
      setOrders(d.orders);
      setError("");
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadOrders();
  }, []);

  const submitReview = async (order) => {
    setReviewBusy(true);
    try {
      await createUserReview(order.id, reviewRating, reviewText);
      setReviewingId(null);
      setReviewText("");
      setReviewRating(5);
      await loadOrders();
    } catch (e) {
      setError(e.message);
    } finally {
      setReviewBusy(false);
    }
  };

  const count = (status) => orders.filter((o) => o.status === status).length;

  const stats = [
    {
      title: "Total Orders",
      count: orders.length,
      subtitle: "All time",
      icon: <FaShoppingBag />,
      className: "total-stat",
    },
    {
      title: "Completed",
      count: count("Completed"),
      subtitle: "Successfully exchanged",
      icon: <FaCheck />,
      className: "delivered-stat",
    },
    {
      title: "Disputed",
      count: count("Disputed"),
      subtitle: "Under admin review",
      icon: <FaClock />,
      className: "active-stat",
    },
    {
      title: "Cancelled",
      count: count("Cancelled"),
      subtitle: "Cancelled orders",
      icon: <FaTimes />,
      className: "cancelled-stat",
    },
  ];

  const tabs = [
    "All Orders",
    "Completed",
    "Disputed",
    "Cancelled",
  ];

  const filteredOrders = orders.filter((order) => {
    if (activeTab === "All Orders") return true;

    return order.status === activeTab;
  });

  const sortedOrders = [...filteredOrders].sort((a, b) => {
    if (sortBy === "Oldest") return a.id - b.id;
    return b.id - a.id;
  });

  return (
    <>
    <Navbar/>
    <Sidebar/>
    <main className="my-orders-page">

      {/* PAGE HEADER */}
      <section className="orders-header">
        <div>
          <h1>My Orders</h1>
          <p>Track and manage all your orders in one place.</p>
        </div>
      </section>

      {/* ORDER STATS */}
      <section className="order-stats-grid">
        {stats.map((stat, index) => (
          <div className="order-stat-card" key={index}>

            <div className={`order-stat-icon ${stat.className}`}>
              {stat.icon}
            </div>

            <div className="order-stat-info">
              <h3>{stat.title}</h3>
              <strong>{stat.count}</strong>
              <span>{stat.subtitle}</span>
            </div>

          </div>
        ))}
      </section>

      {/* FILTER BAR */}
      <section className="orders-filter-bar">

        <div className="order-tabs">
          {tabs.map((tab) => (
            <button
              key={tab}
              className={`order-tab ${
                activeTab === tab ? "active-order-tab" : ""
              }`}
              onClick={() => setActiveTab(tab)}
            >
              {tab}
            </button>
          ))}
        </div>

        <div className="orders-sort">
          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
          >
            <option value="Latest">Sort by: Latest</option>
            <option value="Oldest">Sort by: Oldest</option>
          </select>

          <FaChevronDown />
        </div>

      </section>

      {/* ORDER LIST */}
      <section className="orders-list">

        {loading && <p>Loading your orders...</p>}
        {error && <p style={{ color: "#dc2626" }}>{error}</p>}

        {loading ? null : sortedOrders.length > 0 ? (
          sortedOrders.map((order) => (

            <article className="order-card" key={order.id}>

              {/* PRODUCT IMAGE AREA */}
              <div className="order-product-image">

                {order.image ? (
                  <img src={order.image} alt={order.name} />
                ) : (
                  <FaBoxOpen />
                )}

              </div>

              {/* PRODUCT INFO */}
              <div className="order-product-info">
                <h3>{order.name}</h3>

                <p>
                  Seller: <span>{order.seller}</span>
                </p>

                <span className="order-category">
                  {order.category}
                </span>
              </div>

              {/* ORDER DETAILS */}
              <div className="order-details">
                <div>
                  <span>Order ID</span>
                  <strong>{order.order_no}</strong>
                </div>

                <div>
                  <span>Order Date</span>
                  <strong>{userFormatDate(order.date)}</strong>
                </div>
              </div>

              {/* PRICE AND STATUS */}
              <div className="order-price-status">

                <div className="order-price">
                  <span>Amount</span>
                  <strong>{userFormatPrice(order.price)}</strong>
                </div>

                <div
                  className={`order-status ${
                    order.status
                      .toLowerCase()
                      .replace(/\s+/g, "-")
                  }`}
                >
                  {order.status}
                </div>

              </div>

              {/* ACTION BUTTONS */}
              <div className="order-actions">

                <button
                  className="view-order-btn"
                  onClick={() => navigate(`/buy/${order.product_id}`)}
                >
                  View Details
                </button>

                {order.chat_id && (
                  <button
                    className="view-order-btn"
                    onClick={() => navigate(`/messages/${order.chat_id}`)}
                  >
                    Open Chat
                  </button>
                )}

                {order.status === "Completed" && order.my_rating === null && (
                  <button
                    className="primary-order-btn"
                    onClick={() =>
                      setReviewingId(reviewingId === order.id ? null : order.id)
                    }
                  >
                    Rate Seller
                  </button>
                )}

                {order.my_rating !== null && (
                  <span className="order-category">
                    You rated {order.my_rating}/5
                  </span>
                )}
              </div>

              {reviewingId === order.id && (
                <div className="order-review-form">
                  <div className="review-form-head">
                    <h4>Rate your seller</h4>
                    <p>Your feedback helps other students trade with confidence.</p>
                  </div>

                  <div className="review-field">
                    <label htmlFor={`rating-${order.id}`}>Rating</label>
                    <select
                      id={`rating-${order.id}`}
                      value={reviewRating}
                      onChange={(e) => setReviewRating(Number(e.target.value))}
                    >
                      {[5, 4, 3, 2, 1].map((n) => (
                        <option key={n} value={n}>
                          {"★".repeat(n)}{"☆".repeat(5 - n)} &nbsp;{n} star{n > 1 ? "s" : ""}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="review-field">
                    <label htmlFor={`review-text-${order.id}`}>Review</label>
                    <textarea
                      id={`review-text-${order.id}`}
                      rows={3}
                      placeholder="How was the exchange? (optional)"
                      value={reviewText}
                      onChange={(e) => setReviewText(e.target.value)}
                    />
                  </div>

                  <div className="review-form-actions">
                    <button
                      type="button"
                      className="view-order-btn"
                      onClick={() => setReviewingId(null)}
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      className="primary-order-btn"
                      disabled={reviewBusy}
                      onClick={() => submitReview(order)}
                    >
                      {reviewBusy ? "Saving..." : "Submit Review"}
                    </button>
                  </div>
                </div>
              )}
            </article>
          ))
        ) : (
          <div className="no-orders">
            <FaShoppingBag />
            <h3>No orders found</h3>
            <p>You don't have any orders in this category.</p>
          </div>
        )}

      </section>

    </main>
    </>
  );
};

export default Myorders;