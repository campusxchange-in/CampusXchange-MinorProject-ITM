/* ============================================================
   CAMPUSXCHANGE / CAMPUS SELL & BUY PORTAL
   DEVELOPMENT DUMMY DATA
   ============================================================

   PURPOSE:
   - Clears existing dummy/test data
   - Resets AUTO_INCREMENT values
   - Inserts data matching the supplied database schema
   - Safe to re-import repeatedly in a DEVELOPMENT database

   WARNING:
   This script deletes existing data from the tables below.
   DO NOT run the cleanup section on a production database.
   ============================================================ */


SET FOREIGN_KEY_CHECKS = 0;


/* ============================================================
   1. CLEAR EXISTING DATA
   ============================================================ */

TRUNCATE TABLE System_Settings;
TRUNCATE TABLE Admin_Logs;
TRUNCATE TABLE User_Bans;
TRUNCATE TABLE Login_Security_Activity;
TRUNCATE TABLE Notifications;
TRUNCATE TABLE Report_Evidence;
TRUNCATE TABLE Reports;
TRUNCATE TABLE Reviews;
TRUNCATE TABLE User_Presence;
TRUNCATE TABLE Wishlist;
TRUNCATE TABLE Messages;
TRUNCATE TABLE Chats;
TRUNCATE TABLE Transactions;
TRUNCATE TABLE Product_Images;
TRUNCATE TABLE Products;
TRUNCATE TABLE Categories;
TRUNCATE TABLE User_Verification;
TRUNCATE TABLE Admin_Password_Reset_Tokens;
TRUNCATE TABLE Admin_Email_Verification_Tokens;
TRUNCATE TABLE Admin_Profiles;
TRUNCATE TABLE Admins;
TRUNCATE TABLE Password_Reset_Tokens;
TRUNCATE TABLE Email_Verification_Tokens;
TRUNCATE TABLE Security_Questions;
TRUNCATE TABLE Remember_Tokens;
TRUNCATE TABLE User_Profiles;
TRUNCATE TABLE Users;


/* ============================================================
   2. USERS
   ============================================================ */

INSERT INTO Users
(
    full_name,
    email,
    pending_email,
    phone,
    enrollment_employee_id,
    role,
    password,
    email_verified_at,
    status,
    session_version
)
VALUES
(
    'Rahul Sharma',
    'rahul.sharma@college.edu',
    NULL,
    '9876543210',
    'STU2026001',
    'Student',
    '$2y$10$examplehashedpasswordstudent',
    '2026-08-02 09:30:00',
    'Suspended',
    1
),
(
    'Priya Verma',
    'priya.verma@college.edu',
    NULL,
    '9876543211',
    'FAC2026001',
    'Faculty',
    '$2y$10$examplehashedpasswordfaculty',
    '2026-08-02 09:45:00',
    'Active',
    1
);


/* ============================================================
   3. USER PROFILES
   ============================================================ */

INSERT INTO User_Profiles
(
    user_id,
    profile_picture,
    phone,
    department,
    course,
    year_of_study,
    semester,
    bio,
    location
)
VALUES
(
    1,
    'uploads/profiles/rahul.jpg',
    '9876543210',
    'Computer Science',
    'MCA',
    '1st Year',
    '1st Semester',
    'MCA student interested in technology and academics.',
    'Gwalior'
),
(
    2,
    'uploads/profiles/priya.jpg',
    '9876543211',
    'Computer Applications',
    NULL,
    NULL,
    NULL,
    'Faculty member of the institution.',
    'Gwalior'
);


/* ============================================================
   4. SECURITY QUESTIONS
   ============================================================ */

INSERT INTO Security_Questions
(
    user_id,
    question,
    answer_hash
)
VALUES
(
    1,
    'What was the name of your first school?',
    '$2y$10$examplehashedanswerrahul'
),
(
    2,
    'What is the name of your hometown?',
    '$2y$10$examplehashedanswerpriya'
);


