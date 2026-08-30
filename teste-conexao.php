<?php

$host = "__DB_HOST__";
$dbname = "__DB_NAME__";
$user = "__DB_USER__";
$pass = "__DB_PASS__";

echo "<h2>Teste de conexão</h2>";

echo "Host: " . htmlspecialchars($host) . "<br>";
echo "Banco: " . htmlspecialchars($dbname) . "<br>";
echo "Usuário: " . htmlspecialchars($user) . "<br><br>";

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    echo "<strong>CONEXÃO FUNCIONOU!</strong>";

} catch (PDOException $e) {

    echo "<strong>ERRO:</strong> ";
    echo htmlspecialchars($e->getMessage());
}