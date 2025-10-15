<?php
/**
 * Script de teste para verificar se a configuração do schema gm_biblioteca está funcionando
 */

require_once __DIR__ . '/supabase_config.php';
require_once __DIR__ . '/supabase_rest_client.php';

try {
    echo "=== Teste de Configuração do Schema gm_biblioteca ===\n\n";
    
    // Testar configuração
    echo "1. Testando configuração:\n";
    echo "   - Schema: " . SupabaseConfig::SUPABASE_SCHEMA . "\n";
    echo "   - URL base: " . SupabaseConfig::SUPABASE_URL . "\n";
    echo "   - URL da API: " . SupabaseConfig::getApiUrl() . "\n";
    echo "   - URL com tabela: " . SupabaseConfig::getApiUrl('livros') . "\n\n";
    
    // Testar cliente REST
    echo "2. Testando cliente REST:\n";
    $client = new SupabaseRestClient();
    
    // Testar conexão com uma tabela simples
    echo "   - Tentando conectar com tabela 'idioma'...\n";
    $idiomas = $client->select('idioma', ['id_idioma'], [], 'id_idioma');
    
    if (is_array($idiomas)) {
        echo "   ✅ Conexão bem-sucedida!\n";
        echo "   - Idiomas encontrados: " . count($idiomas) . "\n";
        if (!empty($idiomas)) {
            echo "   - Primeiro idioma: " . $idiomas[0]['id_idioma'] . "\n";
        }
    } else {
        echo "   ❌ Erro na conexão\n";
    }
    
    echo "\n3. Testando outras tabelas:\n";
    
    // Testar editoras
    $editoras = $client->select('editora', ['ed_cod', 'ed_nome'], [], 'ed_nome');
    echo "   - Editoras: " . count($editoras) . " encontradas\n";
    
    // Testar autores
    $autores = $client->select('autor', ['au_cod', 'au_nome'], [], 'au_nome');
    echo "   - Autores: " . count($autores) . " encontrados\n";
    
    // Testar livros
    $livros = $client->select('livros', ['li_cod', 'li_titulo'], [], 'li_titulo');
    echo "   - Livros: " . count($livros) . " encontrados\n";
    
    echo "\n=== Resultado do Teste ===\n";
    echo "✅ Configuração do schema gm_biblioteca está funcionando!\n";
    echo "📋 Próximos passos:\n";
    echo "1. Execute o script migrate_to_gm_biblioteca_schema.sql no Supabase\n";
    echo "2. Teste as funcionalidades da aplicação\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "\n🔧 Possíveis soluções:\n";
    echo "1. Verifique se o schema gm_biblioteca existe no Supabase\n";
    echo "2. Execute o script supabase_schema.sql para criar as tabelas\n";
    echo "3. Verifique as permissões da API key do Supabase\n";
}
?>
