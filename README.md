docker compose up -d
php bin/console tailwind:build
php bin/console asset-map:compile
php bin/console doctrine:migrations:migrate