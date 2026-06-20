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
$stmt = $pdo->prepare("SELECT full_name, email, balance_usd, balance_btc, balance_eth FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch active investments sum
$invStmt = $pdo->prepare("SELECT SUM(amount_usd) as total_invested, COUNT(id) as active_plans FROM investments WHERE user_id = ? AND status = 'active'");
$invStmt->execute([$userId]);
$invData = $invStmt->fetch();

if ($user) {
    echo json_encode([
        "success" => true,
        "data" => [
            "full_name" => $user['full_name'],
            "email" => $user['email'],
            "balance_usd" => (float)$user['balance_usd'],
            "balance_btc" => (float)$user['balance_btc'],
            "balance_eth" => (float)$user['balance_eth'],
            "total_invested" => (float)($invData['total_invested'] ?? 0),
            "active_plans" => (int)($invData['active_plans'] ?? 0)
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
