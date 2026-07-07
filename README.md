# Authentication System — Register / Login / Profile

Built with PHP, jQuery/AJAX, MySQL, MongoDB, Redis, and Bootstrap.

## How it works

1. **Register** — user fills a form → jQuery sends it via AJAX (no page reload) →
   `php/register.php` validates it, hashes the password, saves username/email/password
   to **MySQL**, and saves name/age/bio/interests to **MongoDB**.
2. **Login** — `php/login.php` checks the MySQL record, verifies the password hash,
   and if correct, creates a session token stored in **Redis** and sends it to the
   browser as an httpOnly cookie.
3. **Profile** — `php/profile.php` checks the cookie against Redis. If valid, it
   merges the MySQL account data with the MongoDB profile data and returns it as JSON.
   If the session is missing/expired, the user is redirected back to login.
4. **Logout** — deletes the Redis session and clears the cookie.

## Prerequisites (install these first)

| Tool | Purpose | Windows install |
|---|---|---|
| XAMPP | PHP + Apache + MySQL | https://www.apachefriends.org |
| MongoDB Community Server | profile storage | https://www.mongodb.com/try/download/community |
| Redis | session storage | Easiest on Windows: install **Memurai** (Redis-compatible) from https://www.memurai.com, OR use WSL and run real Redis inside it |
| Composer | PHP dependency manager | https://getcomposer.org |
| MongoDB PHP extension | lets PHP talk to MongoDB | see step 3 below |

## Setup steps

### 1. Copy the project into XAMPP's htdocs folder
Put this whole folder inside `C:\xampp\htdocs\nura-auth-project` (or your XAMPP's htdocs path).

### 2. Create the MySQL database
- Start Apache + MySQL in the XAMPP control panel.
- Open phpMyAdmin (`http://localhost/phpmyadmin`).
- Import `schema.sql` (or run it via the SQL tab). This creates the `auth_system`
  database and the `users` table.

### 3. Enable the MongoDB PHP extension
- Find your PHP version: run `php -v`.
- Download the matching `php_mongodb.dll` from https://pecl.php.net/package/mongodb
  (match the PHP version, thread-safe "TS" build for XAMPP, and x64).
- Copy the `.dll` into `C:\xampp\php\ext`.
- Open `C:\xampp\php\php.ini` and add this line:
  ```
  extension=mongodb
  ```
- Restart Apache.

### 4. Install PHP dependencies
Open a terminal inside the project folder and run:
```bash
composer install
```
This downloads the `mongodb/mongodb` and `predis/predis` libraries into a `vendor/` folder.

### 5. Start MongoDB and Redis
- Start `mongod` (MongoDB server) — XAMPP does not include this, run it separately.
- Start Redis/Memurai — it listens on `127.0.0.1:6379` by default.

### 6. Configure environment variables
Copy `.env.example` to `.env` and adjust if your MySQL password/host differ:
```bash
cp .env.example .env
```

### 7. Run it
Visit `http://localhost/nura-auth-project/index.html` in your browser.
- Click **Register**, create an account.
- Click **Login** with that account.
- You'll land on **Profile**, which shows your data pulled from both MySQL and MongoDB.

## Security notes (also covered in the code)
- Passwords are hashed with `password_hash()` (bcrypt) — never stored as plain text.
- All SQL queries use PDO prepared statements — protects against SQL injection.
- Session tokens are random 64-character hex strings stored server-side in Redis —
  the browser only ever sees an opaque token, not the user ID.
- The session cookie is `httponly` (can't be read by JavaScript) and has a 1-hour
  expiry with sliding renewal on activity.
- `.env` (real secrets) is excluded from Git via `.gitignore` — only `.env.example`
  (with no real passwords) is committed.

## Folder structure
```
nura-auth-project/
├── assets/
├── css/
│   └── style.css
├── js/
│   ├── register.js
│   ├── login.js
│   └── profile.js
├── php/
│   ├── config.php      (shared DB/session helpers)
│   ├── register.php
│   ├── login.php
│   ├── profile.php
│   └── logout.php
├── index.html
├── register.html
├── login.html
├── profile.html
├── schema.sql
├── composer.json
├── .env.example
└── .gitignore
```

## Deploying it (for the "Deployed URL" submission requirement)
This stack (PHP + MySQL + MongoDB + Redis all at once) needs a real server, not
typical free static hosting. The simplest path:
1. Spin up a small VPS (DigitalOcean Droplet / AWS Lightsail, ~$5/month, some offer
   free student credits).
2. Install PHP, MySQL, MongoDB, and Redis on it (or use Docker Compose to run all
   four in containers).
3. Point a subdomain or the server's IP at the project folder.
4. Import `schema.sql`, set your `.env`, run `composer install`, and it's live.
