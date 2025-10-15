<?php
declare(strict_types=1);

/**
 * Script de teste para verificar se todas as páginas podem ser carregadas
 * sem erros 404 ou erros de sintaxe
 */

echo "====================================\n";
echo "Teste de Páginas - Biblioteca Ginestal\n";
echo "====================================\n\n";

// Lista de páginas principais
$pages = [
    'index.php',
    'navbar.php',
    'utentes.php',
    'requisicoes.php',
    'disponiveis.php',
    'editoras.php',
    'autor.php',
    'genero.php',
    'idiomas.php',
    'codigopostal.php',
    'get_exemplares.php',
    'get_exemplares_livro.php',
    'search_books_ajax.php',
    'update_exemplar_status.php',
    'database.php',
    'supabase_config.php',
    'supabase_rest_client.php'
];

// Lista de imagens
$images = [
    'img/Ginestal_Logo.png',
    'img/Ginestal_Machado.jpg',
    'img/Livros_Disponiveis_image.png',
    'img/LogOut_image.png',
    'img/Menu_Hamburguer_imagem.png',
    'img/Requisições_image.png'
];

// Verificar arquivos PHP
echo "1. Verificando arquivos PHP...\n";
echo "-------------------------------\n";
$errors = [];
foreach ($pages as $page) {
    $path = __DIR__ . '/' . $page;
    if (file_exists($path)) {
        // Verificar sintaxe
        $output = [];
        $return_var = 0;
        exec("php -l \"$path\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✓ $page - OK\n";
        } else {
            echo "✗ $page - ERRO DE SINTAXE\n";
            $errors[] = "$page tem erro de sintaxe: " . implode("\n", $output);
        }
    } else {
        echo "✗ $page - ARQUIVO NÃO ENCONTRADO\n";
        $errors[] = "$page não existe em " . __DIR__;
    }
}

echo "\n2. Verificando imagens...\n";
echo "-------------------------\n";
foreach ($images as $image) {
    $path = __DIR__ . '/' . $image;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✓ $image - OK (" . round($size / 1024, 2) . " KB)\n";
    } else {
        echo "✗ $image - ARQUIVO NÃO ENCONTRADO\n";
        $errors[] = "$image não existe em " . __DIR__;
    }
}

echo "\n3. Verificando links entre páginas...\n";
echo "--------------------------------------\n";

// Links do navbar
$navbar_links = [
    'index.php' => 'Início',
    'editoras.php' => 'Editoras',
    'utentes.php' => 'Utentes',
    'autor.php' => 'Autores',
    'codigopostal.php' => 'Códigos postais',
    'idiomas.php' => 'Idiomas',
    'genero.php' => 'Géneros'
];

foreach ($navbar_links as $link => $name) {
    $path = __DIR__ . '/' . $link;
    if (file_exists($path)) {
        echo "✓ Link $name ($link) - OK\n";
    } else {
        echo "✗ Link $name ($link) - QUEBRADO\n";
        $errors[] = "Link $name aponta para $link que não existe";
    }
}

// Links do index.php
$index_links = [
    'disponiveis.php' => 'Livros Disponíveis',
    'requisicoes.php' => 'Requisições'
];

foreach ($index_links as $link => $name) {
    $path = __DIR__ . '/' . $link;
    if (file_exists($path)) {
        echo "✓ Link $name ($link) - OK\n";
    } else {
        echo "✗ Link $name ($link) - QUEBRADO\n";
        $errors[] = "Link $name aponta para $link que não existe";
    }
}

echo "\n4. Verificando redirects...\n";
echo "---------------------------\n";

// Verificar redirect em requisicoes.php
$requisicoes_content = file_get_contents(__DIR__ . '/requisicoes.php');
if (strpos($requisicoes_content, "header('Location: index.php") !== false) {
    if (file_exists(__DIR__ . '/index.php')) {
        echo "✓ Redirect requisicoes.php -> index.php - OK\n";
    } else {
        echo "✗ Redirect requisicoes.php -> index.php - DESTINO NÃO EXISTE\n";
        $errors[] = "Redirect em requisicoes.php aponta para index.php que não existe";
    }
}

echo "\n5. Verificando includes e requires...\n";
echo "--------------------------------------\n";

// Verificar includes em database.php
$database_content = file_get_contents(__DIR__ . '/database.php');
$required_files = [
    'supabase_config.php',
    'supabase_rest_client.php'
];

foreach ($required_files as $file) {
    if (strpos($database_content, $file) !== false) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "✓ database.php require $file - OK\n";
        } else {
            echo "✗ database.php require $file - ARQUIVO NÃO ENCONTRADO\n";
            $errors[] = "database.php requer $file que não existe";
        }
    }
}

// Verificar que todos os arquivos principais incluem database.php
$pages_with_db = ['utentes.php', 'requisicoes.php', 'disponiveis.php', 'editoras.php', 'autor.php', 'genero.php', 'idiomas.php', 'codigopostal.php'];
foreach ($pages_with_db as $page) {
    $content = file_get_contents(__DIR__ . '/' . $page);
    if (strpos($content, "require_once __DIR__ . '/database.php'") !== false) {
        echo "✓ $page inclui database.php - OK\n";
    } else {
        echo "⚠ $page pode não incluir database.php corretamente\n";
    }
}

echo "\n6. Verificando includes do navbar.php...\n";
echo "----------------------------------------\n";

foreach ($pages_with_db as $page) {
    if ($page === 'navbar.php') continue;
    
    $content = file_get_contents(__DIR__ . '/' . $page);
    if (strpos($content, "include __DIR__ . '/navbar.php'") !== false) {
        echo "✓ $page inclui navbar.php - OK\n";
    } else {
        echo "⚠ $page pode não incluir navbar.php\n";
    }
}

// Resumo final
echo "\n====================================\n";
echo "RESUMO\n";
echo "====================================\n";

if (count($errors) === 0) {
    echo "✓ TODOS OS TESTES PASSARAM!\n";
    echo "✓ Não foram encontrados erros 404 ou links quebrados.\n";
    echo "✓ Todas as páginas estão prontas para serem servidas pelo Apache.\n";
} else {
    echo "✗ FORAM ENCONTRADOS " . count($errors) . " ERRO(S):\n\n";
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". $error\n";
    }
}

echo "\n====================================\n";
echo "Caminho da raiz: " . __DIR__ . "\n";
echo "====================================\n";
?>

