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
```bash
git clone https://github.com/yourusername/e-commerce-api.git
cd e-commerce-api
```

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

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to your-email@example.com. All security vulnerabilities will be promptly addressed.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email your-email@example.com or open an issue in the GitHub repository.

## Acknowledgments

- Laravel Framework
- Swagger/OpenAPI
- All contributors who have helped shape this project


