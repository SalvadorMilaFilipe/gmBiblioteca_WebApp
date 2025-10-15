-- ============================================================================
-- CONFIGURAR SCHEMA gm_biblioteca NO SUPABASE
-- Execute este SQL no SQL Editor do Supabase PRIMEIRO
-- ============================================================================

-- 1. CRIAR O SCHEMA gm_biblioteca
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- 2. CONFIGURAR O SCHEMA PARA A API REST
-- Adicionar o schema à lista de schemas expostos pela API REST
INSERT INTO pg_catalog.pg_namespace (nspname) VALUES ('gm_biblioteca') ON CONFLICT DO NOTHING;

-- 3. CONFIGURAR PERMISSÕES DO SCHEMA
-- Dar permissões necessárias ao usuário anon para acessar o schema
GRANT USAGE ON SCHEMA gm_biblioteca TO anon;
GRANT USAGE ON SCHEMA gm_biblioteca TO authenticated;
GRANT USAGE ON SCHEMA gm_biblioteca TO service_role;

-- 4. CONFIGURAR RLS (Row Level Security) NO SCHEMA
ALTER SCHEMA gm_biblioteca OWNER TO postgres;

-- 5. VERIFICAR SE O SCHEMA FOI CRIADO
SELECT 'Schema gm_biblioteca criado com sucesso!' as status;
SELECT schemaname FROM pg_tables WHERE schemaname = 'gm_biblioteca';

-- 6. MOSTRAR TODOS OS SCHEMAS DISPONÍVEIS
SELECT 'Schemas disponíveis:' as info;
SELECT nspname as schema_name FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema';

SELECT '=== CONFIGURAÇÃO CONCLUÍDA ===' as status;
