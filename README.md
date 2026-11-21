Here is a clean, well-structured `README.md` tailored specifically to your **Restora** project structure, tech stack, database schema, and features.

```markdown
# RESTORA — Dine-In Restaurant Management System

A lightweight, web-based Restaurant Management & Point-of-Sale (POS) system built with **PHP 8+**, **MySQL / MariaDB**, and **Bootstrap 5**. Restora streamlines dine-in operations by connecting table occupancy, menu management, order tracking, and INR (₹) billing in a single interface.

---

## Key Features

- **Authentication & Role-Based Access:** Secure login and registration with hashed passwords (`password_hash` / `password_verify`) supporting **Admin** and **Staff** roles.
- **Dashboard Overview:** Quick summary cards for tables, active orders, menu items, and revenue collected.
- **Menu & Category Management:** Create food categories, add/edit dishes with pricing in INR (₹), upload food images, and toggle item availability.
- **Dining Table Control:** Manage restaurant tables, seating capacities, and live statuses (`Available`, `Occupied`, `Reserved`).
- **Dine-In Order Management:** Create table-linked orders with live total calculation, track kitchen progress (`Pending`, `Accepted`, `Preparing`, `Ready`, `Completed`, `Cancelled`), and auto-release tables upon order completion or cancellation.
- **Billing & Payment Settlement:** Generate itemized customer bills and record payments via **Cash**, **UPI**, or **Card**.

---

## Tech Stack

- **Backend:** PHP 8+ (MySQLi with Prepared Statements & ACID Transactions)
- **Database:** MySQL / MariaDB (`restora_db`)
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript
- **Server Environment:** XAMPP (Apache + MariaDB)

---

## Project Structure

```text
restora/
├── index.php                  # Public landing page
├── config/
│   └── database.php           # MySQLi database connection
├── includes/
│   ├── auth_check.php         # Session protection guard
│   ├── functions.php          # Helper functions (order numbers, status badges, counts)
│   ├── header.php             # HTML head & CSS imports
│   ├── sidebar.php            # Navigation sidebar
│   ├── navbar.php             # Top header bar
│   └── footer.php             # Footer & Bootstrap JS
├── assets/
│   ├── css/
│   │   ├── style.css          # Landing page styles
│   │   └── admin.css          # Admin & staff dashboard styles
│   └── images/
│       └── menu/              # Uploaded dish images
└── admin/
    ├── auth/
    │   ├── login.php          # User login
    │   ├── register.php       # Account registration
    │   └── logout.php         # Session destroy & redirect
    ├── dashboard/
    │   └── index.php          # Main overview dashboard
    ├── categories/            # CRUD for menu categories (index, add, edit, delete)
    ├── menu_items/            # CRUD for food items & images (index, add, edit, delete)
    ├── tables/                # CRUD for dining tables (index, add, edit, delete)
    ├── orders/                # Order listing, creation & status tracking (index, create, view)
    └── billing/               # Payment collection & customer bills (index, invoice)

```

---

## Database Schema (`restora_db`)

The system uses 6 relational tables:

1. **`users`** — Stores staff and admin accounts (`id`, `name`, `username`, `password`, `role`, `created_at`).
2. **`categories`** — Stores menu categories (`id`, `name`, `description`, `created_at`).
3. **`menu_items`** — Stores dishes linked to categories (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `is_available`, `created_at`).
4. **`restaurant_tables`** — Tracks dining tables (`id`, `table_number`, `capacity`, `status`, `created_at`).
5. **`orders`** — Tracks dine-in orders (`id`, `order_number`, `table_id`, `user_id`, `subtotal`, `tax_amount`, `total_amount`, `order_status`, `payment_status`, `payment_method`, `created_at`).
6. **`order_items`** — Line items for each order (`id`, `order_id`, `menu_item_id`, `item_name`, `quantity`, `unit_price`, `line_total`).

---

## Setup & Installation (XAMPP)

1. **Clone or Copy Project:**
Place the `restora` folder inside your XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\restora

```


2. **Start Services:**
Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. **Create & Import Database:**
* Open `http://localhost/phpmyadmin`
* Create a new database named `restora_db`
* Import your SQL schema file into `restora_db`


4. **Configure Database Connection:**
Verify credentials in `config/database.php`:
```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "restora_db";

```


5. **Run the Application:**
Visit `http://localhost/restora` in your browser, register an admin/staff account via `admin/auth/register.php`, and log in.

```

```