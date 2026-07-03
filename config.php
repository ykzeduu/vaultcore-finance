<?php
// Se o Render fornecer a string completa DATABASE_URL, nós a quebramos para conectar
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    $dbopts = parse_url($database_url);
    $host = $dbopts["host"];
    $port = $dbopts["port"] ?? 5432;
    $user = $dbopts["user"];
    $pass = $dbopts["pass"];
    $dbname = ltrim($dbopts["path"], '/');
} else {
    // Configurações padrão caso você rode localmente no seu computador com Postgres
    $host = "localhost";
    $port = 5432;
    $user = "postgres";
    $pass = "senha";
    $dbname = "financeiro";
}

try {
    // Conexão via PDO usando o driver 'pgsql'
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================================
    // CRIAÇÃO AUTOMÁTICA DA TABELA (MÁGICA)
    // ==========================================
    
    // Script SQL adaptado para o PostgreSQL
    $sql_criar_tabela = "
    CREATE TABLE IF NOT EXISTS lancamentos (
        id SERIAL PRIMARY KEY,
        tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('entrada', 'saida')),
        categoria VARCHAR(50) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data_lancamento DATE NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        conta VARCHAR(15) DEFAULT 'salario' CHECK (conta IN ('salario', 'vale', 'cartao')),
        fatura_mes VARCHAR(7) DEFAULT NULL
    );";

    // Executa o comando. Se a tabela já existir, ele não faz nada.
    $pdo->exec($sql_criar_tabela);

} catch (PDOException $e) {
    die("Erro na conexão ou criação do banco PostgreSQL: " . $e->getMessage());
}
?>