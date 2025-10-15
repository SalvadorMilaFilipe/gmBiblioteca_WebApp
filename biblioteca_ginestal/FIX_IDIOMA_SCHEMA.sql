-- ============================================================================
-- CORREÇÃO RÁPIDA PARA O PROBLEMA DE INCOMPATIBILIDADE DE TIPOS DOS IDIOMAS
-- Execute este SQL no SQL Editor do Supabase ANTES do script principal
-- ============================================================================

-- 1. Verificar a estrutura atual da tabela idioma no schema public
SELECT 
    'Estrutura atual da tabela public.idioma:' as info,
    column_name, 
    data_type, 
    is_nullable
FROM information_schema.columns 
WHERE table_schema = 'public' AND table_name = 'idioma'
ORDER BY ordinal_position;

-- 2. Criar o schema gm_biblioteca se não existir
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- 3. Criar a tabela idioma no schema gm_biblioteca com a estrutura correta
-- (usando INTEGER para compatibilidade com a tabela existente)
CREATE TABLE IF NOT EXISTS gm_biblioteca.idioma (
    id_idioma INTEGER PRIMARY KEY,
    id_nome TEXT NOT NULL
);

-- 4. Migrar dados de idiomas do public para gm_biblioteca
-- (adaptando-se à estrutura existente)
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome)
SELECT 
    id_idioma,
    COALESCE(id_nome, 'Idioma ' || id_idioma::TEXT) as id_nome
FROM public.idioma
ON CONFLICT (id_idioma) DO NOTHING;

-- 5. Se não houver dados na tabela public.idioma, inserir dados básicos
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) 
VALUES 
    (1, 'Português'),
    (2, 'Inglês'),
    (3, 'Francês'),
    (4, 'Espanhol'),
    (5, 'Alemão')
ON CONFLICT (id_idioma) DO NOTHING;

-- 6. Verificar se a migração foi bem-sucedida
SELECT 'Idiomas migrados para gm_biblioteca:' as status, COUNT(*) as total FROM gm_biblioteca.idioma;

-- 7. Mostrar os idiomas migrados
SELECT 'Idiomas disponíveis:' as info;
SELECT id_idioma, id_nome FROM gm_biblioteca.idioma ORDER BY id_idioma;

SELECT '=== CORREÇÃO CONCLUÍDA ===' as status;
SELECT 'Agora pode executar o script principal MIGRATE_TO_GM_BIBLIOTECA.sql' as proximo_passo;
