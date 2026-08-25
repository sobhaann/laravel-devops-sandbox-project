# Epoch Time Converter

A simple Laravel web application for converting dates and times to Unix/Epoch timestamps, with a history of conversions stored in MySQL.

## Features

- Display current Unix/Epoch timestamp
- Convert any date/time to Unix/Epoch timestamp
- Store conversion history in MySQL
- View all previous conversions (newest first)
- No authentication required - global history for all users

## Requirements

- Docker
- Docker Compose

## Quick Start

### Build and start

```bash
docker compose up -d --build
```

### Run migrations

```bash
docker compose exec app php artisan migrate
```

### Check containers

```bash
docker compose ps
```

### View Laravel logs

```bash
docker compose logs -f app
```

### View MySQL logs

```bash
docker compose logs -f mysql
```

### Stop containers

```bash
docker compose down
```

### Remove all data (including MySQL volume)

```bash
docker compose down -v
```

## Access the Application

After starting the containers and running migrations, the application will be available at:

```
http://localhost:8000/
```

## Project Structure

```
app/
├── Http/
│   └── Controllers/
│       └── ConversionController.php
│
└── Models/
    └── Conversion.php

database/
└── migrations/
    └── *_create_conversions_table.php

resources/
└── views/
    └── conversions/
        └── index.blade.php

routes/
└── web.php
```

## Database

The application uses a single `conversions` table:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| input_datetime | datetime | The original date/time entered by the user |
| epoch_timestamp | bigint | The converted Unix/Epoch timestamp |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

## Docker Architecture

```
Browser
   │
   │ HTTP :8000
   ▼
Laravel container (app)
   │
   │ MySQL :3306
   ▼
MySQL container (mysql)
```

- **app**: Laravel application running on PHP 8.3 with built-in development server on port 8000
- **mysql**: MySQL 8.0 database with persistent volume

## Environment Variables

The following environment variables are configured via Docker Compose:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=epoch_converter
DB_USERNAME=epoch_user
DB_PASSWORD=epoch_password
```

**Note**: The MySQL data persists in a named Docker volume (`mysql_data`). Running `docker compose down` will NOT remove the volume. To remove the volume and all data, use `docker compose down -v`.

## Development

### Running tests

```bash
docker compose exec app php artisan test
```

### Code style

```bash
docker compose exec app ./vendor/bin/pint
```

## License

MIT License