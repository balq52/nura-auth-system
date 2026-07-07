<?php
/**
 * config.php
 * Central place for:
 *  - Loading environment variables from .env
 *  - MySQL connection (PDO)
 *  - MongoDB connection
 *  - Redis connection (for sessions)
 *  - Session helper functions
 *
 * Every other PHP file (register.php, login.php, profile.php, logout.php)
 * includes this file first.
 */

// ---------- 1. Load .env file ----------
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}
loadEnv(__DIR__ . '/../.env');

// ---------- 2. Composer autoload (needed for MongoDB + Redis libraries) ----------
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// ---------- 3. MySQL connection ----------
function getMySQLConnection()
{
    $host = getenv('MYSQL_HOST') ?: 'localhost';
    $db   = getenv('MYSQL_DB') ?: 'auth_system';
    $user = getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQL_PASS') ?: '';

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements (SQL injection safety)
    ];

    return new PDO($dsn, $user, $pass, $options);
}

// ---------- 4. MongoDB connection ----------
function getMongoCollection()
{
    $uri = getenv('MONGO_URI') ?: 'mongodb://localhost:27017';
    $client = new MongoDB\Client($uri);
    $dbName = getenv('MONGO_DB') ?: 'auth_system';
    return $client->selectDatabase($dbName)->selectCollection('profiles');
}

// ---------- 5. Redis connection (via Predis, pure PHP - no extension needed) ----------
function getRedisClient()
{
    $host = getenv('REDIS_HOST') ?: '127.0.0.1';
    $port = getenv('REDIS_PORT') ?: 6379;
    return new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $host,
        'port'   => $port,
    ]);
}

// ---------- 6. Session helpers (Redis-backed) ----------
const SESSION_COOKIE_NAME = 'session_token';
const SESSION_TTL_SECONDS = 3600; // 1 hour

/**
 * Create a new session for a logged-in user.
 * Stores token -> user_id in Redis, and sends the token to the browser as a cookie.
 */
function createSession($userId)
{
    $redis = getRedisClient();
    $token = bin2hex(random_bytes(32));

    $redis->setex("session:$token", SESSION_TTL_SECONDS, $userId);

    setcookie(SESSION_COOKIE_NAME, $token, [
        'expires'  => time() + SESSION_TTL_SECONDS,
        'path'     => '/',
        'httponly' => true,   // JS cannot read the cookie -> protects against XSS token theft
        'samesite' => 'Lax',
    ]);

    return $token;
}

/**
 * Look at the incoming request's cookie, check Redis, and return the
 * logged-in user's ID, or null if there is no valid session.
 */
function getSessionUserId()
{
    if (empty($_COOKIE[SESSION_COOKIE_NAME])) {
        return null;
    }
    $token = $_COOKIE[SESSION_COOKIE_NAME];
    $redis = getRedisClient();
    $userId = $redis->get("session:$token");

    if ($userId === null) {
        return null;
    }

    // Sliding expiry: refresh TTL on activity so active users stay logged in
    $redis->expire("session:$token", SESSION_TTL_SECONDS);

    return (int)$userId;
}

/**
 * Destroy the current session (used by logout).
 */
function destroySession()
{
    if (!empty($_COOKIE[SESSION_COOKIE_NAME])) {
        $token = $_COOKIE[SESSION_COOKIE_NAME];
        $redis = getRedisClient();
        $redis->del(["session:$token"]);
    }
    setcookie(SESSION_COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Send a JSON response and stop execution.
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
