# EdgeMart local verification — 4 October 2026

The application is installed at `C:\wamp64\www\store` and was exercised on the user's WAMP localhost installation. The main demonstration database was not used by the automated financial tests; those tests create isolated `edgemart_test_*` databases.

| Check | Result |
| --- | --- |
| PHP syntax | 29 application and test PHP files linted; 0 errors |
| JavaScript syntax | `public/assets/app.js` and `public/assets/admin.js` passed `node --check` |
| Core transactional suite | 44 passed, 0 failed |
| HTTP workflow suite | 74 passed, 0 failed |
| Security and access suite | 26 passed, 0 failed |
| Apache subdirectory routing | Storefront, admin, API, CSS, JavaScript, and hero asset load at `/store/`; private source/config paths denied |
| Domain-root routing | Root entry point, assets, API and POST flow checked with a temporary PHP server |
| Responsive browser inspection | Home, catalog, product, cart, checkout, admin products, and product dialog inspected at 320, 375, 390, 430, 768, 1024, and 1440 CSS px; no horizontal overflow found |
| PDF | A real invoice downloaded, rendered, and visually inspected; receipt download also passed HTTP testing |

The core suite covered money precision, deposits, proportional discounts, checkout idempotency, payment confirmation, refunds, inventory reservations and fulfillment, pre-order allocation, invoice snapshots, permissions, configurable attributes, reports, and a concurrent final-unit purchase. HTTP/security tests also covered staff sign-in, admin creation/editing, cart and checkout, ownership checks, image uploads, password reset, CSRF, and private-path denial.

Desktop and mobile screenshots are saved outside the web root as `Store-Desktop-Preview.jpg` and `Store-Mobile-Preview.jpg` in the task's `outputs` directory. These are visual references for the current demo content, not approval of actual shop copy, pricing, policies, or branding.

Before public launch, configure and verify a real SMTP provider, any real payment gateway, production HTTPS and secure cookies, a dedicated database user, actual business content/policies, backups, and scheduled reservation expiry. Live SMTP, gateway settlement, Linux/shared hosting, and MariaDB were not tested here. Administrative selectors currently load at most 500 records and should become remote searchable controls for larger catalogs. This local result is not a production certification.
