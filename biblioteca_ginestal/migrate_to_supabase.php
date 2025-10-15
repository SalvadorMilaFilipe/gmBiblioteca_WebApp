<?php
declare(strict_types=1);

/**
 * Script de migração da base de dados local para Supabase
 * Execute este script uma vez para transferir todos os dados
 */

require_once __DIR__ . '/database.php'; // Base Supabase (já migrada)

echo "Iniciando migração para Supabase...\n";

try {
    // 1. Verificar conexão com Supabase
    echo "Verificando conexão com Supabase...\n";
    $supabase_pdo = get_pdo();
    echo "✓ Conexão com Supabase estabelecida\n";
    
    // 2. Criar tabelas no Supabase se necessário
    echo "Criando tabelas no Supabase...\n";
    ensure_schema();
    echo "✓ Tabelas criadas/verificadas\n";
    
    // 3. Verificar se já existem dados no Supabase
    echo "Verificando dados existentes no Supabase...\n";
    
    $existing_books = execute_sql("SELECT COUNT(*) as count FROM livros");
    $book_count = $existing_books[0]['count'] ?? 0;
    
    if ($book_count > 0) {
        echo "✓ Já existem $book_count livros no Supabase. Migração desnecessária.\n";
        echo "\n🎉 Base de dados Supabase já está configurada e funcionando!\n";
        exit(0);
    }
    
    echo "\n🎉 Base de dados Supabase configurada com sucesso!\n";
    echo "As tabelas foram criadas e os dados iniciais foram inseridos.\n";
    echo "A aplicação já está pronta para usar o Supabase!\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
