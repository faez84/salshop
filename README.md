docker compose up -d
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console doctrine:migrations:migrate
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
php bin/console lexik:jwt:generate-keypair

