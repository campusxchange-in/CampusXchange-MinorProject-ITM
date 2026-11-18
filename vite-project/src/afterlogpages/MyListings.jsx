import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import Navbar from "./components/Navbar";

import {
  FaStore,
  FaCheckCircle,
  FaFileAlt,
  FaImage,
  FaEllipsisV,
  FaEye,
  FaEdit,
  FaTrash,
  FaFilter,
  FaChevronDown,
  FaChevronLeft,
  FaChevronRight,
  FaLightbulb,
  FaHandshake,
  FaEyeSlash,
  FaBullhorn,
  FaExternalLinkAlt,
  FaLink,
} from "react-icons/fa";

import "./MyListings.css";

import {
  fetchUserListings,
  fetchUserCategories,
  updateUserListing,
  changeUserListingStatus,
  deleteUserListing,
  markUserChatSold,
} from "../services/userMarketService";
import { fetchUserChats } from "../services/userChatService";
import { userFormatPrice } from "../utils/userFormat";




const MyListings = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("active");

  // three-dot menu (holds the id of the card whose menu is open)
  const [openMenuId, setOpenMenuId] = useState(null);
  const [toast, setToast] = useState("");

  // "Mark as sold" modal
  const [buyers, setBuyers] = useState([]);
  const [buyersLoading, setBuyersLoading] = useState(false);
  const [selectedChatId, setSelectedChatId] = useState(null);
  const [soldPrice, setSoldPrice] = useState("");

  // view-modal image fallback if the file cannot be loaded
  const [imageFailed, setImageFailed] = useState(false);

  // ---- live data from the database ----
  const [listings, setListings] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pageError, setPageError] = useState("");
  const [busy, setBusy] = useState(false);
  const [modalError, setModalError] = useState("");
  const [editForm, setEditForm] = useState({
    title: "",
    price: "",
    category_id: "",
    description: "",
  });

  const loadListings = async () => {
    try {
      const d = await fetchUserListings();
      setListings(d.listings);
      setPageError("");
    } catch (e) {
      setPageError(e.message);
    } finally {
      setLoading(false);
    }
  };

  // close the three-dot menu when clicking anywhere outside it
  useEffect(() => {
    if (openMenuId === null) return undefined;
    const onDown = (e) => {
      if (!e.target.closest(".card-menu-wrap")) setOpenMenuId(null);
    };
    const onKey = (e) => {
      if (e.key === "Escape") setOpenMenuId(null);
    };
    document.addEventListener("mousedown", onDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [openMenuId]);

  // small auto-hiding message ("Link copied" etc.)
  useEffect(() => {
    if (!toast) return undefined;
    const t = setTimeout(() => setToast(""), 2500);
    return () => clearTimeout(t);
  }, [toast]);

  useEffect(() => {
    loadListings();
    fetchUserCategories()
      .then((d) => setCategories(d.categories))
      .catch(() => {});
  }, []);

 //------------------FOR PAGE NUMBERS----
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 5;

  const [sortOption, setSortOption] = useState("newest");
  const [categoryFilter, setCategoryFilter] = useState("all");

  const [showSortMenu, setShowSortMenu] = useState(false);
  const [showFilterMenu, setShowFilterMenu] = useState(false);

  // Modal state
  const [modalType, setModalType] = useState(null);
  const [selectedListing, setSelectedListing] = useState(null);

  const openModal = (type, listing) => {
    setModalType(type);
    setSelectedListing(listing);
    setModalError("");
    setImageFailed(false);
    setOpenMenuId(null);

    if (type === "edit") {
      setEditForm({
        title: listing.name,
        price: listing.price,
        category_id: listing.category_id || "",
        description: listing.description || "",
      });
    }

    if (type === "sold") {
      setSoldPrice(listing.price);
      setSelectedChatId(null);
      setBuyers([]);
      setBuyersLoading(true);
      // buyers = people who chatted with the seller about this product
      fetchUserChats()
        .then((d) => {
          const list = (d.chats || []).filter(
            (c) => c.type === "selling" && c.product_id === listing.id
          );
          setBuyers(list);
          if (list.length === 1) setSelectedChatId(list[0].id);
        })
        .catch((e) => setModalError(e.message))
        .finally(() => setBuyersLoading(false));
    }
  };

  const handleMarkSold = async () => {
    if (!selectedChatId) {
      setModalError("Please choose the buyer.");
      return;
    }
    if (!soldPrice || Number(soldPrice) <= 0) {
      setModalError("Enter a valid selling price.");
      return;
    }
    setBusy(true);
    setModalError("");
    try {
      await markUserChatSold(selectedChatId, Number(soldPrice));
      await loadListings();
      closeModal();
      setActiveTab("sold");
      setCurrentPage(1);
      setToast("Listing marked as sold.");
    } catch (e) {
      setModalError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const handleCopyLink = async (item) => {
    setOpenMenuId(null);
    const url = `${window.location.origin}/buy/${item.id}`;
    try {
      await navigator.clipboard.writeText(url);
      setToast("Listing link copied.");
    } catch {
      setToast(url);
    }
  };

  const handleMenuStatus = (item, action) => {
    setOpenMenuId(null);
    handleStatusChange(item, action);
  };

  const handleSaveEdit = async () => {
    setBusy(true);
    setModalError("");
    try {
      await updateUserListing({
        product_id: selectedListing.id,
        title: editForm.title,
        price: editForm.price,
        category_id: editForm.category_id,
        description: editForm.description,
      });
      await loadListings();
      closeModal();
    } catch (e) {
      setModalError(e.message);
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    setBusy(true);
    setModalError("");
    try {
      await deleteUserListing(selectedListing.id);
      await loadListings();
      closeModal();
    } catch (e) {
      setModalError(e.message);
    } finally {
      setBusy(false);
    }
  };

  // publish / hide / activate straight from the card
  const handleStatusChange = async (item, action) => {
    setPageError("");
    try {
      await changeUserListingStatus(item.id, action);
      await loadListings();
    } catch (e) {
      setPageError(e.message);
    }
  };

  const closeModal = () => {
    setModalType(null);
    setSelectedListing(null);
  };

  {/*-------------------------- FILTER SECTION -----------------------------------------*/}


  const filteredListings = listings
  .filter((item) => {
    // Status filter
    if (activeTab === "drafts") {
      return ["Draft", "Inactive", "Removed"].includes(item.status);
    }

    return item.status.toLowerCase() === activeTab;
  })
  .filter((item) => {
    // Category filter
    if (categoryFilter === "all") {
      return true;
    }

    return item.category === categoryFilter;
  })
  .sort((a, b) => {
    // Sorting
    if (sortOption === "price-low") {
      return a.price - b.price;
    }

    if (sortOption === "price-high") {
      return b.price - a.price;
    }

    if (sortOption === "oldest") {
      return a.id - b.id;
    }

    // newest
    return b.id - a.id;
  });


      const activeCount = listings.filter(
  (item) => item.status === "Active"
).length;

const soldCount = listings.filter(
  (item) => item.status === "Sold"
).length;

const draftCount = listings.filter(
  (item) => ["Draft", "Inactive", "Removed"].includes(item.status)
).length;

  // Dummy data ONLY for showing empty/placeholder cards.
  // Later this will come from the products uploaded by the user.
          const dummyItems = [1, 2, 3, 4];

          const totalPages = Math.ceil(
          filteredListings.length / itemsPerPage
        );

        const startIndex = (currentPage - 1) * itemsPerPage;

        const currentListings = filteredListings.slice(
          startIndex,
          startIndex + itemsPerPage
        );

  return (
<>
      <Navbar />
    <div className="my-listings-page">

      {/* ================= HEADER ================= */}

      <div className="listings-header">

        <div className="listings-title">
          <div className="title-icon">
            <FaStore />
          </div>

          <div>
            <h1>My Listings</h1>
            <p>
              Manage all the items you have listed for sale on CampusXchange.
            </p>
          </div>
        </div>

        {/* Tip */}
        <div className="listing-tip">
          <div className="tip-icon">
            <FaLightbulb />
          </div>

          <div>
            <strong>Tip</strong>
            <p>
              Keep your listings up to date to get more views and faster
              responses.
            </p>
          </div>
        </div>

      </div>


      {/* ================= SUMMARY CARDS ================= */}

      <div className="listing-summary">

        <div
          className="summary-card active-summary"
          onClick={() => {
                    setActiveTab("active");
                    setCurrentPage(1);
                  }}>
        
          <div className="summary-icon">
            <FaStore />
          </div>

          <div className="summary-info">
            <h2>{activeCount}</h2>
            <h3>Active Listings</h3>
            <p>Items currently for sale</p>
          </div>

          <span className="summary-arrow">›</span>
        </div>


        <div
          className="summary-card sold-summary"
            onClick={() => {
               setActiveTab("sold");
                setCurrentPage(1);}}
        >
          <div className="summary-icon">
            <FaCheckCircle />
          </div>

          <div className="summary-info">
            <h2>{soldCount}</h2>
            <h3>Sold Items</h3>
            <p>Successfully sold items</p>
          </div>

          <span className="summary-arrow">›</span>
        </div>


        <div
          className="summary-card draft-summary"
          onClick={() => {
            setActiveTab("drafts");
            setCurrentPage(1);}}
        >
          <div className="summary-icon">
            <FaFileAlt />
          </div>

          <div className="summary-info">
            <h2>{draftCount}</h2>
            <h3>Drafts</h3>
            <p>Saved but not published</p>
          </div>

          <span className="summary-arrow">›</span>
        </div>

      </div>


      {/* ================= LISTINGS CONTAINER ================= */}

      <div className="listings-container">

        {/* Tabs */}

        <div className="listing-tabs">

          <button
            className={activeTab === "active" ? "tab active-tab" : "tab"}
            onClick={() => {setActiveTab("active");setCurrentPage(1);}}
            
          >
            <FaStore />
            Active Listings
          </button>

          <button
            className={activeTab === "sold" ? "tab active-tab" : "tab"}
            onClick={() =>{ setActiveTab("sold");setCurrentPage(1);}}
          >
            <FaCheckCircle />
            Sold Items
          </button>

          <button
            className={activeTab === "drafts" ? "tab active-tab" : "tab"}
            onClick={() => {setActiveTab("drafts");setCurrentPage(1);}}
          >
            <FaFileAlt />
            Drafts
          </button>

        </div>


        {/* ================= CONTENT HEADER ================= */}

        <div className="listing-content-header">

          <h2>
            {activeTab === "active" && "Active Listings"}
            {activeTab === "sold" && "Sold Items"}
            {activeTab === "drafts" && "Drafts"}
          </h2>

          <div className="listing-controls">

            <div className="dropdown-wrapper">

                  <button
                    className="sort-button"
                    onClick={() => {
                      setShowSortMenu(!showSortMenu);
                      setShowFilterMenu(false);
                    }}
                  >
                    Sort by:{" "}
                    {sortOption === "newest" && "Newest"}
                    {sortOption === "oldest" && "Oldest"}
                    {sortOption === "price-low" && "Price: Low to High"}
                    {sortOption === "price-high" && "Price: High to Low"}

                    <FaChevronDown />
                  </button>


                  {showSortMenu && (
                    <div className="dropdown-menu">

                      <button
                        className={sortOption === "newest" ? "selected-option" : ""}
                        onClick={() => {
                          setSortOption("newest");
                          setShowSortMenu(false);
                        }}
                      >
                        Newest
                      </button>

                      <button
                        className={sortOption === "oldest" ? "selected-option" : ""}
                        onClick={() => {
                          setSortOption("oldest");
                          setShowSortMenu(false);
                        }}
                      >
                        Oldest
                      </button>

                      <button
                        className={sortOption === "price-low" ? "selected-option" : ""}
                        onClick={() => {
                          setSortOption("price-low");
                          setShowSortMenu(false);
                        }}
                      >
                        Price: Low to High
                      </button>

                      <button
                        className={sortOption === "price-high" ? "selected-option" : ""}
                        onClick={() => {
                          setSortOption("price-high");
                          setShowSortMenu(false);
                        }}
                      >
                        Price: High to Low
                      </button>

                    </div>
                  )}

                </div>

                     <div className="dropdown-wrapper">

                      <button
                        className="filter-button"
                        onClick={() => {
                          setShowFilterMenu(!showFilterMenu);
                          setShowSortMenu(false);
                        }}
                      >
                        <FaFilter />
                        Filter

                        {categoryFilter !== "all" && (
                          <span className="filter-active-dot"></span>
                        )}
                      </button>


                      {showFilterMenu && (
                        <div className="dropdown-menu filter-menu">

                          <button
                            className={
                              categoryFilter === "all"
                                ? "selected-option"
                                : ""
                            }
                            onClick={() => {
                              setCategoryFilter("all");
                              setCurrentPage(1);
                              setShowFilterMenu(false);
                            }}
                          >
                            All Categories
                          </button>

                          {categories.map((cat) => (
                            <button
                              key={cat.category_id}
                              className={
                                categoryFilter === cat.category_name
                                  ? "selected-option"
                                  : ""
                              }
                              onClick={() => {
                                setCategoryFilter(cat.category_name);
                                setCurrentPage(1);
                                setShowFilterMenu(false);
                              }}
                            >
                              {cat.category_name}
                            </button>
                          ))}

                          

                          

                          

                          

                          

                        </div>
                      )}

                    </div>
          </div>

        </div>


        {/* ================= PLACEHOLDER CARDS ================= */}

        {loading && <p>Loading your listings...</p>}
        {pageError && <p style={{ color: "#dc2626" }}>{pageError}</p>}
        {!loading && !pageError && currentListings.length === 0 && (
          <p>No listings here yet.</p>
        )}

        <div className="listing-grid">

          {currentListings.map((item)=> (

            <div
              className={`listing-card ${openMenuId === item.id ? "menu-open" : ""}`}
              key={item.id}
            >

              {/* Three dot menu */}

              <div className="card-menu-wrap">
                <button
                  className="more-button"
                  aria-label="More options"
                  aria-expanded={openMenuId === item.id}
                  onClick={() =>
                    setOpenMenuId(openMenuId === item.id ? null : item.id)
                  }
                >
                  <FaEllipsisV />
                </button>

                {openMenuId === item.id && (
                  <div className="card-menu">
                    <button onClick={() => openModal("view", item)}>
                      <FaEye /> View details
                    </button>

                    {item.status === "Active" && (
                      <>
                        <button onClick={() => navigate(`/buy/${item.id}`)}>
                          <FaExternalLinkAlt /> View on marketplace
                        </button>
                        <button onClick={() => handleCopyLink(item)}>
                          <FaLink /> Copy link
                        </button>
                      </>
                    )}

                    {item.status !== "Sold" && item.status !== "Removed" && (
                      <button onClick={() => openModal("edit", item)}>
                        <FaEdit /> Edit listing
                      </button>
                    )}

                    {item.status === "Active" && (
                      <button
                        className="menu-sold"
                        onClick={() => openModal("sold", item)}
                      >
                        <FaHandshake /> Mark as sold
                      </button>
                    )}

                    {item.status === "Active" && (
                      <button onClick={() => handleMenuStatus(item, "hide")}>
                        <FaEyeSlash /> Hide listing
                      </button>
                    )}

                    {item.status === "Inactive" && (
                      <button onClick={() => handleMenuStatus(item, "activate")}>
                        <FaEye /> Show listing
                      </button>
                    )}

                    {item.status === "Draft" && (
                      <button onClick={() => handleMenuStatus(item, "publish")}>
                        <FaBullhorn /> Publish
                      </button>
                    )}

                    {item.status !== "Sold" && (
                      <button
                        className="menu-danger"
                        onClick={() => openModal("delete", item)}
                      >
                        <FaTrash /> Delete
                      </button>
                    )}
                  </div>
                )}
              </div>


              {/* Image placeholder */}

              <div className="listing-image-placeholder">
                {item.image ? (
                  <img
                    src={item.image}
                    alt={item.name}
                    style={{ width: "100%", height: "100%", objectFit: "cover", borderRadius: "inherit" }}
                  />
                ) : (
                  <>
                    <FaImage />
                    <span>No image</span>
                  </>
                )}
              </div>


              {/* Product placeholder */}

              <div className="listing-details">

                  <div className="listing-category">
                    {item.category}
                  </div>

                  <h3 className="listing-name">
                    {item.name}
                  </h3>

                  <div className="location-placeholder">
                    <span>⌖</span>
                    <span>{item.condition || "CampusXchange"}</span>
                  </div>

                  <div className="listing-price">
                    {userFormatPrice(item.price)}
                  </div>


                {/* Status */}

                <div
                  className={`listing-status ${
                    item.status === "Active"
                      ? "active-status"
                      : item.status === "Sold"
                      ? "sold-status"
                      : "draft-status"
                  }`}>
                
                  <span></span>
                  {item.status}
                </div>

              </div>


              {/* Actions */}

              <div className="listing-actions">
                    {item.status === "Draft" && (
                      <button onClick={() => handleStatusChange(item, "publish")}>
                        Publish
                      </button>
                    )}
                    {item.status === "Active" && (
                      <button onClick={() => handleStatusChange(item, "hide")}>
                        Hide
                      </button>
                    )}
                    {item.status === "Inactive" && (
                      <button onClick={() => handleStatusChange(item, "activate")}>
                        Show
                      </button>
                    )}
                    <button onClick={() => openModal("view", item)}>
                      <FaEye />
                      View
                    </button>

          {item.status !== "Sold" && item.status !== "Removed" && (
                      <button onClick={() => openModal("edit", item)}>
                        <FaEdit />
                        Edit
                      </button>
                    )}

                    {item.status !== "Sold" && (
                      <button
                        className="delete-action"
                        onClick={() => openModal("delete", item)}
                      >
                        <FaTrash />
                        Delete
                      </button>
                    )}

                  </div>

            </div>

          ))}

        </div>


        {/* ================= PAGINATION ================= */}

        <div className="listing-pagination">

                      <button
                        className="pagination-arrow"
                        disabled={currentPage === 1}
                        onClick={() => setCurrentPage(currentPage - 1)}
                      >
                        <FaChevronLeft />
                      </button>


                      {Array.from({ length: totalPages }, (_, index) => (
                        <button
                          key={index}
                          className={`pagination-number ${
                            currentPage === index + 1
                              ? "active-page"
                              : ""
                          }`}
                          onClick={() => setCurrentPage(index + 1)}
                        >
                          {index + 1}
                        </button>
                      ))}


                      <button
                        className="pagination-arrow"
                        disabled={currentPage === totalPages}
                        onClick={() => setCurrentPage(currentPage + 1)}
                      >
                        <FaChevronRight />
                      </button>
                <span>
                  Showing{" "}
                  {filteredListings.length === 0
                    ? 0
                    : `${startIndex + 1}–${Math.min(
                        startIndex + itemsPerPage,
                        filteredListings.length
                      )}`}{" "}
                  of {filteredListings.length}{" "}
                  {filteredListings.length === 1 ? "item" : "items"}
                </span>

        </div>

      </div>
    


{/* ================= MODAL ================= */}

{modalType && selectedListing && (
  <div className="modal-overlay" onClick={closeModal}>

    <div
      className="listing-modal"
      onClick={(e) => e.stopPropagation()}
    >

      {/* CLOSE BUTTON */}
      <button
        className="modal-close"
        onClick={closeModal}
      >
        ×
      </button>


      {/* VIEW MODAL */}
      {modalType === "view" && (
        <>
          <div className="modal-header">
            <div>
              <span className="modal-category">
                {selectedListing.category}
              </span>

              <h2>{selectedListing.name}</h2>

              <p>CampusXchange</p>
            </div>
          </div>

          <div className={`modal-image ${selectedListing.image && !imageFailed ? "has-image" : ""}`}>
            {selectedListing.image && !imageFailed ? (
              <img
                src={selectedListing.image}
                alt={selectedListing.name}
                onError={() => setImageFailed(true)}
              />
            ) : (
              <>
                <FaImage />
                <span>No image</span>
              </>
            )}
          </div>

          <div className="modal-details">

            <div className="detail-box">
              <span>Price</span>
              <strong>
                {userFormatPrice(selectedListing.price)}
              </strong>
            </div>

            <div className="detail-box">
              <span>Category</span>
              <strong>{selectedListing.category}</strong>
            </div>

            <div className="detail-box">
              <span>Status</span>
              <strong>{selectedListing.status}</strong>
            </div>

            <div className="detail-box">
              <span>Location</span>
              <strong>CampusXchange</strong>
            </div>

          </div>

          <div className="modal-footer">

            <button
              className="modal-secondary-btn"
              onClick={closeModal}
            >
              Close
            </button>

            {selectedListing.status === "Active" && (
              <button
                className="modal-secondary-btn"
                onClick={() => openModal("sold", selectedListing)}
              >
                <FaHandshake />
                Mark as Sold
              </button>
            )}

            {selectedListing.status !== "Sold" &&
              selectedListing.status !== "Removed" && (
                <button
                  className="modal-primary-btn"
                  onClick={() => openModal("edit", selectedListing)}
                >
                  <FaEdit />
                  Edit Listing
                </button>
              )}

          </div>
        </>
      )}


      {/* EDIT MODAL */}
      {modalType === "edit" && (
        <>
          <div className="modal-heading">

            <span className="modal-icon">
              <FaEdit />
            </span>

            <div>
              <h2>Edit Listing</h2>
              <p>Update your product information</p>
            </div>

          </div>

          <div className="edit-form">

            <div className="form-group">
              <label>Product Name</label>

              <input
                type="text"
                value={editForm.title}
                onChange={(e) => setEditForm({ ...editForm, title: e.target.value })}
              />
            </div>


            <div className="form-row">

              <div className="form-group">
                <label>Price</label>

                <input
                  type="number"
                  min="1"
                  value={editForm.price}
                  onChange={(e) => setEditForm({ ...editForm, price: e.target.value })}
                />
              </div>


              <div className="form-group">
                <label>Category</label>

                <select
                  value={editForm.category_id}
                  onChange={(e) => setEditForm({ ...editForm, category_id: e.target.value })}
                >
                  <option value="">Select category</option>
                  {categories.map((cat) => (
                    <option key={cat.category_id} value={cat.category_id}>
                      {cat.category_name}
                    </option>
                  ))}
                </select>
              </div>

            </div>


            


            <div className="form-group">
              <label>Description</label>

              <textarea
                rows="4"
                placeholder="Enter product description..."
                value={editForm.description}
                onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
              ></textarea>
              {modalError && <p style={{ color: "#dc2626", marginTop: 8 }}>{modalError}</p>}
            </div>

          </div>


          <div className="modal-footer">

            <button
              className="modal-secondary-btn"
              onClick={closeModal}
            >
              Cancel
            </button>

            <button
              className="modal-primary-btn"
              onClick={handleSaveEdit}
              disabled={busy}
            >
              {busy ? "Saving..." : "Save Changes"}
            </button>

          </div>
        </>
      )}


      {/* MARK AS SOLD MODAL */}
      {modalType === "sold" && (
        <>
          <div className="modal-heading">
            <span className="modal-icon">
              <FaHandshake />
            </span>

            <div>
              <h2>Mark as Sold</h2>
              <p>Choose who bought "{selectedListing.name}"</p>
            </div>
          </div>

          <div className="edit-form">
            {buyersLoading && <p>Loading buyers...</p>}

            {!buyersLoading && buyers.length === 0 && (
              <p className="sold-empty">
                No buyer has chatted with you about this item yet. A sale needs
                a buyer from your chats so they can review you afterwards.
              </p>
            )}

            {!buyersLoading && buyers.length > 0 && (
              <div className="buyer-list">
                {buyers.map((b) => (
                  <label
                    key={b.id}
                    className={`buyer-option ${
                      selectedChatId === b.id ? "buyer-selected" : ""
                    }`}
                  >
                    <input
                      type="radio"
                      name="sold-buyer"
                      checked={selectedChatId === b.id}
                      onChange={() => setSelectedChatId(b.id)}
                    />
                    <span className="buyer-name">{b.name}</span>
                    {b.last_text && (
                      <span className="buyer-last">{b.last_text}</span>
                    )}
                  </label>
                ))}
              </div>
            )}

            {buyers.length > 0 && (
              <div className="form-group">
                <label>Selling price (₹)</label>
                <input
                  type="number"
                  min="1"
                  value={soldPrice}
                  onChange={(e) => setSoldPrice(e.target.value)}
                />
              </div>
            )}

            {modalError && (
              <p style={{ color: "#dc2626", marginTop: 8 }}>{modalError}</p>
            )}
          </div>

          <div className="modal-footer">
            <button className="modal-secondary-btn" onClick={closeModal}>
              Cancel
            </button>

            <button
              className="modal-primary-btn"
              onClick={handleMarkSold}
              disabled={busy || buyersLoading || buyers.length === 0}
            >
              <FaCheckCircle />
              {busy ? "Saving..." : "Confirm Sale"}
            </button>
          </div>
        </>
      )}


      {/* DELETE MODAL */}
      {modalType === "delete" && (
        <>
          <div className="delete-modal-content">

            <div className="delete-icon">
              <FaTrash />
            </div>

            <h2>Delete Listing?</h2>

            <p>
              Are you sure you want to delete
              <strong> "{selectedListing.name}" </strong>
              ?
            </p>

            <span>
              This action cannot be undone.
            </span>
            {modalError && <p style={{ color: "#dc2626", marginTop: 8 }}>{modalError}</p>}

          </div>


          <div className="modal-footer">

            <button
              className="modal-secondary-btn"
              onClick={closeModal}
            >
              Cancel
            </button>

            <button
              className="modal-delete-btn"
              onClick={handleDelete}
              disabled={busy}
            >
              <FaTrash />
              Delete Listing
            </button>

          </div>
        </>
      )}

    </div>

  </div>

)}




    {toast && <div className="listing-toast">{toast}</div>}

    </div>
    </>
  );
};

export default MyListings;