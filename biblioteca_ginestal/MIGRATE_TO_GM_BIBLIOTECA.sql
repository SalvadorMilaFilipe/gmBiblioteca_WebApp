-- ============================================================================
-- MIGRAÇÃO COMPLETA PARA SCHEMA gm_biblioteca
-- Execute este SQL no SQL Editor do Supabase
-- ============================================================================

-- 1. CRIAR O SCHEMA gm_biblioteca
-- ============================================================================
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- Definir o schema como padrão para as próximas operações
SET search_path TO gm_biblioteca, public;

-- 2. CRIAR TODAS AS TABELAS NO NOVO SCHEMA
-- ============================================================================

-- Tabela idioma
CREATE TABLE IF NOT EXISTS gm_biblioteca.idioma (
    id_idioma INTEGER PRIMARY KEY,
    id_nome TEXT NOT NULL
);

-- Tabela editora
CREATE TABLE IF NOT EXISTS gm_biblioteca.editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome TEXT NOT NULL,
    ed_morada TEXT,
    ed_email TEXT,
    ed_codpostal TEXT,
    ed_tlm TEXT
);

-- Tabela autor
CREATE TABLE IF NOT EXISTS gm_biblioteca.autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome TEXT NOT NULL,
    au_pais TEXT
);

-- Tabela utente
CREATE TABLE IF NOT EXISTS gm_biblioteca.utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome TEXT NOT NULL,
    ut_email TEXT UNIQUE,
    ut_turma TEXT,
    ut_ano INTEGER
);

-- Tabela codigo_postal
CREATE TABLE IF NOT EXISTS gm_biblioteca.codigo_postal (
    cod_postal TEXT PRIMARY KEY,
    cod_localidade TEXT NOT NULL
);

-- Tabela genero
CREATE TABLE IF NOT EXISTS gm_biblioteca.genero (
    ge_genero TEXT PRIMARY KEY
);

-- Tabela livros (com li_isbn como BIGINT)
CREATE TABLE IF NOT EXISTS gm_biblioteca.livros (
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
CREATE TABLE IF NOT EXISTS gm_biblioteca.livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER REFERENCES gm_biblioteca.livros(li_cod),
    ex_estado TEXT NOT NULL DEFAULT 'Bom',
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true
);

-- Tabela requisicao
CREATE TABLE IF NOT EXISTS gm_biblioteca.requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER REFERENCES gm_biblioteca.livro_exemplar(ex_cod),
    re_utcod INTEGER REFERENCES gm_biblioteca.utente(ut_cod),
    re_datarequisicao DATE DEFAULT CURRENT_DATE,
    re_datadevolucao DATE
);

-- 3. INSERIR DADOS INICIAIS (se não existirem)
-- ============================================================================

-- Idiomas
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (1, 'Português') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (2, 'Inglês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (3, 'Francês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (4, 'Espanhol') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (5, 'Alemão') ON CONFLICT (id_idioma) DO NOTHING;

-- Géneros
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Ficção Científica') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Romance') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Mistério') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Fantasia') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Não Ficção') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('História') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Biografia') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Arte') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Educação') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Tecnologia') ON CONFLICT (ge_genero) DO NOTHING;

-- Editoras de exemplo
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Editora Ginestal') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Porto Editora') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Leya') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Presença') ON CONFLICT DO NOTHING;

-- Códigos postais
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('1000-001', 'Lisboa') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('2000-001', 'Santarém') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('3000-001', 'Coimbra') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('4000-001', 'Porto') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('5000-001', 'Braga') ON CONFLICT (cod_postal) DO NOTHING;

-- Autores de exemplo
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('José Saramago', 'Portugal') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('Fernando Pessoa', 'Portugal') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('Eça de Queirós', 'Portugal') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('William Shakespeare', 'Reino Unido') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('George Orwell', 'Reino Unido') ON CONFLICT DO NOTHING;

-- Utente de exemplo
INSERT INTO gm_biblioteca.utente (ut_nome, ut_email, ut_turma, ut_ano) 
VALUES ('Administrador', 'admin@ginestal.edu.pt', 'ADMIN', 12) ON CONFLICT (ut_email) DO NOTHING;

-- 4. MIGRAR DADOS EXISTENTES (se houver dados no schema public)
-- ============================================================================

-- Migrar idiomas do public para gm_biblioteca
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome)
SELECT DISTINCT id_idioma, COALESCE(id_nome, 'Idioma ' || id_idioma::TEXT)
FROM public.idioma
WHERE id_idioma IS NOT NULL
ON CONFLICT (id_idioma) DO NOTHING;

-- Migrar editoras do public para gm_biblioteca
INSERT INTO gm_biblioteca.editora (ed_cod, ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm)
SELECT ed_cod, ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm 
FROM public.editora
ON CONFLICT (ed_cod) DO NOTHING;

-- Migrar autores do public para gm_biblioteca
INSERT INTO gm_biblioteca.autor (au_cod, au_nome, au_pais)
SELECT au_cod, au_nome, au_pais 
FROM public.autor
ON CONFLICT (au_cod) DO NOTHING;

-- Migrar utentes do public para gm_biblioteca
INSERT INTO gm_biblioteca.utente (ut_cod, ut_nome, ut_email, ut_turma, ut_ano)
SELECT ut_cod, ut_nome, ut_email, ut_turma, ut_ano 
FROM public.utente
ON CONFLICT (ut_cod) DO NOTHING;

-- Migrar códigos postais do public para gm_biblioteca
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade)
SELECT cod_postal, cod_localidade 
FROM public.codigo_postal
ON CONFLICT (cod_postal) DO NOTHING;

