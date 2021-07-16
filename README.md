# laravel7-vuexy
## Skeleton laravel 7 menggunakan vuexy
##### install:

```
git clone
npm install
composer install --optimize-autoloader --no-dev
composer dump-autoload
copy .env.production .env

ganti nama database di .env dengan yang sesuai

contoh:
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=vuexy
DB_USERNAME=postgres
DB_PASSWORD=root123
DB_PORT=5432

php artisan key:generate
php artisan config:cache
php artisan config:clear
php artisan view:cache
php artisan migrate
php artisan db:seed
user: admin
pssword: admin

```

##### sudah dilengkapi dengan:
- Auth menggunakan spatie
- Activity log