<?php
/**
 * register.php
 * Receives JSON from register.js via AJAX (POST), validates it,
 * stores account info in MySQL and extra profile info in MongoDB.
 */

require_once 'config.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Read the JSON body sent by jQuery AJAX
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid or missing request data.'], 400);
}

$username = trim($input['username'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$name     = trim($input['name'] ?? '');
$age      = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
$bio      = trim($input['bio'] ?? '');
$interestsRaw = $input['interests'] ?? '';

// interests can come in as a comma separated string from the form
$interests = [];
if (is_array($interestsRaw)) {
    $interests = $interestsRaw;
} elseif (is_string($interestsRaw) && $interestsRaw !== '') {
    $interests = array_map('trim', explode(',', $interestsRaw));
}

// ---------- Validation ----------
$errors = [];

if ($username === '' || strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers, and underscores.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}
if ($age !== null && ($age < 0 || $age > 120)) {
    $errors[] = 'Please provide a valid age.';
}

if (!empty($errors)) {
    jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
}

try {
    $pdo = getMySQLConnection();

    // Check for existing username/email using a prepared statement (SQL injection safe)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Username or email is already registered.'], 409);
    }

    // Hash password - never store plain text passwords
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())'
    );
    $stmt->execute([
        'username' => $username,
        'email'    => $email,
        'password' => $hashedPassword,
    ]);

    $userId = (int)$pdo->lastInsertId();

    // Store the extra profile fields in MongoDB
    $profiles = getMongoCollection();
    $profiles->insertOne([
        'user_id'    => $userId,
        'name'       => $name,
        'age'        => $age,
        'bio'        => $bio,
        'interests'  => $interests,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);

    jsonResponse(['success' => true, 'message' => 'Registration successful! You can now log in.']);

} catch (PDOException $e) {
    error_log('Register DB error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred. Please try again.'], 500);
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
