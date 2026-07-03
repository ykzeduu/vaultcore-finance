<?php
// Tenta pegar as variáveis de ambiente do Render, se não existirem, usa o padrão do InfinityFree
$host = getenv('DB_HOST') ?: "sql210.infinityfree.com";
$user = getenv('DB_USER') ?: "if0_41175004";
$pass = getenv('DB_PASS') ?: "UZ5E9cEop7ARX"; 
$dbname = getenv('DB_NAME') ?: "if0_41175004_financeiro";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Exibe o erro de forma limpa se falhar
    die("Erro de conexão: " . $e->getMessage());
}
?>