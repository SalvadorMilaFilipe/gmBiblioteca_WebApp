<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Aceitar tanto JSON quanto FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Dados JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Dados JSON inválidos');
        }
        $exemplar_cod = (int)($input['exemplar_cod'] ?? 0);
        $novo_estado = trim($input['novo_estado'] ?? '');
        $utente_cod = (int)($input['utente_cod'] ?? 0);
        $data_requisicao = trim($input['data_requisicao'] ?? '');
        $data_mudanca = trim($input['data_mudanca'] ?? '');
    } else {
        // Dados de formulário (FormData)
        $exemplar_cod = (int)($_POST['exemplar_cod'] ?? 0);
        $novo_estado = trim($_POST['novo_estado'] ?? '');
        $utente_cod = (int)($_POST['utente_cod'] ?? 0);
        $data_requisicao = trim($_POST['data_requisicao'] ?? '');
        $data_mudanca = trim($_POST['data_mudanca'] ?? '');
    }
    
    if ($exemplar_cod <= 0) {
        throw new Exception('Código do exemplar inválido');
    }
    
    if (!in_array($novo_estado, ['disponivel', 'emprestado', 'indisponivel'])) {
        throw new Exception('Estado inválido');
    }
    
    if ($novo_estado === 'emprestado' && $utente_cod <= 0) {
        throw new Exception('Código do utente obrigatório para empréstimo');
    }
    
    if (empty($data_requisicao)) {
        $data_requisicao = date('Y-m-d');
    }
    
    if (empty($data_mudanca)) {
        $data_mudanca = date('Y-m-d');
    }
    
    // Verificar se o exemplar existe usando API REST
    $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod', 'ex_li_cod'], ['ex_cod' => $exemplar_cod]);
    
    if (count($exemplares) === 0) {
        throw new Exception('Exemplar não encontrado');
    }
    
    $exemplar = $exemplares[0];
    
    // Verificar se o utente existe (se for empréstimo) usando API REST
    if ($novo_estado === 'emprestado') {
        $utentes = execute_rest_sql('utente', ['ut_cod'], ['ut_cod' => $utente_cod]);
        if (count($utentes) === 0) {
            throw new Exception('Utente não encontrado');
        }
    }
    
    try {
        // Atualizar estado do exemplar usando API REST
        $disponivel = ($novo_estado === 'disponivel') ? true : false;
        $permrequisicao = ($novo_estado === 'indisponivel') ? false : true;
        
        execute_rest_update('livro_exemplar', [
            'ex_disponivel' => $disponivel,
            'ex_permrequisicao' => $permrequisicao
        ], ['ex_cod' => $exemplar_cod]);
        
        // Se mudando para emprestado, criar requisição
        if ($novo_estado === 'emprestado') {
            // Verificar se já existe uma requisição ativa para este exemplar
            $todas_requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_datadevolucao'], [
                're_lexcod' => $exemplar_cod
            ]);
            
            // Filtrar apenas as que não têm data de devolução
            $requisicoes = array_filter($todas_requisicoes, function($req) {
                return empty($req['re_datadevolucao']) || $req['re_datadevolucao'] === null;
            });
            
            if (count($requisicoes) > 0) {
                // Atualizar requisição existente
                $requisicao_existente = $requisicoes[0];
                execute_rest_update('requisicao', [
                    're_utcod' => $utente_cod,
                    're_datarequisicao' => $data_requisicao
                ], ['re_cod' => $requisicao_existente['re_cod']]);
            } else {
                // Criar nova requisição
                execute_rest_insert('requisicao', [
                    're_lexcod' => $exemplar_cod,
                    're_utcod' => $utente_cod,
                    're_datarequisicao' => $data_requisicao
                ]);
            }
        }
        
        // Se mudando para disponível, marcar requisição como devolvida
        if ($novo_estado === 'disponivel') {
            $todas_requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_datadevolucao'], [
                're_lexcod' => $exemplar_cod
            ]);
            
            // Filtrar apenas as que não têm data de devolução
            $requisicoes = array_filter($todas_requisicoes, function($req) {
                return empty($req['re_datadevolucao']) || $req['re_datadevolucao'] === null;
            });
            
            if (count($requisicoes) > 0) {
                $requisicao = $requisicoes[0];
                execute_rest_update('requisicao', [
                    're_datadevolucao' => $data_mudanca
                ], ['re_cod' => $requisicao['re_cod']]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado do exemplar atualizado com sucesso'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Erro ao atualizar exemplar: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>