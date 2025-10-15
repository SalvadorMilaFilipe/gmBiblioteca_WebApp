<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

try {
    $q = trim($_POST['q'] ?? '');
    $searchType = $_POST['search_type'] ?? 'all';
    
    // Buscar livros com todos os campos
    if ($q !== '') {
        // Se há pesquisa, usar função de pesquisa existente
        $livrosDisponiveis = search_available_books($q, $searchType);
        // Converter para formato completo
        $livrosCompletos = [];
        foreach ($livrosDisponiveis as $livro) {
            $livrosCompletos[] = [
                'li_cod' => $livro['li_cod'],
                'li_titulo' => $livro['li_titulo'],
                'li_autor' => $livro['li_autor'],
                'li_isbn' => $livro['li_isbn'] ?? '',
                'li_editora' => $livro['li_editora'] ?? '',
                'li_idioma' => $livro['li_idioma'] ?? '',
                'li_edicao' => $livro['li_edicao'],
                'li_ano' => $livro['li_ano'],
                'li_genero' => $livro['li_genero'],
                'editora_nome' => $livro['editora_nome'] ?? '',
                'exemplares_disponiveis' => $livro['count'] ?? 1
            ];
        }
        echo json_encode($livrosCompletos, JSON_UNESCAPED_UNICODE);
    } else {
        // Buscar todos os livros com todos os campos
        $lista = get_all_books_full();
        
        // Adicionar contagem de exemplares para cada livro
        foreach ($lista as &$livro) {
            // Buscar exemplares disponíveis para este livro
            $exemplares = get_available_exemplares_for_book($livro['li_cod']);
            $livro['exemplares_disponiveis'] = count($exemplares);
        }
        
        echo json_encode($lista, JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
