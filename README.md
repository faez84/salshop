# Symfony Shop - Local Development Guide

This repository contains the Symfony shop application, Docker setup, and Kubernetes manifests used for local development and deployment.

## Project Structure

- `app/`: Symfony application code
- `container/`: Dockerfiles and container-level configuration
- `kubernetes/`: Kubernetes manifests
- `scripts/k8s/`: Utility scripts for cluster operations
- `app/docs/`: Performance and observability guides

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin)
- Git
- PowerShell (for helper scripts on Windows)

## Quick Start (Docker)

1. Start the default stack:

```bash
docker compose up -d --build
```

2. Install PHP dependencies:

```bash
docker compose exec app composer install
```

3. Run database migrations:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate
```

4. Build frontend assets:

```bash
docker compose exec app php bin/console importmap:install
docker compose exec app php bin/console tailwind:build
docker compose exec app php bin/console asset-map:compile
docker compose exec app php bin/console assets:install --symlink --relative
```

5. Open the app:

- Application: http://localhost:8000
- MySQL: `localhost:3345`
- Redis: `localhost:6379`
- RabbitMQ UI: http://localhost:15672 (guest/guest)

## Worker (Messenger Consumer)

Start the background worker when you need async message processing:

```bash
docker compose up -d worker
docker compose logs -f worker
```

## Optional FrankenPHP Runtime

Run the app with the `frankenphp` profile instead of Nginx + PHP-FPM:

```bash
docker compose --profile frankenphp up -d --build db redis rabbitmq frankenphp
docker compose logs -f frankenphp
```

App URL with this profile: http://localhost:8080

## Optional Ops/Observability Stack

Start monitoring components (Elasticsearch, Kibana, Prometheus, Grafana, exporters):

```bash
docker compose --profile ops up -d
```

Useful URLs:

- Kibana: http://localhost:5601
- Grafana: http://localhost:3001 (admin/admin)
- Prometheus: http://localhost:9091
- Elasticsearch: http://localhost:9200

## Makefile Shortcuts

```bash
make up
make up-with-recreate
make down
```

## Kubernetes Secrets (PowerShell Helper)

You can apply Kubernetes secrets from environment variables:

```powershell
$env:APP_SECRET = "..."
$env:DATABASE_URL = "..."
$env:DATABASE_REPLICA_URL = "..."
$env:PAYPAL_CLIENT_ID = "..."
$env:PAYPAL_CLIENT_SECRET = "..."
$env:MYSQL_ROOT_PASSWORD = "..."
$env:MYSQL_DATABASE = "..."
$env:MYSQL_USER = "..."
$env:MYSQL_PASSWORD = "..."
$env:MYSQL_REPLICATION_USER = "..."
$env:MYSQL_REPLICATION_PASSWORD = "..."
$env:MYSQL_EXPORTER_DATA_SOURCE_NAME = "..."

.\scripts\k8s\apply-secrets.ps1 -Namespace default
```

Reference manifest template: `kubernetes/app-secrets.yml`

## Performance Benchmarking

See:

- `app/docs/k6-benchmark-quickstart.md`
- `app/docs/k6-performance-baseline.js`
- `app/docs/performance-before-after-template.md`
