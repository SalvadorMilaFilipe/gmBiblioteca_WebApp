<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

try {
    $exemplar_cod = (int)($_POST['exemplar_cod'] ?? 0);
    $novo_estado = trim($_POST['novo_estado'] ?? '');
    $utente_cod = (int)($_POST['utente_cod'] ?? 0);
    $data_requisicao = trim($_POST['data_requisicao'] ?? '');
    $descricao_indisponivel = trim($_POST['descricao_indisponivel'] ?? '');
    $data_mudanca = trim($_POST['data_mudanca'] ?? '');
    
    // Validações básicas
    if ($exemplar_cod <= 0) {
        throw new Exception('Código do exemplar inválido');
    }
    
    if (!in_array($novo_estado, ['disponivel', 'emprestado', 'indisponivel'])) {
        throw new Exception('Estado inválido');
    }
    
    if ($novo_estado === 'emprestado') {
        if ($utente_cod <= 0) {
            throw new Exception('ID do utente é obrigatório para empréstimo');
        }
        if (empty($data_requisicao)) {
            throw new Exception('Data de requisição é obrigatória para empréstimo');
        }
    }
    
    if (empty($data_mudanca)) {
        $data_mudanca = date('Y-m-d');
    }
    
    // Verificar se o exemplar existe
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT ex_cod, ex_li_cod FROM livro_exemplar WHERE ex_cod = ?");
    $stmt->execute([$exemplar_cod]);
    $exemplar = $stmt->fetch();
    
    if (!$exemplar) {
        throw new Exception('Exemplar não encontrado');
    }
    
    // Verificar se o utente existe (se for empréstimo)
    if ($novo_estado === 'emprestado') {
        $stmt = $pdo->prepare("SELECT ut_cod FROM utente WHERE ut_cod = ?");
        $stmt->execute([$utente_cod]);
        if (!$stmt->fetch()) {
            throw new Exception('Utente não encontrado');
        }
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    try {
        // Atualizar estado do exemplar
        $disponivel = ($novo_estado === 'disponivel') ? 1 : 0;
        $permrequisicao = ($novo_estado === 'indisponivel') ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE livro_exemplar SET ex_disponivel = ?, ex_permrequisicao = ? WHERE ex_cod = ?");
        $stmt->execute([$disponivel, $permrequisicao, $exemplar_cod]);
        
        // Se mudando para emprestado, criar requisição
        if ($novo_estado === 'emprestado') {
            // Verificar se já existe uma requisição ativa para este exemplar
            $stmt = $pdo->prepare("SELECT re_cod FROM requisicao WHERE re_lexcod = ? AND re_datadevolucao IS NULL");
            $stmt->execute([$exemplar_cod]);
            $requisicao_existente = $stmt->fetch();
            
            if ($requisicao_existente) {
                // Atualizar requisição existente
                $stmt = $pdo->prepare("UPDATE requisicao SET re_utcod = ?, re_datarequisicao = ? WHERE re_cod = ?");
                $stmt->execute([$utente_cod, $data_requisicao, $requisicao_existente['re_cod']]);
            } else {
                // Criar nova requisição
                $stmt = $pdo->prepare("INSERT INTO requisicao (re_lexcod, re_utcod, re_datarequisicao) VALUES (?, ?, ?)");
                $stmt->execute([$exemplar_cod, $utente_cod, $data_requisicao]);
            }
        } else {
            // Se mudando para disponível ou indisponível, finalizar requisições ativas
            $stmt = $pdo->prepare("UPDATE requisicao SET re_datadevolucao = ? WHERE re_lexcod = ? AND re_datadevolucao IS NULL");
            $stmt->execute([$data_mudanca, $exemplar_cod]);
        }
        
        // Se for indisponível, podemos adicionar a descrição no campo ex_estado
        if ($novo_estado === 'indisponivel' && !empty($descricao_indisponivel)) {
            $stmt = $pdo->prepare("UPDATE livro_exemplar SET ex_estado = ? WHERE ex_cod = ?");
            $stmt->execute([$descricao_indisponivel, $exemplar_cod]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Estado do exemplar atualizado com sucesso'], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
