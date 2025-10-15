<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

// Executar correção dos status dos exemplares
try {
    fix_exemplar_status();
    echo "✅ Status dos exemplares corrigido com sucesso!<br>";
    echo "Agora os exemplares emprestados devem aparecer corretamente no modal.<br>";
    echo "<a href='disponiveis.php'>Voltar para disponíveis</a>";
} catch (Exception $e) {
    echo "❌ Erro ao corrigir status: " . $e->getMessage();
}
?>
