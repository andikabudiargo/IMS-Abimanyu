# IMS
## IMS Integrated Manufature System using vuexy template
##### install:

```
git clone
npm install
composer install --optimize-autoloader --no-dev
composer dump-autoload
copy .env.staging .env

#ganti nama database di .env dengan yang sesuai

contoh:
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=ims_local
DB_USERNAME=postgres
DB_PASSWORD=admin
DB_PORT=5432

php artisan key:generate

php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

#jalankan laravel di local

php artisan serve

```

##### sudah dilengkapi dengan:
- Auth menggunakan spatie
- Activity log
- Jwt

PHP :

PHP 7.4.19

Laravel :

Laravel Framework 7.30.4

Database :

Postgresql 13