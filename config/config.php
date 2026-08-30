<?php
/**
 * Configuração de conexão com o banco de dados.
 *
 * IMPORTANTE: os valores abaixo são MARCADORES (placeholders).
 * Eles são substituídos automaticamente pelos valores reais durante
 * o deploy via GitHub Actions (veja .github/workflows/deploy.yml).
 *
 * Para rodar o projeto LOCALMENTE (ex: XAMPP), troque manualmente
 * os marcadores pelos dados do seu banco local antes de testar.
 */

$host   = "__DB_HOST__";
$dbname = "__DB_NAME__";
$user   = "__DB_USER__";
$pass   = "__DB_PASS__";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Falha na conexão com o banco de dados: " . $e->getMessage());
}
