<?php
require_once 'db.php';

if (isset($db_type) && $db_type === 'sqlite') {
    $sql = file_get_contents('schema_sqlite.sql');
    echo "Initializing SQLite database...\n";
} else {
    $sql = file_get_contents('schema.sql');
    echo "Initializing MySQL database...\n";
}

try {
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        if (trim($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "Database schema initialized successfully.\n";
} catch (PDOException $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";
}
?>