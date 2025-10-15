<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

echo "🔍 Testando inserção e listagem de livros...\n\n";

try {
    // 1. Listar livros existentes
    echo "📋 Livros existentes na base de dados:\n";
    $livros_existentes = execute_rest_sql('livros', ['li_cod', 'li_titulo', 'li_autor', 'li_isbn'], [], 'li_cod');
    
    if (empty($livros_existentes)) {
        echo "❌ Nenhum livro encontrado na base de dados\n";
    } else {
        echo "✅ Encontrados " . count($livros_existentes) . " livros:\n";
        foreach ($livros_existentes as $livro) {
            echo "  - ID: {$livro['li_cod']}, Título: {$livro['li_titulo']}, Autor: {$livro['li_autor']}\n";
        }
    }
    
    echo "\n📋 Testando função get_available_books():\n";
    $livros_disponiveis = get_available_books();
    
    if (empty($livros_disponiveis)) {
        echo "❌ Nenhum livro disponível encontrado\n";
    } else {
        echo "✅ Encontrados " . count($livros_disponiveis) . " livros disponíveis:\n";
        foreach ($livros_disponiveis as $livro) {
            echo "  - ID: {$livro['li_cod']}, Título: {$livro['li_titulo']}, Autor: {$livro['li_autor']}\n";
        }
    }
    
    echo "\n📋 Verificando exemplares:\n";
    $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod', 'ex_li_cod', 'ex_disponivel', 'ex_permrequisicao'], [], 'ex_cod');
    
    if (empty($exemplares)) {
        echo "❌ Nenhum exemplar encontrado\n";
    } else {
        echo "✅ Encontrados " . count($exemplares) . " exemplares:\n";
        foreach ($exemplares as $exemplar) {
            echo "  - ID: {$exemplar['ex_cod']}, Livro: {$exemplar['ex_li_cod']}, Disponível: {$exemplar['ex_disponivel']}, Permite Requisição: {$exemplar['ex_permrequisicao']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n🏁 Teste concluído.\n";
?>
