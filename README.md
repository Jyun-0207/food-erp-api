# Food ERP API

A comprehensive Food ERP (Enterprise Resource Planning) REST API built with Laravel, covering sales, purchasing, inventory, manufacturing, accounting, HR/attendance, and storefront operations.

## Tech Stack

- **Framework:** Laravel 12
- **PHP:** 8.2+
- **Authentication:** Laravel Sanctum (token-based)
- **Database:** MySQL (SQLite for testing)
- **ID Strategy:** ULID string primary keys

## Installation

```bash
# Clone the repository
git clone <repo-url>
cd food-erp-api

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

## API Modules

| Module | Prefix | Description |
|--------|--------|-------------|
| Auth | `/api/auth` | Registration, login, logout, profile |
| Products | `/api/products` | Product CRUD with category association |
| Categories | `/api/categories` | Hierarchical product categories |
| Customers | `/api/customers` | Customer management with price lists |
| Suppliers | `/api/suppliers` | Supplier management with price lists |
| Sales Orders | `/api/sales-orders` | Order lifecycle: create, ship, return, refund |
| Purchase Orders | `/api/purchase-orders` | PO lifecycle: create, receive, return, refund |
| Inventory | `/api/inventory` | Batches, movements, stock counts, adjustments |
| Manufacturing | `/api/boms`, `/api/work-orders` | BOM and work order management |
| Accounting | `/api/accounting` | Chart of accounts, vouchers, journal entries, AR/AP, periods |
| Attendance | `/api/attendance` | Employee attendance, shifts, leave management |
| Store | `/api/store` | Public checkout and order lookup |
| Settings | `/api/settings` | Site settings and payment methods |
| Users | `/api/users` | User management (admin only) |

## Authentication

The API uses **Laravel Sanctum** for token-based authentication.

```bash
# Register
POST /api/auth/register
{ "name": "User", "email": "user@example.com", "password": "password" }

# Login (returns token)
POST /api/auth/login
{ "email": "user@example.com", "password": "password" }

# Use token in subsequent requests
Authorization: Bearer <token>
```

### Roles

- **admin** - Full access to all endpoints including user management
- **manager** / **staff** - Access to internal business operations
- **customer** - Access to public endpoints and own profile only

## Rate Limiting

| Limiter | Limit | Applied To |
|---------|-------|------------|
| `auth` | 10 req/min per IP | Login, register |
| `public-form` | 5 req/min per IP | Checkout, contact, visitors |

## Testing

```bash
# Run all tests
php artisan test

# Run with verbose output
php artisan test --verbose

# Run specific test file
php artisan test --filter=AuthTest
```

Tests use SQLite in-memory database and are located in `tests/Feature/`.
