CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,
    pending_email VARCHAR(150) NULL UNIQUE,

    phone VARCHAR(15) UNIQUE NULL,

    role ENUM('Student', 'Faculty') NULL,

    password VARCHAR(255) NOT NULL,

    email_verified_at DATETIME NULL,

    status ENUM('Pending', 'Active', 'Suspended', 'Banned')
        DEFAULT 'Pending',

    session_version INT NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL
);


CREATE TABLE Remember_Tokens (
    remember_token_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    token_hash VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    last_used_at DATETIME NULL,

    revoked_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_remember_token_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Security_Questions (
    security_question_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    question TEXT NOT NULL,

    answer_hash VARCHAR(255) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_security_question_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Email_Verification_Tokens (
    verification_token_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    email VARCHAR(150) NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    verified_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_verification_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE User_Profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    profile_picture VARCHAR(255) NULL,

    phone VARCHAR(20) NULL,

    department VARCHAR(100) NULL,
    course VARCHAR(100) NULL,
    year_of_study VARCHAR(20) NULL,
    semester VARCHAR(20) NULL,

    bio TEXT NULL,
    location VARCHAR(150) NULL,

    college_name VARCHAR(150) NULL,
    graduation_year  VARCHAR(10)  NULL,
    address_line     VARCHAR(255) NULL,
    city             VARCHAR(100) NULL,
    state            VARCHAR(100) NULL,
    pincode          VARCHAR(10)  NULL,
    live_photo       VARCHAR(255) NULL,


    profile_completion_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,

    profile_status ENUM(
        'Incomplete',
        'Complete'
    ) NOT NULL DEFAULT 'Incomplete',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_profile_completion
        CHECK (profile_completion_percentage BETWEEN 0 AND 100)
);


CREATE TABLE Password_Reset_Tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
);


CREATE TABLE Admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,
    pending_email VARCHAR(150) NULL UNIQUE,
    email_verified_at DATETIME NULL,

    password VARCHAR(255) NOT NULL,

    admin_role ENUM('Main Admin', 'Sub Admin') NOT NULL,

    status ENUM('Active', 'Suspended', 'Banned') DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL
);


