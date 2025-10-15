<?php
declare(strict_types=1);

/**
 * Verificação Rápida do Site
 * Execute este arquivo diretamente no navegador para testar se tudo está funcionando
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação do Site - Biblioteca Ginestal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #7b1113;
            text-align: center;
            margin-bottom: 10px;
        }
        h2 {
            color: #7b1113;
            border-bottom: 2px solid #7b1113;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .test-item {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-success { background: #28a745; }
        .badge-error { background: #dc3545; }
        .badge-warning { background: #ffc107; color: #333; }
        .link-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        .link-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            border: 2px solid transparent;
        }
        .link-item:hover {
            background: #7b1113;
            color: white;
            border-color: #7b1113;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 17, 19, 0.3);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .summary h3 {
            margin: 0;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação do Site</h1>
        <p class="subtitle">Biblioteca Ginestal - Sistema de Gestão</p>

        <?php
        $errors = [];
        $warnings = [];
        $successes = [];

        // 1. Verificar arquivos PHP essenciais
        $essential_files = [
            'database.php' => 'Conexão com Base de Dados',
            'supabase_config.php' => 'Configuração Supabase',
            'supabase_rest_client.php' => 'Cliente REST Supabase',
            'navbar.php' => 'Barra de Navegação',
            'index.php' => 'Página Principal'
        ];

        echo '<h2>📁 Arquivos Essenciais</h2>';
        foreach ($essential_files as $file => $description) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                echo "<div class='test-item success'>";
                echo "<span>✓ $description ($file)</span>";
                echo "<span class='badge badge-success'>OK</span>";
                echo "</div>";
                $successes[] = $file;
            } else {
                echo "<div class='test-item error'>";
                echo "<span>✗ $description ($file)</span>";
                echo "<span class='badge badge-error'>FALTANDO</span>";
                echo "</div>";
                $errors[] = "$file não encontrado";
            }
        }

        // 2. Verificar imagens
        $images = [
            'img/Ginestal_Logo.png' => 'Logo Ginestal',
            'img/Ginestal_Machado.jpg' => 'Foto Ginestal Machado',
            'img/Livros_Disponiveis_image.png' => 'Ícone Livros Disponíveis',
            'img/Requisições_image.png' => 'Ícone Requisições',
            'img/LogOut_image.png' => 'Ícone Logout'
        ];

        echo '<h2>🖼️ Recursos de Imagem</h2>';
        foreach ($images as $image => $description) {
            $path = __DIR__ . '/' . $image;
            if (file_exists($path)) {
                echo "<div class='test-item success'>";
                echo "<span>✓ $description</span>";
                echo "<span class='badge badge-success'>OK</span>";
                echo "</div>";
                $successes[] = $image;
            } else {
                echo "<div class='test-item warning'>";
                echo "<span>⚠ $description</span>";
                echo "<span class='badge badge-warning'>FALTANDO</span>";
                echo "</div>";
                $warnings[] = "$image não encontrado";
            }
        }

        // 3. Verificar dependências
        echo '<h2>🔧 Dependências PHP</h2>';
        
        // Verificar se database.php inclui os arquivos necessários
        $database_content = file_get_contents(__DIR__ . '/database.php');
        if (strpos($database_content, "require_once __DIR__ . '/supabase_config.php'") !== false) {
            echo "<div class='test-item success'>";
            echo "<span>✓ database.php → supabase_config.php</span>";
            echo "<span class='badge badge-success'>OK</span>";
            echo "</div>";
        } else {
            echo "<div class='test-item error'>";
            echo "<span>✗ database.php → supabase_config.php</span>";
            echo "<span class='badge badge-error'>FALTANDO</span>";
            echo "</div>";
            $errors[] = "database.php não inclui supabase_config.php";
        }

        if (strpos($database_content, "require_once __DIR__ . '/supabase_rest_client.php'") !== false) {
            echo "<div class='test-item success'>";
            echo "<span>✓ database.php → supabase_rest_client.php</span>";
            echo "<span class='badge badge-success'>OK</span>";
            echo "</div>";
        } else {
            echo "<div class='test-item error'>";
            echo "<span>✗ database.php → supabase_rest_client.php</span>";
            echo "<span class='badge badge-error'>FALTANDO</span>";
            echo "</div>";
            $errors[] = "database.php não inclui supabase_rest_client.php";
        }

        // 4. Resumo
        $total_tests = count($successes) + count($errors) + count($warnings);
        $success_rate = round((count($successes) / $total_tests) * 100, 1);

        echo '<div class="summary">';
        echo "<h3>📊 Resumo da Verificação</h3>";
        echo "<p style='font-size: 18px; margin: 10px 0;'>";
        echo "✓ Sucessos: " . count($successes) . " | ";
        echo "✗ Erros: " . count($errors) . " | ";
        echo "⚠ Avisos: " . count($warnings);
        echo "</p>";
        echo "<p style='font-size: 24px; font-weight: bold;'>Taxa de Sucesso: {$success_rate}%</p>";
        echo '</div>';

        if (count($errors) === 0 && count($warnings) === 0) {
            echo '<div class="test-item success" style="font-size: 18px; padding: 20px; text-align: center;">';
            echo '🎉 <strong>TUDO ESTÁ FUNCIONANDO PERFEITAMENTE!</strong>';
            echo '</div>';
        }

        // 5. Links para páginas
        echo '<h2>🔗 Páginas Disponíveis</h2>';
        echo '<div class="link-list">';
        
        $pages = [
            'index.php' => '🏠 Início',
            'utentes.php' => '👥 Utentes',
            'requisicoes.php' => '📋 Requisições',
            'disponiveis.php' => '📚 Livros Disponíveis',
            'editoras.php' => '🏢 Editoras',
            'autor.php' => '✍️ Autores',
            'genero.php' => '🎭 Géneros',
            'idiomas.php' => '🌍 Idiomas',
            'codigopostal.php' => '📮 Códigos Postais'
        ];

        foreach ($pages as $page => $name) {
            if (file_exists(__DIR__ . '/' . $page)) {
                echo "<a href='$page' class='link-item'>$name</a>";
            }
        }
        
        echo '</div>';

        // Footer
        echo '<div class="footer">';
        echo '<p><strong>Biblioteca Ginestal</strong></p>';
        echo '<p>Desenvolvido por Salvador Mila Filipe com Cursor AI</p>';
        echo '<p>Caminho: ' . __DIR__ . '</p>';
        echo '<p>Servidor: ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
        echo '</div>';
        ?>
    </div>
</body>
</html>

