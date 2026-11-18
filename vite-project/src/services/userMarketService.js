// Browse / dashboard / wishlist-toggle / navbar calls. All URLs come from
// userApiConfig.js, so nothing here changes when the site goes live.
import { userGet, userPost } from "./userApiClient";

export const fetchUserNavbar = () => userGet("navbar.php");
export const fetchUserDashboard = () => userGet("dashboard.php");
export const fetchUserCategories = () => userGet("categories_list.php");
export const fetchUserProducts = (params) => userGet("products_list.php", params);
export const toggleUserWishlist = (productId) =>
    userPost("wishlist_toggle.php", { product_id: productId });

export const fetchUserWishlist = () => userGet("wishlist_list.php");

// ---- seller side (My Listings) ----
export const fetchUserListings = () => userGet("my_listings.php");
export const updateUserListing = (data) => userPost("listing_update.php", data);
export const changeUserListingStatus = (productId, action) =>
    userPost("listing_status.php", { product_id: productId, action });
export const deleteUserListing = (productId) =>
    userPost("listing_delete.php", { product_id: productId });

// ---- Sell Item (multipart form) ----
import { userPostForm } from "./userApiClient";
export const createUserListing = (formData) => userPostForm("sell_create.php", formData);

// ---- orders / sold / reviews ----
export const fetchUserOrders = () => userGet("orders_list.php");
export const markUserChatSold = (chatId, sellingPrice) =>
    userPost("transaction_create.php", { chat_id: chatId, selling_price: sellingPrice });
export const createUserReview = (transactionId, rating, text) =>
    userPost("review_create.php", { transaction_id: transactionId, rating, text });

// ---- profile + verification ----
export const fetchUserProfile = () => userGet("profile_get.php");
export const saveUserProfile = (formData) => userPostForm("profile_save.php", formData);
export const submitUserVerification = () => userPost("verification_submit.php", {});

// ---- account security ----
export const fetchUserSecurity = () => userGet("security_overview.php");
export const changeUserPassword = (current_password, new_password, confirm_password) =>
    userPost("password_change.php", { current_password, new_password, confirm_password });
export const logoutUserOtherDevices = () => userPost("logout_others.php", {});
export const deleteUserAccount = (confirm) => userPost("account_delete.php", { confirm });

// ---- reports ----
export const createUserProductReport = (formData) => userPostForm("report_create.php", formData);

// ---- notifications ----
export const fetchUserNotifications = () => userGet("notifications_list.php");
export const markUserNotificationRead = (id) => userPost("notification_read.php", { id });
export const markAllUserNotificationsRead = () => userPost("notification_read.php", { all: true });

// ---- profile page (section based) + email OTP ----
export const updateUserProfileSection = (formData) => userPostForm("profile_update.php", formData);
export const sendUserEmailOtp = (new_email, password) =>
    userPost("email_otp_send.php", { new_email, password });
export const verifyUserEmailOtp = (otp) => userPost("email_otp_verify.php", { otp });
