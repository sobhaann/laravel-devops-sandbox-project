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
docker compose logs -f mysql1   # or mysql2 / mysql3 / router
```

### Check cluster status

```bash
docker compose exec mysql1 mysql -uroot -p"$(grep '^MYSQL_ROOT_PASSWORD' .env | cut -d= -f2)" \
  -e "SELECT MEMBER_HOST, MEMBER_ROLE, MEMBER_STATE FROM performance_schema.replication_group_members;"
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
   │ MySQL :6446 (read/write) / :6447 (read-only)
   ▼
MySQL Router container (router)
   │
   ├──> mysql1  (PRIMARY)
   ├──> mysql2  (SECONDARY)
   └──> mysql3  (SECONDARY)
```

- **mysql1..3**: MySQL Server 8.0 nodes forming a single-primary **InnoDB Cluster** via Group Replication. Config: `database_cluster/innodb-cluster.cnf`.
- **init-cluster**: one-shot job (built from `database_cluster/mysqlsh.Dockerfile`) that runs `database_cluster/init-cluster.py` with the MySQL Shell AdminAPI (Python mode): `dba.configure_instance()` on each node, `dba.create_cluster()` on mysql1, then `cluster.add_instance()` (clone recovery) for the secondaries. Safe to re-run.
- **router**: MySQL Router, auto-bootstrapped against the cluster metadata; exposes RW `:6446` and RO `:6447`.
- **app**: Laravel application (PHP 8.4-fpm) connecting to the router.

Startup order is enforced by compose conditions: healthy servers -> init-cluster completes -> router bootstraps -> app starts (after waiting for router to accept connections).

### Resetting the cluster

The cluster topology lives in the `mysql*_data` volumes. To re-provision from scratch:

```bash
docker compose down -v
docker compose up --build -d
```

**Note**: MySQL 8.0 is approaching end of life; plan an upgrade to MySQL 8.4 LTS (`mysql:8.4`, `mysql/mysql-router:8.4` and matching Shell) when convenient - the configs here are already compatible with it.

## Environment Variables

The following environment variables are configured via Docker Compose:

```env
DB_CONNECTION=mysql
DB_HOST=router
DB_PORT=6446
DB_DATABASE=epoch_converter
DB_USERNAME=epoch_user
DB_PASSWORD=epoch_password
MYSQL_ROOT_PASSWORD=root_password
```

**Note**: The MySQL data persists in named Docker volumes (`mysql1_data`, `mysql2_data`, `mysql3_data`). Running `docker compose down` will NOT remove them. To remove the volumes and all data, use `docker compose down -v`.

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