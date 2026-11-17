# RESTORA — Restaurant Management System

Restora is a web-based **Restaurant Management System** developed as an MCA academic project. It is designed strictly for single-restaurant, dine-in operations—allowing restaurant administrators and staff to manage menu categories, food items, tables, dine-in orders, kitchen status updates, billing, payments, and sales reports.

---

## Project Scope

- **Restaurant Type:** Single restaurant
- **Order Mode:** Dine-in only (every order is linked to a restaurant table)
- **Users:** Restaurant Admin and Staff only (no customer-facing portal)
- **Currency:** Indian Rupee (`₹` / INR)

---

## Technology Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, Bootstrap Icons, JavaScript, jQuery
- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB (using MySQLi with prepared statements)
- **Local Server & Tools:** XAMPP, phpMyAdmin, VS Code, Git & GitHub

---

## Core Modules & Features

1. **Admin & Staff Authentication**
   - Secure session-based login and logout
   - Password hashing (`password_hash` / `password_verify`)
   - Role-based access control (`Admin` and `Staff`)
   - Staff account registration by Admin
2. **Dashboard**
   - Live summary cards for total menu items, total categories, available/total tables, today's orders, and today's paid sales
   - Recent orders overview table
3. **Category Management**
   - Add, view, edit, and delete food categories
4. **Menu Management**
   - Add, view, edit, and delete menu items
   - Assign categories, upload item images, and toggle item availability
5. **Table Management**
   - Add, view, edit, and delete restaurant tables with seating capacity
   - Track and manage `Available` and `Occupied` table statuses
6. **Dine-In Order Management**
   - Select an available table and create a dine-in order
   - Add multiple menu items with custom quantities
   - Automatic subtotal, tax, and total calculation
7. **Kitchen & Order Status Tracking**
   - View incoming orders and update statuses (`Pending`, `Accepted`, `Preparing`, `Ready`, `Completed`, `Cancelled`)
8. **Billing & Payments**
   - Generate and view printable customer invoices
   - Record payments via **Cash**, **UPI**, or **Card** and update payment status (`Unpaid` / `Paid`)
9. **Reports**
   - Daily sales report, order report, popular menu items, and payment method summary

---

## Database Structure (`restora_db`)

The database consists of six relational tables:

- `users` — Admin and staff credentials and roles
- `categories` — Menu item categories
- `menu_items` — Food items, pricing, image paths, availability, and category foreign key
- `restaurant_tables` — Table numbers, capacity, and availability status
- `orders` — Dine-in order records, table/user references, totals, order status, and payment details
- `order_items` — Individual line items, quantities, unit prices, and line totals per order

---

## Folder Structure

```text
restora/
├── admin/
│   ├── auth/           # Login, logout, and staff registration
│   ├── dashboard/      # Overview metrics and recent orders
│   ├── categories/     # Category CRUD
│   ├── menu/           # Menu item CRUD & image uploads
│   ├── tables/         # Restaurant table CRUD & status
│   ├── orders/         # Dine-in order creation & status updates
│   ├── billing/        # Invoices & payment recording
│   └── reports/        # Sales, order, and item reports
├── ajax/               # AJAX endpoints for menu and orders
├── assets/
│   ├── css/            # Custom stylesheets (style.css, admin.css)
│   ├── js/             # Custom scripts (script.js, admin.js)
│   └── images/         # Logo and uploaded menu item images
├── config/
│   └── database.php    # MySQLi database connection
├── database/
│   └── restora.sql     # Database schema and initial seed SQL
├── includes/           # Reusable auth checks, helpers, header, sidebar, navbar, footer
├── index.php           # Landing page
├── .gitignore
└── README.md