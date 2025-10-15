# 🚀 SETUP SUPABASE - Biblioteca Ginestal

## ⚠️ PROBLEMA IDENTIFICADO
As tabelas **NÃO EXISTEM** no seu projeto Supabase. Por isso a aplicação não funciona.

## ✅ SOLUÇÃO SIMPLES

### 1. Acesse o Painel do Supabase
- URL: https://supabase.com/dashboard
- Faça login na sua conta
- Selecione o projeto: `ibkzqvfpkuuwquiyhrtt`

### 2. Vá para SQL Editor
- No menu lateral, clique em **"SQL Editor"**
- Clique em **"New query"**

### 3. Execute o SQL
Copie e cole **TODO** o conteúdo do arquivo `supabase_schema.sql` e execute:

```sql
-- Schema para Biblioteca Ginestal - Supabase
-- Execute este SQL no SQL Editor do Supabase

-- Tabela idioma
CREATE TABLE IF NOT EXISTS idioma (
    id_idioma TEXT PRIMARY KEY
);

-- Tabela editora
CREATE TABLE IF NOT EXISTS editora (
    ed_cod SERIAL PRIMARY KEY,
    ed_nome TEXT NOT NULL
);

-- Tabela autor
CREATE TABLE IF NOT EXISTS autor (
    au_cod SERIAL PRIMARY KEY,
    au_nome TEXT NOT NULL,
    au_pais TEXT
);

-- Tabela utente
CREATE TABLE IF NOT EXISTS utente (
    ut_cod SERIAL PRIMARY KEY,
    ut_nome TEXT NOT NULL,
    ut_email TEXT UNIQUE
);

-- Tabela genero
CREATE TABLE IF NOT EXISTS genero (
    ge_genero TEXT PRIMARY KEY
);

-- Tabela livros
CREATE TABLE IF NOT EXISTS livros (
    li_cod SERIAL PRIMARY KEY,
    li_titulo TEXT NOT NULL,
    li_autor TEXT NOT NULL,
    li_isbn INTEGER,
    li_ed_cod INTEGER REFERENCES editora(ed_cod),
    li_idioma TEXT REFERENCES idioma(id_idioma),
    li_edicao INTEGER,
    li_ano INTEGER,
    li_genero TEXT REFERENCES genero(ge_genero)
);

-- Tabela livro_exemplar
CREATE TABLE IF NOT EXISTS livro_exemplar (
    ex_cod SERIAL PRIMARY KEY,
    ex_li_cod INTEGER REFERENCES livros(li_cod),
    ex_estado TEXT NOT NULL DEFAULT 'Bom',
    ex_disponivel BOOLEAN DEFAULT true,
    ex_permrequisicao BOOLEAN DEFAULT true
);

-- Tabela requisicao
CREATE TABLE IF NOT EXISTS requisicao (
    re_cod SERIAL PRIMARY KEY,
    re_lexcod INTEGER REFERENCES livro_exemplar(ex_cod),
    re_utcod INTEGER REFERENCES utente(ut_cod),
    re_datarequisicao DATE DEFAULT CURRENT_DATE,
    re_datadevolucao DATE
);

-- Dados iniciais
INSERT INTO idioma (id_idioma) VALUES ('Português') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO idioma (id_idioma) VALUES ('Inglês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO idioma (id_idioma) VALUES ('Francês') ON CONFLICT (id_idioma) DO NOTHING;
INSERT INTO idioma (id_idioma) VALUES ('Espanhol') ON CONFLICT (id_idioma) DO NOTHING;

INSERT INTO genero (ge_genero) VALUES ('Ficção Científica') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO genero (ge_genero) VALUES ('Romance') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO genero (ge_genero) VALUES ('Mistério') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO genero (ge_genero) VALUES ('Fantasia') ON CONFLICT (ge_genero) DO NOTHING;
INSERT INTO genero (ge_genero) VALUES ('Não Ficção') ON CONFLICT (ge_genero) DO NOTHING;

INSERT INTO editora (ed_nome) VALUES ('Editora A') ON CONFLICT DO NOTHING;
INSERT INTO editora (ed_nome) VALUES ('Editora B') ON CONFLICT DO NOTHING;
INSERT INTO editora (ed_nome) VALUES ('Editora C') ON CONFLICT DO NOTHING;

INSERT INTO autor (au_nome, au_pais) VALUES ('Autor Teste', 'Portugal') ON CONFLICT DO NOTHING;

INSERT INTO utente (ut_nome, ut_email) VALUES ('Utente Teste', 'teste@example.com') ON CONFLICT (ut_email) DO NOTHING;

-- Adicionar um livro de teste
INSERT INTO livros (li_titulo, li_autor, li_ano, li_edicao, li_genero) 
VALUES ('Livro Teste', 'Autor Teste', 2024, 1, 'Ficção Científica') ON CONFLICT DO NOTHING;

-- Adicionar exemplar do livro
INSERT INTO livro_exemplar (ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao)
SELECT li_cod, 'Bom', true, true FROM livros WHERE li_titulo = 'Livro Teste' LIMIT 1;
```

### 4. Clique em "RUN"
- Clique no botão **"RUN"** ou pressione **Ctrl+Enter**
- Aguarde a execução terminar

### 5. Teste a Aplicação
Após executar o SQL:
- Acesse: http://localhost:8000/index.php
- A aplicação deve funcionar normalmente
- O dashboard deve mostrar "Livros totais: 1"

## 🎯 RESULTADO ESPERADO
- ✅ Todas as tabelas criadas
- ✅ Dados iniciais inseridos
- ✅ Aplicação funcionando 100%
- ✅ Conexão com Supabase via API REST

## 📞 SUPORTE
Se tiver problemas:
1. Verifique se está no projeto correto no Supabase
2. Certifique-se de que executou TODO o SQL
3. Teste novamente a aplicação
