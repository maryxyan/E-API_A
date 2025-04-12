# E-Commerce API

A robust E-Commerce API built with Laravel 11, featuring comprehensive product management, order processing, and user authentication systems.

## Features

- 🔐 JWT Authentication
- 📦 Product Management
- 🛒 Order Processing
- 👥 User Management
- 📊 Admin Dashboard
- 📝 API Documentation (Swagger/OpenAPI)
- 🔍 Advanced Search & Filtering
- 🛡️ Role-based Access Control

## Prerequisites

- PHP >= 8.1
- Composer
- Node.js & npm
- MySQL/PostgreSQL
- Git

## Installation

1. Clone the repository:

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Create environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure your database in `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

7. Run migrations and seeders:
```bash
php artisan migrate --seed
```

8. Start the development server:
```bash
php artisan serve
```

9. In a separate terminal, start the Vite development server:
```bash
npm run dev
```

## API Documentation

The API documentation is available at `/api/documentation` when running the application. It's built using Swagger/OpenAPI and provides detailed information about all available endpoints, request/response formats, and authentication requirements.

## Testing

Run the test suite:
```bash
php artisan test
```


## Mai Technilogies

- Laravel Framework
- Swagger/OpenAPI


