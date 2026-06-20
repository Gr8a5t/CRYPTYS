<?php
// server/api.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Not authenticated",
        "data" => [
            "balance_usd" => 0.00,
            "balance_btc" => 0.00,
            "balance_eth" => 0.00
        ]
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT balance_usd, balance_btc, balance_eth FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode([
        "success" => true,
        "data" => [
            "balance_usd" => (float)$user['balance_usd'],
            "balance_btc" => (float)$user['balance_btc'],
            "balance_eth" => (float)$user['balance_eth']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "User not found",
        "data" => [
            "balance_usd" => 0.00,
            "balance_btc" => 0.00,
            "balance_eth" => 0.00
        ]
    ]);
}
?>
