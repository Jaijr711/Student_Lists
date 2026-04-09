<?php
require_once 'db.php';

$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'registrar';
$name = 'System Registrar';

$sql = "INSERT INTO users (username, password, role, name) VALUES (:username, :password, :role, :name)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashed_password,
        ':role' => $role,
        ':name' => $name
    ]);
    echo "Registrar account created successfully.\n";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo "Registrar account already exists.\n";
    } else {
        echo "Error creating registrar: " . $e->getMessage() . "\n";
    }
}
?>