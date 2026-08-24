# PhoneVault – Second-Hand Phone Store & Warranty Management System

PhoneVault is a PHP & MySQL web application designed for managing inventory, sales, warranties, returns, and export reporting for second-hand phone stores.

## Features

- **Dashboard**: High-level store metrics, sales summaries, and quick action shortcuts.
- **Inventory Management**: Track phone stock, IMEIs, conditions, prices, and status.
- **Point of Sale (POS)**: Fast checkout workflow with automatic warranty generation.
- **Warranty Tracking**: Look up customer warranty claims and coverage by IMEI or sale ID.
- **Returns & Refunds**: Process phone returns and claims smoothly.
- **Export & Reports**: Export inventory, sales, and warranty records to PDF and CSV.
- **Settings & User Management**: Admin panel for managing staff accounts and store preferences.

## Requirements

- PHP 8.x
- MySQL / MariaDB (XAMPP recommended)
- Composer (for PDF dependencies)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/sjyousef/phone_vault.git
   ```
2. Import the database schema from `db/schema.sql` into MySQL.
3. Configure database credentials in `config/database.php`.
4. Install PHP dependencies:
   ```bash
   composer install
   ```
5. Open in browser via local server (e.g. `http://localhost/Second_Hand_Phone_Store`).
