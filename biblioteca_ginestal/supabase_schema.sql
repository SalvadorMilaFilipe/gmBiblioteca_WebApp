-- Schema para Biblioteca Ginestal - Supabase
-- Execute este SQL no SQL Editor do Supabase

-- Criar schema personalizado
CREATE SCHEMA IF NOT EXISTS gm_biblioteca;

-- Definir schema como padrão para as próximas operações
SET search_path TO gm_biblioteca;

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

-- Tabela livros
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

-- Dados iniciais
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (1, 'Português') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (2, 'Inglês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (3, 'Francês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO gm_biblioteca.idioma (id_idioma, id_nome) VALUES (4, 'Espanhol') ON CONFLICT (id_idioma) DO NOTHING;

INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Ficção Científica') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Romance') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Mistério') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Fantasia') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO gm_biblioteca.genero (ge_genero) VALUES ('Não Ficção') ON CONFLICT (ge_genero) DO NOTHING;

INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Editora A') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Editora B') ON CONFLICT DO NOTHING;
INSERT INTO gm_biblioteca.editora (ed_nome) VALUES ('Editora C') ON CONFLICT DO NOTHING;

INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('1000-001', 'Lisboa') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('2000-001', 'Santarém') ON CONFLICT (cod_postal) DO NOTHING;
INSERT INTO gm_biblioteca.codigo_postal (cod_postal, cod_localidade) VALUES ('3000-001', 'Coimbra') ON CONFLICT (cod_postal) DO NOTHING;

INSERT INTO gm_biblioteca.autor (au_nome, au_pais) VALUES ('Autor Teste', 'Portugal') ON CONFLICT DO NOTHING;

INSERT INTO gm_biblioteca.utente (ut_nome, ut_email) VALUES ('Utente Teste', 'teste@example.com') ON CONFLICT (ut_email) DO NOTHING;

-- Adicionar um livro de teste
INSERT INTO gm_biblioteca.livros (li_titulo, li_autor, li_ano, li_edicao, li_genero) 
VALUES ('Livro Teste', 'Autor Teste', 2024, 1, 'Ficção Científica') ON CONFLICT DO NOTHING;

-- Adicionar exemplar do livro
INSERT INTO gm_biblioteca.livro_exemplar (ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao)
SELECT li_cod, 'Bom', true, true FROM gm_biblioteca.livros WHERE li_titulo = 'Livro Teste' LIMIT 1;
