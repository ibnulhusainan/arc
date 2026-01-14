# ARC – Automatic Resource Creator for Laravel

ARC is a Laravel package that helps you **rapidly generate modules and CRUD scaffolding** using simple Artisan commands.  
Designed to speed up development and keep project structure consistent.

---

## Features

- Generate complete module structure
- CRUD-ready controllers, models, migrations, routes, and views
- Opinionated and consistent folder structure
- Artisan-based workflow
- Compatible with Laravel 9, 10, 11, and 12

---

## Requirements

- PHP ^8.1
- Laravel ^9.0 | ^10.0 | ^11.0 | ^12.0

---

## Installation

Install the package via Composer:

```
composer require ibnulhusainan/arc
```

Laravel will automatically discover the service provider.

---

## Publishing Resources

Publish ARC configuration and resources:

```
php artisan vendor:publish --provider="IbnulHusainan\Arc\Providers\ArcServiceProvider"
```

---

### Usage

1. **Generate new module**

   ```bash
   php artisan make:module member
   ```

2. **Edit and run migrations**

   ```bash
   php artisan migrate
   ```

3. **Build frontend assets**

   ```bash
   npm install && npm run build
   ```

4. **Start dev server**

   ```bash
   php artisan serve
   ```

5. **Visit in browser**

   ```
   http://localhost:8000/member
   ```

---