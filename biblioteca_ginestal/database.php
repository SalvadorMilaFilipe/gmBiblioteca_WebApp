<?php
declare(strict_types=1);

// Incluir configurações e cliente REST do Supabase
require_once __DIR__ . '/supabase_config.php';
require_once __DIR__ . '/supabase_rest_client.php';

/**
 * Cliente REST para Supabase (quando PostgreSQL não está disponível)
 */
function get_supabase_client(): SupabaseRestClient {
    static $client = null;
    
    if ($client === null) {
        $client = new SupabaseRestClient();
    }
    
    return $client;
}

/**
 * Verificar se estamos usando PostgreSQL ou API REST
 */
function is_using_postgresql(): bool {
    return SupabaseConfig::isPostgreSQLAvailable();
}

/**
 * Conexão com base de dados Supabase (PostgreSQL ou API REST)
 */
function get_pdo(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        if (is_using_postgresql()) {
            try {
                // Usar PostgreSQL se disponível
                $dsn = SupabaseConfig::getConnectionString();
                
                $pdo = new PDO($dsn, SupabaseConfig::SUPABASE_DB_USER, SupabaseConfig::SUPABASE_DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ]);
            } catch (PDOException $e) {
                throw new Exception('Erro de conexão PostgreSQL: ' . $e->getMessage());
            }
        } else {
            // Usar API REST do Supabase
            throw new Exception('PostgreSQL não disponível. Use as funções específicas da API REST.');
        }
    }
    
    return $pdo;
}

/**
 * Executar query SQL no Supabase
 */
function execute_sql(string $sql, array $params = []): array {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } else {
        throw new Exception('execute_sql() não suportado via API REST. Use execute_rest_sql().');
    }
}

/**
 * Executar comando SQL no Supabase (INSERT, UPDATE, DELETE)
 */
function execute_command(string $sql, array $params = []): int {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } else {
        throw new Exception('execute_command() não suportado via API REST. Use execute_rest_insert/update/delete().');
    }
}

/**
 * Obter último ID inserido
 */
function get_last_insert_id(): int {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        return (int)$pdo->lastInsertId();
    } else {
        throw new Exception('get_last_insert_id() não suportado via API REST.');
    }
}

/**
 * Verificar se as tabelas existem no Supabase
 */
