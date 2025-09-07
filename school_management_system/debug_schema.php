<?php
require_once 'config/database.php';

echo "<h2>Database Schema Analysis</h2>";

// Get all tables
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        echo "<pre>";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo sprintf("%-20s %-20s %-10s %-10s %-10s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'], 
                $column['Extra']
            );
        }
        echo "</pre><hr>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
