# 🌍 SAFAR — Travel & Tour Booking Web App

SAFAR is a full-stack travel and tour booking platform with **three user roles** — Traveler, Agency and Admin. Travelers explore and book packages, agencies list and manage their own packages/bookings, and admins oversee the entire platform (agencies, payments, coupons, analytics) from a dedicated dashboard.

The project is a **monorepo** with a PHP/MySQL backend (REST API + traveler/agency-facing site) and a React/Next.js admin panel that talks to the backend over JWT-authenticated APIs.

---

## 📑 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Installation](#️-installation)
- [Testing](#-testing)
- [Security Notes](#-security-notes)
- [License](#-license)

---

## ✨ Features

### 👤 Traveler
- Signup / login, JWT-based authentication
- Forgot / reset password via email
- Browse, filter, sort & paginate travel packages
- Homepage curation: featured, popular, recommended & special-offer packages
- Package & hotel details
- Wishlist (save packages for later)
- Booking flow with coupon/discount code application
- Online payments via **SSLCommerz** (sandbox) — success / fail / cancel handling
- Booking cancellation requests
- Downloadable invoices
- Ratings & reviews submission
- In-app notifications
- Profile management
- Dark mode

### 🏢 Agency
- Agency signup & profile
- Verification workflow — admin approves before the agency can list packages
- Add / edit / manage own travel packages
- Bulk hotel data import (legacy format → normalized via adapter)
- View & manage incoming bookings for own packages (accept / reject / update status)
- Track own booking stats, earnings & commission
- Dark mode

### 🛠️ Admin Panel (React / Next.js)
- Secure JWT-protected admin dashboard with analytics overview (Recharts)
- **Package management** — full CRUD
- **Booking management** — view, update status, booking details, email + in-app notifications on changes
- **Agency management** — approve / reject / suspend / activate / unverify (Command pattern), agency profile & reviews view
- **User management** — view, moderate & remove traveler/agency accounts
- **Cancellation management** — review & resolve traveler cancellation requests
- **Commission & revenue tracking** — auto-synced from successful payments, configurable platform commission %
- **Coupon management** — percentage or fixed-value discounts, min booking amount, usage limits, expiry
- **Featured packages** — curate homepage sections (featured / popular / recommended / special offers)
- **Payment management** — view & manage traveler payments and booking-linked payments
- **Review moderation**
- **Notification management**
- Dark mode

---

## 🧱 Tech Stack

| Layer          | Technology                                             |
|----------------|---------------------------------------------------------|
| Backend        | PHP 8.2, MySQL, REST API, JWT (`firebase/php-jwt`)       |
| Frontend (Admin) | React 19, Next.js 16, Tailwind CSS 4, Recharts        |
| Payments       | SSLCommerz (sandbox)                                    |
| Email          | PHPMailer                                                |
| Testing        | PHPUnit 11 + PCOV (code coverage)                        |
| Dev Environment| XAMPP (Apache + MySQL), Windows/PowerShell               |

---

## 📂 Project Structure

```text
SAFAR/
├── backend/                 # PHP/MySQL backend + traveler-facing site
│   ├── api/                 # REST API endpoints (auth, admin, traveler, payment)
│   ├── admin/                # Legacy PHP admin views
│   ├── dashboard/            # Agency & traveler dashboards
│   ├── includes/             # Shared helpers (DB, JWT, auth, config)
│   ├── tests/Unit/           # PHPUnit test suites
│   │   ├── Validator/ QueryBuilder/ Service/ Helper/
│   │   └── Singleton/ Factory/ Strategy/ Observer/ Command/ Facade/ Adapter/
│   ├── index.php, explore.php, package-details.php, login.php, signup.php ...
│   ├── composer.json
│   ├── phpunit.xml
│   └── database.sql
│
└── frontend/                 # React / Next.js admin panel
    └── src/
        ├── app/
        │   ├── admin/         # Admin dashboard routes (packages, bookings, coupons...)
        │   └── login/
        ├── components/
        └── lib/               # API client, auth helpers
```

---

## ⚙️ Installation

### Backend (PHP/MySQL — XAMPP)

1. Clone the repo into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/icsumaiya/SAFAR-Web-App.git
   ```
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Create a database (e.g. `safar_db`) in phpMyAdmin and import:
   - `backend/database.sql`
   - `backend/seed_packages.sql`, `backend/setup_hotels.sql`, `backend/setup_payments.sql` (sample data)
4. Copy the example config files and fill in your own values:
   ```bash
   cd backend/includes
   cp jwt_config.example.php jwt_config.php
   cp payment_config.example.php payment_config.php
   cp email_config.example.php email_config.php
   ```
   > ⚠️ `jwt_config.php` must define a JWT secret of **32+ characters**. These files are gitignored — never commit real secrets.
5. Install PHP dependencies:
   ```bash
   cd backend
   composer install
   ```
6. Visit `http://localhost/SAFAR/backend` in your browser.

### Frontend (Admin Panel — Next.js)

```bash
cd frontend
npm install
npm run dev
```
Open `http://localhost:3000` — you'll be redirected to `/login`, then to `/admin` after signing in with an admin account.

---

## 🧪 Testing

The backend uses **PHPUnit 11** with **PCOV** for code coverage. Business logic is extracted out of plain `api/*.php` files into dedicated, unit-testable classes (the **ApiHandler pattern**), keeping HTTP/CORS concerns separate from the logic being tested.

```bash
cd backend
composer install
php vendor/bin/phpunit                 # run tests
php vendor/bin/phpunit --coverage-text # coverage summary
```

### Current Coverage

Core backend logic (`admin/`) is at **58.23% line coverage** — above the **50% minimum requirement**.

| Scope        | Line Coverage |
| ------------ | ------------: |
| **Overall**  |  **58.23% ✅** (target: 50%+) |

Functions/Methods coverage: **84.55%** · Classes/Traits coverage: **82.43%**
`api/` and `dashboard/` logic is being extracted into testable service/handler classes (ApiHandler pattern) to bring their coverage up next.

Design patterns implemented and covered by tests: **Singleton, Factory Method, Strategy, Observer, Facade, Adapter, Command**.

---

## 🔐 Security Notes

- JWT secret is externalized to a gitignored config file (`jwt_config.php`)
- File uploads are restricted by an extension whitelist
- Payment integration runs against the SSLCommerz **sandbox** environment only

---

## 📄 License

Educational project developed for academic coursework.
