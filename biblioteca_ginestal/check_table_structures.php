<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

echo "🔍 Verificando estruturas das tabelas...\n\n";

$tabelas = ['editora', 'idioma', 'genero', 'codigo_postal', 'autor', 'livros', 'livro_exemplar', 'utente', 'requisicao'];

foreach ($tabelas as $tabela) {
    echo "📋 Tabela: $tabela\n";
    
    try {
        // Tentar buscar todas as colunas
        $dados = execute_rest_sql($tabela, ['*'], [], '');
        
        if (!empty($dados)) {
            echo "✅ Estrutura da tabela $tabela:\n";
            $primeiro_registro = $dados[0];
            foreach ($primeiro_registro as $coluna => $valor) {
                echo "  - $coluna: " . gettype($valor) . "\n";
            }
            echo "📊 Total de registros: " . count($dados) . "\n";
        } else {
            echo "📊 Tabela $tabela está vazia\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro ao acessar tabela $tabela: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "🏁 Verificação concluída.\n";
?>
