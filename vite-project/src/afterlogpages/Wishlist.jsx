import React, { useState, useEffect, useMemo } from "react";
import Navbar from "./components/Navbar";
import Sidebar from "./components/Sidebar";
import { useNavigate,Link } from "react-router-dom";
import {
  FaHeart,
  FaShoppingCart,
  FaMapMarkerAlt,
  FaChevronDown,
  FaArrowRight,
  FaBell,
  FaSlidersH,
  FaTrash,
  FaBolt,
} from "react-icons/fa";

import "./Wishlist.css";

import {
  fetchUserWishlist,
  toggleUserWishlist,
} from "../services/userMarketService";
import { userFormatPrice } from "../utils/userFormat";



function Wishlist() {
  const navigate = useNavigate();
  const [activeFilter, setActiveFilter] = useState("All Items");
  const [sortOption, setSortOption] = useState("Recently Added");
  const [wishlist, setWishlist] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;

    fetchUserWishlist()
      .then((d) => active && setWishlist(d.items))
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoading(false));

    return () => {
      active = false;
    };
  }, []);

  // Filter tabs: "All Items" + the categories the user has actually saved
  const filters = useMemo(
    () => ["All Items", ...new Set(wishlist.map((p) => p.category))],
    [wishlist]
  );

  // Remove = soft delete in the database; UI updates immediately
  const removeFromWishlist = async (id) => {
    const removed = wishlist.find((p) => p.id === id);

    setWishlist((previous) =>
      previous.filter((product) => product.id !== id)
    );

    try {
      await toggleUserWishlist(id);
    } catch (e) {
      if (removed) setWishlist((previous) => [removed, ...previous]);
      setError(e.message);
    }
  };

  const filteredProducts = wishlist.filter((product) => {
    if (activeFilter === "All Items") {
      return true;
    }
    return product.category === activeFilter;
  });

  const sortedProducts = [...filteredProducts].sort((a, b) => {
    if (sortOption === "Price: Low to High") {
      return a.price - b.price;
    }

    if (sortOption === "Price: High to Low") {
      return b.price - a.price;
    }

    return new Date(String(b.saved_at).replace(" ", "T")) - new Date(String(a.saved_at).replace(" ", "T"));
  });

  return (
    <>
    <Navbar />
    <Sidebar />
    <main className="wishlist-page">
      <div className="wishlist-container">

        {/* HEADER */}

        <section className="wishlist-header">

          <div className="wishlist-header-left">

            <div className="wishlist-title-icon">
              <FaHeart />
            </div>

            <div>
              <h1 className="wishlist-title">
                My Wishlist
                <span className="wishlist-title-heart">
                  <FaHeart />
                </span>
              </h1>

              <p className="wishlist-subtitle">
                Items you love and want to buy later.
              </p>
            </div>

          </div>

          <div className="wishlist-header-actions">

            <span className="wishlist-item-count">
              {wishlist.length} Items
            </span>

            <Link to="/sell-item" className="wishlist-sell-button">
              <span>+</span>
              Sell an Item
            </Link>

          </div>

        </section>


        {/* FILTER BAR */}

        <section className="wishlist-filter-section">

          <div className="wishlist-filters">

            {filters.map((filter) => (
              <button
                key={filter}
                className={`wishlist-filter-button ${
                  activeFilter === filter
                    ? "wishlist-filter-active"
                    : ""
                }`}
                onClick={() => setActiveFilter(filter)}
              >
                {filter}

                {filter === "All Items" && (
                  <span className="wishlist-filter-count">
                    {wishlist.length}
                  </span>
                )}

                
              </button>
            ))}

          </div>


          <div className="wishlist-sort-wrapper">

            <FaSlidersH className="wishlist-sort-icon" />

            <select
              className="wishlist-sort-select"
              value={sortOption}
              onChange={(e) => setSortOption(e.target.value)}
            >
              <option>Recently Added</option>
              <option>Price: Low to High</option>
              <option>Price: High to Low</option>
            </select>

            <FaChevronDown className="wishlist-sort-arrow" />

          </div>

        </section>


        {/* PRODUCTS */}

        {loading && <p className="wishlist-subtitle">Loading your wishlist...</p>}
        {error && <p className="wishlist-subtitle">{error}</p>}

        {loading ? null : sortedProducts.length > 0 ? (

          <section className="wishlist-grid">

            {sortedProducts.map((product) => (

              <article
                className="wishlist-card"
                key={product.id}
              >

                {/* IMAGE */}

                <div className="wishlist-image-wrapper">

                  {product.image && (
                    <img
                      src={product.image}
                      alt={product.name}
                      className="wishlist-product-image"
                    />
                  )}

                  {product.status !== "Active" && (
                    <span className="wishlist-price-drop">
                      {product.status === "Sold" ? "SOLD" : "UNAVAILABLE"}
                    </span>
                  )}

                  <button
                    className="wishlist-heart-button"
                    onClick={() =>
                      removeFromWishlist(product.id)
                    }
                    aria-label="Remove from wishlist"
                  >
                    <FaHeart />
                  </button>

                </div>


                {/* PRODUCT INFO */}

                <div className="wishlist-product-info">

                  <span
                    className={`wishlist-category wishlist-category-${product.category
                      .toLowerCase()
                      .replace(/\s+/g, "-")
                      .replace("&", "and")}`}
                  >
                    {product.category}
                  </span>

                  <h2 className="wishlist-product-name">
                    {product.name}
                  </h2>


                  <div className="wishlist-price-row">

                    <span className="wishlist-current-price">
                      {userFormatPrice(product.price)}
                    </span>

                  </div>


                  {/* SELLER */}

                  <div className="wishlist-seller-row">

                    <div className="wishlist-seller">

                      <div className="wishlist-seller-avatar">
                        {product.seller.charAt(0)}
                      </div>

                      <span>{product.seller}</span>

                    </div>

                    <span className="wishlist-dot">
                      •
                    </span>

                    <div className="wishlist-location">

                      <FaMapMarkerAlt />

                      <span>{product.location}</span>

                    </div>

                  </div>

                </div>


                {/* CARD ACTIONS */}

                <div className="wishlist-card-actions">

                  <button
                    className="wishlist-cart-button"
                    disabled={product.status !== "Active"}
                    onClick={() => navigate(`/buy/${product.id}`)}
                  >
                    <FaBolt />
                    {product.status === "Active" ? "Buy Now" : "Unavailable"}
                  </button>

                  <button
                    className="wishlist-details-button"
                    onClick={() => navigate(`/buy/${product.id}`)}
                  >
                    View Details
                  </button>

                </div>

              </article>

            ))}

          </section>

        ) : (

          <div className="wishlist-empty">

            <div className="wishlist-empty-icon">
              <FaHeart />
            </div>

            <h2>Your wishlist is empty</h2>

            <p>
              Save items you love and come back to them later.
            </p>

            <Link to="/browseproducts" className="wishlist-browse-button">
              Browse Items
              <FaArrowRight />
            </Link>

          </div>

        )}


        
      </div>
    </main>
    </>
  );
}

export default Wishlist;