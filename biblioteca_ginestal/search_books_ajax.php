<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

try {
    $q = trim($_POST['q'] ?? '');
    $searchType = $_POST['search_type'] ?? 'all';
    
    // Usar a função de pesquisa existente
    if ($q !== '') {
        $livrosDisponiveis = search_available_books($q, $searchType);
    } else {
        $livrosDisponiveis = get_available_books();
    }
    
    // Agrupar por livro e contar exemplares disponíveis (mesma lógica do arquivo principal)
    $agrupados = [];
    foreach ($livrosDisponiveis as $row) {
        $livroId = (int)$row['li_cod'] ?? 0;
        if (!isset($agrupados[$livroId])) {
            $agrupados[$livroId] = [
                'li_cod' => $livroId,
                'li_titulo' => $row['li_titulo'],
                'li_autor' => $row['li_autor'],
                'li_ano' => $row['li_ano'],
                'li_edicao' => $row['li_edicao'],
                'li_genero' => $row['li_genero'],
                'count' => 0,
            ];
        }
        $agrupados[$livroId]['count']++;
    }
    $agrupados = array_values($agrupados);
    
    echo json_encode($agrupados, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
