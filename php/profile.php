<?php
/**
 * profile.php
 * GET  -> returns the logged-in user's account + profile data (MySQL + MongoDB merged)
 * POST -> updates the profile fields (name, age, bio, interests) in MongoDB
 *
 * Access is gated by a valid Redis session (see config.php -> getSessionUserId()).
 */

require_once 'config.php';

header('Content-Type: application/json');

$userId = getSessionUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'message' => 'Not authenticated. Please log in.'], 401);
}

try {
    $pdo = getMySQLConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ---- Fetch account info from MySQL ----
        $stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $account = $stmt->fetch();

        if (!$account) {
            jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        }

        // ---- Fetch profile info from MongoDB ----
        $profiles = getMongoCollection();
        $profile = $profiles->findOne(['user_id' => $userId]);

        jsonResponse([
            'success' => true,
            'data' => [
                'id'         => $account['id'],
                'username'   => $account['username'],
                'email'      => $account['email'],
                'created_at' => $account['created_at'],
                'name'       => $profile['name'] ?? '',
                'age'        => $profile['age'] ?? null,
                'bio'        => $profile['bio'] ?? '',
                'interests'  => $profile['interests'] ?? [],
            ],
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['success' => false, 'message' => 'Invalid request data.'], 400);
        }

        $name = trim($input['name'] ?? '');
        $age  = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
        $bio  = trim($input['bio'] ?? '');
        $interestsRaw = $input['interests'] ?? '';

        $interests = [];
        if (is_array($interestsRaw)) {
            $interests = $interestsRaw;
        } elseif (is_string($interestsRaw) && $interestsRaw !== '') {
            $interests = array_map('trim', explode(',', $interestsRaw));
        }

        if ($age !== null && ($age < 0 || $age > 120)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid age.'], 422);
        }

        $profiles = getMongoCollection();
        $profiles->updateOne(
            ['user_id' => $userId],
            ['$set' => [
                'name'       => $name,
                'age'        => $age,
                'bio'        => $bio,
                'interests'  => $interests,
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
            ]],
            ['upsert' => true]
        );

        jsonResponse(['success' => true, 'message' => 'Profile updated successfully.']);
    }

} catch (PDOException $e) {
    error_log('Profile DB error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'A database error occurred.'], 500);
} catch (Exception $e) {
    error_log('Profile error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Something went wrong.'], 500);
}
