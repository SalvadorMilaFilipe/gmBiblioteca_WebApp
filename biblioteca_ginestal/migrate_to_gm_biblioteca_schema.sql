-- Script para migrar dados do schema public para gm_biblioteca
-- Execute este SQL no SQL Editor do Supabase

-- 1. Criar o schema gm_biblioteca se não existir
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- 2. Criar tabelas no novo schema (se não existirem)
CREATE TABLE IF NOT EXISTS gm_biblioteca.idioma (
    id_idioma TEXT PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome TEXT NOT NULL,
    ed_morada TEXT,
    ed_email TEXT,
    ed_codpostal TEXT,
    ed_tlm TEXT
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome TEXT NOT NULL,
    au_pais TEXT
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome TEXT NOT NULL,
    ut_email TEXT UNIQUE,
    ut_turma TEXT,
    ut_ano INTEGER
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.codigo_postal (
    cod_postal TEXT PRIMARY KEY,
    cod_localidade TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.genero (
    ge_genero TEXT PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.livros (
    li_cod SERIAL PRIMARY KEY,
    li_titulo TEXT NOT NULL,
    li_autor TEXT NOT NULL,
    li_isbn BIGINT,
    li_ed_cod INTEGER REFERENCES gm_biblioteca.editora(ed_cod),
    li_idioma TEXT REFERENCES gm_biblioteca.idioma(id_idioma),
    li_edicao INTEGER,
    li_ano INTEGER,
    li_genero TEXT REFERENCES gm_biblioteca.genero(ge_genero)
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER REFERENCES gm_biblioteca.livros(li_cod),
    ex_estado TEXT NOT NULL DEFAULT 'Bom',
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true
);

CREATE TABLE IF NOT EXISTS gm_biblioteca.requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER REFERENCES gm_biblioteca.livro_exemplar(ex_cod),
    re_utcod INTEGER REFERENCES gm_biblioteca.utente(ut_cod),
    re_datarequisicao DATE DEFAULT CURRENT_DATE,
    re_datadevolucao DATE
);

-- 3. Migrar dados existentes (se houver dados no schema public)
-- Nota: Execute apenas se tiver dados no schema public para migrar

-- Migrar idiomas
INSERT INTO gm_biblioteca.idioma (id_idioma)
SELECT id_idioma FROM public.idioma
ON CONFLICT (id_idioma) DO NOTHING;

-- Migrar editoras
INSERT INTO gm_biblioteca.editora (ed_cod, ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm)
SELECT ed_cod, ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm FROM public.editora
ON CONFLICT (ed_cod) DO NOTHING;

-- Migrar autores
INSERT INTO gm_biblioteca.autor (au_cod, au_nome, au_pais)
SELECT au_cod, au_nome, au_pais FROM public.autor
ON CONFLICT (au_cod) DO NOTHING;

-- Migrar utentes
INSERT INTO gm_biblioteca.utente (ut_cod, ut_nome, ut_email, ut_turma, ut_ano)
SELECT ut_cod, ut_nome, ut_email, ut_turma, ut_ano FROM public.utente
ON CONFLICT (ut_cod) DO NOTHING;

-- Migrar códigos postais
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade)
SELECT cod_postal, cod_localidade FROM public.codigo_postal
ON CONFLICT (cod_postal) DO NOTHING;

-- Migrar géneros
INSERT INTO gm_biblioteca.genero (ge_genero)
SELECT ge_genero FROM public.genero
ON CONFLICT (ge_genero) DO NOTHING;

-- Migrar livros
INSERT INTO gm_biblioteca.livros (li_cod, li_titulo, li_autor, li_isbn, li_ed_cod, li_idioma, li_edicao, li_ano, li_genero)
SELECT li_cod, li_titulo, li_autor, li_isbn, li_ed_cod, li_idioma, li_edicao, li_ano, li_genero FROM public.livros
ON CONFLICT (li_cod) DO NOTHING;

-- Migrar exemplares
INSERT INTO gm_biblioteca.livro_exemplar (ex_cod, ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao)
SELECT ex_cod, ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao FROM public.livro_exemplar
ON CONFLICT (ex_cod) DO NOTHING;

-- Migrar requisições
INSERT INTO gm_biblioteca.requisicao (re_cod, re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao)
SELECT re_cod, re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao FROM public.requisicao
ON CONFLICT (re_cod) DO NOTHING;

-- 4. Verificar se a migração foi bem-sucedida
SELECT 'Migração concluída! Verificando dados:' as status;

SELECT 'Idiomas migrados:' as tabela, COUNT(*) as total FROM gm_biblioteca.idioma;
SELECT 'Editoras migradas:' as tabela, COUNT(*) as total FROM gm_biblioteca.editora;
SELECT 'Autores migrados:' as tabela, COUNT(*) as total FROM gm_biblioteca.autor;
SELECT 'Utentes migrados:' as tabela, COUNT(*) as total FROM gm_biblioteca.utente;
SELECT 'Livros migrados:' as tabela, COUNT(*) as total FROM gm_biblioteca.livros;
SELECT 'Exemplares migrados:' as tabela, COUNT(*) as total FROM gm_biblioteca.livro_exemplar;
SELECT 'Requisições migradas:' as tabela, COUNT(*) as total FROM gm_biblioteca.requisicao;
