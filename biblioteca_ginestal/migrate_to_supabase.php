<?php
declare(strict_types=1);

/**
 * Script de migração da base de dados local para Supabase
 * Execute este script uma vez para transferir todos os dados
 */

require_once __DIR__ . '/database.php'; // Base local
require_once __DIR__ . '/database_supabase.php'; // Base Supabase

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
    
    // 3. Migrar dados da base local
    echo "Migrando dados da base local...\n";
    
    // Conectar à base local
    $local_pdo = new PDO('sqlite:' . __DIR__ . '/biblioteca.db');
    $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Migrar idiomas
    echo "Migrando idiomas...\n";
    $idiomas = $local_pdo->query("SELECT id_idioma, id_pais FROM idioma")->fetchAll();
    foreach ($idiomas as $idioma) {
        execute_command(
            "INSERT INTO idioma (id_idioma, id_pais) VALUES (?, ?) ON CONFLICT (id_idioma) DO NOTHING",
            [$idioma['id_idioma'], $idioma['id_pais']]
        );
    }
    echo "✓ Idiomas migrados: " . count($idiomas) . " registos\n";
    
    // Migrar géneros
    echo "Migrando géneros...\n";
    $generos = $local_pdo->query("SELECT ge_genero FROM genero")->fetchAll();
    foreach ($generos as $genero) {
        execute_command(
            "INSERT INTO genero (ge_genero) VALUES (?) ON CONFLICT (ge_genero) DO NOTHING",
            [$genero['ge_genero']]
        );
    }
    echo "✓ Géneros migrados: " . count($generos) . " registos\n";
    
    // Migrar códigos postais
    echo "Migrando códigos postais...\n";
    $codigos = $local_pdo->query("SELECT cod_postal, cod_localidade FROM codigo_postal")->fetchAll();
    foreach ($codigos as $codigo) {
        execute_command(
            "INSERT INTO codigo_postal (cod_postal, cod_localidade) VALUES (?, ?) ON CONFLICT (cod_postal) DO NOTHING",
            [$codigo['cod_postal'], $codigo['cod_localidade']]
        );
    }
    echo "✓ Códigos postais migrados: " . count($codigos) . " registos\n";
    
    // Migrar editoras
    echo "Migrando editoras...\n";
    $editoras = $local_pdo->query("SELECT ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm FROM editora")->fetchAll();
    foreach ($editoras as $editora) {
        execute_command(
            "INSERT INTO editora (ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm) VALUES (?, ?, ?, ?, ?)",
            [$editora['ed_nome'], $editora['ed_morada'], $editora['ed_email'], $editora['ed_codpostal'], $editora['ed_tlm']]
        );
    }
    echo "✓ Editoras migradas: " . count($editoras) . " registos\n";
    
    // Migrar autores
    echo "Migrando autores...\n";
    $autores = $local_pdo->query("SELECT au_nome, au_pais FROM autor")->fetchAll();
    foreach ($autores as $autor) {
        execute_command(
            "INSERT INTO autor (au_nome, au_pais) VALUES (?, ?)",
            [$autor['au_nome'], $autor['au_pais']]
        );
    }
    echo "✓ Autores migrados: " . count($autores) . " registos\n";
    
    // Migrar utentes
    echo "Migrando utentes...\n";
    $utentes = $local_pdo->query("SELECT ut_nome, ut_email, ut_turma, ut_ano FROM utente")->fetchAll();
    foreach ($utentes as $utente) {
        execute_command(
            "INSERT INTO utente (ut_nome, ut_email, ut_turma, ut_ano) VALUES (?, ?, ?, ?)",
            [$utente['ut_nome'], $utente['ut_email'], $utente['ut_turma'], $utente['ut_ano']]
        );
    }
    echo "✓ Utentes migrados: " . count($utentes) . " registos\n";
    
    // Migrar livros
    echo "Migrando livros...\n";
    $livros = $local_pdo->query("SELECT li_titulo, li_autor, li_isbn, li_editora, li_idioma, li_edicao, li_ano, li_genero FROM livros")->fetchAll();
    foreach ($livros as $livro) {
        execute_command(
            "INSERT INTO livros (li_titulo, li_autor, li_isbn, li_editora, li_idioma, li_edicao, li_ano, li_genero) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$livro['li_titulo'], $livro['li_autor'], $livro['li_isbn'], $livro['li_editora'], $livro['li_idioma'], $livro['li_edicao'], $livro['li_ano'], $livro['li_genero']]
        );
    }
    echo "✓ Livros migrados: " . count($livros) . " registos\n";
    
    // Migrar exemplares
    echo "Migrando exemplares...\n";
    $exemplares = $local_pdo->query("SELECT ex_li_cod, ex_disponivel, ex_permrequisicao, ex_estado FROM livro_exemplar")->fetchAll();
    foreach ($exemplares as $exemplar) {
        execute_command(
            "INSERT INTO livro_exemplar (ex_li_cod, ex_disponivel, ex_permrequisicao, ex_estado) VALUES (?, ?, ?, ?)",
            [$exemplar['ex_li_cod'], $exemplar['ex_disponivel'], $exemplar['ex_permrequisicao'], $exemplar['ex_estado']]
        );
    }
    echo "✓ Exemplares migrados: " . count($exemplares) . " registos\n";
    
    // Migrar requisições
    echo "Migrando requisições...\n";
    $requisicoes = $local_pdo->query("SELECT re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao FROM requisicao")->fetchAll();
    foreach ($requisicoes as $requisicao) {
        execute_command(
            "INSERT INTO requisicao (re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao) VALUES (?, ?, ?, ?)",
            [$requisicao['re_lexcod'], $requisicao['re_utcod'], $requisicao['re_datarequisicao'], $requisicao['re_datadevolucao']]
        );
    }
    echo "✓ Requisições migradas: " . count($requisicoes) . " registos\n";
    
    echo "\n🎉 Migração concluída com sucesso!\n";
    echo "Agora pode alterar os arquivos para usar database_supabase.php em vez de database.php\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
