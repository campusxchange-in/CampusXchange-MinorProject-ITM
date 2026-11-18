# CampusXchange

A campus marketplace where verified students buy, sell and exchange items
with each other (peer-to-peer: chat with the seller, agree on price and a
meeting spot on campus, pay in person).

**Includes**

- **User site** (React + Vite): sign up, verification, browse products, sell items, wishlist, chat, orders, reports
- **Admin panel** (PHP): users, products, verification requests, reports, reviews, categories, analytics, activity logs, settings
- **Backend API** (PHP + MySQL): `backend/`

## Tech stack

| Part | Technology |
|---|---|
| Frontend | React 19, Vite, React Router, Bootstrap |
| Backend / Admin | PHP 8.x (mysqli), Apache |
| Database | MySQL / MariaDB (database name: `cxdb`) |
| Email | PHPMailer (via `server/mail/PrivateMailer`), Gmail SMTP |

## Folder structure

```
CX/
├── admin/            PHP admin panel
├── backend/          PHP API for the user site (auth, user, public)
├── config/           app_config.php (frontend URL, base URL, timezone)
├── credentials/      .env (email settings)  + .env.example
├── database files/   cxdb.sql  (import this)  + database.sql / dummy_data.sql
├── server/           db_connect.php, helpers, mail library
├── uploads/          uploaded images (products, profiles, reports, ID proofs)
└── vite-project/     React frontend
```

## Requirements

- [XAMPP](https://www.apachefriends.org/) with **PHP 8.1 or newer** (Apache + MySQL)
- [Node.js](https://nodejs.org/) **20.19 or newer** (includes npm)
- A Gmail account with an **App Password** (only needed for sending emails)

---

## Setup (Windows + XAMPP)

### 1. Put the project in `htdocs`

Copy the whole project folder to:

```
C:\xampp\htdocs\CX
```

> The folder name must be exactly **`CX`**. The app expects the backend at `http://localhost/CX`.

### 2. Start XAMPP

Open **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.

### 3. Create and import the database

1. Open **http://localhost/phpmyadmin**
2. Click **New** in the left sidebar
3. Database name: `cxdb`, collation: `utf8mb4_general_ci`, then click **Create**
4. Click the new **cxdb** database, then open the **Import** tab
5. Click **Choose file** and select `database files/cxdb.sql`
6. Leave everything else as default and click **Import** at the bottom

You should see about 30 tables (admins, users, products, chats, reports, ...).

> The database settings are in `server/database/db_connect.php`
> (host `localhost`, user `root`, empty password, database `cxdb`).
> These are the XAMPP defaults. Only change them if you set a MySQL password.

### 4. Configure email (`credentials/.env`)

Sign-up verification, password reset and OTP emails use Gmail SMTP.

1. Copy `credentials/.env.example` to `credentials/.env`
2. Fill it in:

```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=CampusXchange
```

To get an App Password: Google Account → Security → turn on **2-Step
Verification** → **App passwords** → create one and paste it as
`MAIL_PASSWORD` (no spaces).

> `credentials/.env` is not uploaded to GitHub because it holds a real password.
> The app will not start without this file, so create it on every new machine.

### 5. Check the URLs (`config/app_config.php`)

The defaults work for local XAMPP, so no change is needed:

```php
FRONTEND_URL  = http://localhost:5173
PHP_BASE_URL  = http://localhost/CX
```

### 6. Run the frontend

Open a terminal in the `vite-project` folder:

```bash
cd vite-project
npm install
npm run dev
```

`vite-project/.env` should contain:

```
VITE_API_BASE_URL=http://localhost/CX
```

Open **http://localhost:5173** in your browser.

> The dev server must run on port **5173**, which matches `FRONTEND_URL`.
> If it says the port is in use, close the other app using it.

### 7. Open the app

| What | URL |
|---|---|
| User site | http://localhost:5173 |
| Admin login | http://localhost:5173/admin-login |
| phpMyAdmin | http://localhost/phpmyadmin |

**Demo logins:** `<add your demo user / admin email and password here>`

---

## Common problems

| Problem | Fix |
|---|---|
| "Database connection failed." | MySQL is not running in XAMPP, or the database is not named `cxdb`. |
| "Environment file is missing or unreadable" | Create `credentials/.env` (step 4). |
| Frontend shows "Unable to reach the server." | Apache is not running, or the folder is not named `CX` in `htdocs`. |
| Login or API calls blocked (CORS) | The frontend must be opened at exactly `http://localhost:5173` (not `127.0.0.1`). |
| Emails are not arriving | Check the Gmail App Password, `MAIL_PORT=587` and `MAIL_ENCRYPTION=tls`. |
| Image upload fails | In `C:\xampp\php\php.ini` check `file_uploads=On` and `extension=fileinfo`, then restart Apache. |
| `npm install` fails | Update Node.js to 20.19 or newer. |

## Resetting the database

In phpMyAdmin, select `cxdb` → **Operations** → **Drop the database**, then repeat step 3.

## Authors

CampusXchange, MCA minor project, ITM University.
