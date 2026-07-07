<?php
/**
 * logout.php
 * Destroys the Redis session tied to the current cookie and clears the cookie.
 */

require_once 'config.php';

header('Content-Type: application/json');

destroySession();

jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
