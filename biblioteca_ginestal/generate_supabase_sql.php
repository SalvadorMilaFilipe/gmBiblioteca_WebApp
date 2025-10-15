<?php
declare(strict_types=1);

echo "=== SQL PARA CRIAR TABELAS NO SUPABASE ===\n\n";
echo "Execute este SQL no painel do Supabase (SQL Editor):\n\n";

$sql = "
-- 1. Tabela de idiomas
CREATE TABLE IF NOT EXISTS idioma (
    id_idioma VARCHAR(10) PRIMARY KEY,
    id_pais VARCHAR(100) NOT NULL
);

-- 2. Tabela de géneros
CREATE TABLE IF NOT EXISTS genero (
    ge_genero VARCHAR(50) PRIMARY KEY
);

-- 3. Tabela de códigos postais
CREATE TABLE IF NOT EXISTS codigo_postal (
    cod_postal VARCHAR(10) PRIMARY KEY,
    cod_localidade VARCHAR(100) NOT NULL
);

-- 4. Tabela de editoras
CREATE TABLE IF NOT EXISTS editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome VARCHAR(200) NOT NULL,
    ed_morada TEXT NOT NULL,
    ed_email VARCHAR(100) NOT NULL,
    ed_codpostal VARCHAR(10) NOT NULL,
    ed_tlm VARCHAR(20) NOT NULL,
    FOREIGN KEY (ed_codpostal) REFERENCES codigo_postal(cod_postal)
);

-- 5. Tabela de autores
CREATE TABLE IF NOT EXISTS autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome VARCHAR(200) NOT NULL,
    au_pais VARCHAR(100) NOT NULL
);

-- 6. Tabela de utentes
CREATE TABLE IF NOT EXISTS utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome VARCHAR(200) NOT NULL,
    ut_email VARCHAR(100) NOT NULL,
    ut_turma VARCHAR(10) NOT NULL,
    ut_ano INTEGER NOT NULL CHECK (ut_ano >= 7 AND ut_ano <= 12)
);

-- 7. Tabela de livros
CREATE TABLE IF NOT EXISTS livros (
    li_cod SERIAL PRIMARY KEY,
    li_titulo VARCHAR(300) NOT NULL,
    li_autor TEXT NOT NULL,
    li_isbn BIGINT NOT NULL,
    li_editora INTEGER NOT NULL,
    li_idioma VARCHAR(10) NOT NULL,
    li_edicao INTEGER NOT NULL,
    li_ano INTEGER NOT NULL,
    li_genero VARCHAR(50) NOT NULL,
    FOREIGN KEY (li_editora) REFERENCES editora(ed_cod),
    FOREIGN KEY (li_idioma) REFERENCES idioma(id_idioma),
    FOREIGN KEY (li_genero) REFERENCES genero(ge_genero)
);

-- 8. Tabela de exemplares
CREATE TABLE IF NOT EXISTS livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER NOT NULL,
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true,
    ex_estado VARCHAR(50),
    FOREIGN KEY (ex_li_cod) REFERENCES livros(li_cod)
);

-- 9. Tabela de requisições
CREATE TABLE IF NOT EXISTS requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER NOT NULL,
    re_utcod INTEGER NOT NULL,
    re_datarequisicao DATE NOT NULL DEFAULT CURRENT_DATE,
    re_datadevolucao DATE,
    FOREIGN KEY (re_lexcod) REFERENCES livro_exemplar(ex_cod),
    FOREIGN KEY (re_utcod) REFERENCES utente(ut_cod)
);

-- Inserir dados iniciais
INSERT INTO idioma (id_idioma, id_pais) VALUES 
('pt-PT', 'Portugal'),
('en-GB', 'Reino Unido'),
('es-ES', 'Espanha'),
('fr-FR', 'França'),
('de-DE', 'Alemanha')
ON CONFLICT (id_idioma) DO NOTHING;

INSERT INTO genero (ge_genero) VALUES 
('Arte'),
('Autoajuda'),
('Aventura'),
('Biografia'),
('Drama'),
('Economia'),
('Educação'),
('Fantasia'),
('Ficção Científica'),
('Filosofia'),
('História'),
('Horror'),
('Humor'),
('Infantil'),
('Mistério'),
('Música'),
('Poesia'),
('Romance'),
('Tecnologia'),
('Thriller')
ON CONFLICT (ge_genero) DO NOTHING;
";

echo $sql;
echo "\n\n=== INSTRUÇÕES ===\n";
echo "1. Acesse o painel do Supabase: https://supabase.com/dashboard\n";
echo "2. Selecione o seu projeto: ibkzqvfpkuuwquiyhrtt\n";
echo "3. Vá para 'SQL Editor' no menu lateral\n";
echo "4. Cole o SQL acima e execute (botão 'Run')\n";
echo "5. Após criar as tabelas, execute: php test_supabase_connection.php\n\n";

// Salvar SQL em arquivo
file_put_contents('supabase_tables.sql', $sql);
echo "✓ SQL salvo em: supabase_tables.sql\n";
?>
