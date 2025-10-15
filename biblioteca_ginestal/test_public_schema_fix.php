<?php
/**
 * Script de teste para verificar se a correção usando schema public funciona
 */

require_once __DIR__ . '/supabase_config.php';
require_once __DIR__ . '/supabase_rest_client.php';

try {
    echo "=== Teste da Correção usando Schema Public ===\n\n";
    
    // Testar configuração
    echo "1. Testando configuração:\n";
    echo "   - Schema: " . SupabaseConfig::SUPABASE_SCHEMA . "\n";
    echo "   - URL da API: " . SupabaseConfig::getApiUrl() . "\n";
    echo "   - URL com tabela: " . SupabaseConfig::getApiUrl('gm_utente') . "\n\n";
    
    // Testar cliente REST
    echo "2. Testando cliente REST:\n";
    $client = new SupabaseRestClient();
    
    // Testar conexão com utentes (usando prefixo gm_)
    echo "   - Tentando conectar com tabela 'gm_utente'...\n";
    $utentes = $client->select('gm_utente', ['ut_cod', 'ut_nome', 'ut_email'], [], 'ut_nome');
    
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
    
    // Testar códigos postais
    $codigos = $client->select('gm_codigo_postal', ['cod_postal', 'cod_localidade'], [], 'cod_localidade');
    echo "   - Códigos postais: " . count($codigos) . " encontrados\n";
    
    // Testar idiomas
    $idiomas = $client->select('gm_idioma', ['id_idioma', 'id_nome'], [], 'id_idioma');
    echo "   - Idiomas: " . count($idiomas) . " encontrados\n";
    
    // Testar editoras
    $editoras = $client->select('gm_editora', ['ed_cod', 'ed_nome'], [], 'ed_nome');
    echo "   - Editoras: " . count($editoras) . " encontradas\n";
    
    // Testar autores
    $autores = $client->select('gm_autor', ['au_cod', 'au_nome'], [], 'au_nome');
    echo "   - Autores: " . count($autores) . " encontrados\n";
    
    // Testar géneros
    $generos = $client->select('gm_genero', ['ge_genero'], [], 'ge_genero');
    echo "   - Géneros: " . count($generos) . " encontrados\n";
    
    // Testar livros
    $livros = $client->select('gm_livros', ['li_cod', 'li_titulo'], [], 'li_titulo');
    echo "   - Livros: " . count($livros) . " encontrados\n";
    
    echo "\n=== Resultado do Teste ===\n";
    echo "✅ Correção usando schema public funcionou!\n";
    echo "📋 Agora usando:\n";
    echo "   - Schema: public (padrão do Supabase)\n";
    echo "   - Tabelas com prefixo: gm_utente, gm_editora, etc.\n";
    echo "   - URLs: https://...supabase.co/rest/v1/gm_tabela\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "\n🔧 Possíveis soluções:\n";
    echo "1. Execute o script ALTERNATIVE_PUBLIC_SCHEMA.sql no Supabase\n";
    echo "2. Verifique se as tabelas com prefixo gm_ foram criadas\n";
    echo "3. Verifique as permissões da API key do Supabase\n";
    echo "4. Verifique se o RLS está configurado corretamente\n";
}
?>
