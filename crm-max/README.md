# CRM-Max — back office

A Laravel 11 back office: leads, agents and managers, KYC, finances, calendar,
reports and support.

This is a complete, runnable application. 

---

## Requirements

| | |
|---|---|
| PHP | 8.2 or newer, with `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip` |
| Composer | 2.x |
| Node.js | 18 or newer, with Yarn |
| MySQL | 8.0 (MariaDB 10.6+ also works) |
| Redis | optional — see *Without Redis* below |

---

## Install

```bash
composer install
yarn
```

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set the database block:

```
DB_DATABASE=crmmax
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Create that database first — the application does not create it:

```bash
mysql -u root -p -e "CREATE DATABASE crmmax CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then build the schema and the front-end assets:

```bash
php artisan migrate
php artisan storage:link
yarn build
```

### Without Redis

`.env.example` assumes Redis for cache and queues. To run without it, set:

```
CACHE_STORE=database
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
```

`QUEUE_CONNECTION=sync` runs background work inline — lead imports and staff
alerts happen during the request instead of in a worker. Slower, but it means
you do not need a queue worker running to see the application behave correctly.

---

## First run

```bash
php artisan serve
```

Open <http://localhost:8000>.

You need an administrator account. There is no default login — create one:

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'first_name' => 'Admin',
    'last_name'  => 'User',
    'email'      => 'admin@example.com',
    'password'   => Hash::make('choose-a-strong-password'),
    'email_verified_at' => now(),
]);

$user->addRole('admin');   // Laratrust; roles are seeded by the migrations
```

Sign in, and the back office is at `/panel`.

> If `addRole('admin')` complains that the role does not exist, run
> `php artisan db:seed --class=LaratrustSeeder` first — that creates the roles
> and permissions.

---

## Day-to-day

```bash
php artisan serve        # the application
yarn dev                 # front-end assets, rebuilt as you edit
php artisan queue:work   # background jobs — only if QUEUE_CONNECTION is not `sync`
```

The interface is server-rendered Blade via [Splade](https://splade.dev). Pages
are swapped in as fragments rather than fully reloaded, which is why the
JavaScript in `resources/js/leadmax*.js` binds its handlers to `document`
rather than to elements: an element bound at page load will not exist after the
next fragment swap. Keep to that pattern when adding behaviour.

---

## Deploying

`public/build` is not committed, so assets are built on the server:

```bash
composer install --no-dev --optimize-autoloader
yarn && yarn build
php artisan migrate --force
php artisan optimize
```

Point the web server's document root at `public/`, and make `storage/` and
`bootstrap/cache/` writable by the web server user.

---
