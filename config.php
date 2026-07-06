<?php
try {
    // Conecta diretamente em um arquivo local chamado banco.sqlite
    $pdo = new PDO("sqlite:" . __DIR__ . "/banco.sqlite");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cria a tabela automaticamente caso ela não exista (adaptado para SQLite)
    $sql_criar_tabela = "
    CREATE TABLE IF NOT EXISTS lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo TEXT NOT NULL CHECK (tipo IN ('entrada', 'saida')),
        categoria TEXT NOT NULL,
        valor REAL NOT NULL,
        data_lancamento TEXT NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        conta TEXT DEFAULT 'salario' CHECK (conta IN ('salario', 'vale', 'cartao')),
        fatura_mes TEXT DEFAULT NULL
    );";

    $pdo->exec($sql_criar_tabela);

} catch (PDOException $e) {
    die("Erro no banco SQLite: " . $e->getMessage());
}
?>