import "./FeaturedProduct.css";

import { useEffect, useState } from "react";
import { fetchFeaturedProducts } from "../services/userPublicService";

import {
  FaHeart,
  FaMapMarkerAlt,
  FaArrowRight,
} from "react-icons/fa";


function FeaturedProducts({ onProtectedClick }) {
  const [products, setProducts] = useState([]);
  const [status, setStatus] = useState("loading"); // loading | ready | error

  useEffect(() => {
    let active = true;
    fetchFeaturedProducts(6)
      .then((list) => {
        if (!active) return;
        setProducts(list);
        setStatus("ready");
      })
      .catch(() => {
        if (active) setStatus("error");
      });
    return () => {
      active = false;
    };
  }, []);

  const handleProtectedLink = (e) => {
    e.preventDefault();      // normal navigation rok do
    onProtectedClick();       // login check + modal khol dega agar zaroorat ho
  };
  return (
    <section
      className="featured-products"
      id="featured-products"
      data-aos="fade-up"
    >
      <div className="featured-heading">

        <span>Featured Products</span>

        <h2>
          Discover Popular Products
          <br />
          From Your Campus
        </h2>

        <p>
          Browse quality products posted by verified
          students and grab the best deals.
        </p>

      </div>

      <div className="products-grid">

        {status === "loading" && (
          <p style={{ gridColumn: "1 / -1", textAlign: "center" }}>Loading products...</p>
        )}

        {status === "error" && (
          <p style={{ gridColumn: "1 / -1", textAlign: "center" }}>
            Unable to load products right now. Please try again later.
          </p>
        )}

        {status === "ready" && products.length === 0 && (
          <p style={{ gridColumn: "1 / -1", textAlign: "center" }}>
            No products have been listed yet.
          </p>
        )}

        {products.map((item) => (
          <div className="product-card" key={item.id}>

            <div className="product-image">

              <img
                src={item.image}
                alt={item.name}
              />

              <button className="wishlist">
                <FaHeart />
              </button>

              <span className="badge">
                {item.category}
              </span>

            </div>

            <div className="product-content">

              <h3>{item.name}</h3>

              <h4>₹{item.price.toLocaleString("en-IN")}</h4>

              <p>
                <FaMapMarkerAlt />
                {item.location}
              </p>

              <button className="view-btn" onClick={handleProtectedLink}>
                View Details
                <FaArrowRight />
              </button>

            </div>

          </div>
        ))}

      </div>

    </section>
  );
}

export default FeaturedProducts;