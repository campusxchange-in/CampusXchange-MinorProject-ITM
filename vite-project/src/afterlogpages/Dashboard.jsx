import React, { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "./components/Navbar";
import Sidebar from "./components/Sidebar";

import "./Dashboard.css";

import {
  FaHome,
  FaSearch,
  FaThLarge,
  FaHeart,
  FaRegHeart,
  FaShoppingBag,
  FaBell,
  FaCommentDots,
  FaUser,
  FaCog,
  FaQuestionCircle,
  FaSignOutAlt,
  FaPlus,
  FaChevronDown,
  FaChevronRight,
  FaMapMarkerAlt,
  FaBoxOpen,
  FaEye,
  FaCheckCircle,
} from "react-icons/fa";

// CHANGE THESE PATHS ACCORDING TO YOUR ASSETS
import storeImage from "../assets/store.png";

import { useAuth } from "../context/AuthContext";
import {
  fetchUserDashboard,
  fetchUserProducts,
  toggleUserWishlist,
} from "../services/userMarketService";
import {
  userFormatPrice,
  userFormatDate,
  userGreeting,
} from "../utils/userFormat";


const Dashboard = () => {
  const [profileOpen, setProfileOpen] = useState(false);

  const navigate = useNavigate();

  const { user } = useAuth();
  const firstName = (user?.full_name || "").trim().split(" ")[0];

  // Everything below comes from the database (backend/user/*.php)
  const [products, setProducts] = useState([]);
  const [stats, setStats] = useState({ wishlist: 0, orders: 0, active_listings: 0 });
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // ids of products the user has wishlisted
  const [dashboardWishlist, setDashboardWishlist] = useState([]);

  useEffect(() => {
    let active = true;

    Promise.all([
      fetchUserProducts({ limit: 9 }),
      fetchUserDashboard(),
    ])
      .then(([p, d]) => {
        if (!active) return;
        setProducts(p.products);
        setDashboardWishlist(
          p.products.filter((x) => x.wishlisted).map((x) => x.id)
        );
        setStats(d.stats);
        setOrders(d.recent_orders);
      })
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoading(false));

    return () => {
      active = false;
    };
  }, []);

  // Popular tags / category -> Browse page with category filter
  const goToCategory = (categoryName) => {
    navigate("/browseproducts", { state: { category: categoryName } });
  };

  const goToProduct = (productId) => navigate(`/buy/${productId}`);

  // Heart click — saved in DB; card click is not triggered
  const toggleDashboardWishlist = async (e, productId) => {
    e.stopPropagation();

    const wasSaved = dashboardWishlist.includes(productId);

    // optimistic UI
    setDashboardWishlist((prev) =>
      wasSaved ? prev.filter((id) => id !== productId) : [...prev, productId]
    );
    setStats((s) => ({ ...s, wishlist: Math.max(0, s.wishlist + (wasSaved ? -1 : 1)) }));

    try {
      await toggleUserWishlist(productId);
    } catch (err) {
      // roll back
      setDashboardWishlist((prev) =>
        wasSaved ? [...prev, productId] : prev.filter((id) => id !== productId)
      );
      setStats((s) => ({ ...s, wishlist: Math.max(0, s.wishlist + (wasSaved ? 1 : -1)) }));
    }
  };

  // First 4 newest = Featured, next 5 = Recently Added
  const featuredProducts = products.slice(0, 4);
  const recentProducts = products.slice(4, 9);

    

  return (

        <>
        <Navbar />
        <Sidebar />
    <div className="dashboard-page">

      


      {/* ==============================
              MAIN AREA
      =============================== */}

      <main className="dashboard-main">

        {/* ==============================
                TOP NAVBAR
        =============================== */}

        


        {/* ==============================
                DASHBOARD CONTENT
        =============================== */}

        <div className="dashboard-content">


          {/* ==============================
                  HERO
          =============================== */}

          <section className="dashboard-hero">

            <div className="hero-content">

              <p className="hero-small-title">
                {userGreeting()}
              </p>

              <h1>
                {firstName || "there"}! <span>👋</span>
              </h1>

              <p className="hero-description">
                Find everything you need on your campus.
              </p>


              {/* HERO SEARCH */}

              {/* <div className="hero-search">

                <FaSearch />

                <input
                  type="text"
                  placeholder="Search books, electronics, furniture..."
                />

                <button>
                  Search
                </button>

              </div> */}


              {/* POPULAR */}

              <div className="popular-tags">

                <span>Popular:</span>

                <button onClick={() => goToCategory("Books")}>Books</button>
                <button onClick={() => goToCategory("Electronics")}>Laptop</button>
                <button onClick={() => goToCategory("Books")}>Notes</button>
                <button onClick={() => goToCategory("Furniture")}>Chair</button>
                <button onClick={() => goToCategory("Electronics")}>iPhone</button>
                <button onClick={() => goToCategory("Vehicles")}>Cycle</button>

              </div>

            </div>


            {/* STORE IMAGE */}

            <div className="hero-image">

              <img
                src={storeImage}
                alt="Campus marketplace"
              />

            </div>

          </section>


          {/* ==============================
                  FEATURED PRODUCTS
          =============================== */}

          <section className="dashboard-section">

            <div className="section-heading">

              <div>

                <h2>
                  Featured for You
                </h2>

              </div>

              <Link to="/browseproducts">
                View All <FaChevronRight />
              </Link>

            </div>


            {error && <p className="product-description">{error}</p>}
            {loading && <p className="product-description">Loading products...</p>}
            {!loading && !error && products.length === 0 && (
              <p className="product-description">No products have been listed yet.</p>
            )}

            <div className="featured-grid">

              {featuredProducts.map((product, index) => (

                <div
                  className="dashboard-product-card"
                  key={product.id}
                  onClick={() => goToProduct(product.id)}
                >

                  {/* IMAGE */}

                  <div className="dashboard-product-image">

                    {product.image && (
                      <img
                        src={product.image}
                        alt={product.name}
                      />
                    )}

                    <button
                      className="heart-button"
                      onClick={(e) =>
                        toggleDashboardWishlist(e, product.id)
                      }
                    >
                      {dashboardWishlist.includes(product.id) ? (
                        <FaHeart />
                      ) : (
                        <FaRegHeart />
                      )}
                    </button>

                    <span className="product-category">
                      {product.category}
                    </span>

                  </div>


                  {/* DETAILS */}

                  <div className="product-details">

                    <h3>
                      {product.name}
                    </h3>

                    <p className="product-description">
                      {product.condition}
                    </p>

                    <div className="product-location">

                      <FaMapMarkerAlt />

                      {product.location}

                    </div>


                    <div className="product-bottom">

                      <strong>
                        {userFormatPrice(product.price)}
                      </strong>

                      <button>
                        View Details
                      </button>

                    </div>

                  </div>

                </div>

              ))}

            </div>

          </section>


          {/* ==============================
                  RECENTLY ADDED
          =============================== */}

          <section className="dashboard-section">

            <div className="section-heading">

              <h2>
                Recently Added
              </h2>

              <Link to="/browseproducts">
                View All <FaChevronRight />
              </Link>

            </div>


            <div className="recent-products">

              {recentProducts.map((product, index) => (

                <div
                  className="recent-product-card"
                  key={product.id}
                  onClick={() => goToProduct(product.id)}
                >

                  <div className="recent-image">

                    {product.image && (
                      <img
                        src={product.image}
                        alt={product.name}
                      />
                    )}

                    <button
                      onClick={(e) =>
                        toggleDashboardWishlist(e, product.id)
                      }
                    >
                      {dashboardWishlist.includes(product.id) ? (
                        <FaHeart />
                      ) : (
                        <FaRegHeart />
                      )}
                    </button>

                  </div>

                  <h3>
                    {product.name}
                  </h3>

                  <strong>
                    {userFormatPrice(product.price)}
                  </strong>

                </div>

              ))}

            </div>

          </section>


        {/* ==============================
                  SELL CTA BANNER
          =============================== */}

          <section className="sell-cta-banner">

            <div className="sell-cta-text">

              <h2>
                Got something to sell?
              </h2>

              <p>
                List your item in under 2 minutes and reach
                thousands of students on your campus.
              </p>

            </div>

            <button
              className="sell-cta-button"
              onClick={() => navigate("/sell-item")}
            >
              <FaPlus />
              Sell an Item
            </button>

          </section>
        </div>



        {/* ==============================
              RIGHT ACTIVITY PANEL
        =============================== */}

        <aside className="dashboard-right">


          {/* ACTIVITY */}

          <div className="right-card">

            <h2>
              Your Activity
            </h2>


            <div className="activity-item">

              <div className="activity-icon blue">
                <FaHeart />
              </div>

              <span>
                Wishlist Items
              </span>

              <strong>
                {stats.wishlist}
              </strong>

            </div>


            <div className="activity-item">

              <div className="activity-icon green">
                <FaShoppingBag />
              </div>

              <span>
                My Orders
              </span>

              <strong>
                {stats.orders}
              </strong>

            </div>


            <div className="activity-item">

              <div className="activity-icon purple">
                <FaBoxOpen />
              </div>

              <span>
                Active Listings
              </span>

              <strong>
                {stats.active_listings}
              </strong>

            </div>

          </div>


          {/* RECENT ORDERS */}

          <div className="right-card">

            <div className="right-card-heading">

              <h2>
                Recent Orders
              </h2>

              <Link to="/myorders">
                View All
              </Link>

            </div>


            <div className="orders-list">

              {orders.length === 0 && !loading && (
                <p className="product-description">No orders yet.</p>
              )}

              {orders.map((order) => (

                <div
                  className="order-item"
                  key={order.id}
                >

                  {order.image && (
                    <img
                      src={order.image}
                      alt={order.name}
                    />
                  )}

                  <div className="order-info">

                    <h3>
                      {order.name}
                    </h3>

                    <span
                      className={
                        order.status === "Completed"
                          ? "status delivered"
                          : "status shipped"
                      }
                    >
                      {order.status}
                    </span>

                    <small>
                      {userFormatDate(order.date)}
                    </small>

                  </div>

                  <strong>
                    {userFormatPrice(order.price)}
                  </strong>

                </div>

              ))}

            </div>

          </div>

        </aside>

      </main>

    </div>
    </>
  );
};

export default Dashboard;