/* ============================================================
   5. EMAIL VERIFICATION TOKENS
   ============================================================ */

INSERT INTO Email_Verification_Tokens
(
    user_id,
    email,
    token,
    expires_at,
    verified_at
)
VALUES
(
    1,
    'rahul.sharma@college.edu',
    'test_email_verify_user_1_a83f92',
    '2026-09-07 20:00:00',
    '2026-08-02 09:35:00'
),
(
    2,
    'priya.verma@college.edu',
    'test_email_verify_user_2_b72c41',
    '2026-09-07 20:00:00',
    '2026-08-02 09:50:00'
);


/* ============================================================
   6. PASSWORD RESET TOKENS
   ============================================================ */

INSERT INTO Password_Reset_Tokens
(
    user_id,
    token,
    expires_at
)
VALUES
(
    1,
    'test_reset_token_user_1_8f3a92',
    '2026-09-07 20:00:00'
),
(
    2,
    'test_reset_token_user_2_b7c421',
    '2026-09-07 20:15:00'
);


/* ============================================================
   7. ADMINS
   ============================================================ */

INSERT INTO Admins
(
    full_name,
    email,
    pending_email,
    email_verified_at,
    password,
    admin_role,
    status
)
VALUES
(
    'Amit Sharma',
    'admin@campusportal.edu',
    NULL,
    '2026-08-02 08:30:00',
    '$2y$10$examplehashedpasswordmainadmin',
    'Main Admin',
    'Active'
),
(
    'Neha Verma',
    'subadmin@campusportal.edu',
    NULL,
    '2026-08-02 08:45:00',
    '$2y$10$examplehashedpasswordsubadmin',
    'Sub Admin',
    'Active'
);


/* ============================================================
   8. ADMIN PROFILES
   ============================================================ */

INSERT INTO Admin_Profiles
(
    admin_id,
    profile_picture,
    phone,
    designation,
    department,
    address,
    city,
    state,
    postal_code,
    bio
)
VALUES
(
    1,
    'uploads/admin_profiles/amit_sharma.jpg',
    '9876500001',
    'System Administrator',
    'Information Technology',
    'Admin Block, Campus Portal Office',
    'Gwalior',
    'Madhya Pradesh',
    '474001',
    'Main administrator responsible for overall system management and administration.'
),
(
    2,
    'uploads/admin_profiles/neha_verma.jpg',
    '9876500002',
    'Assistant Administrator',
    'Information Technology',
    'Admin Block, Campus Portal Office',
    'Gwalior',
    'Madhya Pradesh',
    '474001',
    'Sub administrator responsible for routine platform management and user support.'
);


/* ============================================================
   9. ADMIN EMAIL VERIFICATION TOKENS
   ============================================================ */

INSERT INTO Admin_Email_Verification_Tokens
(
    admin_id,
    email,
    token,
    expires_at,
    verified_at
)
VALUES
(
    1,
    'admin@campusportal.edu',
    'test_admin_email_verify_1_a83f92',
    '2026-09-07 20:30:00',
    '2026-08-02 08:35:00'
),
(
    2,
    'subadmin@campusportal.edu',
    'test_admin_email_verify_2_b72c41',
    '2026-09-07 20:30:00',
    '2026-08-02 08:50:00'
);


/* ============================================================
   10. ADMIN PASSWORD RESET TOKENS
   ============================================================ */

INSERT INTO Admin_Password_Reset_Tokens
(
    admin_id,
    token,
    expires_at
)
VALUES
(
    1,
    'test_reset_token_admin_1_a83f92',
    '2026-09-07 21:00:00'
),
(
    2,
    'test_reset_token_admin_2_b72c41',
    '2026-09-07 21:15:00'
);


/* ============================================================
   11. USER VERIFICATION
   ============================================================ */