-- Migrar géneros do public para gm_biblioteca
INSERT INTO gm_biblioteca.genero (ge_genero)
SELECT DISTINCT ge_genero FROM public.genero
WHERE ge_genero IS NOT NULL
ON CONFLICT (ge_genero) DO NOTHING;

-- Migrar livros do public para gm_biblioteca (corrigindo o tipo do ISBN e idioma)
INSERT INTO gm_biblioteca.livros (li_cod, li_titulo, li_autor, li_isbn, li_ed_cod, li_idioma, li_edicao, li_ano, li_genero)
SELECT li_cod, li_titulo, li_autor, 
       CASE 
           WHEN li_isbn IS NOT NULL THEN li_isbn::BIGINT
           ELSE NULL
       END,
       li_ed_cod, 
       CASE 
           WHEN li_idioma ~ '^[0-9]+$' THEN li_idioma::INTEGER
           ELSE NULL
       END,
       li_edicao, li_ano, li_genero
FROM public.livros
ON CONFLICT (li_cod) DO NOTHING;

-- Migrar exemplares do public para gm_biblioteca
INSERT INTO gm_biblioteca.livro_exemplar (ex_cod, ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao)
SELECT ex_cod, ex_li_cod, 
       COALESCE(ex_estado, 'Bom'),
       COALESCE(ex_disponivel, true),
       COALESCE(ex_permrequisicao, true)
FROM public.livro_exemplar
ON CONFLICT (ex_cod) DO NOTHING;

-- Migrar requisições do public para gm_biblioteca
INSERT INTO gm_biblioteca.requisicao (re_cod, re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao)
SELECT re_cod, re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao
FROM public.requisicao
ON CONFLICT (re_cod) DO NOTHING;

-- 5. VERIFICAR E CORRIGIR SEQUÊNCIAS SERIAL
-- ============================================================================

-- Atualizar sequências para evitar conflitos de IDs
SELECT setval('gm_biblioteca.editora_ed_cod_seq', COALESCE((SELECT MAX(ed_cod) FROM gm_biblioteca.editora), 1), true);
SELECT setval('gm_biblioteca.autor_au_cod_seq', COALESCE((SELECT MAX(au_cod) FROM gm_biblioteca.autor), 1), true);
SELECT setval('gm_biblioteca.utente_ut_cod_seq', COALESCE((SELECT MAX(ut_cod) FROM gm_biblioteca.utente), 1), true);
SELECT setval('gm_biblioteca.livros_li_cod_seq', COALESCE((SELECT MAX(li_cod) FROM gm_biblioteca.livros), 1), true);
SELECT setval('gm_biblioteca.livro_exemplar_ex_cod_seq', COALESCE((SELECT MAX(ex_cod) FROM gm_biblioteca.livro_exemplar), 1), true);
SELECT setval('gm_biblioteca.requisicao_re_cod_seq', COALESCE((SELECT MAX(re_cod) FROM gm_biblioteca.requisicao), 1), true);

-- 6. VERIFICAÇÃO FINAL E RELATÓRIO
-- ============================================================================

-- Mostrar estatísticas da migração
SELECT '=== RELATÓRIO DE MIGRAÇÃO ===' as status;

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

-- Verificar integridade das foreign keys
SELECT '=== VERIFICAÇÃO DE INTEGRIDADE ===' as status;

-- Verificar livros com editoras válidas
SELECT 'Livros com editoras inválidas:' as verificação, COUNT(*) as total
FROM gm_biblioteca.livros l
LEFT JOIN gm_biblioteca.editora e ON l.li_ed_cod = e.ed_cod
WHERE l.li_ed_cod IS NOT NULL AND e.ed_cod IS NULL;

-- Verificar livros com idiomas válidos
SELECT 'Livros com idiomas inválidos:' as verificação, COUNT(*) as total
FROM gm_biblioteca.livros l
LEFT JOIN gm_biblioteca.idioma i ON l.li_idioma = i.id_idioma
WHERE l.li_idioma IS NOT NULL AND i.id_idioma IS NULL;

-- Verificar livros com géneros válidos
SELECT 'Livros com géneros inválidos:' as verificação, COUNT(*) as total
FROM gm_biblioteca.livros l
LEFT JOIN gm_biblioteca.genero g ON l.li_genero = g.ge_genero
WHERE l.li_genero IS NOT NULL AND g.ge_genero IS NULL;

-- Verificar exemplares com livros válidos
SELECT 'Exemplares com livros inválidos:' as verificação, COUNT(*) as total
FROM gm_biblioteca.livro_exemplar ex
LEFT JOIN gm_biblioteca.livros l ON ex.ex_li_cod = l.li_cod
WHERE ex.ex_li_cod IS NOT NULL AND l.li_cod IS NULL;

-- Verificar requisições com exemplares válidos
SELECT 'Requisições com exemplares inválidos:' as verificação, COUNT(*) as total
FROM gm_biblioteca.requisicao r
LEFT JOIN gm_biblioteca.livro_exemplar ex ON r.re_lexcod = ex.ex_cod
WHERE r.re_lexcod IS NOT NULL AND ex.ex_cod IS NULL;

-- Verificar requisições com utentes válidos
SELECT 'Requisições com utentes inválidos:' as verificação, COUNT(*) as total
FROM gm_biblioteca.requisicao r
LEFT JOIN gm_biblioteca.utente u ON r.re_utcod = u.ut_cod
WHERE r.re_utcod IS NOT NULL AND u.ut_cod IS NULL;

SELECT '=== MIGRAÇÃO CONCLUÍDA COM SUCESSO! ===' as status;
SELECT 'O schema gm_biblioteca está pronto para uso.' as mensagem;
