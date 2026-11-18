import React, { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "./components/Navbar";

import {
  FaHome,
  FaSearch,
  FaThLarge,
  FaHeart,
  FaShoppingBag,
  FaCommentDots,
  FaPlus,
  FaSignOutAlt,
  FaBell,
  FaChevronDown,
  FaArrowLeft,
  FaCloudUploadAlt,
  FaTimes,
  FaMapMarkerAlt,
  FaPhone,
  FaRocket,
  FaSave,
  FaUser,
  FaCog,
  FaQuestionCircle,
  FaClipboardList ,
} from "react-icons/fa";

import "./SellItem.css";
import UserModal from "./components/UserModal";
import {
  fetchUserCategories,
  createUserListing,
} from "../services/userMarketService";
import ElectronicsVerification from "./components/ElectronicsVerification";

const SellItem = () => {
  const navigate = useNavigate();

  const [images, setImages] = useState([]);

  const [showProfileMenu, setShowProfileMenu] = useState(false);
const [billFile, setBillFile] = useState(null);
const [boxImage, setBoxImage] = useState(null);

  // categories + submit state come from / go to the database
  const [categories, setCategories] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState("");
  const [notice, setNotice] = useState({ text: "", goToListings: false });

  useEffect(() => {
    fetchUserCategories()
      .then((d) => setCategories(d.categories))
      .catch((e) => setFormError(e.message));
  }, []);
  const [formData, setFormData] = useState({
    name: "",
    category: "",
    price: "",
    condition: "",
    tags: "",
    description: "",
    location: "",
    hostel: "",
    contactMethod: "phone",
    productType: "",
    phone: "",
  });

  /* =========================================
     HANDLE INPUT
  ========================================= */

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };


  /* =========================================
     IMAGE UPLOAD
  ========================================= */
const handleImageUpload = (e) => {
  const files = Array.from(e.target.files);

  if (images.length + files.length > 5) {
    setNotice({ text: "You can upload maximum 5 images.", goToListings: false });
    return;
  }

  const newImages = files.map((file) => ({
    file: file,
    url: URL.createObjectURL(file),
  }));

  setImages((prev) => [...prev, ...newImages]);
};


// ELECTRONICS FILE UPLOAD

const handleBillChange = (e) => {
  const file = e.target.files[0];

  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    setNotice({ text: "Bill file must be smaller than 5MB.", goToListings: false });
    return;
  }

  setBillFile(file);
};


