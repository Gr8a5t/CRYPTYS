<?php
// server/db.php

// ---------------------------------------------------------
// DATABASE CONFIGURATION
// ---------------------------------------------------------

// LOCAL DEVELOPMENT (SQLite)
// This will automatically create a 'database.sqlite' file in this folder
$dbFile = __DIR__ . '/database.sqlite';
$dsn = "sqlite:$dbFile";
$username = null;
$password = null;

// =========================================================
// PRODUCTION DEPLOYMENT (Render + PostgreSQL)
// When you host on Render, COMMENT OUT the SQLite section above,
// and UNCOMMENT the lines below, pasting your Render Database URL.
// =========================================================
/*
// Example Render URL: postgres://user:pass@host:5432/dbname
$dbUrl = parse_url("postgres://your_render_db_url_here");
$dsn = "pgsql:host=" . $dbUrl['host'] . ";port=" . $dbUrl['port'] . ";dbname=" . ltrim($dbUrl['path'], '/');
$username = $dbUrl['user'];
$password = $dbUrl['pass'];
*/

try {
    // Create the PDO connection
    $pdo = new PDO($dsn, $username, $password);
    
    // Set error mode to exception to help with debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Return results as associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ---------------------------------------------------------
    // AUTO-MIGRATION: Create Users Table if it doesn't exist
    // ---------------------------------------------------------
    // NOTE: PostgreSQL uses 'SERIAL' instead of 'INTEGER PRIMARY KEY AUTOINCREMENT'. 
    // When you switch to Postgres, change the first line to: id SERIAL PRIMARY KEY,
    
    $query = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        balance_usd DECIMAL(18,2) DEFAULT 0.00,
        balance_btc DECIMAL(18,8) DEFAULT 0.00000000,
        balance_eth DECIMAL(18,8) DEFAULT 0.00000000,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($query);

    // ---------------------------------------------------------
    // AUTO-MIGRATION: Create Investments Table
    // ---------------------------------------------------------
    $query2 = "
    CREATE TABLE IF NOT EXISTS investments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_name VARCHAR(50) NOT NULL,
        amount_usd DECIMAL(18,2) NOT NULL,
        daily_roi DECIMAL(5,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query2);

    // ---------------------------------------------------------
    // AUTO-MIGRATION: Create Withdrawals Table
    // ---------------------------------------------------------
    $query3 = "
    CREATE TABLE IF NOT EXISTS withdrawals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        coin VARCHAR(10) NOT NULL,
        amount DECIMAL(18,6) NOT NULL,
        address VARCHAR(100) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query3);

    // ---------------------------------------------------------
    // AUTO-MIGRATION: Create Deposits Table
    // ---------------------------------------------------------
    $query4 = "
    CREATE TABLE IF NOT EXISTS deposits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