INSERT INTO User_Verification
(
    user_id,
    id_type,
    id_number,
    college_id_image,
    verification_status,
    verified_by,
    verified_at
)
VALUES
(
    1,
    'Student ID',
    'STU2026001',
    'uploads/verification/rahul_student_id.jpg',
    'Approved',
    1,
    '2026-08-02 10:30:00'
),
(
    2,
    'Faculty ID',
    'FAC2026001',
    'uploads/verification/priya_faculty_id.jpg',
    'Approved',
    2,
    '2026-08-02 11:15:00'
);


/* ============================================================
   12. CATEGORIES
   ============================================================ */

INSERT INTO Categories
(
    category_name,
    description,
    status
)
VALUES
(
    'Books & Study Materials',
    'Academic books, reference books, notes, and study materials.',
    'Active'
),
(
    'Electronics',
    'Laptops, calculators, headphones, accessories, and other electronic items.',
    'Active'
),
(
    'Furniture',
    'Tables, chairs, study desks, shelves, and other furniture items.',
    'Active'
),
(
    'Clothing & Accessories',
    'Clothing, bags, watches, shoes, and other personal accessories.',
    'Active'
);


/* ============================================================
   13. PRODUCTS
   IMPORTANT:
   Database column = product_condition
   NOT condition
   ============================================================ */

INSERT INTO Products
(
    seller_id,
    category_id,
    title,
    description,
    price,
    product_condition,
    ownership_document,
    status
)
VALUES
(
    1,
    1,
    'Engineering Mathematics Book',
    'Engineering Mathematics reference book in good condition. Suitable for undergraduate students.',
    450.00,
    'Good',
    'uploads/ownership/rahul_math_book_receipt.jpg',
    'Sold'
),
(
    2,
    2,
    'Wireless Headphones',
    'Wireless headphones with good sound quality and battery backup. Lightly used.',
    1200.00,
    'Like New',
    'uploads/ownership/priya_headphones_receipt.jpg',
    'Sold'
);


/* ============================================================
   14. PRODUCT IMAGES
   ============================================================ */

INSERT INTO Product_Images
(
    product_id,
    image_path,
    is_primary
)
VALUES
(
    1,
    'uploads/products/engineering_mathematics_book_1.jpg',
    TRUE
),
(
    2,
    'uploads/products/wireless_headphones_1.jpg',
    TRUE
);


/* ============================================================
   15. TRANSACTIONS
   ============================================================ */

INSERT INTO Transactions
(
    product_id,
    seller_id,
    buyer_id,
    selling_price,
    transaction_status,
    transaction_date,
    notes
)
VALUES
(
    1,
    1,
    2,
    450.00,
    'Completed',
    '2026-08-02 12:00:00',
    'Transaction completed through mutual agreement.'
),
(
    2,
    2,
    1,
    1200.00,
    'Completed',
    '2026-08-02 13:00:00',
    'Product exchanged after buyer and seller agreement.'
);


/* ============================================================
   16. CHATS
   ============================================================ */

INSERT INTO Chats
(
    product_id,
    buyer_id,
    seller_id
)
VALUES
(
    1,
    2,
    1
),
(
    2,
    1,
    2
);


/* ============================================================
   17. MESSAGES
   IMPORTANT:
   Messages table DOES NOT have is_read.
   Uses read_at instead.
   ============================================================ */

INSERT INTO Messages
(
    chat_id,
    sender_id,
    message_text,
    message_type,
    attachment_path,
    sent_at,
    delivered_at,
    read_at
)
VALUES
(
    1,
    2,
    'Is the Engineering Mathematics book still available?',
    'Text',
    NULL,
    '2026-08-02 11:00:00',
    '2026-08-02 11:00:05',
    '2026-08-02 11:01:00'
),
(
    1,
    1,
    'Yes, it is still available.',
    'Text',
    NULL,
    '2026-08-02 11:02:00',
    '2026-08-02 11:02:05',
    '2026-08-02 11:03:00'
),
(
    2,
    1,
    'Is the wireless headphone price negotiable?',
    'Text',
    NULL,
    '2026-08-02 11:30:00',
    '2026-08-02 11:30:05',
    NULL
),
(
    2,
    2,
    'I can reduce the price slightly.',
    'Text',
    NULL,
    '2026-08-02 11:32:00',
    '2026-08-02 11:32:05',
    NULL
);


