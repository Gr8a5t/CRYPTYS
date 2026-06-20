<?php
// server/invest.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['plan_name']) || !isset($input['amount']) || !isset($input['payment_method'])) {
    echo json_encode(["success" => false, "message" => "Invalid investment request."]);
    exit();
}

$userId = $_SESSION['user_id'];
$planName = trim($input['plan_name']);
$amount = (float)$input['amount'];
$paymentMethod = strtoupper(trim($input['payment_method']));
$rates = isset($input['rates']) ? $input['rates'] : ['BTC' => 67000, 'ETH' => 3500];

// Hardcoded Plan Validation logic to prevent frontend tampering
$plans = [
    "Starter Plan" => ["min" => 50, "max" => 499, "roi" => 1.5],
    "Growth Plan" => ["min" => 500, "max" => 1999, "roi" => 2.5],
    "Premium Plan" => ["min" => 2000, "max" => 100000, "roi" => 4.0]
];

if (!array_key_exists($planName, $plans)) {
    echo json_encode(["success" => false, "message" => "Invalid plan selected."]);
    exit();
}

$plan = $plans[$planName];

if ($amount < $plan['min'] || $amount > $plan['max']) {
    echo json_encode(["success" => false, "message" => "Investment amount out of bounds for " . $planName]);
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
    
    // Begin Transaction
    $pdo->beginTransaction();
    
    if ($paymentMethod === 'USDT') {
        if ($user['balance_usd'] < $amount) {
            echo json_encode(["success" => false, "message" => "Insufficient USDT balance."]);
            $pdo->rollBack();
            exit();
        }
        $deductStmt = $pdo->prepare("UPDATE users SET balance_usd = balance_usd - ? WHERE id = ?");
        $deductStmt->execute([$amount, $userId]);
    } else if ($paymentMethod === 'BTC') {
        $price = (float)$rates['BTC'];
        if ($price <= 0) $price = 67000;
        $cryptoNeeded = $amount / $price;
        
        if ($user['balance_btc'] < $cryptoNeeded) {
            echo json_encode(["success" => false, "message" => "Insufficient BTC balance. You need " . number_format($cryptoNeeded, 6) . " BTC."]);
            $pdo->rollBack();
            exit();
        }
        $deductStmt = $pdo->prepare("UPDATE users SET balance_btc = balance_btc - ? WHERE id = ?");
        $deductStmt->execute([$cryptoNeeded, $userId]);
    } else if ($paymentMethod === 'ETH') {
        $price = (float)$rates['ETH'];
        if ($price <= 0) $price = 3500;
        $cryptoNeeded = $amount / $price;
        
        if ($user['balance_eth'] < $cryptoNeeded) {
            echo json_encode(["success" => false, "message" => "Insufficient ETH balance. You need " . number_format($cryptoNeeded, 6) . " ETH."]);
            $pdo->rollBack();
            exit();
        }
        $deductStmt = $pdo->prepare("UPDATE users SET balance_eth = balance_eth - ? WHERE id = ?");
        $deductStmt->execute([$cryptoNeeded, $userId]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid payment method."]);
        $pdo->rollBack();
        exit();
    }
    
    // Create Investment
    $insertStmt = $pdo->prepare("INSERT INTO investments (user_id, plan_name, amount_usd, daily_roi, status) VALUES (?, ?, ?, ?, 'active')");
    $insertStmt->execute([$userId, $planName, $amount, $plan['roi']]);
    
    // Commit
    $pdo->commit();
    
    echo json_encode([
        "success" => true,
        "message" => "Successfully invested $" . number_format($amount, 2) . " in " . $planName
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Investment failed: " . $e->getMessage()]);
}
?>
