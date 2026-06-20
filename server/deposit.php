<?php
// server/deposit.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit();
}

// Parse JSON payload from the frontend
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coin']) || !isset($input['amount'])) {
    echo json_encode(["success" => false, "message" => "Invalid deposit request."]);
    exit();
}

$userId = $_SESSION['user_id'];
$coin = strtoupper(trim($input['coin']));
$amount = (float)$input['amount'];

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Amount must be greater than 0."]);
    exit();
}

try {
    $pdo->beginTransaction();
    
    if ($coin === 'BTC') {
        $stmt = $pdo->prepare("UPDATE users SET balance_btc = balance_btc + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
    } else if ($coin === 'ETH') {
        $stmt = $pdo->prepare("UPDATE users SET balance_eth = balance_eth + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
    } else if ($coin === 'USDT') {
        $stmt = $pdo->prepare("UPDATE users SET balance_usd = balance_usd + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Unsupported coin."]);
        exit();
    }
    
    // Log the deposit
    $logStmt = $pdo->prepare("INSERT INTO deposits (user_id, coin, amount, status) VALUES (?, ?, ?, 'completed')");
    $logStmt->execute([$userId, $coin, $amount]);
    
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Deposit of {$amount} {$coin} successful!"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Deposit failed: " . $e->getMessage()]);
}
?>
