# Migração para Supabase

## Passos para migrar da base de dados local para Supabase

### 1. Configurar Senha do Banco Supabase
Antes de executar a migração, você precisa configurar a senha do banco de dados no Supabase:

1. Acesse o painel do Supabase: https://supabase.com/dashboard
2. Vá para o projeto: `pfmhoslnnnagdpyjtvir`
3. Vá em Settings > Database
4. Copie a senha do banco de dados
5. Edite o arquivo `supabase_config.php` e substitua `const SUPABASE_DB_PASSWORD = '';` pela senha real

### 2. Executar Migração
Execute o script de migração:
```bash
php migrate_to_supabase.php
```

### 3. Alterar Conexão nos Arquivos
Após a migração bem-sucedida, altere todos os arquivos PHP para usar a nova conexão:

**Substituir em todos os arquivos:**
```php
// De:
require_once __DIR__ . '/database.php';

// Para:
require_once __DIR__ . '/database_supabase.php';
```

**Arquivos que precisam ser alterados:**
- index.php
- disponiveis.php
- editoras.php
- utentes.php
- autor.php
- codigopostal.php
- idiomas.php
- genero.php
- requisicoes.php (se existir)
- get_exemplares.php
- update_exemplar_status.php
- search_books_ajax.php

### 4. Testar a Aplicação
Após fazer todas as alterações:
1. Acesse a aplicação no navegador
2. Teste todas as funcionalidades
3. Verifique se os dados foram migrados corretamente

### 5. Backup da Base Local (Opcional)
Antes de remover a base local, faça um backup:
```bash
cp biblioteca.db biblioteca_backup.db
```

### 6. Remover Base Local (Opcional)
Após confirmar que tudo funciona com Supabase:
```bash
rm biblioteca.db
rm biblioteca_backup.db
```

## Vantagens do Supabase

- ✅ Base de dados na nuvem
- ✅ Backup automático
- ✅ Escalabilidade
- ✅ Acesso remoto
- ✅ Interface web para gestão
- ✅ APIs REST automáticas
- ✅ Autenticação integrada (futuro)

## Suporte

Se encontrar problemas durante a migração, verifique:
1. Se a senha do banco está correta
2. Se a conexão com a internet está estável
3. Se todas as tabelas foram criadas no Supabase
4. Se os dados foram migrados corretamente
