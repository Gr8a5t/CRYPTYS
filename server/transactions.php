<?php
// server/transactions.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit();
}

$userId = $_SESSION['user_id'];
$transactions = [];

try {
    // 1. Fetch Deposits
    $depStmt = $pdo->prepare("SELECT id, coin, amount, status, created_at FROM deposits WHERE user_id = ?");
    $depStmt->execute([$userId]);
    while ($row = $depStmt->fetch()) {
        $transactions[] = [
            'id' => 'dep_' . $row['id'],
            'type' => 'Deposit',
            'coin' => $row['coin'],
            'amount' => (float)$row['amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    // 2. Fetch Withdrawals
    $witStmt = $pdo->prepare("SELECT id, coin, amount, status, created_at FROM withdrawals WHERE user_id = ?");
    $witStmt->execute([$userId]);
    while ($row = $witStmt->fetch()) {
        $transactions[] = [
            'id' => 'wit_' . $row['id'],
            'type' => 'Withdrawal',
            'coin' => $row['coin'],
            'amount' => (float)$row['amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    // 3. Fetch Investments
    $invStmt = $pdo->prepare("SELECT id, amount_usd, status, created_at FROM investments WHERE user_id = ?");
    $invStmt->execute([$userId]);
    while ($row = $invStmt->fetch()) {
        $transactions[] = [
            'id' => 'inv_' . $row['id'],
            'type' => 'Investment',
            'coin' => 'USDT', // Investments are always stored in USD equivalent
            'amount' => (float)$row['amount_usd'],
            'status' => $row['status'] === 'active' ? 'completed' : $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Sort transactions by created_at DESC (newest first)
    usort($transactions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode([
        "success" => true,
        "data" => $transactions
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Failed to fetch transactions: " . $e->getMessage()]);
}
?>