function check_supabase_tables(): bool {
    if (is_using_postgresql()) {
        try {
            $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('livros', 'livro_exemplar', 'requisicao', 'utente', 'autor', 'editora', 'idioma', 'genero', 'codigo_postal')";
            $tables = execute_sql($sql);
            return count($tables) >= 9; // Verificar se todas as tabelas principais existem
        } catch (Exception $e) {
            return false;
        }
    } else {
        // Para API REST, tentar acessar uma tabela básica
        try {
            execute_rest_sql('idioma', ['id_idioma'], [], 'id_idioma');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Criar tabelas no Supabase se não existirem
 */
function create_supabase_tables(): void {
    if (!is_using_postgresql()) {
        throw new Exception('create_supabase_tables() só é suportado via PostgreSQL. Use o SQL Editor do Supabase.');
    }
    
    $pdo = get_pdo();
    
    // SQL para criar todas as tabelas necessárias (sintaxe PostgreSQL)
    $sql = "
    -- Tabela de idiomas
    CREATE TABLE IF NOT EXISTS idioma (
        id_idioma VARCHAR(10) PRIMARY KEY,
        id_pais VARCHAR(100) NOT NULL
    );
    
    -- Tabela de géneros
    CREATE TABLE IF NOT EXISTS genero (
        ge_genero VARCHAR(50) PRIMARY KEY
    );
    
    -- Tabela de códigos postais
    CREATE TABLE IF NOT EXISTS codigo_postal (
        cod_postal VARCHAR(10) PRIMARY KEY,
        cod_localidade VARCHAR(100) NOT NULL
    );
    
    -- Tabela de editoras
    CREATE TABLE IF NOT EXISTS editora (
        ed_cod SERIAL PRIMARY KEY,
        ed_nome VARCHAR(200) NOT NULL,
        ed_morada TEXT NOT NULL,
        ed_email VARCHAR(100) NOT NULL,
        ed_codpostal VARCHAR(10) NOT NULL,
        ed_tlm VARCHAR(20) NOT NULL,
        FOREIGN KEY (ed_codpostal) REFERENCES codigo_postal(cod_postal)
    );
    
    -- Tabela de autores
    CREATE TABLE IF NOT EXISTS autor (
        au_cod SERIAL PRIMARY KEY,
        au_nome VARCHAR(200) NOT NULL,
        au_pais VARCHAR(100) NOT NULL
    );
    
    -- Tabela de utentes
    CREATE TABLE IF NOT EXISTS utente (
        ut_cod SERIAL PRIMARY KEY,
        ut_nome VARCHAR(200) NOT NULL,
        ut_email VARCHAR(100) NOT NULL,
        ut_turma VARCHAR(10) NOT NULL,
        ut_ano INTEGER NOT NULL CHECK (ut_ano >= 7 AND ut_ano <= 12)
    );
    
    -- Tabela de livros
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
    
    -- Tabela de exemplares
    CREATE TABLE IF NOT EXISTS livro_exemplar (
        ex_cod SERIAL PRIMARY KEY,
        ex_li_cod INTEGER NOT NULL,
        ex_disponivel BOOLEAN DEFAULT true,
        ex_permrequisicao BOOLEAN DEFAULT true,
        ex_estado VARCHAR(50),
        FOREIGN KEY (ex_li_cod) REFERENCES livros(li_cod)
    );
    
    -- Tabela de requisições
    CREATE TABLE IF NOT EXISTS requisicao (
        re_cod SERIAL PRIMARY KEY,
        re_lexcod INTEGER NOT NULL,
        re_utcod INTEGER NOT NULL,
        re_datarequisicao DATE NOT NULL DEFAULT CURRENT_DATE,
        re_datadevolucao DATE,
        FOREIGN KEY (re_lexcod) REFERENCES livro_exemplar(ex_cod),
        FOREIGN KEY (re_utcod) REFERENCES utente(ut_cod)
    );
    ";
    
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        throw new Exception('Erro ao criar tabelas no Supabase: ' . $e->getMessage());
    }
}

/**
 * Inserir dados iniciais no Supabase
 */
function seed_supabase_data(): void {
    $pdo = get_pdo();
    
    // Inserir idiomas
    $idiomas = [
        ['pt-PT', 'Portugal'],
        ['en-GB', 'Reino Unido'],
        ['es-ES', 'Espanha'],
        ['fr-FR', 'França'],
        ['de-DE', 'Alemanha']
    ];
    
    foreach ($idiomas as $idioma) {
        execute_command(
            "INSERT INTO idioma (id_idioma, id_pais) VALUES (?, ?) ON CONFLICT (id_idioma) DO NOTHING",
            $idioma
        );
    }
    
    // Inserir géneros
    $generos = [
        'Romance', 'Ficção Científica', 'Fantasia', 'Mistério', 'Thriller',
        'Biografia', 'História', 'Aventura', 'Poesia', 'Drama',
        'Horror', 'Humor', 'Tecnologia', 'Educação', 'Filosofia',
        'Autoajuda', 'Economia', 'Arte', 'Música', 'Infantil'
    ];
    
    foreach ($generos as $genero) {
        execute_command(
            "INSERT INTO genero (ge_genero) VALUES (?) ON CONFLICT (ge_genero) DO NOTHING",
            [$genero]
        );
    }
}

// Funções de compatibilidade com o código existente
function ensure_schema(): void {
    if (is_using_postgresql()) {
        // Usar PostgreSQL se disponível
        if (!check_supabase_tables()) {
            create_supabase_tables();
            seed_supabase_data();
        }
    } else {
        // Usar API REST - apenas verificar se as tabelas existem
        try {
            $client = get_supabase_client();
            // Tentar acessar uma tabela básica para verificar se existe
            $client->select('idioma', ['id_idioma'], [], 'id_idioma');
            echo "<!-- Schema verificado via API REST -->\n";
        } catch (Exception $e) {
            echo "<!-- ATENÇÃO: Tabelas não encontradas no Supabase. Execute o SQL no painel do Supabase. -->\n";
            echo "<!-- Erro: " . htmlspecialchars($e->getMessage()) . " -->\n";
        }
    }
}

function ensure_defaults_and_capacity(): void {
    // Esta função agora é desnecessária pois o Supabase já tem as configurações corretas
    return;
}

// Manter todas as outras funções existentes mas adaptadas para Supabase
// (As funções de CRUD serão mantidas iguais, apenas a conexão muda)

// Funções de livros
function get_available_books(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, e.ex_cod as exemplar_cod
            FROM livros l 
            JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
            WHERE e.ex_disponivel = true AND e.ex_permrequisicao = true
            ORDER BY l.li_titulo
        ");
    } else {
        // Para API REST, vamos buscar todos os livros e filtrar depois
        try {
            $livros = execute_rest_sql('livro', ['li_cod', 'li_titulo', 'li_autor', 'li_ano', 'li_edicao', 'li_genero'], [], 'li_cod');
            $resultado = [];
            
            foreach ($livros as $livro) {
                // Verificar se há exemplares disponíveis
                $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod'], ['ex_li_cod' => $livro['li_cod'], 'ex_disponivel' => true, 'ex_permrequisicao' => true]);
                
                if (count($exemplares) > 0) {
                    $livro['exemplar_cod'] = $exemplares[0]['ex_cod'];
                    $resultado[] = $livro;
                }
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

function get_borrowed_books(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL
            ORDER BY r.re_datarequisicao DESC
        ");
    } else {
        // Versão simplificada para API REST
        try {
            $requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_lexcod', 're_utcod', 're_datarequisicao', 're_datadevolucao'], ['re_datadevolucao' => 'null'], 're_datarequisicao');
            $resultado = [];
            
            foreach ($requisicoes as $req) {
                // Buscar dados do exemplar e livro
                $exemplar = execute_rest_sql('livro_exemplar', ['ex_li_cod'], ['ex_cod' => $req['re_lexcod']]);
                if (count($exemplar) > 0) {
                    $livro = execute_rest_sql('livro', ['li_titulo', 'li_autor'], ['li_cod' => $exemplar[0]['ex_li_cod']]);
                    $utente = execute_rest_sql('utente', ['ut_nome'], ['ut_cod' => $req['re_utcod']]);
                    
                    if (count($livro) > 0 && count($utente) > 0) {
                        $resultado[] = [
                            're_cod' => $req['re_cod'],
                            'li_titulo' => $livro[0]['li_titulo'],
                            'li_autor' => $livro[0]['li_autor'],
                            'ut_nome' => $utente[0]['ut_nome'],
                            're_datarequisicao' => $req['re_datarequisicao'],
                            're_datadevolucao' => $req['re_datadevolucao']
                        ];
                    }
                }
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

function get_overdue_books(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL 
            AND r.re_datarequisicao < CURRENT_DATE - INTERVAL '14 days'
            ORDER BY r.re_datarequisicao ASC
        ");
    } else {
        // Para API REST, implementação simplificada
        try {
            $requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_lexcod', 're_utcod', 're_datarequisicao', 're_datadevolucao'], ['re_datadevolucao' => null]);
            $resultado = [];
            
            foreach ($requisicoes as $requisicao) {
                // Verificar se está atrasada (mais de 14 dias)
                $dataRequisicao = new DateTime($requisicao['re_datarequisicao']);
                $dataLimite = (new DateTime())->modify('-14 days');
                
                if ($dataRequisicao < $dataLimite) {
                    // Buscar dados do exemplar, livro e utente
                    $exemplares = execute_rest_sql('livro_exemplar', ['ex_li_cod'], ['ex_cod' => $requisicao['re_lexcod']]);
                    if (!empty($exemplares)) {
                        $livros = execute_rest_sql('livro', ['li_titulo', 'li_autor'], ['li_cod' => $exemplares[0]['ex_li_cod']]);
                        $utentes = execute_rest_sql('utente', ['ut_nome'], ['ut_cod' => $requisicao['re_utcod']]);
                        
                        if (!empty($livros) && !empty($utentes)) {
                            $resultado[] = [
                                're_cod' => $requisicao['re_cod'],
                                'li_titulo' => $livros[0]['li_titulo'],
                                'li_autor' => $livros[0]['li_autor'],
                                'ut_nome' => $utentes[0]['ut_nome'],
                                're_datarequisicao' => $requisicao['re_datarequisicao'],
                                're_datadevolucao' => $requisicao['re_datadevolucao']
                            ];
                        }
                    }
                }
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

function get_expiring_books(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL 
            AND r.re_datarequisicao BETWEEN CURRENT_DATE - INTERVAL '11 days' AND CURRENT_DATE - INTERVAL '11 days'
            ORDER BY r.re_datarequisicao ASC
        ");
    } else {
        // Para API REST, implementação simplificada
        try {
            $requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_lexcod', 're_utcod', 're_datarequisicao', 're_datadevolucao'], ['re_datadevolucao' => null]);
            $resultado = [];
            
            foreach ($requisicoes as $requisicao) {
                // Verificar se está a expirar (exatamente 11 dias)
                $dataRequisicao = new DateTime($requisicao['re_datarequisicao']);
                $dataLimite = (new DateTime())->modify('-11 days');
                
                if ($dataRequisicao->format('Y-m-d') === $dataLimite->format('Y-m-d')) {
                    // Buscar dados do exemplar, livro e utente
                    $exemplares = execute_rest_sql('livro_exemplar', ['ex_li_cod'], ['ex_cod' => $requisicao['re_lexcod']]);
                    if (!empty($exemplares)) {
                        $livros = execute_rest_sql('livro', ['li_titulo', 'li_autor'], ['li_cod' => $exemplares[0]['ex_li_cod']]);
                        $utentes = execute_rest_sql('utente', ['ut_nome'], ['ut_cod' => $requisicao['re_utcod']]);
                        
                        if (!empty($livros) && !empty($utentes)) {
                            $resultado[] = [
                                're_cod' => $requisicao['re_cod'],
                                'li_titulo' => $livros[0]['li_titulo'],
                                'li_autor' => $livros[0]['li_autor'],
                                'ut_nome' => $utentes[0]['ut_nome'],
                                're_datarequisicao' => $requisicao['re_datarequisicao'],
                                're_datadevolucao' => $requisicao['re_datadevolucao']
                            ];
                        }
                    }
                }
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

function search_available_books(string $query, string $searchType = 'all'): array {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        $sql = "SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_isbn, l.li_editora, l.li_idioma, 
                       l.li_edicao, l.li_ano, l.li_genero, e.ex_cod as exemplar_cod,
                       ed.ed_nome as editora_nome
                FROM livros l 
                JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
                LEFT JOIN editora ed ON l.li_editora = ed.ed_cod
                WHERE e.ex_disponivel = true AND e.ex_permrequisicao = true";
        
        $whereConditions = [];
        $params = [];
        
        if ($query !== '') {
            switch ($searchType) {
                case 'titulo':
                    $whereConditions[] = "l.li_titulo LIKE :query1";
                    $params[':query1'] = '%' . $query . '%';
                    break;
                case 'autor':
                    $whereConditions[] = "l.li_autor LIKE :query1";
                    $params[':query1'] = '%' . $query . '%';
                    break;
                case 'genero':
                    $whereConditions[] = "l.li_genero LIKE :query1";
                    $params[':query1'] = '%' . $query . '%';
                    break;
                case 'all':
                default:
                    $whereConditions[] = "(l.li_titulo LIKE :query1 OR l.li_autor LIKE :query2 OR l.li_genero LIKE :query3)";
                    $params[':query1'] = '%' . $query . '%';
                    $params[':query2'] = '%' . $query . '%';
                    $params[':query3'] = '%' . $query . '%';
                    break;
            }
        }
        
        if (!empty($whereConditions)) {
            $sql .= ' AND ' . implode(' AND ', $whereConditions);
        }
        
        $sql .= ' ORDER BY l.li_titulo';
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } else {
        // Para API REST, buscar todos os livros com todos os campos e filtrar
        $livros = get_all_books_full();
        
        if ($query === '') {
            return $livros;
        }
        
        $resultado = [];
        $queryLower = strtolower($query);
        
        foreach ($livros as $livro) {
            $match = false;
            
            switch ($searchType) {
                case 'titulo':
                    $match = strpos(strtolower($livro['li_titulo']), $queryLower) !== false;
                    break;
                case 'autor':
                    $match = strpos(strtolower($livro['li_autor']), $queryLower) !== false;
                    break;
                case 'genero':
                    $match = strpos(strtolower($livro['li_genero']), $queryLower) !== false;
                    break;
                case 'all':
                default:
                    $match = strpos(strtolower($livro['li_titulo']), $queryLower) !== false ||
                             strpos(strtolower($livro['li_autor']), $queryLower) !== false ||
                             strpos(strtolower($livro['li_genero']), $queryLower) !== false;
                    break;
            }
            
            if ($match) {
                $resultado[] = $livro;
            }
        }
        
        return $resultado;
    }
}

function get_exemplares_with_status(int $livro_cod): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT e.ex_cod, e.ex_estado, e.ex_disponivel, e.ex_permrequisicao,
                   CASE 
                       WHEN e.ex_disponivel = false AND e.ex_permrequisicao = true THEN 'emprestado'
                       WHEN e.ex_disponivel = true AND e.ex_permrequisicao = true THEN 'disponivel'
                       ELSE 'indisponivel'
                   END as status,
                   r.re_cod as requisicao_cod,
                   r.re_datarequisicao,
                   r.re_datadevolucao,
                   u.ut_cod as utente_cod,
                   u.ut_nome as utente_nome,
                   u.ut_email as utente_email
            FROM livro_exemplar e
            LEFT JOIN (
                SELECT r1.*, 
                       ROW_NUMBER() OVER (PARTITION BY r1.re_lexcod ORDER BY r1.re_datarequisicao DESC) as rn
                FROM requisicao r1
            ) r ON e.ex_cod = r.re_lexcod AND r.rn = 1
            LEFT JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE e.ex_li_cod = ?
            ORDER BY e.ex_cod
        ", [$livro_cod]);
    } else {
        // Para API REST, implementação simplificada
        try {
            $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod', 'ex_estado', 'ex_disponivel', 'ex_permrequisicao'], ['ex_li_cod' => $livro_cod], 'ex_cod');
            $resultado = [];
            
            foreach ($exemplares as $exemplar) {
                $status = 'indisponivel';
                if ($exemplar['ex_disponivel'] === true && $exemplar['ex_permrequisicao'] === true) {
                    $status = 'disponivel';
                } elseif ($exemplar['ex_disponivel'] === false && $exemplar['ex_permrequisicao'] === true) {
                    $status = 'emprestado';
                }
                
                // Buscar requisição mais recente para este exemplar
                $requisicoes = execute_rest_sql('requisicao', ['re_cod', 're_datarequisicao', 're_datadevolucao', 're_utcod'], ['re_lexcod' => $exemplar['ex_cod']], 're_datarequisicao');
                $requisicao = !empty($requisicoes) ? end($requisicoes) : null;
                
                $utente = null;
                if ($requisicao) {
                    $utentes = execute_rest_sql('utente', ['ut_cod', 'ut_nome', 'ut_email'], ['ut_cod' => $requisicao['re_utcod']]);
                    $utente = !empty($utentes) ? $utentes[0] : null;
                }
                
                $resultado[] = [
                    'ex_cod' => $exemplar['ex_cod'],
                    'ex_estado' => $exemplar['ex_estado'],
                    'ex_disponivel' => $exemplar['ex_disponivel'],
                    'ex_permrequisicao' => $exemplar['ex_permrequisicao'],
                    'status' => $status,
                    'requisicao_cod' => $requisicao ? $requisicao['re_cod'] : null,
                    're_datarequisicao' => $requisicao ? $requisicao['re_datarequisicao'] : null,
                    're_datadevolucao' => $requisicao ? $requisicao['re_datadevolucao'] : null,
                    'utente_cod' => $utente ? $utente['ut_cod'] : null,
                    'utente_nome' => $utente ? $utente['ut_nome'] : null,
                    'utente_email' => $utente ? $utente['ut_email'] : null
                ];
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Funções de CRUD básicas
function create_book(string $titulo, string $autor, string $isbn, int $editora, string $idioma, int $edicao, int $ano, string $genero): int {
    if (is_using_postgresql()) {
        execute_command(
            "INSERT INTO livros (li_titulo, li_autor, li_isbn, li_ed_cod, li_idioma, li_edicao, li_ano, li_genero) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$titulo, $autor, $isbn, $editora, $idioma, $edicao, $ano, $genero]
        );
        return get_last_insert_id();
    } else {
        // Limpar e validar dados
        $data = [
            'li_titulo' => trim($titulo),
            'li_autor' => trim($autor),
            'li_isbn' => trim($isbn),
            'li_ed_cod' => (int)$editora,
            'li_idioma' => trim($idioma),
            'li_edicao' => (int)$edicao,
            'li_ano' => (int)$ano,
            'li_genero' => trim($genero)
        ];
        
        // Validar dados obrigatórios
        if (empty($data['li_titulo'])) {
            throw new Exception('Título do livro é obrigatório');
        }
        if (empty($data['li_autor'])) {
            throw new Exception('Autor do livro é obrigatório');
        }
        if (empty($data['li_isbn'])) {
            throw new Exception('ISBN do livro é obrigatório');
        }
        
        $client = get_supabase_client();
        $result = $client->insert('livros', $data);
        
        if (!$result) {
            throw new Exception('Erro ao inserir livro na base de dados');
        }
        
        // Para obter o ID do livro inserido, fazer uma consulta usando apenas ISBN
        $livros = $client->select('livros', ['li_cod'], ['li_isbn' => $data['li_isbn']], 'li_cod DESC');
        
        if (empty($livros)) {
            throw new Exception('Erro ao obter ID do livro inserido');
        }
        
        return (int)$livros[0]['li_cod'];
    }
}

function create_exemplar(int $livro_cod): int {
    if (is_using_postgresql()) {
        execute_command(
            "INSERT INTO livro_exemplar (ex_li_cod) VALUES (?)",
            [$livro_cod]
        );
        return get_last_insert_id();
    } else {
        $data = [
            'ex_li_cod' => $livro_cod,
            'ex_disponivel' => true,
            'ex_estado' => 'disponivel'
        ];
        
        $client = get_supabase_client();
        $result = $client->insert('livro_exemplar', $data);
        
        if (!$result) {
            throw new Exception('Erro ao inserir exemplar na base de dados');
        }
        
        // Para obter o ID do exemplar inserido, fazer uma consulta
        $exemplares = $client->select('livro_exemplar', ['ex_cod'], ['ex_li_cod' => $livro_cod], 'ex_cod DESC');
        $exemplares = array_slice($exemplares, 0, 1); // Limitar a 1 resultado
        
        if (empty($exemplares)) {
            throw new Exception('Erro ao obter ID do exemplar inserido');
        }
        
        return (int)$exemplares[0]['ex_cod'];
    }
}

function create_loan(int $utente_cod, int $exemplar_cod): void {
    $pdo = get_pdo();
    $pdo->beginTransaction();
    
    try {
        // Criar requisição
        execute_command(
            "INSERT INTO requisicao (re_lexcod, re_utcod) VALUES (?, ?)",
            [$exemplar_cod, $utente_cod]
        );
        
        // Marcar exemplar como indisponível
        execute_command(
                            "UPDATE livro_exemplar SET ex_disponivel = false WHERE ex_cod = ?",
            [$exemplar_cod]
        );
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function return_book(int $requisicao_cod): bool {
    $pdo = get_pdo();
    $pdo->beginTransaction();
    
    try {
        // Obter exemplar da requisição
        $exemplar = execute_sql(
            "SELECT re_lexcod FROM requisicao WHERE re_cod = ? AND re_datadevolucao IS NULL",
            [$requisicao_cod]
        );
        
        if (empty($exemplar)) {
            return false;
        }
        
        // Marcar devolução
        execute_command(
            "UPDATE requisicao SET re_datadevolucao = CURRENT_DATE WHERE re_cod = ?",
            [$requisicao_cod]
        );
        
        // Marcar exemplar como disponível
        execute_command(
            "UPDATE livro_exemplar SET ex_disponivel = true WHERE ex_cod = ?",
            [$exemplar[0]['re_lexcod']]
        );
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Funções auxiliares
function get_editoras(): array {
    if (is_using_postgresql()) {
        return execute_sql("SELECT ed_cod, ed_nome FROM editora ORDER BY ed_nome");
    } else {
        return execute_rest_sql('editora', ['ed_cod', 'ed_nome'], [], 'ed_nome');
    }
}

function get_generos(): array {
    if (is_using_postgresql()) {
        return execute_sql("SELECT ge_genero FROM genero ORDER BY ge_genero");
    } else {
        return execute_rest_sql('genero', ['ge_genero'], [], 'ge_genero');
    }
}

function get_idiomas(): array {
    if (is_using_postgresql()) {
        return execute_sql("SELECT id_idioma FROM idioma ORDER BY id_idioma");
    } else {
        return execute_rest_sql('idioma', ['id_idioma'], [], 'id_idioma');
    }
}

function get_autores(): array {
    if (is_using_postgresql()) {
        return execute_sql("SELECT au_cod, au_nome, au_pais FROM autor ORDER BY au_nome");
    } else {
        return execute_rest_sql('autor', ['au_cod', 'au_nome', 'au_pais'], [], 'au_nome');
    }
}

function get_utentes(): array {
    if (is_using_postgresql()) {
        return execute_sql("SELECT ut_cod, ut_nome, ut_email FROM utente ORDER BY ut_nome");
    } else {
        return execute_rest_sql('utente', ['ut_cod', 'ut_nome', 'ut_email'], [], 'ut_nome');
    }
}

function get_available_exemplares_for_book(int $livro_cod): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT e.ex_cod, e.ex_estado, l.li_titulo, l.li_autor
            FROM livro_exemplar e
            JOIN livros l ON e.ex_li_cod = l.li_cod
            WHERE e.ex_li_cod = ? AND e.ex_disponivel = 1 AND e.ex_permrequisicao = 1
            ORDER BY e.ex_cod
        ", [$livro_cod]);
    } else {
        // Para API REST
        try {
            $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod', 'ex_estado', 'ex_li_cod'], ['ex_li_cod' => $livro_cod, 'ex_disponivel' => true, 'ex_permrequisicao' => true], 'ex_cod');
            
            // Buscar dados do livro para cada exemplar
            $livro = execute_rest_sql('livro', ['li_titulo', 'li_autor'], ['li_cod' => $livro_cod]);
            $livro_nome = count($livro) > 0 ? $livro[0]['li_titulo'] : 'Livro não encontrado';
            $livro_autor = count($livro) > 0 ? $livro[0]['li_autor'] : '';
            
            // Adicionar dados do livro a cada exemplar
            foreach ($exemplares as &$exemplar) {
                $exemplar['li_titulo'] = $livro_nome;
                $exemplar['li_autor'] = $livro_autor;
            }
            
            return $exemplares;
        } catch (Exception $e) {
            return [];
        }
    }
}

function get_books_with_counts(?string $query = null, bool $available_only = false): array {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        
        $sql = "SELECT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, 
                       COUNT(e.ex_cod) as total_exemplares,
                       COUNT(CASE WHEN e.ex_disponivel = true AND e.ex_permrequisicao = true THEN 1 END) as exemplares_disponiveis
                FROM livros l 
                LEFT JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod";
        
        $whereConditions = [];
        $params = [];
        
        if ($query) {
            $whereConditions[] = "(l.li_titulo LIKE :query1 OR l.li_autor LIKE :query2 OR l.li_genero LIKE :query3)";
            $params[':query1'] = '%' . $query . '%';
            $params[':query2'] = '%' . $query . '%';
            $params[':query3'] = '%' . $query . '%';
        }
        
        if ($available_only) {
            $whereConditions[] = "e.ex_disponivel = true AND e.ex_permrequisicao = true";
        }
        
        if (!empty($whereConditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $sql .= " GROUP BY l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero
                  ORDER BY l.li_titulo";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } else {
        // Usar API REST - implementação simplificada
        try {
            $livros = execute_rest_sql('livro', ['li_cod', 'li_titulo', 'li_autor', 'li_ano', 'li_edicao', 'li_genero'], [], 'li_titulo');
            $resultado = [];
            
            foreach ($livros as $livro) {
                // Buscar exemplares para este livro
                $exemplares = execute_rest_sql('livro_exemplar', ['ex_cod', 'ex_disponivel', 'ex_permrequisicao'], ['ex_li_cod' => $livro['li_cod']]);
                
                $total_exemplares = count($exemplares);
                $exemplares_disponiveis = 0;
                
                foreach ($exemplares as $exemplar) {
                    if ($exemplar['ex_disponivel'] === true && $exemplar['ex_permrequisicao'] === true) {
                        $exemplares_disponiveis++;
                    }
                }
                
                // Aplicar filtros
                if ($query) {
                    $searchText = strtolower($query);
                    if (strpos(strtolower($livro['li_titulo']), $searchText) === false &&
                        strpos(strtolower($livro['li_autor']), $searchText) === false &&
                        strpos(strtolower($livro['li_genero']), $searchText) === false) {
                        continue;
                    }
                }
                
                if ($available_only && $exemplares_disponiveis === 0) {
                    continue;
                }
                
                $livro['total_exemplares'] = $total_exemplares;
                $livro['exemplares_disponiveis'] = $exemplares_disponiveis;
                $resultado[] = $livro;
            }
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Função para obter todos os livros (usada em requisicoes.php)
function get_all_books(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT li_cod, li_titulo, li_autor, li_ano, li_edicao, li_genero
            FROM livros 
            ORDER BY li_titulo
        ");
    } else {
        return execute_rest_sql('livro', ['li_cod', 'li_titulo', 'li_autor', 'li_ano', 'li_edicao', 'li_genero'], [], 'li_titulo');
    }
}

// Função para obter todos os livros com todos os campos (usada em disponiveis.php)
function get_all_books_full(): array {
    if (is_using_postgresql()) {
        return execute_sql("
            SELECT l.li_cod, l.li_titulo, l.li_autor, l.li_isbn, l.li_editora, l.li_idioma, 
                   l.li_edicao, l.li_ano, l.li_genero,
                   e.ed_nome as editora_nome
            FROM livros l 
            LEFT JOIN editora e ON l.li_editora = e.ed_cod
            ORDER BY l.li_titulo
        ");
    } else {
        // Para API REST, buscar livros e autores
        try {
            $livros = execute_rest_sql('livro', ['li_cod', 'li_titulo', 'li_autor', 'li_isbn', 'li_idioma', 'li_edicao', 'li_ano', 'li_genero'], [], 'li_titulo');
            $autores = execute_rest_sql('autor', ['au_cod', 'au_nome'], [], 'au_cod');
            
            // Criar array de autores para lookup
            $autoresMap = [];
            foreach ($autores as $autor) {
                $autoresMap[$autor['au_cod']] = $autor['au_nome'];
            }
            
            // Adicionar campos calculados
            foreach ($livros as &$livro) {
                $livro['li_editora'] = null; // Campo não existe na tabela
                
                // Definir editora baseada no livro (sistema de mapeamento)
                $editora_nome = 'Editora não especificada';
                
                // Mapeamento de livros conhecidos para suas editoras
                $mapeamento_editoras = [
                    10 => 'Porto Editora', // Mensagem de Fernando Pessoa
                    // Adicionar mais livros conforme necessário
                ];
                
                if (isset($mapeamento_editoras[$livro['li_cod']])) {
                    $editora_nome = $mapeamento_editoras[$livro['li_cod']];
                }
                
                $livro['editora_nome'] = $editora_nome;
                
                // Converter ID do autor para nome
                if (!empty($livro['li_autor'])) {
                    $livro['autor_nome'] = $autoresMap[$livro['li_autor']] ?? 'Autor não encontrado';
                } else {
                    $livro['autor_nome'] = 'Sem autor';
                }
            }
            
            return $livros;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Função para criar empréstimo com datas específicas (usada em requisicoes.php)
function create_loan_with_dates(int $utente_cod, int $exemplar_cod, string $data_requisicao, ?string $data_devolucao = null): void {
    if (is_using_postgresql()) {
        $pdo = get_pdo();
        $pdo->beginTransaction();
        
        try {
            // Criar requisição com datas específicas
            if ($data_devolucao) {
                execute_command(
                    "INSERT INTO requisicao (re_lexcod, re_utcod, re_datarequisicao, re_datadevolucao) VALUES (?, ?, ?, ?)",
                    [$exemplar_cod, $utente_cod, $data_requisicao, $data_devolucao]
                );
                
                // Se tem data de devolução, o exemplar fica disponível
                execute_command(
                    "UPDATE livro_exemplar SET ex_disponivel = true WHERE ex_cod = ?",
                    [$exemplar_cod]
                );
            } else {
                execute_command(
                    "INSERT INTO requisicao (re_lexcod, re_utcod, re_datarequisicao) VALUES (?, ?, ?)",
                    [$exemplar_cod, $utente_cod, $data_requisicao]
                );
                
                // Se não tem data de devolução, o exemplar fica indisponível
                execute_command(
                    "UPDATE livro_exemplar SET ex_disponivel = false WHERE ex_cod = ?",
                    [$exemplar_cod]
                );
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        // Usar API REST
        try {
            $requisicao_data = [
                're_lexcod' => $exemplar_cod,
                're_utcod' => $utente_cod,
                're_datarequisicao' => $data_requisicao
            ];
            
            if ($data_devolucao) {
                $requisicao_data['re_datadevolucao'] = $data_devolucao;
            }
            
            // Criar requisição
            execute_rest_insert('requisicao', $requisicao_data);
            
            // Atualizar estado do exemplar
            $exemplar_status = $data_devolucao ? true : false;
            execute_rest_update('livro_exemplar', ['ex_disponivel' => $exemplar_status], ['ex_cod' => $exemplar_cod]);
            
        } catch (Exception $e) {
            throw new Exception('Erro ao criar empréstimo via API REST: ' . $e->getMessage());
        }
    }
}

// ============================================================================
// FUNÇÕES PARA API REST DO SUPABASE (quando PostgreSQL não está disponível)
// ============================================================================

/**
 * Executar consulta usando API REST do Supabase
 */
function execute_rest_sql(string $table, array $columns = ['*'], array $filters = [], string $orderBy = ''): array {
    $client = get_supabase_client();
    
    // Usar tabelas normais sem prefixo (public.tabela)
    
    // Converter todos os valores dos filtros para string
    $stringFilters = [];
    foreach ($filters as $key => $value) {
        if ($value === null) {
            $stringFilters[$key] = null;
        } else {
            $stringFilters[$key] = (string)$value;
        }
    }
    
    return $client->select($table, $columns, $stringFilters, $orderBy);
}

/**
 * Inserir dados usando API REST do Supabase
 */
function execute_rest_insert(string $table, array $data): bool {
    $client = get_supabase_client();
    return $client->insert($table, $data);
}

/**
 * Atualizar dados usando API REST do Supabase
 */
function execute_rest_update(string $table, array $data, array $filters): bool {
    $client = get_supabase_client();
    
    // Converter todos os valores dos filtros para string
    $stringFilters = [];
    foreach ($filters as $key => $value) {
        if ($value === null) {
            $stringFilters[$key] = null;
        } else {
            $stringFilters[$key] = (string)$value;
        }
    }
    
    return $client->update($table, $data, $stringFilters);
}

/**
 * Eliminar dados usando API REST do Supabase
 */
function execute_rest_delete(string $table, array $filters): bool {
    $client = get_supabase_client();
    
    // Converter todos os valores dos filtros para string
    $stringFilters = [];
    foreach ($filters as $key => $value) {
        if ($value === null) {
            $stringFilters[$key] = null;
        } else {
            $stringFilters[$key] = (string)$value;
        }
    }
    
    return $client->delete($table, $stringFilters);
}

/**
 * Função adaptadora que escolhe entre PostgreSQL e API REST
 */
function execute_sql_adaptive(string $table, array $columns = ['*'], array $filters = [], string $orderBy = ''): array {
    if (is_using_postgresql()) {
        // Construir SQL para PostgreSQL
        $sql = "SELECT " . implode(', ', $columns) . " FROM " . $table;
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $key => $value) {
                $conditions[] = $key . " = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy;
        }
        
        return execute_sql($sql, array_values($filters));
    } else {
        return execute_rest_sql($table, $columns, $filters, $orderBy);
    }
}

/**
 * Função adaptadora para inserção
 */
function execute_insert_adaptive(string $table, array $data): bool {
    if (is_using_postgresql()) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        execute_command($sql, array_values($data));
        return true;
    } else {
        return execute_rest_insert($table, $data);
    }
}

/**
 * Função adaptadora para atualização
 */
function execute_update_adaptive(string $table, array $data, array $filters): bool {
    if (is_using_postgresql()) {
        $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($key) => "$key = ?", array_keys($filters)));
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        
        execute_command($sql, array_merge(array_values($data), array_values($filters)));
        return true;
    } else {
        return execute_rest_update($table, $data, $filters);
    }
}

/**
 * Testar conexão com Supabase (PostgreSQL ou API REST)
 */
function test_supabase_connection(): array {
    $result = [
        'postgresql_available' => is_using_postgresql(),
        'connection_type' => is_using_postgresql() ? 'PostgreSQL' : 'API REST',
        'status' => 'unknown',
        'message' => ''
    ];
    
    try {
        if (is_using_postgresql()) {
            $pdo = get_pdo();
            $version = execute_sql("SELECT version()")[0]['version'];
            $result['status'] = 'success';
            $result['message'] = 'Conectado via PostgreSQL: ' . substr($version, 0, 50);
        } else {
            $client = get_supabase_client();
            if ($client->testConnection()) {
                $result['status'] = 'success';
                $result['message'] = 'Conectado via API REST do Supabase';
            } else {
                $result['status'] = 'error';
                $result['message'] = 'Erro na conexão via API REST';
            }
        }
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();
    }
    
    return $result;
}
?>