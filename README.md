# E-API

A RESTful API for an e-commerce platform built with Laravel, providing product management and order processing capabilities.

## Features

- User authentication with role-based access (Admin/Customer)
- Product catalog management
- Order processing system
- Admin dashboard for product and order management
- Customer interface for browsing and ordering
- Swagger/OpenAPI documentation

## Requirements

- PHP 8.1+
- Composer 2.0+
- MySQL 8.0+
- Laravel 11.x

## Quick Start

1. Clone and setup:
   ```bash
   git clone <repository-url>
   cd E-API_A
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. Configure database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. Initialize database:
   ```bash
   php artisan migrate --seed
   ```

4. Start server:
   ```bash
   php artisan serve
   ```

## API Documentation

Access the API documentation at `/api/swagger.json` after starting the server. Import this file into Swagger UI or Postman for interactive documentation.

### Key Endpoints

#### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user

#### Products
- `GET /api/products` - Public product listing
- `GET /api/customer/products/{id}` - Get specific product

#### Admin Products
- `GET /api/admin/products` - List all products
- `POST /api/admin/products` - Create product
- `PUT /api/admin/products/{id}` - Update product
- `DELETE /api/admin/products/{id}` - Delete product

#### Orders
- `GET /api/customer/orders` - List customer orders
- `POST /api/customer/orders` - Create order
- `GET /api/customer/orders/{id}` - Get order details

#### Admin Orders
- `GET /api/admin/orders` - List all orders
- `PUT /api/admin/orders/{id}/status` - Update order status

## Testing

Run the test scripts to verify API functionality:

```powershell
# Test customer endpoints
.\test_api.ps1

# Test admin endpoints
.\test_api_admin.ps1
```

## Authentication

All endpoints except `/register`, `/login`, and `/products` require a Bearer token:

```
Authorization: Bearer <your_token>
```

Tokens expire after 24 hours.

## Error Handling

The API uses standard HTTP status codes and returns JSON responses:

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        "field": ["Error message"]
    }
}
```

## Rate Limiting

- 60 requests/minute (authenticated)
- 30 requests/minute (unauthenticated)

## Troubleshooting

Common issues:
1. Server not starting: Check port 8000 and PHP version
2. Database issues: Verify credentials and MySQL service
3. Authentication: Check token validity and headers
4. Testing: Ensure server is running and migrations are complete

## Support

- [GitHub Issues](https://github.com/your-repo/issues)
- [Laravel Documentation](https://laravel.com/docs)


