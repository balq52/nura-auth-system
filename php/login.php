<?php
/**
 * login.php
 * Verifies credentials against MySQL, and if correct, creates a Redis
 * session and sends a session cookie back to the browser.
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid or missing request data.'], 400);
}

$identifier = trim($input['identifier'] ?? ''); // username OR email
$password   = $input['password'] ?? '';

if ($identifier === '' || $password === '') {
    jsonResponse(['success' => false, 'message' => 'Username/email and password are required.'], 422);
}

try {
    $pdo = getMySQLConnection();
$stmt = $pdo->prepare('SELECT id, username, email, password FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$identifier, $identifier]);    $user = $stmt->fetch();

    // Deliberately vague error message - don't reveal whether username or password was wrong
    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }
$token = createSession($user['id']);
    jsonResponse([
        'success' => true,
        'message' => 'Login successful!',
        'token'   => $token,
        'user'    => [
            'id'       => $user['id'],
            'username' => $user['username'],
        ],
    ]);
    

} catch (PDOException $e) {
    error_log('Login DB error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred. Please try again.'], 500);
}