const handleBoxImageChange = (e) => {
  const file = e.target.files[0];

  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    setNotice({ text: "Box image must be smaller than 5MB.", goToListings: false });
    return;
  }

  setBoxImage(file);
};



  /* =========================================
     REMOVE IMAGE
  ========================================= */

  const removeImage = (index) => {
    setImages((prev) =>
      prev.filter((_, i) => i !== index)
    );
  };


  /* =========================================
     PUBLISH ITEM
  ========================================= */

  const selectedCategory = categories.find(
    (c) => String(c.category_id) === String(formData.category)
  );

  const sendListing = async (mode) => {
    if (submitting) return;
    setFormError("");

    if (mode === "publish") {
      if (
        !formData.name ||
        !formData.category ||
        !formData.price ||
        !formData.condition ||
        !formData.description
      ) {
        setFormError("Please fill all required fields.");
        return;
      }

      if (images.length === 0) {
        setFormError("Please upload at least one product image.");
        return;
      }

      if (selectedCategory?.invoice_required && !billFile) {
        setFormError("This category requires your purchase bill / invoice.");
        return;
      }
    } else if (!formData.name.trim()) {
      setFormError("Enter a product name to save a draft.");
      return;
    }

    const body = new FormData();
    body.append("mode", mode);
    body.append("title", formData.name);
    body.append("description", formData.description);
    body.append("price", formData.price);
    body.append("condition", formData.condition);
    body.append("category_id", formData.category);
    images.forEach((image) => body.append("images[]", image.file));
    if (billFile) body.append("invoice", billFile);

    setSubmitting(true);

    try {
      const res = await createUserListing(body);
      setNotice({ text: res.message, goToListings: true });
    } catch (err) {
      setFormError(err.message);
      window.scrollTo({ top: 0, behavior: "smooth" });
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmit = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    sendListing("publish");
  };

  const handleSaveDraft = () => sendListing("draft");

  return (
<>
      <Navbar />
      <UserModal
        open={Boolean(notice.text)}
        title="CampusXchange"
        onClose={() => {
          const go = notice.goToListings;
          setNotice({ text: "", goToListings: false });
          if (go) navigate("/mylistings");
        }}
      >
        {notice.text}
      </UserModal>

    <div className="sell-page">

      


      {/* =====================================
          MAIN AREA
      ===================================== */}

      <div className="sell-layout">


        {/* ===================================
            SIDEBAR
        =================================== */}

        <aside className="sell-sidebar">

        <nav>

          {/*  <Link to="/dashboard">
              <FaHome />
              Dashboard
            </Link>

            <Link to="/browse">
              <FaSearch />
              Browse Products
            </Link>

            <Link to="/categories">
              <FaThLarge />
              Categories
            </Link>

            <Link to="/wishlist">
              <FaHeart />
              Wishlist
            </Link>

            <Link to="/orders">
              <FaShoppingBag />
              My Orders
            </Link>


            <div className="sidebar-divider"></div>*/}


            <Link to= "/messages">
              <FaCommentDots />
              Messages

              {/* <span className="message-count">
                3
              </span> */}
            </Link>


            <Link
              to="/sell-item"
              className="active-sidebar-link"
            >
              <FaPlus />
              Sell an Item
            </Link>

          </nav>


          {/* <button
            className="sidebar-logout"
            onClick={() => navigate("/")}
          >
            <FaSignOutAlt />
            Logout
          </button> */}

        </aside>


        {/* ===================================
            CONTENT
        =================================== */}

        <main className="sell-content">


          {/* Page heading */}

          <div className="sell-heading">

            <div>

              <Link
                to="/dashboard"
                className="back-dashboard"
              >
                <FaArrowLeft />
                Back to Dashboard
              </Link>

              <h1>
                Sell an Item
              </h1>

              <p>
                Fill in the details below to list your item
                and start selling.
              </p>

            </div>


            {/* Tips */}

            <div className="sales-tip">

              <div className="tip-icon">
                <FaRocket />
              </div>

              <div>
                <strong>
                  Tips for better sales
                </strong>

                <p>
                  Add clear photos, write a detailed description,
                  set a fair price and choose the right category.
                </p>
              </div>

            </div>

          </div>


          {/* =================================
              FORM
          ================================= */}

          <form
            className="sell-form"
            onSubmit={handleSubmit}
          >
          {formError && (
            <p style={{ color: "#dc2626", fontWeight: 600, margin: "0 0 12px" }}>
              {formError}
            </p>
          )}



            {/* =================================
                LEFT COLUMN
            ================================= */}

            <div className="sell-main-column">


              {/* Product Photos */}

              <section className="sell-section">

                <div className="section-title">

                  <h2>
                    Product Photos
                  </h2>

                  <p>
                    Add up to 5 photos of your item
                  </p>

                </div>


                {/* Upload */}

                <label
                  htmlFor="product-images"
                  className="upload-box"
                >

                  <FaCloudUploadAlt />

                  <h3>
                    Drag & drop photos here
                  </h3>

                  <p>
                    or click to upload
                  </p>

                  <span>
                    JPG, PNG • Max size 5MB • Up to 5 images
                  </span>

                </label>


                <input
                  id="product-images"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg"
                  multiple
                  hidden
                  onChange={handleImageUpload}
                />


                {/* Image previews */}

                {images.length > 0 && (

                  <div className="uploaded-images">

                    {images.map((image, index) => (

                      <div
                        className="uploaded-image"
                        key={index}
                      >

                        <img
                          src={image.url}
                          alt={`Product ${index + 1}`}
                        />

                        <button
                          type="button"
                          onClick={() =>
                            removeImage(index)
                          }
                        >
                          <FaTimes />
                        </button>

                        {index === 0 && (
                          <span>
                            Main
                          </span>
                        )}

                      </div>

                    ))}


                    {images.length < 5 && (

                      <label
                        htmlFor="product-images"
                        className="add-more-image"
                      >
                        <FaPlus />
                        <span>Add more</span>
                      </label>

                    )}

                  </div>

                )}

              </section>


              {/* Description */}

              <section className="sell-section">

                <div className="section-title">

                  <h2>
                    Description
                  </h2>

                  <p>
                    Describe your item, its features,
                    condition and any other details.
                  </p>

                </div>


                <div className="textarea-wrapper">

                  <textarea
                    name="description"
                    value={formData.description}
                    onChange={handleChange}
                    placeholder="Write a detailed description..."
                    maxLength="1000"
                    required
                  ></textarea>

                  <span>
                    {formData.description.length} / 1000
                  </span>

                </div>

              </section>

            </div>


            {/* =================================
                RIGHT COLUMN
            ================================= */}

            <div className="sell-side-column">


              {/* Product Information */}

              <section className="sell-section">

                <div className="section-title">

                  <h2>
                    Product Information
                  </h2>

                </div>


                {/* Name */}

                <div className="field">

                  <label>
                    Product Name <b>*</b>
                  </label>

                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    placeholder="Enter product name"
                    required
                  />

                </div>


                {/* Category */}

                <div className="field">

                  <label>
                    Category <b>*</b>
                  </label>

                  <select
                    name="category"
                    value={formData.category}
                    onChange={handleChange}
                    required
                  >

                    <option value="">
                      Select category
                    </option>

                    {categories.map((c) => (
                      <option key={c.category_id} value={c.category_id}>
                        {c.category_name}
                      </option>
                    ))}

                  </select>

                </div>


              

                {/* Electronics Verification */}

                                {selectedCategory && /electronic/i.test(selectedCategory.category_name) && (
                                        <>
                                          <div className="field">
                                            <label>
                                              Product Type <b>*</b>
                                            </label>

                                            <select
                                              name="productType"
                                              value={formData.productType}
                                              onChange={handleChange}
                                              required
                                            >
                                              <option value="">
                                                Select product type
                                              </option>

                                              <option value="Mobile">
                                                Mobile / Smartphone
                                              </option>

                                              <option value="Laptop">
                                                Laptop
                                              </option>

                                              <option value="Tablet">
                                                Tablet
                                              </option>

                                              <option value="Smartwatch">
                                                Smartwatch
                                              </option>

                                              <option value="Headphones">
                                                Headphones
                                              </option>

                                              <option value="Fan">
                                                Fan
                                              </option>

                                              <option value="Table Lamp">
                                                Table Lamp
                                              </option>

                                              <option value="Keyboard">
                                                Keyboard
                                              </option>

                                              <option value="Mouse">
                                                Mouse
                                              </option>

                                              <option value="Other">
                                                Other Electronics
                                              </option>
                                            </select>
                                          </div>

                                          {selectedCategory?.invoice_required && (
                                            <ElectronicsVerification
                                              billFile={billFile}
                                              boxImage={boxImage}
                                              onBillChange={handleBillChange}
                                              onBoxImageChange={handleBoxImageChange}
                                            />
                                          )}
                                        </>
                                      )}

                {/* Price + Condition */}

                <div className="two-fields">

                  <div className="field">

                    <label>
                      Price (₹) <b>*</b>
                    </label>

                    <input
                      type="number"
                      name="price"
                      value={formData.price}
                      onChange={handleChange}
                      placeholder="Enter price"
                      min="0"
                      required
                    />

                  </div>


                  <div className="field">

                    <label>
                      Condition <b>*</b>
                    </label>

                    <select
                      name="condition"
                      value={formData.condition}
                      onChange={handleChange}
                      required
                    >

                      <option value="">
                        Select condition
                      </option>

                      <option value="New">
                        New
                      </option>

                      <option value="Like New">
                        Like New
                      </option>

                      <option value="Good">
                        Good
                      </option>

                      

                      <option value="Used">
                        Used
                      </option>

                    </select>

                  </div>

                </div>


                {/* Tags */}

                <div className="field">

                  <label>
                    Description Tags
                    <small>(optional)</small>
                  </label>

                  <input
                    type="text"
                    name="tags"
                    value={formData.tags}
                    onChange={handleChange}
                    placeholder="Add tags like: gaming, study, useful, etc."
                  />

                </div>

              </section>


              {/* Location */}

              <section className="sell-section">

                <div className="section-title">

                  <h2>
                    Location & Contact
                  </h2>

                </div>


                <div className="field">

                  <label>
                    Campus / Location <b>*</b>
                  </label>

                  <div className="icon-input">

                    <input
                      type="text"
                      name="location"
                      value={formData.location}
                      onChange={handleChange}
                      placeholder="e.g. ITM University, Gwalior"
                      required
                    />

                    <FaMapMarkerAlt />

                  </div>

                </div>


                <div className="field">

                  <label>
                    Hostel / Block / Area
                    <small>(optional)</small>
                  </label>

                  <input
                    type="text"
                    name="hostel"
                    value={formData.hostel}
                    onChange={handleChange}
                    placeholder="e.g. Boys Hostel A, Room 203"
                  />

                </div>


                {/* Contact method */}

                <div className="field">

                  <label>
                    Preferred Contact Method <b>*</b>
                  </label>

                  <div className="contact-method">

                    {/* <button
                      type="button"
                      className={
                        formData.contactMethod === "phone"
                          ? "selected"
                          : ""
                      }
                      onClick={() =>
                        setFormData((prev) => ({
                          ...prev,
                          contactMethod: "phone",
                        }))
                      }
                    >
                      <FaPhone />
                      Phone Call
                    </button> */}


                    <button
                      type="button"
                      className={
                        formData.contactMethod === "chat"
                          ? "selected"
                          : ""
                      }
                      onClick={() =>
                        setFormData((prev) => ({
                          ...prev,
                          contactMethod: "chat",
                        }))
                      }
                    >
                      <FaCommentDots />
                      Chat / Message
                    </button>

                  </div>

                </div>


                {/* Phone */}

                <div className="field">

                  <label>
                    Contact Number <b>*</b>
                  </label>

                  <div className="phone-input">

                    <span>
                      +91
                    </span>

                    <input
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      placeholder="Enter your mobile number"
                      required
                    />

                  </div>

                  <small className="field-help">
                    Buyers will use this number to contact you.
                  </small>

                </div>

              </section>

            </div>

          </form>


          {/* =================================
              BOTTOM ACTION BAR
          ================================= */}

          <div className="sell-bottom-actions">

            <button
              type="button"
              className="save-draft"
              onClick={handleSaveDraft}
            >
              <FaSave />
              Save Draft
            </button>


            <button
              type="button"
              className="publish-item"
              onClick={handleSubmit}
            >
              <FaRocket />
              Publish Item
            </button>

          </div>

        </main>

      </div>

    </div>
    </>
  );
};

export default SellItem;