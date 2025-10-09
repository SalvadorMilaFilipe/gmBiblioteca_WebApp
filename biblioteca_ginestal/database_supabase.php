<?php
declare(strict_types=1);
require_once __DIR__ . '/supabase_config.php';

/**
 * Conexão com Supabase PostgreSQL
 */
function get_pdo(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = SupabaseConfig::getConnectionString();
            $pdo = new PDO($dsn, SupabaseConfig::SUPABASE_DB_USER, SupabaseConfig::SUPABASE_DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ]);
        } catch (PDOException $e) {
            throw new Exception('Erro de conexão com Supabase: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Executar query SQL no Supabase
 */
function execute_sql(string $sql, array $params = []): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Executar comando SQL no Supabase (INSERT, UPDATE, DELETE)
 */
function execute_command(string $sql, array $params = []): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Obter último ID inserido
 */
function get_last_insert_id(): int {
    $pdo = get_pdo();
    return (int)$pdo->lastInsertId();
}

/**
 * Verificar se as tabelas existem no Supabase
 */
function check_supabase_tables(): bool {
    try {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('livros', 'livro_exemplar', 'requisicao', 'utente', 'autor', 'editora', 'idioma', 'genero', 'codigo_postal')";
        $tables = execute_sql($sql);
        return count($tables) >= 9; // Verificar se todas as tabelas principais existem
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Criar tabelas no Supabase se não existirem
 */
function create_supabase_tables(): void {
    $pdo = get_pdo();
    
    // SQL para criar todas as tabelas necessárias
    $sql = "
    -- Tabela de idiomas
    CREATE TABLE IF NOT EXISTS idioma (
        id_idioma VARCHAR(10) PRIMARY KEY,
        id_pais VARCHAR(50) NOT NULL
    );
    
    -- Tabela de géneros
    CREATE TABLE IF NOT EXISTS genero (
        ge_genero VARCHAR(50) PRIMARY KEY
    );
    
    -- Tabela de códigos postais
    CREATE TABLE IF NOT EXISTS codigo_postal (
        cod_postal VARCHAR(8) PRIMARY KEY,
        cod_localidade VARCHAR(100) NOT NULL
    );
    
    -- Tabela de editoras
    CREATE TABLE IF NOT EXISTS editora (
        ed_cod SERIAL PRIMARY KEY,
        ed_nome VARCHAR(100) NOT NULL,
        ed_morada VARCHAR(200) NOT NULL,
        ed_email VARCHAR(100) NOT NULL,
        ed_codpostal VARCHAR(8) NOT NULL,
        ed_tlm VARCHAR(20) NOT NULL,
        FOREIGN KEY (ed_codpostal) REFERENCES codigo_postal(cod_postal)
    );
    
    -- Tabela de autores
    CREATE TABLE IF NOT EXISTS autor (
        au_cod SERIAL PRIMARY KEY,
        au_nome VARCHAR(100) NOT NULL,
        au_pais VARCHAR(50) NOT NULL
    );
    
    -- Tabela de utentes
    CREATE TABLE IF NOT EXISTS utente (
        ut_cod SERIAL PRIMARY KEY,
        ut_nome VARCHAR(100) NOT NULL,
        ut_email VARCHAR(100) NOT NULL,
        ut_turma VARCHAR(20) NOT NULL,
        ut_ano INTEGER NOT NULL CHECK (ut_ano >= 7 AND ut_ano <= 12)
    );
    
    -- Tabela de livros
    CREATE TABLE IF NOT EXISTS livros (
        li_cod SERIAL PRIMARY KEY,
        li_titulo VARCHAR(200) NOT NULL,
        li_autor VARCHAR(255) NOT NULL,
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
        ex_estado TEXT,
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
    if (!check_supabase_tables()) {
        create_supabase_tables();
        seed_supabase_data();
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
    return execute_sql("
        SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, e.ex_cod as exemplar_cod
        FROM livros l 
        JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
        WHERE e.ex_disponivel = true AND e.ex_permrequisicao = true
        ORDER BY l.li_titulo
    ");
}

function get_borrowed_books(): array {
    return execute_sql("
        SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
        FROM requisicao r
        JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
        JOIN livros l ON e.ex_li_cod = l.li_cod
        JOIN utente u ON r.re_utcod = u.ut_cod
        WHERE r.re_datadevolucao IS NULL
        ORDER BY r.re_datarequisicao DESC
    ");
}

function get_overdue_books(): array {
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
}

function get_expiring_books(): array {
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
}

function search_available_books(string $query, string $searchType = 'all'): array {
    $pdo = get_pdo();
    $sql = "SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, e.ex_cod as exemplar_cod
            FROM livros l 
            JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
            WHERE e.ex_disponivel = true AND e.ex_permrequisicao = true";
    
    $whereConditions = [];
    $params = [];
    
    if ($query !== '') {
        switch ($searchType) {
            case 'titulo':
                $whereConditions[] = "l.li_titulo ILIKE :query1";
                $params[':query1'] = '%' . $query . '%';
                break;
            case 'autor':
                $whereConditions[] = "l.li_autor ILIKE :query1";
                $params[':query1'] = '%' . $query . '%';
                break;
            case 'genero':
                $whereConditions[] = "l.li_genero ILIKE :query1";
                $params[':query1'] = '%' . $query . '%';
                break;
            case 'all':
            default:
                $whereConditions[] = "(l.li_titulo ILIKE :query1 OR l.li_autor ILIKE :query2 OR l.li_genero ILIKE :query3)";
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
}

function get_exemplares_with_status(int $livro_cod): array {
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
}

// Funções de CRUD básicas
function create_book(string $titulo, string $autor, int $isbn, int $editora, string $idioma, int $edicao, int $ano, string $genero): int {
    execute_command(
        "INSERT INTO livros (li_titulo, li_autor, li_isbn, li_editora, li_idioma, li_edicao, li_ano, li_genero) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$titulo, $autor, $isbn, $editora, $idioma, $edicao, $ano, $genero]
    );
    return get_last_insert_id();
}

function create_exemplar(int $livro_cod): int {
    execute_command(
        "INSERT INTO livro_exemplar (ex_li_cod) VALUES (?)",
        [$livro_cod]
    );
    return get_last_insert_id();
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
    return execute_sql("SELECT ed_cod, ed_nome FROM editora ORDER BY ed_nome");
}

function get_generos(): array {
    return execute_sql("SELECT ge_genero FROM genero ORDER BY ge_genero");
}

function get_idiomas(): array {
    return execute_sql("SELECT id_idioma FROM idioma ORDER BY id_idioma");
}

function get_autores(): array {
    return execute_sql("SELECT au_cod, au_nome, au_pais FROM autor ORDER BY au_nome");
}

function get_utentes(): array {
    return execute_sql("SELECT ut_cod, ut_nome, ut_email FROM utente ORDER BY ut_nome");
}

function get_available_exemplares_for_book(int $livro_cod): array {
    return execute_sql("
        SELECT e.ex_cod, e.ex_estado, l.li_titulo, l.li_autor
        FROM livro_exemplar e
        JOIN livros l ON e.ex_li_cod = l.li_cod
        WHERE e.ex_li_cod = ? AND e.ex_disponivel = true AND e.ex_permrequisicao = true
        ORDER BY e.ex_cod
    ", [$livro_cod]);
}

function get_books_with_counts(?string $query = null, bool $available_only = false): array {
    $pdo = get_pdo();
    
    $sql = "SELECT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, 
                   COUNT(e.ex_cod) as total_exemplares,
                   COUNT(CASE WHEN e.ex_disponivel = true AND e.ex_permrequisicao = true THEN 1 END) as exemplares_disponiveis
            FROM livros l 
            LEFT JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod";
    
    $whereConditions = [];
    $params = [];
    
    if ($query) {
        $whereConditions[] = "(l.li_titulo ILIKE :query1 OR l.li_autor ILIKE :query2 OR l.li_genero ILIKE :query3)";
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
}
?>
