<?php
/**
 * EXEMPLO DE COMO ALTERAR UM ARQUIVO PARA USAR SUPABASE
 * 
 * Este arquivo mostra como alterar qualquer arquivo PHP para usar
 * a base de dados Supabase em vez da base local.
 */

// ANTES (usando base local):
// require_once __DIR__ . '/database.php';

// DEPOIS (usando Supabase):
require_once __DIR__ . '/database_supabase.php';

// O resto do código permanece exatamente igual!
// Todas as funções (get_pdo(), create_book(), etc.) funcionam da mesma forma

echo "Exemplo de migração - todas as funções funcionam igual!\n";
echo "Apenas mude a linha require_once no topo de cada arquivo.\n";
?>
