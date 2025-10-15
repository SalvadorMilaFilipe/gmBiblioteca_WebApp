<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

try {
    $livro_cod = (int)($_POST['livro_cod'] ?? 0);
    
    if ($livro_cod <= 0) {
        throw new Exception('Código do livro inválido');
    }
    
    $exemplares = get_exemplares_with_status($livro_cod);
    
    echo json_encode($exemplares, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
