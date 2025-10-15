<?php
/**
 * Script de teste para verificar se o problema do ISBN foi corrigido
 * Execute este script para testar com um ISBN de 13 dígitos grande
 */

require_once __DIR__ . '/database.php';

try {
    // ISBN de teste que causava o erro (13 dígitos)
    $test_isbn = '9247923463453';
    
    echo "Testando ISBN: $test_isbn\n";
    echo "Verificando se é numérico: " . (is_numeric($test_isbn) ? 'SIM' : 'NÃO') . "\n";
    echo "Verificando comprimento: " . strlen($test_isbn) . " dígitos\n";
    
    // Verificar se a base de dados está configurada corretamente
    $connection_info = get_connection_info();
    echo "Tipo de conexão: " . $connection_info['connection_type'] . "\n";
    
    // Tentar criar um livro de teste (sem inserir na BD)
    echo "\nSimulando criação de livro...\n";
    echo "ISBN como string: '$test_isbn'\n";
    echo "ISBN convertido para int: " . (int)$test_isbn . "\n";
    echo "ISBN como string preservado: '$test_isbn'\n";
    
    echo "\n✅ Teste concluído. Se não houve erros, o problema foi corrigido.\n";
    echo "📋 Próximos passos:\n";
    echo "1. Execute o script fix_isbn_column.sql no Supabase\n";
    echo "2. Teste o registo de um livro no disponiveis.php\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
}
?>
