# API de Utentes - Documentação

Esta API fornece endpoints para gerenciar utentes da biblioteca, ideal para integração com aplicações Flutter.

## Base URL
```
http://localhost:8000/api_utentes.php
```

## Endpoints Disponíveis

### 1. Listar Todos os Utentes
**GET** `/api_utentes.php?path=utentes`

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "ut_cod": 1,
      "ut_nome": "João Silva",
      "ut_email": "joao@email.com",
      "ut_turma": "12A",
      "ut_ano": 12
    }
  ]
}
```

### 2. Pesquisar Utentes por Nome
**GET** `/api_utentes.php?path=utentes/search&q={termo_pesquisa}`

**Parâmetros:**
- `q` (string): Termo de pesquisa (pesquisa por nome)

**Exemplo:**
```
GET /api_utentes.php?path=utentes/search&q=joão
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "ut_cod": 1,
      "ut_nome": "João Silva",
      "ut_email": "joao@email.com",
      "ut_turma": "12A",
      "ut_ano": 12
    }
  ]
}
```

### 3. Criar Novo Utente
**POST** `/api_utentes.php?path=utentes`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "ut_nome": "Maria Santos",
  "ut_email": "maria@email.com",
  "ut_turma": "11B",
  "ut_ano": 11
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "message": "Utente criado com sucesso"
  }
}
```

### 4. Atualizar Utente
**PUT** `/api_utentes.php?path=utentes`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "ut_cod": 1,
  "ut_nome": "João Silva Santos",
  "ut_email": "joao.silva@email.com",
  "ut_turma": "12B",
  "ut_ano": 12
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "message": "Utente atualizado com sucesso"
  }
}
```

### 5. Eliminar Utente
**DELETE** `/api_utentes.php?path=utentes&id={ut_cod}`

**Parâmetros:**
- `id` (int): ID do utente a eliminar

**Exemplo:**
```
DELETE /api_utentes.php?path=utentes&id=1
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "message": "Utente eliminado com sucesso"
  }
}
```

## Códigos de Status HTTP

- `200` - Sucesso
- `400` - Erro de validação (dados inválidos)
- `404` - Endpoint não encontrado
- `500` - Erro interno do servidor

## Estrutura de Resposta de Erro

```json
{
  "success": false,
  "error": "Mensagem de erro"
}
```

## Validações

### Criar/Atualizar Utente:
- `ut_nome`: Obrigatório (string)
- `ut_email`: Obrigatório (string, formato email)
- `ut_turma`: Obrigatório (string)
- `ut_ano`: Obrigatório (int, entre 7 e 12)

## CORS

A API está configurada para aceitar requisições de qualquer origem (`Access-Control-Allow-Origin: *`), ideal para desenvolvimento com Flutter.

## Exemplo de Uso em Flutter

```dart
// Buscar todos os utentes
final response = await http.get(
  Uri.parse('http://localhost:8000/api_utentes.php?path=utentes'),
);

// Pesquisar utentes
final searchResponse = await http.get(
  Uri.parse('http://localhost:8000/api_utentes.php?path=utentes/search&q=joão'),
);

// Criar novo utente
final createResponse = await http.post(
  Uri.parse('http://localhost:8000/api_utentes.php?path=utentes'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'ut_nome': 'Maria Santos',
    'ut_email': 'maria@email.com',
    'ut_turma': '11B',
    'ut_ano': 11,
  }),
);
```

## Funcionalidades da Interface Web

A página `utentes.php` agora inclui:

1. **Barra de Pesquisa Dinâmica**: Pesquisa em tempo real por nome
2. **Debounce**: Aguarda 500ms após parar de digitar
3. **Pesquisa Imediata**: Enter para pesquisar instantaneamente
4. **Botão Limpar**: Limpa a pesquisa com um clique
5. **Contador de Resultados**: Mostra quantos utentes foram encontrados
6. **Mensagens Contextuais**: Diferentes mensagens para lista vazia vs. pesquisa sem resultados
7. **Foco Automático**: Campo de pesquisa recebe foco automaticamente

## Integração com Flutter

Esta API é perfeita para integração com Flutter porque:

- **RESTful**: Segue padrões REST
- **JSON**: Respostas em formato JSON
- **CORS**: Configurado para aceitar requisições de qualquer origem
- **Validação**: Validações robustas no servidor
- **Pesquisa**: Endpoint específico para pesquisa por nome
- **CRUD Completo**: Create, Read, Update, Delete
