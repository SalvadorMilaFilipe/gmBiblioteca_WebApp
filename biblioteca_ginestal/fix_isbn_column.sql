-- Script para corrigir o tipo de dados da coluna li_isbn na tabela livros
-- Execute este SQL no SQL Editor do Supabase para alterar a coluna existente

-- Alterar o tipo de dados de INTEGER para BIGINT
ALTER TABLE livros ALTER COLUMN li_isbn TYPE BIGINT;

-- Verificar se a alteração foi bem-sucedida
SELECT column_name, data_type, character_maximum_length 
FROM information_schema.columns 
WHERE table_name = 'livros' AND column_name = 'li_isbn';
