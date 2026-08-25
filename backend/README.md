# 🌍 SAFAR Web App

## ✈️ Modern Travel & Tour Booking Web Application

SAFAR is a responsive travel and tourism management platform built with **PHP, MySQL, HTML, CSS, and JavaScript**. Users can explore travel packages, manage profiles, and make bookings, while admins can manage packages, users, agencies, and bookings.

## 🛠️ Technologies

- PHP
- MySQL
- HTML5 / CSS3
- JavaScript
- XAMPP
- PHPUnit 11

## 📌 Main Features

### 👤 User
- Authentication
- Explore travel packages
- Package details
- Booking management
- Profile management

### 🛠️ Admin
- Admin dashboard
- Package management
- User management
- Agency management
- Booking management

## 📂 Project Structure

```text
SAFAR-Web-App/
├── admin/
│   └── includes/       # Extracted and tested business logic
├── api/
├── dashboard/
├── includes/
├── assets/
├── tests/
│   ├── bootstrap.php
│   └── Unit/
├── composer.json
├── phpunit.xml
├── database.sql
└── README.md
```

## ⚙️ Installation

1. Clone the repo into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/icsumaiya/SAFAR-Web-App.git
   ```
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Create a database (e.g. `safar_db`) in phpMyAdmin and import `database.sql`
   (and `seed_packages.sql` / `setup_hotels.sql` if you want sample data).
4. Update the DB credentials in `includes/db.php` if needed.
5. Visit `http://localhost/SAFAR-Web-App` in your browser.

## 🧪 Software Testing

The project uses **PHPUnit 11** for automated unit testing.
Business logic was extracted from page-controller files into independent classes so that the logic can be tested without running the web application or database.

### Tested Components

- `PackageValidator`
- `PackageSearchQueryBuilder`
- `BookingRequestValidator`
- `ListingFilterFactory`
- `AgencyCommandFactory`
- `UserManagementValidator`
- `UserSearchQueryBuilder`
- `BookingManagementHelper`
- `TravelerBookingSearchQueryBuilder`
- `AdminDashboardService`
- `NavHelper`
- Factory, Adapter, Strategy, Observer, Command, Facade and Singleton classes

### Test Result

```text
136 tests passing
236 assertions
~15% overall line coverage
```

**All extracted business-logic classes listed above are 100% covered.**

### Why is overall coverage only ~15%?

The coverage is calculated across the scoped backend folders:

```text
admin/
api/
includes/
dashboard/
```

Most of the remaining uncovered lines are HTML/UI code inside PHP page-controller files, such as tables, forms, layouts, and presentation markup. These lines are not meaningful targets for unit testing.

The actual business logic — validation, SQL/query building, decision-making, data transformation, and service logic — has been extracted into separate classes and fully tested.

Therefore, the low overall percentage is mainly caused by the large amount of HTML/presentation code included in the coverage scope, **not** by untested extracted business logic.

### Running Tests

```bash
composer install
php vendor/bin/phpunit
```

### Coverage Report

```bash
php vendor/bin/phpunit --coverage-text
```


## 📄 License

Educational project developed for learning purposes.
