docker compose up -d
docker compose up -d worker
docker compose logs -f worker

# Run with FrankenPHP (optional profile)
docker compose --profile frankenphp up -d --build db redis rabbitmq frankenphp
docker compose logs -f frankenphp
# App URL: http://localhost:8080

php bin/console tailwind:build
php bin/console asset-map:compile
php bin/console importmap:install
php bin/console assets:install --symlink --relative
php bin/console doctrine:migrations:migrate

# Performance benchmarking
# See app/docs/k6-benchmark-quickstart.md
# Script: app/docs/k6-performance-baseline.js
# Report template: app/docs/performance-before-after-template.md
