<?php
// server/db.php

// ---------------------------------------------------------
// DATABASE CONFIGURATION
// ---------------------------------------------------------

// DYNAMIC DATABASE CONFIGURATION
// If deploying to Render, set the DATABASE_URL environment variable.
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // PRODUCTION DEPLOYMENT (Render + PostgreSQL)
    $parsedUrl = parse_url($dbUrl);
    if (!$parsedUrl) {
        die(json_encode(["success" => false, "message" => "Invalid DATABASE_URL"]));
    }
    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'localhost';
    $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 5432;
    $dbname = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
    $username = isset($parsedUrl['user']) ? $parsedUrl['user'] : null;
    $password = isset($parsedUrl['pass']) ? $parsedUrl['pass'] : null;
    
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    $isPostgres = true;
} else {
    // LOCAL DEVELOPMENT (SQLite)
    $dbFile = __DIR__ . '/database.sqlite';
    $dsn = "sqlite:$dbFile";
    $username = null;
    $password = null;
    $isPostgres = false;
}

try {
    // Create the PDO connection
    $pdo = new PDO($dsn, $username, $password);
    
    // Set error mode to exception to help with debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Return results as associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ---------------------------------------------------------
    // AUTO-MIGRATION
    // ---------------------------------------------------------
    $primaryKeySyntax = $isPostgres ? "SERIAL PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT";

    // 1. Create Users Table
    $query = "
    CREATE TABLE IF NOT EXISTS users (
        id {$primaryKeySyntax}, 
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        balance_usd DECIMAL(18,2) DEFAULT 0.00,
        balance_btc DECIMAL(18,8) DEFAULT 0.00000000,
        balance_eth DECIMAL(18,8) DEFAULT 0.00000000,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query);

    // 2. Create Investments Table
    $query2 = "
    CREATE TABLE IF NOT EXISTS investments (
        id {$primaryKeySyntax},
        user_id INTEGER NOT NULL,
        plan_name VARCHAR(50) NOT NULL,
        amount_usd DECIMAL(18,2) NOT NULL,
        daily_roi DECIMAL(5,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query2);

    // 3. Create Withdrawals Table
    $query3 = "
    CREATE TABLE IF NOT EXISTS withdrawals (
        id {$primaryKeySyntax},
        user_id INTEGER NOT NULL,
        coin VARCHAR(10) NOT NULL,
        amount DECIMAL(18,6) NOT NULL,
        address VARCHAR(100) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query3);

    // 4. Create Deposits Table
    $query4 = "
    CREATE TABLE IF NOT EXISTS deposits (
        id {$primaryKeySyntax},
        user_id INTEGER NOT NULL,
        coin VARCHAR(10) NOT NULL,
        amount DECIMAL(18,6) NOT NULL,
        status VARCHAR(20) DEFAULT 'completed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query4);

} catch (PDOException $e) {
    // If the database fails to connect, output a JSON error and exit
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>
