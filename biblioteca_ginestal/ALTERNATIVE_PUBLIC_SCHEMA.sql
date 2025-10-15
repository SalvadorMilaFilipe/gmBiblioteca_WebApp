-- ============================================================================
-- ALTERNATIVA: USAR SCHEMA PUBLIC COM PREFIXO NAS TABELAS
-- Execute este SQL no SQL Editor do Supabase se o schema personalizado não funcionar
-- ============================================================================

-- 1. CRIAR TABELAS NO SCHEMA PUBLIC COM PREFIXO gm_
-- ============================================================================

-- Tabela gm_codigo_postal
CREATE TABLE IF NOT EXISTS gm_codigo_postal (
    cod_postal TEXT PRIMARY KEY,
    cod_localidade TEXT NOT NULL
);

-- Tabela gm_idioma
CREATE TABLE IF NOT EXISTS gm_idioma (
    id_idioma INTEGER PRIMARY KEY,
    id_nome TEXT NOT NULL
);

-- Tabela gm_editora
CREATE TABLE IF NOT EXISTS gm_editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome TEXT NOT NULL,
    ed_morada TEXT,
    ed_email TEXT,
    ed_codpostal TEXT,
    ed_tlm TEXT
);

-- Tabela gm_autor
CREATE TABLE IF NOT EXISTS gm_autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome TEXT NOT NULL,
    au_pais TEXT
);

-- Tabela gm_utente
CREATE TABLE IF NOT EXISTS gm_utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome TEXT NOT NULL,
    ut_email TEXT UNIQUE,
    ut_turma TEXT,
    ut_ano INTEGER
);

-- Tabela gm_genero
CREATE TABLE IF NOT EXISTS gm_genero (
    ge_genero TEXT PRIMARY KEY
);

-- Tabela gm_livros (com li_isbn como BIGINT)
CREATE TABLE IF NOT EXISTS gm_livros (
    li_cod SERIAL PRIMARY KEY,
    li_titulo TEXT NOT NULL,
    li_autor TEXT NOT NULL,
    li_isbn BIGINT,
    li_ed_cod INTEGER REFERENCES gm_editora(ed_cod),
    li_idioma INTEGER REFERENCES gm_idioma(id_idioma),
    li_edicao INTEGER,
    li_ano INTEGER,
    li_genero TEXT REFERENCES gm_genero(ge_genero)
);

-- Tabela gm_livro_exemplar
CREATE TABLE IF NOT EXISTS gm_livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER REFERENCES gm_livros(li_cod),
    ex_estado TEXT NOT NULL DEFAULT 'Bom',
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true
);

-- Tabela gm_requisicao
CREATE TABLE IF NOT EXISTS gm_requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER REFERENCES gm_livro_exemplar(ex_cod),
    re_utcod INTEGER REFERENCES gm_utente(ut_cod),
    re_datarequisicao DATE DEFAULT CURRENT_DATE,
    re_datadevolucao DATE
);

-- 2. CONFIGURAR RLS (Row Level Security)
-- ============================================================================

-- Ativar RLS nas tabelas
ALTER TABLE gm_codigo_postal ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_idioma ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_editora ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_autor ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_utente ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_genero ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_livros ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_livro_exemplar ENABLE ROW LEVEL SECURITY;
ALTER TABLE gm_requisicao ENABLE ROW LEVEL SECURITY;

-- Criar políticas para permitir acesso completo (ajustar conforme necessário)
CREATE POLICY "Allow all operations on gm_codigo_postal" ON gm_codigo_postal FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_idioma" ON gm_idioma FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_editora" ON gm_editora FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_autor" ON gm_autor FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_utente" ON gm_utente FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_genero" ON gm_genero FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_livros" ON gm_livros FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_livro_exemplar" ON gm_livro_exemplar FOR ALL USING (true);
CREATE POLICY "Allow all operations on gm_requisicao" ON gm_requisicao FOR ALL USING (true);

-- 3. INSERIR DADOS BÁSICOS ESSENCIAIS
-- ============================================================================

-- Idiomas básicos
INSERT INTO gm_idioma (id_idioma, id_nome) VALUES 
(1, 'Português'),
(2, 'Inglês'),
(3, 'Francês'),
(4, 'Espanhol'),
(5, 'Alemão')
ON CONFLICT (id_idioma) DO NOTHING;

-- Géneros básicos
INSERT INTO gm_genero (ge_genero) VALUES 
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
INSERT INTO gm_editora (ed_nome) VALUES 
('Editora Ginestal'),
('Porto Editora'),
('Leya'),
('Presença')
ON CONFLICT DO NOTHING;

-- Códigos postais básicos
INSERT INTO gm_codigo_postal (cod_postal, cod_localidade) VALUES 
('1000-001', 'Lisboa'),
('2000-001', 'Santarém'),
('3000-001', 'Coimbra'),
('4000-001', 'Porto'),
('5000-001', 'Braga')
ON CONFLICT (cod_postal) DO NOTHING;

-- Autores básicos
INSERT INTO gm_autor (au_nome, au_pais) VALUES 
('José Saramago', 'Portugal'),
('Fernando Pessoa', 'Portugal'),
('Eça de Queirós', 'Portugal'),
('William Shakespeare', 'Reino Unido'),
('George Orwell', 'Reino Unido')
ON CONFLICT DO NOTHING;

-- Utente administrador
INSERT INTO gm_utente (ut_nome, ut_email, ut_turma, ut_ano) VALUES 
('Administrador', 'admin@ginestal.edu.pt', 'ADMIN', 12)
ON CONFLICT (ut_email) DO NOTHING;

-- 4. VERIFICAÇÃO FINAL
-- ============================================================================

-- Mostrar estatísticas das tabelas criadas
SELECT '=== ESTRUTURA CRIADA COM SUCESSO ===' as status;

SELECT 'Idiomas:' as tabela, COUNT(*) as total FROM gm_idioma
UNION ALL
SELECT 'Editoras:' as tabela, COUNT(*) as total FROM gm_editora
UNION ALL
SELECT 'Autores:' as tabela, COUNT(*) as total FROM gm_autor
UNION ALL
SELECT 'Utentes:' as tabela, COUNT(*) as total FROM gm_utente
UNION ALL
SELECT 'Códigos Postais:' as tabela, COUNT(*) as total FROM gm_codigo_postal
UNION ALL
SELECT 'Géneros:' as tabela, COUNT(*) as total FROM gm_genero
UNION ALL
SELECT 'Livros:' as tabela, COUNT(*) as total FROM gm_livros
UNION ALL
SELECT 'Exemplares:' as tabela, COUNT(*) as total FROM gm_livro_exemplar
UNION ALL
SELECT 'Requisições:' as tabela, COUNT(*) as total FROM gm_requisicao;

SELECT '=== MIGRAÇÃO CONCLUÍDA ===' as status;
SELECT 'Tabelas criadas no schema public com prefixo gm_' as mensagem;
SELECT 'Agora atualize o código PHP para usar as tabelas com prefixo.' as proximo_passo;