/* ============================================================
   18. WISHLIST
   ============================================================ */

INSERT INTO Wishlist
(
    user_id,
    product_id
)
VALUES
(
    1,
    2
),
(
    2,
    1
);


/* ============================================================
   19. USER PRESENCE
   ============================================================ */

INSERT INTO User_Presence
(
    user_id,
    last_seen_at
)
VALUES
(
    1,
    '2026-09-07 17:45:00'
),
(
    2,
    '2026-09-07 17:50:00'
);


/* ============================================================
   20. REVIEWS
   ============================================================ */

INSERT INTO Reviews
(
    product_id,
    reviewer_id,
    transaction_id,
    rating,
    review_text,
    status
)
VALUES
(
    1,
    2,
    1,
    5,
    'The book was in very good condition and exactly as described.',
    'Visible'
),
(
    2,
    1,
    2,
    4,
    'The headphones work well and were in almost new condition.',
    'Visible'
);


/* ============================================================
   21. REPORTS
   ============================================================ */

INSERT INTO Reports
(
    reporter_id,
    reported_user_id,
    reported_product_id,
    report_type,
    report_category,
    reason,
    description,
    status,
    reviewed_by,
    admin_notes,
    resolved_at
)
VALUES
(
    2,
    1,
    NULL,
    'User',
    'Inappropriate Behavior',
    'Inappropriate language',
    'The user used inappropriate language during the conversation.',
    'Resolved',
    1,
    'Report reviewed. Temporary ban issued to the reported user.',
    '2026-08-02 15:00:00'
),
(
    1,
    NULL,
    2,
    'Product',
    'Fake/Misleading Listing',
    'Misleading product information',
    'The product description did not accurately match the listed condition.',
    'Resolved',
    1,
    'Report reviewed. Warning issued to the seller.',
    '2026-08-02 16:00:00'
);


/* ============================================================
   22. REPORT EVIDENCE
   ============================================================ */

INSERT INTO Report_Evidence
(
    report_id,
    file_path,
    file_name,
    file_type,
    file_size
)
VALUES
(
    1,
    'uploads/reports/report_1_screenshot_1.png',
    'inappropriate_message.png',
    'image/png',
    245000
),
(
    2,
    'uploads/reports/report_2_screenshot_1.jpg',
    'product_condition.jpg',
    'image/jpeg',
    318000
);


/* ============================================================
   23. NOTIFICATIONS
   ============================================================ */

INSERT INTO Notifications
(
    user_id,
    admin_id,
    notification_type,
    title,
    message,
    reference_type,
    reference_id,
    is_read,
    read_at
)
VALUES
(
    1,
    NULL,
    'Product',
    'Product Sold',
    'Your Engineering Mathematics Book has been sold.',
    'Product',
    1,
    TRUE,
    '2026-08-02 12:30:00'
),
(
    NULL,
    1,
    'Report',
    'New Report Submitted',
    'A new report has been submitted and requires administrative review.',
    'Report',
    1,
    TRUE,
    '2026-08-02 14:00:00'
),
(
    1,
    NULL,
    'Ban',
    'Temporary Account Suspension',
    'Your account has been temporarily suspended until 2026-08-09.',
    'Ban',
    1,
    FALSE,
    NULL
),
(
    2,
    NULL,
    'Transaction',
    'Transaction Completed',
    'Your transaction for Wireless Headphones has been completed.',
    'Transaction',
    2,
    FALSE,
    NULL
);


