# 💊 MediCare Pharmacy — Pharmacy Management System

A complete, ready-to-run **PHP + MySQL** pharmacy management system.

## Features

- **Medicine classification** — Tablet, Capsule, Syrup, Suspension, Drops, Drink/Sachet, Injection, Ointment, Inhaler, Other (you can add more anytime under **Categories**)
- **Medicines master list** — add/edit/search medicines with generic name, manufacturer, batch no, expiry date, purchase & selling price, reorder level
- **Inventory management** — view current stock for every item, add or remove stock manually anytime (with a reason), color-coded low-stock / out-of-stock warnings
- **Full audit trail** — every stock change (purchase, sale, manual add/remove) is permanently logged in **Stock History** with before/after quantities, who did it, and when
- **Purchases (buying stock)** — record purchases from suppliers with multiple line items; stock is automatically increased
- **Sales / POS (selling to customers)** — pick items, quantities auto check against available stock, computes total/discount/paid/due; stock is automatically decreased
- **Printable memo** — every sale generates a clean, print-ready receipt (memo) you can hand to or print for the customer
- **Sales history** & **Purchase history** — searchable/filterable by date
- **Suppliers** and **Customers** management
- **Dashboard** — today's sales, this month's sales/purchases, low stock alerts, expiry alerts, recent sales
- **Login system** so only authorized staff can access it

## Requirements

- PHP 7.4+ (PHP 8 is fine too)
- MySQL 5.7+ / MariaDB
- Any local server stack: **XAMPP**, **WAMP**, **MAMP**, or **LAMP** (Linux)

## Installation (step by step)

1. **Copy the folder** `pharmacy_management_system` into your server's web root:
   - XAMPP (Windows): `C:\xampp\htdocs\pharmacy_management_system`
   - WAMP (Windows): `C:\wamp64\www\pharmacy_management_system`
   - MAMP (Mac): `/Applications/MAMP/htdocs/pharmacy_management_system`
   - Linux: `/var/www/html/pharmacy_management_system`

2. **Start Apache and MySQL** from your XAMPP/WAMP/MAMP control panel.

3. **Create the database**:
   - Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`)
   - Click **Import**, choose the file `sql/schema.sql` from this project, click **Go**.
   - This creates the `pharmacy_db` database with all tables and starter medicine categories.

4. **Check your DB credentials** in `config.php` (defaults already match standard XAMPP: host `localhost`, user `root`, no password). Change if your MySQL setup is different.

5. **Create your admin login** — open this URL in your browser **once**:
   ```
   http://localhost/pharmacy_management_system/install.php
   ```
   Fill in a username/password (defaults: `admin` / `admin123`) and submit.
   ⚠️ **Delete `install.php` afterward** for security — it won't run again once a user exists anyway.

6. **Log in**:
   ```
   http://localhost/pharmacy_management_system/login.php
   ```

You're ready to go!

## How to use it

1. **Categories** — check/add the medicine types you sell (Syrup, Tablet, Capsule, Drops, Suspension, Drink, etc.) — several are pre-loaded already.
2. **Medicines** — add your medicine items, assign each a category, set purchase price / selling price / reorder level, and optional starting stock.
3. **Purchases → New Purchase** — whenever you buy stock from a supplier, record it here. Stock increases automatically and it's saved to Purchase History.
4. **Inventory** — see live stock for every medicine. Use the **Add/Remove** buttons anytime you need a manual correction (damaged goods, stock count correction, etc.) — every change is reasoned and logged.
5. **Sales → New Sale** — this is your point-of-sale screen. Pick medicines and quantities (it warns you if stock is insufficient), enter discount/paid amount, and submit. It automatically:
   - deducts the sold quantity from inventory
   - logs it in Stock History
   - generates a unique memo number
   - opens a **printable memo/receipt** you can print and hand to the customer
6. **Stock History** — full audit trail of every inventory movement (purchases, sales, manual changes) — filterable by medicine or type.
7. **Sales History / Purchase History** — browse and filter past transactions, reprint any memo, view any purchase invoice.
8. **Dashboard** — quick overview: today's/month's sales, month's purchases, low-stock & expiring-soon alerts, recent sales.

## Notes on customization

- **Currency symbol**: change `CURRENCY` in `config.php`.
- **Pharmacy name / letterhead**: change `APP_NAME` in `config.php` and edit the header text inside `memo_print.php` (address/phone line).
- **Timezone**: change in `config.php`.
- Want batch-level / multiple-expiry-date tracking per medicine instead of one batch per item? That's a natural next step (a separate `inventory_batches` table) if your pharmacy needs to track many batches of the same medicine at once — ask and this can be extended.

## File structure

```
pharmacy_management_system/
├── sql/schema.sql              ← import this into MySQL first
├── config.php                  ← DB connection settings
├── install.php                 ← run once to create admin login, then delete
├── login.php / logout.php
├── index.php                   ← dashboard
├── categories.php               ← medicine classifications
├── medicines.php / medicine_form.php
├── inventory.php                ← view stock + manual add/remove
├── inventory_history.php        ← full stock audit trail
├── purchase_form.php / purchase_view.php / purchase_history.php
├── sales.php                    ← POS (new sale)
├── memo_print.php               ← printable customer memo/receipt
├── sales_history.php
├── suppliers.php / customers.php
└── includes/                    ← shared header, footer, auth & helper functions
```

Enjoy running your pharmacy! 🏥
