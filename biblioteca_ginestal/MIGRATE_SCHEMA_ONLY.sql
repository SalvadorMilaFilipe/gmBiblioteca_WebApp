-- ============================================================================
-- MIGRAÇÃO APENAS DA ESTRUTURA - SEM DADOS EXISTENTES
-- Execute este SQL no SQL Editor do Supabase
-- ============================================================================

-- 1. CRIAR O SCHEMA gm_biblioteca
-- ============================================================================
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- Definir o schema como padrão para as próximas operações
SET search_path TO gm_biblioteca, public;

-- 2. VERIFICAR ESTRUTURA ATUAL DAS TABELAS EXISTENTES
-- ============================================================================

-- Verificar estrutura da tabela idioma existente
SELECT 'Estrutura da tabela public.idioma:' as info;
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns 
WHERE table_schema = 'public' AND table_name = 'idioma'
ORDER BY ordinal_position;

-- Verificar estrutura da tabela editora existente
SELECT 'Estrutura da tabela public.editora:' as info;
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns 
WHERE table_schema = 'public' AND table_name = 'editora'
ORDER BY ordinal_position;

-- 3. CRIAR TABELAS NO SCHEMA gm_biblioteca (SEM DADOS)
-- ============================================================================

-- Dropar tabelas se existirem (para recriar com estrutura correta)
DROP TABLE IF EXISTS gm_biblioteca.requisicao CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.livro_exemplar CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.livros CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.utente CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.autor CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.editora CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.codigo_postal CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.genero CASCADE;
DROP TABLE IF EXISTS gm_biblioteca.idioma CASCADE;

-- Tabela codigo_postal
CREATE TABLE gm_biblioteca.codigo_postal (
    cod_postal TEXT PRIMARY KEY,
    cod_localidade TEXT NOT NULL
);

-- Tabela idioma (estrutura simples)
CREATE TABLE gm_biblioteca.idioma (
    id_idioma INTEGER PRIMARY KEY,
    id_nome TEXT NOT NULL
);

-- Tabela editora
CREATE TABLE gm_biblioteca.editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome TEXT NOT NULL,
    ed_morada TEXT,
    ed_email TEXT,
    ed_codpostal TEXT,
    ed_tlm TEXT
);

-- Tabela autor
CREATE TABLE gm_biblioteca.autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome TEXT NOT NULL,
    au_pais TEXT
);

-- Tabela utente
CREATE TABLE gm_biblioteca.utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome TEXT NOT NULL,
    ut_email TEXT UNIQUE,
    ut_turma TEXT,
    ut_ano INTEGER
);

-- Tabela genero
CREATE TABLE gm_biblioteca.genero (
    ge_genero TEXT PRIMARY KEY
);

-- Tabela livros (com li_isbn como BIGINT)
CREATE TABLE gm_biblioteca.livros (
    li_cod SERIAL PRIMARY KEY,
    li_titulo TEXT NOT NULL,
    li_autor TEXT NOT NULL,
    li_isbn BIGINT,
    li_ed_cod INTEGER REFERENCES gm_biblioteca.editora(ed_cod),
    li_idioma INTEGER REFERENCES gm_biblioteca.idioma(id_idioma),
    li_edicao INTEGER,
    li_ano INTEGER,
    li_genero TEXT REFERENCES gm_biblioteca.genero(ge_genero)
);

-- Tabela livro_exemplar
CREATE TABLE gm_biblioteca.livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER REFERENCES gm_biblioteca.livros(li_cod),
    ex_estado TEXT NOT NULL DEFAULT 'Bom',
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true
);

-- Tabela requisicao
CREATE TABLE gm_biblioteca.requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER REFERENCES gm_biblioteca.livro_exemplar(ex_cod),
    re_utcod INTEGER REFERENCES gm_biblioteca.utente(ut_cod),
    re_datarequisicao DATE DEFAULT CURRENT_DATE,
    re_datadevolucao DATE
);

-- 4. INSERIR APENAS DADOS BÁSICOS ESSENCIAIS
-- ============================================================================

-- Idiomas básicos
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES 
(1, 'Português'),
(2, 'Inglês'),
(3, 'Francês'),
(4, 'Espanhol'),
(5, 'Alemão')
ON CONFLICT (id_idioma) DO NOTHING;

-- Géneros básicos
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES 
('Ficção Científica'),
('Romance'),
('Mistério'),
('Fantasia'),
('Não Ficção'),
('História'),
('Biografia'),
('Arte'),
('Educação'),
('Tecnologia')
ON CONFLICT (ge_genero) DO NOTHING;

-- Editoras básicas
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES 
('Editora Ginestal'),
('Porto Editora'),
('Leya'),
('Presença')
ON CONFLICT DO NOTHING;

-- Códigos postais básicos
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES 
('1000-001', 'Lisboa'),
('2000-001', 'Santarém'),
('3000-001', 'Coimbra'),
('4000-001', 'Porto'),
('5000-001', 'Braga')
ON CONFLICT (cod_postal) DO NOTHING;

-- Autores básicos
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES 
('José Saramago', 'Portugal'),
('Fernando Pessoa', 'Portugal'),
('Eça de Queirós', 'Portugal'),
('William Shakespeare', 'Reino Unido'),
('George Orwell', 'Reino Unido')
ON CONFLICT DO NOTHING;

-- Utente administrador
INSERT INTO gm_biblioteca.utente (ut_nome, ut_email, ut_turma, ut_ano) VALUES 
('Administrador', 'admin@ginestal.edu.pt', 'ADMIN', 12)
ON CONFLICT (ut_email) DO NOTHING;

-- 5. VERIFICAÇÃO FINAL
-- ============================================================================

-- Mostrar estatísticas das tabelas criadas
SELECT '=== ESTRUTURA CRIADA COM SUCESSO ===' as status;

SELECT 'Idiomas:' as tabela, COUNT(*) as total FROM gm_biblioteca.idioma
UNION ALL
SELECT 'Editoras:' as tabela, COUNT(*) as total FROM gm_biblioteca.editora
UNION ALL
SELECT 'Autores:' as tabela, COUNT(*) as total FROM gm_biblioteca.autor
UNION ALL
SELECT 'Utentes:' as tabela, COUNT(*) as total FROM gm_biblioteca.utente
UNION ALL
SELECT 'Códigos Postais:' as tabela, COUNT(*) as total FROM gm_biblioteca.codigo_postal
UNION ALL
SELECT 'Géneros:' as tabela, COUNT(*) as total FROM gm_biblioteca.genero
UNION ALL
SELECT 'Livros:' as tabela, COUNT(*) as total FROM gm_biblioteca.livros
UNION ALL
SELECT 'Exemplares:' as tabela, COUNT(*) as total FROM gm_biblioteca.livro_exemplar
UNION ALL
SELECT 'Requisições:' as tabela, COUNT(*) as total FROM gm_biblioteca.requisicao;

-- Verificar estrutura das tabelas criadas
SELECT 'Estrutura da tabela gm_biblioteca.idioma:' as info;
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns 
WHERE table_schema = 'gm_biblioteca' AND table_name = 'idioma'
ORDER BY ordinal_position;

SELECT 'Estrutura da tabela gm_biblioteca.livros:' as info;
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns 
WHERE table_schema = 'gm_biblioteca' AND table_name = 'livros'
ORDER BY ordinal_position;

SELECT '=== MIGRAÇÃO CONCLUÍDA ===' as status;
SELECT 'Schema gm_biblioteca criado com estrutura limpa e dados básicos.' as mensagem;
SELECT 'Agora pode usar a aplicação normalmente.' as proximo_passo;