/* ============================================================
   24. LOGIN SECURITY ACTIVITY
   ============================================================ */

INSERT INTO Login_Security_Activity
(
    user_id,
    activity_type,
    title,
    description
)
VALUES
(
    1,
    'Login',
    'Successful Login',
    'User successfully logged into the CampusXchange portal.'
),
(
    1,
    'New Device',
    'New Device Login',
    'A login was detected from a new device.'
),
(
    2,
    'Login',
    'Successful Login',
    'User successfully logged into the CampusXchange portal.'
),
(
    2,
    'Remembered Login',
    'Remembered Login Used',
    'A previously trusted login session was used.'
);


/* ============================================================
   25. USER BANS
   ============================================================ */

INSERT INTO User_Bans
(
    user_id,
    report_id,
    admin_id,
    ban_type,
    reason,
    description,
    start_date,
    end_date,
    status
)
VALUES
(
    1,
    1,
    1,
    'Temporary',
    'Inappropriate behavior',
    'Temporary suspension issued after review of the reported conversation.',
    '2026-08-02 15:00:00',
    '2026-09-10 15:00:00',
    'Active'
),
(
    2,
    2,
    1,
    'Warning',
    'Misleading product information',
    'Warning issued for providing inaccurate information in a product listing.',
    '2026-08-02 16:00:00',
    NULL,
    'Active'
);


/* ============================================================
   26. ADMIN LOGS
   ============================================================ */

INSERT INTO Admin_Logs
(
    admin_id,
    action_type,
    action_description,
    target_type,
    target_id,
    ip_address,
    user_agent
)
VALUES
(
    1,
    'Verify User',
    'College ID verification approved for Rahul Sharma.',
    'User',
    1,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    1,
    'Verify User',
    'College ID verification approved for Priya Verma.',
    'User',
    2,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    1,
    'Review Report',
    'Report regarding inappropriate user behavior was reviewed.',
    'Report',
    1,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    1,
    'Issue Ban',
    'Temporary ban issued to Rahul Sharma after report review.',
    'Ban',
    1,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    1,
    'Review Report',
    'Product report regarding misleading information was reviewed.',
    'Report',
    2,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
),
(
    1,
    'Issue Ban',
    'Warning issued to Priya Verma regarding misleading product information.',
    'Ban',
    2,
    '192.168.1.10',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
);


/* ============================================================
   27. SYSTEM SETTINGS
   ============================================================ */

INSERT INTO System_Settings
(
    setting_key,
    setting_value,
    setting_type,
    description,
    updated_by
)
VALUES
(
    'site_name',
    'Campus Sell & Buy Portal',
    'Text',
    'Name displayed across the marketplace.',
    1
),
(
    'maintenance_mode',
    '0',
    'Boolean',
    'Controls whether the portal is placed into maintenance mode.',
    1
),
(
    'user_favicon',
    'uploads/settings/user_favicon.ico',
    'Image',
    'Favicon displayed on the user-facing CampusXchange portal.',
    1
),
(
    'admin_favicon',
    'uploads/settings/admin_favicon.ico',
    'Image',
    'Favicon displayed on the administrator dashboard.',
    1
),
(
    'support_email',
    'support@campusportal.edu',
    'Email',
    'Email address used for general platform support.',
    1
);


/* ============================================================
   28. FINISH
   ============================================================ */

SET FOREIGN_KEY_CHECKS = 1;


/* ============================================================
   OPTIONAL VERIFICATION QUERIES
   Run these after import if you want to verify the data.
   ============================================================ */

SELECT * FROM Users;
SELECT * FROM Admins;
SELECT * FROM Categories;
SELECT * FROM Products;
SELECT * FROM Transactions;
SELECT * FROM Chats;
SELECT * FROM Messages;
SELECT * FROM Reviews;
SELECT * FROM Reports;
SELECT * FROM User_Bans;
SELECT * FROM System_Settings;
