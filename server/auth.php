<?php
// server/auth.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin for local dev if needed
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request from browser fetch
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connect to the database (and auto-migrate the table if it doesn't exist)
require_once __DIR__ . '/db.php';

// Parse JSON payload from the frontend
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid request. Action is required."]);
    exit();
}

$action = $input['action'];

// ---------------------------------------------------------
// REGISTRATION LOGIC
// ---------------------------------------------------------
if ($action === 'register') {
    $fullName = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Please enter a valid email format."]);
        exit();
    }

    // 1. Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "An account with this email already exists."]);
        exit();
    }

    // 2. Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insert into database
    $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
    try {
        $insertStmt->execute([$fullName, $email, $hashedPassword]);
        $userId = $pdo->lastInsertId();
        
        // Log the user in immediately
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName;

        echo json_encode([
            "success" => true, 
            "message" => "Registration successful! Redirecting...",
            "user" => ["id" => $userId, "name" => $fullName]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Registration failed: " . $e->getMessage()]);
    }
    exit();
}

// ---------------------------------------------------------
// LOGIN LOGIC
// ---------------------------------------------------------
if ($action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Email and password are required."]);
        exit();
    }

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password against hash
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];

        echo json_encode([
            "success" => true, 
            "message" => "Login successful! Redirecting...",
            "user" => ["id" => $user['id'], "name" => $user['full_name']]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }
    exit();
}

// Fallback
echo json_encode(["success" => false, "message" => "Unknown action."]);
exit();
?>
