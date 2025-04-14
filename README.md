# E-Commerce API

A Laravel-based REST API for an e-commerce platform with:
- User authentication (admin/customer roles)
- Product management
- Order processing
- Comprehensive API documentation

## Requirements

- PHP 8.2+
- Composer 2.0+
- MySQL 8.0+ or MariaDB 10.3+
- Laravel 10.x

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/e-commerce-api.git
   cd e-commerce-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Environment setup:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Run migrations:
   ```bash
   php artisan migrate --seed
   ```

6. Start the development server:
   ```bash
   php artisan serve
   ```

## API Documentation

Interactive API documentation is available at `/docs` when the application is running.

## API Endpoints

### Authentication
| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/api/register` | Register new user | Public |
| POST | `/api/login` | User login | Public |
| POST | `/api/logout` | User logout | Authenticated |

### Products
| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/api/products` | List products | Public |
| GET | `/api/products/{id}` | Get product details | Public |
| POST | `/api/admin/products` | Create product | Admin |
| PUT | `/api/admin/products/{id}` | Update product | Admin |
| DELETE | `/api/admin/products/{id}` | Delete product | Admin |

### Orders
| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/api/customer/orders` | Create order | Customer |
| GET | `/api/customer/orders` | List customer orders | Customer |
| GET | `/api/customer/orders/{id}` | Get order details | Customer |
| PUT | `/api/admin/orders/{id}/status` | Update order status | Admin |

## Authentication & Authorization

The API uses Laravel Sanctum for authentication with role-based access control:

1. Include the JWT token in request headers:
```http
Authorization: Bearer <your-token>
```

2. Available roles:
- Admin: Full access to all endpoints
- Customer: Can manage own orders and view products

## Testing

Run the comprehensive test suite:
```bash
php artisan test


Test coverage includes:
- Product model and business logic
- Order processing and status transitions
- Authentication and role validation
- API endpoint responses

## Error Handling

The API returns standard HTTP status codes and JSON responses:
```json
{
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

## Security Features

- JWT authentication with Sanctum
- Role-based access control
- Input validation on all endpoints
- CSRF protection
- Rate limiting (60 requests/minute)
- Password hashing
- Secure HTTP headers


## License

This project is licensed under the MIT License. See `LICENSE` for more information.
