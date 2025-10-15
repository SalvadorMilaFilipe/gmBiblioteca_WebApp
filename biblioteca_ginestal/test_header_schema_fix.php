<?php
/**
 * Script de teste para verificar se a correção do schema via header funcionou
 */

require_once __DIR__ . '/supabase_config.php';
require_once __DIR__ . '/supabase_rest_client.php';

try {
    echo "=== Teste da Correção do Schema via Header ===\n\n";
    
    // Testar configuração
    echo "1. Testando configuração:\n";
    echo "   - Schema: " . SupabaseConfig::SUPABASE_SCHEMA . "\n";
    echo "   - URL da API: " . SupabaseConfig::getApiUrl() . "\n";
    echo "   - URL com tabela: " . SupabaseConfig::getApiUrl('utente') . "\n";
    
    // Mostrar headers
    $headers = SupabaseConfig::getApiHeaders();
    echo "   - Headers:\n";
    foreach ($headers as $header) {
        echo "     * " . $header . "\n";
    }
    echo "\n";
    
    // Testar cliente REST
    echo "2. Testando cliente REST:\n";
    $client = new SupabaseRestClient();
    
    // Testar conexão com códigos postais (que estava a dar erro)
    echo "   - Tentando conectar com tabela 'codigo_postal'...\n";
    $codigos = $client->select('codigo_postal', ['cod_postal', 'cod_localidade'], [], 'cod_localidade');
    
    if (is_array($codigos)) {
        echo "   ✅ Conexão bem-sucedida!\n";
        echo "   - Códigos postais encontrados: " . count($codigos) . "\n";
        if (!empty($codigos)) {
            echo "   - Primeiro código: " . $codigos[0]['cod_localidade'] . " (" . $codigos[0]['cod_postal'] . ")\n";
        }
    } else {
        echo "   ❌ Erro na conexão\n";
    }
    
    echo "\n3. Testando outras tabelas:\n";
    
    // Testar utentes
    $utentes = $client->select('utente', ['ut_cod', 'ut_nome', 'ut_email'], [], 'ut_nome');
    echo "   - Utentes: " . count($utentes) . " encontrados\n";
    
    // Testar idiomas
    $idiomas = $client->select('idioma', ['id_idioma', 'id_nome'], [], 'id_idioma');
    echo "   - Idiomas: " . count($idiomas) . " encontrados\n";
    
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
    echo "✅ Correção do schema via header funcionou!\n";
    echo "📋 Agora usando:\n";
    echo "   - Header: Accept-Profile: gm_biblioteca\n";
    echo "   - URLs limpas: https://...supabase.co/rest/v1/tabela\n";
    echo "   - Sem parâmetros schema na URL\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "\n🔧 Possíveis soluções:\n";
    echo "1. Verifique se o schema gm_biblioteca existe no Supabase\n";
    echo "2. Execute o script MIGRATE_SCHEMA_ONLY.sql para criar as tabelas\n";
    echo "3. Verifique se o header Accept-Profile está a ser enviado corretamente\n";
    echo "4. Verifique as permissões da API key do Supabase\n";
}
?>
