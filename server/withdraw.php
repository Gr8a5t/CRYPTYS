<?php
// server/withdraw.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coin']) || !isset($input['amount']) || !isset($input['address'])) {
    echo json_encode(["success" => false, "message" => "Invalid withdrawal request."]);
    exit();
}

$userId = $_SESSION['user_id'];
$coin = strtoupper(trim($input['coin']));
$amount = (float)$input['amount'];
$address = trim($input['address']);

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Amount must be greater than 0."]);
    exit();
}

if (strlen($address) < 10) {
    echo json_encode(["success" => false, "message" => "Invalid destination address."]);
    exit();
}

$allowedCoins = ['BTC', 'ETH', 'USDT'];
if (!in_array($coin, $allowedCoins)) {
    echo json_encode(["success" => false, "message" => "Unsupported coin."]);
    exit();
}

try {
    // Check user balance
    $stmt = $pdo->prepare("SELECT balance_usd, balance_btc, balance_eth FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit();
    }
    
    // Balance check mapping
    $balanceMap = [
        'BTC' => 'balance_btc',
        'ETH' => 'balance_eth',
        'USDT' => 'balance_usd'
    ];
    
    $column = $balanceMap[$coin];
    $currentBalance = (float)$user[$column];
    
    if ($currentBalance < $amount) {
        echo json_encode(["success" => false, "message" => "Insufficient {$coin} balance. You only have " . number_format($currentBalance, 6) . " {$coin}."]);
        exit();
    }
    
    // Begin Transaction
    $pdo->beginTransaction();
    
    // Deduct Balance
    $deductStmt = $pdo->prepare("UPDATE users SET {$column} = {$column} - ? WHERE id = ?");
    $deductStmt->execute([$amount, $userId]);
    
    // Log Withdrawal
    $insertStmt = $pdo->prepare("INSERT INTO withdrawals (user_id, coin, amount, address, status) VALUES (?, ?, ?, ?, 'pending')");
    $insertStmt->execute([$userId, $coin, $amount, $address]);
    
    // Commit
    $pdo->commit();
    
    echo json_encode([
        "success" => true,
        "message" => "Withdrawal of {$amount} {$coin} submitted successfully. Status: Pending."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Withdrawal failed: " . $e->getMessage()]);
}
?>
