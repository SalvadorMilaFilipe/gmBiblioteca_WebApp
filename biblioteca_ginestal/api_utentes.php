<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/database.php';
ensure_schema();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($path);
            break;
        case 'POST':
            handlePost($path);
            break;
        case 'PUT':
            handlePut($path);
            break;
        case 'DELETE':
            handleDelete($path);
            break;
        default:
            sendError('Método não suportado', 405);
    }
} catch (Exception $e) {
    sendError('Erro interno: ' . $e->getMessage(), 500);
}

function handleGet($path) {
    switch ($path) {
        case 'utentes':
            getUtentes();
            break;
        case 'utentes/search':
            searchUtentes();
            break;
        default:
            sendError('Endpoint não encontrado', 404);
    }
}

function handlePost($path) {
    switch ($path) {
        case 'utentes':
            createUtente();
            break;
        default:
            sendError('Endpoint não encontrado', 404);
    }
}

function handlePut($path) {
    switch ($path) {
        case 'utentes':
            updateUtente();
            break;
        default:
            sendError('Endpoint não encontrado', 404);
    }
}

function handleDelete($path) {
    switch ($path) {
        case 'utentes':
            deleteUtente();
            break;
        default:
            sendError('Endpoint não encontrado', 404);
    }
}

function getUtentes() {
    try {
        $utentes = execute_rest_sql('utente', ['ut_cod', 'ut_nome', 'ut_email', 'ut_turma', 'ut_ano'], [], 'ut_nome');
        sendSuccess($utentes);
    } catch (Exception $e) {
        sendError('Erro ao carregar utentes: ' . $e->getMessage(), 500);
    }
}

function searchUtentes() {
    $query = trim($_GET['q'] ?? '');
    
    if ($query === '') {
        getUtentes();
        return;
    }
    
    try {
        // Buscar todos os utentes e filtrar no PHP
        $all_utentes = execute_rest_sql('utente', ['ut_cod', 'ut_nome', 'ut_email', 'ut_turma', 'ut_ano'], [], 'ut_nome');
        $search_lower = strtolower($query);
        $resultado = [];
        
        foreach ($all_utentes as $utente) {
            if (strpos(strtolower($utente['ut_nome']), $search_lower) !== false) {
                $resultado[] = $utente;
            }
        }
        
        sendSuccess($resultado);
    } catch (Exception $e) {
        sendError('Erro ao pesquisar utentes: ' . $e->getMessage(), 500);
    }
}

function createUtente() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Dados JSON inválidos', 400);
        return;
    }
    
    $ut_nome = trim($input['ut_nome'] ?? '');
    $ut_email = trim($input['ut_email'] ?? '');
    $ut_turma = trim($input['ut_turma'] ?? '');
    $ut_ano = (int)($input['ut_ano'] ?? 0);
    
    // Validações
    if ($ut_nome === '') {
        sendError('Nome é obrigatório', 400);
        return;
    }
    if ($ut_email === '') {
        sendError('Email é obrigatório', 400);
        return;
    }
    if ($ut_turma === '') {
        sendError('Turma é obrigatória', 400);
        return;
    }
    if ($ut_ano < 7 || $ut_ano > 12) {
        sendError('Ano deve ser entre 7º e 12º ano', 400);
        return;
    }
    
    try {
        execute_rest_insert('utente', [
            'ut_nome' => $ut_nome,
            'ut_email' => $ut_email,
            'ut_turma' => $ut_turma,
            'ut_ano' => $ut_ano
        ]);
        
        sendSuccess(['message' => 'Utente criado com sucesso']);
    } catch (Exception $e) {
        sendError('Erro ao criar utente: ' . $e->getMessage(), 500);
    }
}

function updateUtente() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Dados JSON inválidos', 400);
        return;
    }
    
    $ut_cod = (int)($input['ut_cod'] ?? 0);
    $ut_nome = trim($input['ut_nome'] ?? '');
    $ut_email = trim($input['ut_email'] ?? '');
    $ut_turma = trim($input['ut_turma'] ?? '');
    $ut_ano = (int)($input['ut_ano'] ?? 0);
    
    if ($ut_cod <= 0) {
        sendError('ID do utente é obrigatório', 400);
        return;
    }
    
    // Validações
    if ($ut_nome === '') {
        sendError('Nome é obrigatório', 400);
        return;
    }
    if ($ut_email === '') {
        sendError('Email é obrigatório', 400);
        return;
    }
    if ($ut_turma === '') {
        sendError('Turma é obrigatória', 400);
        return;
    }
    if ($ut_ano < 7 || $ut_ano > 12) {
        sendError('Ano deve ser entre 7º e 12º ano', 400);
        return;
    }
    
    try {
        execute_rest_update('utente', [
            'ut_nome' => $ut_nome,
            'ut_email' => $ut_email,
            'ut_turma' => $ut_turma,
            'ut_ano' => $ut_ano
        ], ['ut_cod' => $ut_cod]);
        
        sendSuccess(['message' => 'Utente atualizado com sucesso']);
    } catch (Exception $e) {
        sendError('Erro ao atualizar utente: ' . $e->getMessage(), 500);
    }
}

function deleteUtente() {
    $ut_cod = (int)($_GET['id'] ?? 0);
    
    if ($ut_cod <= 0) {
        sendError('ID do utente é obrigatório', 400);
        return;
    }
    
    try {
        execute_rest_delete('utente', ['ut_cod' => $ut_cod]);
        sendSuccess(['message' => 'Utente eliminado com sucesso']);
    } catch (Exception $e) {
        sendError('Erro ao eliminar utente: ' . $e->getMessage(), 500);
    }
}

function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}
?>
