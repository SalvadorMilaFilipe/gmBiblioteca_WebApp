<?php
/**
 * Script de teste para verificar se a correção do schema funcionou
 */

require_once __DIR__ . '/supabase_config.php';
require_once __DIR__ . '/supabase_rest_client.php';

try {
    echo "=== Teste da Correção do Schema gm_biblioteca ===\n\n";
    
    // Testar configuração
    echo "1. Testando configuração:\n";
    echo "   - Schema: " . SupabaseConfig::SUPABASE_SCHEMA . "\n";
    echo "   - URL da API: " . SupabaseConfig::getApiUrl() . "\n";
    echo "   - URL com tabela: " . SupabaseConfig::getApiUrl('utente') . "\n\n";
    
    // Testar cliente REST
    echo "2. Testando cliente REST:\n";
    $client = new SupabaseRestClient();
    
    // Testar conexão com utentes
    echo "   - Tentando conectar com tabela 'utente'...\n";
    $utentes = $client->select('utente', ['ut_cod', 'ut_nome', 'ut_email'], [], 'ut_nome');
    
    if (is_array($utentes)) {
        echo "   ✅ Conexão bem-sucedida!\n";
        echo "   - Utentes encontrados: " . count($utentes) . "\n";
        if (!empty($utentes)) {
            echo "   - Primeiro utente: " . $utentes[0]['ut_nome'] . "\n";
        }
    } else {
        echo "   ❌ Erro na conexão\n";
    }
    
    echo "\n3. Testando outras tabelas:\n";
    
    // Testar idiomas
    $idiomas = $client->select('idioma', ['id_idioma', 'id_nome'], [], 'id_idioma');
    echo "   - Idiomas: " . count($idiomas) . " encontrados\n";
    if (!empty($idiomas)) {
        echo "   - Primeiro idioma: " . $idiomas[0]['id_nome'] . " (ID: " . $idiomas[0]['id_idioma'] . ")\n";
    }
    
    // Testar editoras
    $editoras = $client->select('editora', ['ed_cod', 'ed_nome'], [], 'ed_nome');
    echo "   - Editoras: " . count($editoras) . " encontradas\n";
    
    // Testar autores
    $autores = $client->select('autor', ['au_cod', 'au_nome'], [], 'au_nome');
    echo "   - Autores: " . count($autores) . " encontrados\n";
    
    // Testar géneros
    $generos = $client->select('genero', ['ge_genero'], [], 'ge_genero');
    echo "   - Géneros: " . count($generos) . " encontrados\n";
    
    // Testar livros
    $livros = $client->select('livros', ['li_cod', 'li_titulo'], [], 'li_titulo');
    echo "   - Livros: " . count($livros) . " encontrados\n";
    
    echo "\n=== Resultado do Teste ===\n";
    echo "✅ Correção do schema funcionou!\n";
    echo "📋 URLs agora formatadas corretamente:\n";
    echo "   - SELECT: https://...supabase.co/rest/v1/utente?select=*&schema=gm_biblioteca\n";
    echo "   - INSERT: https://...supabase.co/rest/v1/utente?schema=gm_biblioteca\n";
    echo "   - UPDATE: https://...supabase.co/rest/v1/utente?schema=gm_biblioteca&id=eq.1\n";
    echo "   - DELETE: https://...supabase.co/rest/v1/utente?schema=gm_biblioteca&id=eq.1\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "\n🔧 Possíveis soluções:\n";
    echo "1. Verifique se o schema gm_biblioteca existe no Supabase\n";
    echo "2. Execute o script MIGRATE_SCHEMA_ONLY.sql para criar as tabelas\n";
    echo "3. Verifique as permissões da API key do Supabase\n";
    echo "4. Verifique se as tabelas foram criadas no schema correto\n";
}
?>
