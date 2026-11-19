![Mira Edit project cover](docs/readme-banner.svg)

<div align="center">
  <h1>Mira Edit Fashion Store</h1>
  <p><strong>A fashion storefront with an operations-focused admin console.</strong></p>
  <p><img alt="PHP 8.2" src="https://img.shields.io/badge/PHP%208.2-243447?style=flat-square"> <img alt="MySQL" src="https://img.shields.io/badge/MySQL-243447?style=flat-square"> <img alt="Composer" src="https://img.shields.io/badge/Composer-243447?style=flat-square"> <img alt="Retail demo" src="https://img.shields.io/badge/Retail%20demo-243447?style=flat-square"></p>
</div>

---

## Overview

A standalone retail demo with customer-facing shopping and a broad management workspace. The source includes fictional fashion products, local generated imagery, checkout using manual payment methods and staff operations for catalog, orders and inventory.

> [!NOTE]
> This is a public source snapshot prepared from a local project. It includes implementation code and safe schema/sample data, without live records or credentials.

## What the software can do

| Area | Implemented scope |
| --- | --- |
| **Storefront** | Categories, filters/search, product galleries, size variants, wishlist and policies. |
| **Shopping** | Customer accounts, cart, coupons, checkout, order tracking, reviews and invoices. |
| **Operations** | Products, attributes, brands, suppliers, inventory, purchases and stock reservations. |
| **Commerce admin** | Orders, customers, manual payments, refunds, content, staff permissions, audit logs and reports. |
| **Demo content** | Fictional products in five categories with size variants and local images. |

### Typical workflow

1. Staff seed or manage the catalogue and available variants.
2. A customer browses, adds items to a cart and submits checkout with a manual payment choice.
3. Staff verify payment and process the order from admin; inventory and audit records reflect the workflow.

## Run locally

1. Use PHP 8.2 with PDO MySQL, mbstring, GD and Composer.
2. Copy `config/example.php` to `config/local.php` and set new local database credentials.
3. Run `composer install` and `php bin/setup.php` for a fresh schema.
4. Optionally run `php bin/seed-fashion.php` on an empty catalogue for fictional products.
5. Create a new administrator using the CLI tool documented in `bin/admin.php`, then open the storefront and `/admin/`.

## Database and sample content

`database/database.sql` and `database/migrations/` define the retail schema. The seed script adds fictional content only when the catalogue is empty. Customer, order, payment and staff data from the local installation are excluded.

## Repository guide

| Path | Purpose |
| --- | --- |
| `app/` | Commerce, authentication, admin and view services |
| `public/` | Storefront/admin entry points and assets |
| `resources/views/` | Store and order templates |
| `bin/` | Setup, seed and admin CLI tools |
| `database/`, `tests/` | Schema, migrations and checks |

## Security and privacy

- Customer/admin actions use CSRF and password hashing.
- Staff permissions and audit records are part of the admin workflow.
- The installation-specific `config/local.php` is ignored and excluded.

## Current boundaries

- Cash and bank transfer are manual verification methods; card checkout is disabled until a provider is integrated.
- Sample policy, bank, inventory and product details require business review.
- SMTP delivery and production hosting have not been validated by this source snapshot.

## Verification

Run the included PHP/Python tests on a disposable database and exercise catalog-to-order processing in a browser.

---

<div align="center">

Software Development by <a href="https://www.energeticedge.lk/" target="_blank" rel="noopener noreferrer">Energetic Edge (Pvt) Ltd</a>

</div>