CREATE TABLE Admin_Profiles (
    admin_profile_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL UNIQUE,

    profile_picture VARCHAR(255) NULL,

    phone VARCHAR(15) UNIQUE,

    designation VARCHAR(100) NULL,

    department VARCHAR(100) NULL,

    address TEXT NULL,

    city VARCHAR(100) NULL,

    state VARCHAR(100) NULL,

    postal_code VARCHAR(10) NULL,

    bio TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_admin_profile_admin
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE Admin_Email_Verification_Tokens (
    verification_token_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    email VARCHAR(150) NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    verified_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_email_verification
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Admin_Password_Reset_Tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    token VARCHAR(255) NOT NULL UNIQUE,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_password_reset
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
);


CREATE TABLE Admin_Restrictions (
    restriction_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action_type ENUM(
        'Suspended',
        'Unsuspended',
        'Banned',
        'Unbanned'
    ) NOT NULL,

    reason TEXT NOT NULL,

    performed_by INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_restriction_target
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_admin_restriction_performer
        FOREIGN KEY (performed_by)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE User_Verification (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    id_type ENUM('Student ID', 'Faculty ID') NULL,

    id_number VARCHAR(50) NULL,

    -- college_id_image VARCHAR(255) NULL,

    college_id_front_image VARCHAR(255) NULL,
    college_id_back_image VARCHAR(255) NULL,

    verification_status ENUM(
        'Not Submitted',
        'Pending',
        'Approved',
        'Rejected'
    ) DEFAULT 'Not Submitted',

    submitted_at DATETIME NULL,

    verified_by INT NULL,

    verified_at DATETIME NULL,

    rejection_reason VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    INDEX idx_user_verification_status (verification_status),

    INDEX idx_user_verification_submitted_at (submitted_at),

    CONSTRAINT fk_verification_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_verification_admin
        FOREIGN KEY (verified_by)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);


CREATE TABLE Categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    description VARCHAR(255) NULL,

    invoice_required BOOLEAN NOT NULL DEFAULT FALSE,

    status ENUM(
        'Active',
        'Inactive'
    ) DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL
);


CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,

    seller_id INT NOT NULL,
    category_id INT NULL,

    product_type VARCHAR(50) NULL,

    title VARCHAR(150) NULL,
    description TEXT NULL,

    price DECIMAL(10,2) NULL,

    product_condition ENUM(
        'New',
        'Like New',
        'Good',
        'Used'
    ) NULL,

    invoice_document VARCHAR(255) NULL,

    product_box_image VARCHAR(255) NULL,

    status ENUM(
        'Draft',
        'Active',
        'Sold',
        'Removed',
        'Inactive'
    ) DEFAULT 'Draft',

    /* =====================================================
       ADMIN MODERATION
    ===================================================== */

    removed_by_admin_id INT NULL,

    removal_reason VARCHAR(255) NULL,

    removed_at DATETIME NULL,

    /* =====================================================
       TIMESTAMPS
    ===================================================== */

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    /* =====================================================
       FOREIGN KEYS
    ===================================================== */

    CONSTRAINT fk_product_seller
        FOREIGN KEY (seller_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id)
        REFERENCES Categories(category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_product_removed_by_admin
        FOREIGN KEY (removed_by_admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);


CREATE TABLE Product_Images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_product_image_product
        FOREIGN KEY (product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    buyer_id INT NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    transaction_status ENUM('Completed', 'Cancelled', 'Disputed') DEFAULT 'Completed',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_transaction_product
        FOREIGN KEY (product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_transaction_seller
        FOREIGN KEY (seller_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_transaction_buyer
        FOREIGN KEY (buyer_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE Chats (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_chat_product
        FOREIGN KEY (product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_chat_buyer
        FOREIGN KEY (buyer_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_chat_seller
        FOREIGN KEY (seller_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_chat_product_buyer_seller
        UNIQUE (product_id, buyer_id, seller_id),

    CONSTRAINT chk_chat_buyer_seller
        CHECK (buyer_id <> seller_id)
);


CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,

    chat_id INT NOT NULL,

    sender_id INT NOT NULL,

    message_text TEXT NULL,

    message_type ENUM(
        'Text',
        'Image'
    ) DEFAULT 'Text',

    attachment_path VARCHAR(255) NULL,

    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    delivered_at DATETIME NULL,

    read_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_message_chat
        FOREIGN KEY (chat_id)
        REFERENCES Chats(chat_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_message_sender
        FOREIGN KEY (sender_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE Wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_wishlist_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_wishlist_product
        FOREIGN KEY (product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_wishlist_user_product
        UNIQUE (user_id, product_id)
);

CREATE TABLE User_Presence (
    presence_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    last_seen_at DATETIME NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_presence_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE Product_Reviews (
    product_review_id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    transaction_id INT NOT NULL,

    rating TINYINT NOT NULL,
    review_text TEXT NULL,

    status ENUM(
        'Visible',
        'Hidden',
        'Removed'
    ) DEFAULT 'Visible',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_product_review_product
        FOREIGN KEY (product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_product_review_reviewer
        FOREIGN KEY (reviewer_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_product_review_transaction
        FOREIGN KEY (transaction_id)
        REFERENCES Transactions(transaction_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_product_review_rating
        CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT uq_product_review_transaction
        UNIQUE (transaction_id)
);

CREATE TABLE Seller_Reviews (
    seller_review_id INT AUTO_INCREMENT PRIMARY KEY,

    seller_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    transaction_id INT NOT NULL,

    rating TINYINT NOT NULL,
    review_text TEXT NULL,

    status ENUM(
        'Visible',
        'Hidden',
        'Removed'
    ) DEFAULT 'Visible',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_seller_review_seller
        FOREIGN KEY (seller_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_seller_review_reviewer
        FOREIGN KEY (reviewer_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_seller_review_transaction
        FOREIGN KEY (transaction_id)
        REFERENCES Transactions(transaction_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_seller_review_rating
        CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT chk_seller_review_not_self
        CHECK (seller_id <> reviewer_id),

    CONSTRAINT uq_seller_review_transaction
        UNIQUE (transaction_id)
);

CREATE TABLE Reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,

    reporter_id INT NOT NULL,

    reported_user_id INT NULL,
    reported_product_id INT NULL,

    report_type ENUM('User', 'Product') NOT NULL,

    report_category ENUM(
        'Inappropriate Behavior',
        'Scam/Fraud',
        'Harassment',
        'Spam',
        'Fake/Misleading Listing',
        'Prohibited Product',
        'Other'
    ) NOT NULL,

    reason VARCHAR(255) NOT NULL,
    description TEXT NULL,

    status ENUM(
        'Pending',
        'Under Review',
        'Resolved',
        'Rejected'
    ) DEFAULT 'Pending',

    reviewed_by INT NULL,
    admin_notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_report_reporter
        FOREIGN KEY (reporter_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reported_user
        FOREIGN KEY (reported_user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reported_product
        FOREIGN KEY (reported_product_id)
        REFERENCES Products(product_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_report_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);


CREATE TABLE Report_Evidence (
    evidence_id INT AUTO_INCREMENT PRIMARY KEY,

    report_id INT NOT NULL,

    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL,

    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_evidence_report
        FOREIGN KEY (report_id)
        REFERENCES Reports(report_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,
    admin_id INT NULL,

    notification_type ENUM(
        'Verification',
        'Message',
        'Product',
        'Transaction',
        'Wishlist',
        'Review',
        'Report',
        'Ban',
        'Security',
        'System'
    ) NOT NULL,

    title VARCHAR(255) NOT NULL,

    message TEXT NOT NULL,

    reference_type ENUM(
        'User',
        'Product',
        'Transaction',
        'Chat',
        'Review',
        'Report',
        'Ban',
        'Security',
        'System'
    ) NULL,

    reference_id INT NULL,

    is_read BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    read_at DATETIME NULL,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_notification_admin
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE Login_Security_Activity (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    activity_type ENUM(
        'Login',
        'New Device',
        'Remembered Login',
        'Unusual Login',
        'Logout All Devices',
        'Token Revoked',
        'Password Changed',
        'Email Changed',
        'Account Recovery'
    ) NOT NULL,

    title VARCHAR(255) NOT NULL,

    description TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_security_activity_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE User_Bans (
    ban_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    report_id INT NULL,
    admin_id INT NOT NULL,

    ban_type ENUM(
        'Warning',
        'Temporary',
        'Permanent'
    ) NOT NULL,

    reason VARCHAR(255) NOT NULL,
    description TEXT NULL,

    start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,

    status ENUM(
        'Active',
        'Expired',
        'Revoked'
    ) DEFAULT 'Active',

    revoked_by INT NULL,
    revoked_at DATETIME NULL,
    revocation_reason VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_ban_user
        FOREIGN KEY (user_id)
        REFERENCES Users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_ban_report
        FOREIGN KEY (report_id)
        REFERENCES Reports(report_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_ban_admin
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_ban_revoked_by
        FOREIGN KEY (revoked_by)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);


CREATE TABLE Admin_Logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action_type ENUM(
        'Login',
        'Logout',
        'Create',
        'Update',
        'Delete',
        'Restore',
        'Verify User',
        'Manage User',
        'Manage Product',
        'Review Report',
        'Issue Ban',
        'Revoke Ban',
        'Manage Category',
        'Change Settings',
        'Manage Admin',
        'Other'
    ) NOT NULL,

    action_description TEXT NOT NULL,

    target_type ENUM(
        'User',
        'Admin',
        'Product',
        'Category',
        'Report',
        'Ban',
        'System Setting',
        'Other'
    ) NULL,

    target_id INT NULL,

    ip_address VARCHAR(45) NULL,

    user_agent VARCHAR(500) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_admin_log_admin
        FOREIGN KEY (admin_id)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE System_Settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,

    setting_type ENUM(
        'Text',
        'Number',
        'Boolean',
        'Image',
        'Email'
    ) DEFAULT 'Text',

    description VARCHAR(255) NULL,

    updated_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at DATETIME NULL,

    CONSTRAINT fk_setting_admin
        FOREIGN KEY (updated_by)
        REFERENCES Admins(admin_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

