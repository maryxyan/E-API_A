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

This document provides comprehensive documentation for the E-Commerce API endpoints.

## Base URL

```
http://localhost:8000/api
```

## Authentication

The API uses Bearer token authentication. After logging in or registering, include the token in the Authorization header:

```
Authorization: Bearer <your_token>
```

## API Endpoints

### Authentication

#### Register a New User

```http
POST /register
```

**Request Body:**
```json
{
    "name": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string",
    "role": "customer|admin"
}
```

**Response (201):**
```json
{
    "access_token": "string",
    "token_type": "Bearer"
}
```

#### Login

```http
POST /login
```

**Request Body:**
```json
{
    "email": "string",
    "password": "string"
}
```

**Response (200):**
```json
{
    "access_token": "string",
    "token_type": "Bearer"
}
```

#### Logout

```http
POST /logout
```

**Response (200):**
```json
{
    "message": "Successfully logged out"
}
```

### Products

#### List All Products (Public)

```http
GET /products
```

**Query Parameters:**
- `search` (optional): Search term for product name or description
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter
- `in_stock` (optional): Filter by stock availability (true/false)

**Response (200):**
```json
[
    {
        "id": "integer",
        "name": "string",
        "description": "string",
        "price": "number",
        "stock_quantity": "integer",
        "created_at": "datetime",
        "updated_at": "datetime"
    }
]
```

#### Get Single Product (Authenticated)

```http
GET /customer/products/{product_id}
```

**Response (200):**
```json
{
    "id": "integer",
    "name": "string",
    "description": "string",
    "price": "number",
    "stock_quantity": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Orders (Customer)

#### List Customer Orders

```http
GET /customer/orders
```

**Query Parameters:**
- `status` (optional): Filter by order status (pending/processing/completed/cancelled)
- `date_from` (optional): Start date filter
- `date_to` (optional): End date filter

**Response (200):**
```json
[
    {
        "id": "integer",
        "user_id": "integer",
        "total_price": "number",
        "status": "string",
        "created_at": "datetime",
        "updated_at": "datetime",
        "order_items": [
            {
                "id": "integer",
                "product_id": "integer",
                "quantity": "integer",
                "price": "number",
                "product": {
                    "id": "integer",
                    "name": "string",
                    "price": "number"
                }
            }
        ]
    }
]
```

#### Create Order

```http
POST /customer/orders
```

**Request Body:**
```json
{
    "items": [
        {
            "product_id": "integer",
            "quantity": "integer"
        }
    ]
}
```

**Response (201):**
```json
{
    "id": "integer",
    "user_id": "integer",
    "total_price": "number",
    "status": "pending",
    "created_at": "datetime",
    "updated_at": "datetime",
    "order_items": [
        {
            "id": "integer",
            "product_id": "integer",
            "quantity": "integer",
            "price": "number"
        }
    ]
}
```

#### Get Single Order

```http
GET /customer/orders/{order_id}
```

**Response (200):**
```json
{
    "id": "integer",
    "user_id": "integer",
    "total_price": "number",
    "status": "string",
    "created_at": "datetime",
    "updated_at": "datetime",
    "order_items": [
        {
            "id": "integer",
            "product_id": "integer",
            "quantity": "integer",
            "price": "number",
            "product": {
                "id": "integer",
                "name": "string",
                "price": "number"
            }
        }
    ]
}
```

#### Cancel Order

```http
POST /customer/orders/{order_id}/cancel
```

**Response (200):**
```json
{
    "id": "integer",
    "status": "cancelled",
    "order_items": [...]
}
```

### Admin Endpoints

#### List All Orders

```http
GET /admin/orders
```

**Response (200):**
```json
[
    {
        "id": "integer",
        "user_id": "integer",
        "total_price": "number",
        "status": "string",
        "created_at": "datetime",
        "updated_at": "datetime",
        "order_items": [...]
    }
]
```

#### Update Order Status

```http
PUT /admin/orders/{order_id}/status
```

**Request Body:**
```json
{
    "status": "processing|completed|cancelled"
}
```

**Response (200):**
```json
{
    "id": "integer",
    "status": "string",
    "order_items": [...]
}
```

#### Create Product

```http
POST /admin/products
```

**Request Body:**
```json
{
    "name": "string",
    "description": "string",
    "price": "number",
    "stock_quantity": "integer"
}
```

**Response (201):**
```json
{
    "id": "integer",
    "name": "string",
    "description": "string",
    "price": "number",
    "stock_quantity": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

#### Update Product

```http
PUT /admin/products/{product_id}
```

**Request Body:**
```json
{
    "name": "string",
    "description": "string",
    "price": "number",
    "stock_quantity": "integer"
}
```

**Response (200):**
```json
{
    "id": "integer",
    "name": "string",
    "description": "string",
    "price": "number",
    "stock_quantity": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

#### Delete Product

```http
DELETE /admin/products/{product_id}
```

**Response (204):** No content

## Error Responses

### Validation Error (422)
```json
{
    "message": "Validation failed",
    "errors": {
        "field_name": [
            "Error message"
        ]
    }
}
```

### Authentication Error (401)
```json
{
    "message": "Unauthenticated"
}
```

### Authorization Error (403)
```json
{
    "message": "Unauthorized"
}
```

### Not Found Error (404)
```json
{
    "message": "Resource not found"
}
```

### Server Error (500)
```json
{
    "message": "An error occurred",
    "error": "Error details"
}
```

## Rate Limiting

The API implements rate limiting to prevent abuse. The current limit is:
- 60 requests per minute per IP address
- 1000 requests per hour per authenticated user

## Testing

A PowerShell test script (`test_api.ps1`) is provided to test all API endpoints. Run it using:

```powershell
.\test_api.ps1
```

The script tests all endpoints with proper authentication and provides detailed output for each request.

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
