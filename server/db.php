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
