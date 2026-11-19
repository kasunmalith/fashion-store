# Mira Edit Fashion Store

> A standalone fashion storefront and retail administration demo.

## What it does

- Products, variants, search, filters and wishlist
- Customer accounts, cart, coupons, checkout and tracking
- Manual payment methods, orders, reviews and invoices
- Admin inventory, suppliers, staff permissions and reports
- Fictional sample catalog and product imagery

## Technology

PHP 8.2, MySQL, Composer, JavaScript

## Local setup

1. Copy `config/example.php` to `config/local.php` and set local database details.
2. Run `composer install`.
3. Run `bin/setup.php`, then `bin/seed-fashion.php` for fictional sample products.
4. Open the storefront through WAMP.

## Database and sample data

Base schema in `database/database.sql` and additive migrations in `database/migrations/`.

Only schema and fictional starter data are intended for this public repository. Live customer, patient, order and enquiry records are never part of this snapshot.

## Publication note

This repository is a source snapshot. It does not include deployment credentials, live database records, customer uploads or server backups. Create fresh secrets and review the application before production use.

## Development credit

Software Development by [Energetic Edge (Pvt) Ltd](https://www.energeticedge.lk/)
