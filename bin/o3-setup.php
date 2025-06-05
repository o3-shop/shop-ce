<?php
// o3-setup.php

// Check for MySQLi extension
if (!extension_loaded('mysqli')) {
    die("MySQLi extension not loaded. Install php8.2-mysql first.\n");
}

// Database credentials
$dbHost = 'db';
$dbUser = 'o3shop';
$dbPwd  = 'o3shop';
$dbName = 'o3shop';
$dbPort = 3306;

// Shop configuration
$shopUrl = 'http://127.0.0.1:8080';
$shopName = 'My O3 Shop';
$adminEmail = 'admin@example.com';
$adminPass = 'admin123';

// Connect to MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName, $dbPort);
if ($mysqli->connect_error) {
    die("DB Connection failed: " . $mysqli->connect_error . "\n");
}

// Check if schema is already imported
$result = $mysqli->query("SHOW TABLES LIKE 'oxshops'");
if ($result->num_rows == 0) {
    echo "Importing database schema...\n";
    $schemaFile = __DIR__ . '/../source/Setup/Sql/database_schema.sql';
    if (file_exists($schemaFile)) {
        $commands = file_get_contents($schemaFile);
        if (!$mysqli->multi_query($commands)) {
            die("Schema import failed: " . $mysqli->error . "\n");
        }
        while ($mysqli->next_result()) { /* flush results */ }
    }

    echo "Importing initial data...\n";
    $dataFile = __DIR__ . '/../source/Setup/Sql/initial_data.sql';
    if (file_exists($dataFile)) {
        $commands = file_get_contents($dataFile);
        if (!$mysqli->multi_query($commands)) {
            echo "Warning: Initial data import failed: " . $mysqli->error . "\n";
        }
        while ($mysqli->next_result()) { /* flush results */ }
    }
} else {
    echo "Database already initialized, skipping schema import.\n";
}

// Get actual oxshops table structure
echo "Checking oxshops table structure...\n";
$result = $mysqli->query("DESCRIBE oxshops");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "Available columns: " . implode(', ', $columns) . "\n";

// Create/Update shop record with correct columns
echo "Setting up shop configuration...\n";
$shopId = 'oxbaseshop';

// Build INSERT with only existing columns
$shopColumns = [
    'OXID' => $shopId,
    'OXACTIVE' => '1',
    'OXNAME' => $shopName,
    'OXURL' => $shopUrl,
    'OXCOMPANY' => 'My Company',
    'OXCITY' => 'My City',
    'OXCOUNTRY' => 'Germany',
    'OXINFOEMAIL' => $adminEmail,
    'OXORDEREMAIL' => $adminEmail,
    'OXOWNEREMAIL' => $adminEmail,
];

// Filter only existing columns
$validColumns = array_intersect_key($shopColumns, array_flip($columns));
$columnNames = implode(', ', array_keys($validColumns));
$placeholders = implode(', ', array_fill(0, count($validColumns), '?'));
$values = array_values($validColumns);

$stmt = $mysqli->prepare("
    INSERT INTO oxshops ($columnNames) 
    VALUES ($placeholders)
    ON DUPLICATE KEY UPDATE OXNAME = VALUES(OXNAME), OXURL = VALUES(OXURL)
");
$types = str_repeat('s', count($values));
$stmt->bind_param($types, ...$values);
$stmt->execute();

// Create admin user
echo "Creating admin user...\n";
$adminPassHash = password_hash($adminPass, PASSWORD_BCRYPT);
$adminId = md5($adminEmail . time());

$stmt = $mysqli->prepare("
    INSERT INTO oxuser (
        OXID, OXUSERNAME, OXPASSWORD, OXACTIVE, OXRIGHTS, OXFNAME, OXLNAME, OXSHOPID
    ) VALUES (?, ?, ?, 1, 'malladmin', 'Admin', 'User', 1)
    ON DUPLICATE KEY UPDATE 
        OXPASSWORD = VALUES(OXPASSWORD),
        OXRIGHTS = VALUES(OXRIGHTS)
");
$stmt->bind_param('sss', $adminId, $adminEmail, $adminPassHash);
$stmt->execute();

// Generate database views - THIS IS CRUCIAL
echo "Generating database views...\n";
$viewCommand = __DIR__ . '/../vendor/bin/oe-eshop-db_views_generate';
if (file_exists($viewCommand)) {
    exec("cd " . __DIR__ . " /.. && php $viewCommand 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        echo "Database views generated successfully.\n";
    } else {
        echo "View generation output:\n" . implode("\n", $output) . "\n";
    }
} else {
    echo "WARNING: View generator not found at $viewCommand\n";
    echo "You MUST run this manually:\n";
    echo "cd /var/www/html && php vendor/bin/oe-eshop-db_views_generate\n";
}

echo "\n=== Setup Complete ===\n";
echo "Shop URL: $shopUrl\n";
echo "Admin Email: $adminEmail\n";
echo "Admin Password: $adminPass\n";
echo "\nIMPORTANT: If views weren't generated, run:\n";
echo "php vendor/bin/oe-eshop-db_views_generate\n";
?>
