# IMS
## IMS Integrated Manufature System using vuexy template
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
DB_DATABASE=ims
DB_USERNAME=postgres
DB_PASSWORD=root123
DB_PORT=5432

php artisan key:generate

php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

php artisan migrate
php artisan db:seed
user: admin
pssword: admin

```

##### sudah dilengkapi dengan:
- Auth menggunakan spatie
- Activity log
- Jwt

clear chace untuk browser
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return "Cache is cleared";
});