<?php
// Database configuration and connection helper using PDO

declare(strict_types=1);

// Database connection parameters
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);
$DB_NAME = getenv('DB_NAME') ?: 'gmbiblioteca';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

/**
 * Returns a PDO connection to MySQL. If the database doesn't exist, it tries to create it.
 */
function get_pdo(): PDO {
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // Try connecting directly to the target database
        $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
        return new PDO($dsn, $DB_USER, $DB_PASS, $options);
    } catch (PDOException $e) {
        // If database is unknown, create it and try again
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            $serverDsn = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
            $serverPdo = new PDO($serverDsn, $DB_USER, $DB_PASS, $options);
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
            return new PDO($dsn, $DB_USER, $DB_PASS, $options);
        }
        throw $e;
    }
}

/**
 * Ensures the gmbiblioteca schema exists by running the SQL file.
 */
function ensure_schema(): void {
    $pdo = get_pdo();
    
    // Check if tables already exist
    $tables = $pdo->query("SHOW TABLES LIKE 'livros'")->fetchAll();
    if (!empty($tables)) {
        return; // Schema already exists
    }
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/gmbiblioteca.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
            continue; // Skip comments
        }
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Log error but continue with other statements
            error_log("SQL Error: " . $e->getMessage() . " - Statement: " . substr($statement, 0, 100));
        }
    }
}

/**
 * Ensure defaults for idioma and genero exist; expand livros.li_autor capacity for multi-author entries.
 */
function ensure_defaults_and_capacity(): void {
    $pdo = get_pdo();

    // Expand livros.li_autor to VARCHAR(255) if smaller
    try {
        $col = $pdo->query("SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'livros' AND COLUMN_NAME = 'li_autor'")->fetch();
        if ($col && (int)$col['len'] < 100) {
            $pdo->exec("ALTER TABLE livros MODIFY li_autor VARCHAR(255) NOT NULL");
        }
    } catch (Throwable $e) {
        // ignore if INFORMATION_SCHEMA not accessible; best-effort
    }

    // Seed idiomas
    try {
        $countIdiomas = (int)$pdo->query("SELECT COUNT(*) FROM idioma")->fetchColumn();
        if ($countIdiomas < 5) {
            $idiomas = [
                ['id' => 'pt-PT', 'pais' => 'Portugal'],
                ['id' => 'en-GB', 'pais' => 'Reino Unido'],
                ['id' => 'es-ES', 'pais' => 'Espanha'],
                ['id' => 'fr-FR', 'pais' => 'França'],
                ['id' => 'de-DE', 'pais' => 'Alemanha'],
            ];
            $stmt = $pdo->prepare("INSERT IGNORE INTO idioma (id_idioma, id_pais) VALUES (?, ?)");
            foreach ($idiomas as $i) {
                $stmt->execute([$i['id'], $i['pais']]);
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    // Seed generos
    try {
        $countGen = (int)$pdo->query("SELECT COUNT(*) FROM genero")->fetchColumn();
        if ($countGen < 20) {
            $generos = [
                'Romance','Ficção Científica','Fantasia','Mistério','Thriller',
                'Biografia','História','Aventura','Poesia','Drama',
                'Horror','Humor','Tecnologia','Educação','Filosofia',
                'Autoajuda','Economia','Arte','Música','Infantil'
            ];
            $stmt = $pdo->prepare("INSERT IGNORE INTO genero (ge_genero) VALUES (?)");
            foreach ($generos as $g) {
                $stmt->execute([$g]);
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    // Ligação entre editoras e idiomas removida: não adicionar coluna/seed ed_lang

    // Ensure PRIMARY KEYs are AUTO_INCREMENT where appropriate
    try {
        $tablesAi = [
            ['table' => 'livros', 'col' => 'li_cod'],
            ['table' => 'livro_exemplar', 'col' => 'ex_cod'],
            ['table' => 'requisicao', 'col' => 're_cod'],
            ['table' => 'utente', 'col' => 'ut_cod'],
            ['table' => 'autor', 'col' => 'au_cod'],
            ['table' => 'editora', 'col' => 'ed_cod'],
        ];
        foreach ($tablesAi as $t) {
            $q = $pdo->prepare("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $q->execute([$t['table'], $t['col']]);
            $extra = $q->fetchColumn();
            if ($extra === false || stripos((string)$extra, 'auto_increment') === false) {
                // We need each column type to re-declare; fetch type
                $typeQ = $pdo->prepare("SELECT COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                $typeQ->execute([$t['table'], $t['col']]);
                $info = $typeQ->fetch();
                if ($info && stripos($info['COLUMN_TYPE'], 'int') !== false) {
                    $pdo->exec("ALTER TABLE `{$t['table']}` MODIFY `{$t['col']}` INT NOT NULL AUTO_INCREMENT");
                }
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    // Align livros.li_genero to match genero.ge_genero (VARCHAR)
    try {
        $genType = $pdo->query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'livros' AND COLUMN_NAME = 'li_genero'")->fetchColumn();
        if ($genType && strtolower((string)$genType) !== 'varchar') {
            $pdo->exec("ALTER TABLE livros MODIFY li_genero VARCHAR(50) NOT NULL");
        }
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * Get available books (livros with available exemplares)
 */
function get_available_books(): array {
    $pdo = get_pdo();
    $sql = "SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, e.ex_cod as exemplar_cod
            FROM livros l 
            JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
            WHERE e.ex_disponivel = 1 AND IFNULL(e.ex_permrequisicao, 1) = 1
            ORDER BY l.li_titulo";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get borrowed books (requisicoes without return date)
 */
function get_borrowed_books(): array {
    $pdo = get_pdo();
    $sql = "SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL
            ORDER BY r.re_datarequisicao DESC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get overdue books
 */
function get_overdue_books(): array {
    $pdo = get_pdo();
    $sql = "SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL 
            AND r.re_datarequisicao < DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
            ORDER BY r.re_datarequisicao ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get books expiring soon (within 3 days)
 */
function get_expiring_books(): array {
    $pdo = get_pdo();
    $sql = "SELECT r.re_cod, l.li_titulo, l.li_autor, u.ut_nome, r.re_datarequisicao, r.re_datadevolucao
            FROM requisicao r
            JOIN livro_exemplar e ON r.re_lexcod = e.ex_cod
            JOIN livros l ON e.ex_li_cod = l.li_cod
            JOIN utente u ON r.re_utcod = u.ut_cod
            WHERE r.re_datadevolucao IS NULL 
            AND r.re_datarequisicao BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY) AND DATE_SUB(CURRENT_DATE, INTERVAL 11 DAY)
            ORDER BY r.re_datarequisicao ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Search books by title
 */
function search_books(string $query): array {
    $pdo = get_pdo();
    $sql = "SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, e.ex_cod as exemplar_cod
            FROM livros l 
            JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
            WHERE l.li_titulo LIKE :query
            ORDER BY l.li_titulo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Search available books by title, author, or genre
 */
function search_available_books(string $query, string $searchType = 'all'): array {
    $pdo = get_pdo();
    $sql = "SELECT DISTINCT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao, l.li_genero, e.ex_cod as exemplar_cod
            FROM livros l 
            JOIN livro_exemplar e ON l.li_cod = e.ex_li_cod 
            WHERE e.ex_disponivel = 1 AND IFNULL(e.ex_permrequisicao, 1) = 1";
    
    $whereConditions = [];
    $params = [];
    
    if ($query !== '') {
        switch ($searchType) {
            case 'titulo':
                $whereConditions[] = "l.li_titulo LIKE :query";
                $params[':query'] = '%' . $query . '%';
                break;
            case 'autor':
                $whereConditions[] = "l.li_autor LIKE :query";
                $params[':query'] = '%' . $query . '%';
                break;
            case 'genero':
                $whereConditions[] = "l.li_genero LIKE :query";
                $params[':query'] = '%' . $query . '%';
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
}

/**
 * Create a new book
 */
function create_book(string $titulo, string $autor, int $isbn, int $editora, string $idioma, int $edicao, int $ano, string $genero): int {
    $pdo = get_pdo();
    $sql = "INSERT INTO livros (li_titulo, li_autor, li_isbn, li_editora, li_idioma, li_edicao, li_ano, li_genero) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titulo, $autor, $isbn, $editora, $idioma, $edicao, $ano, $genero]);
    return (int)$pdo->lastInsertId();
}

/**
 * Create a new exemplar for a book
 */
function create_exemplar(int $livro_cod, string $estado = 'Bom', bool $disponivel = true, bool $permrequisicao = true): int {
    $pdo = get_pdo();
    $sql = "INSERT INTO livro_exemplar (ex_li_cod, ex_estado, ex_disponivel, ex_permrequisicao) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$livro_cod, $estado, $disponivel ? 1 : 0, $permrequisicao ? 1 : 0]);
    return (int)$pdo->lastInsertId();
}

/**
 * Create a new loan (requisicao)
 */
function create_loan(int $utente_cod, int $exemplar_cod): int {
    $pdo = get_pdo();
    try {
        $pdo->beginTransaction();

        // Atomically reserve exemplar if available
        $reserve = $pdo->prepare("UPDATE livro_exemplar SET ex_disponivel = 0 WHERE ex_cod = ? AND ex_disponivel = 1");
        $reserve->execute([$exemplar_cod]);
        if ($reserve->rowCount() === 0) {
            $pdo->rollBack();
            throw new Exception('Exemplar indisponível. Atualize a página e tente novamente.');
        }

        // Insert requisicao
        $sql = "INSERT INTO requisicao (re_utcod, re_lexcod, re_datarequisicao) VALUES (?, ?, CURRENT_DATE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$utente_cod, $exemplar_cod]);
        $loanId = (int)$pdo->lastInsertId();

        $pdo->commit();
        return $loanId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Create a new loan with custom dates
 */
function create_loan_with_dates(int $utente_cod, int $exemplar_cod, string $data_requisicao, ?string $data_devolucao = null): int {
    $pdo = get_pdo();
    try {
        $pdo->beginTransaction();

        // Atomically reserve exemplar if available
        $reserve = $pdo->prepare("UPDATE livro_exemplar SET ex_disponivel = 0 WHERE ex_cod = ? AND ex_disponivel = 1");
        $reserve->execute([$exemplar_cod]);
        if ($reserve->rowCount() === 0) {
            $pdo->rollBack();
            throw new Exception('Exemplar indisponível. Atualize a página e tente novamente.');
        }

        // Insert requisicao with custom dates
        $sql = "INSERT INTO requisicao (re_utcod, re_lexcod, re_datarequisicao, re_datadevolucao) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$utente_cod, $exemplar_cod, $data_requisicao, $data_devolucao]);
        $loanId = (int)$pdo->lastInsertId();

        $pdo->commit();
        return $loanId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Return a book (mark as returned)
 */
function return_book(int $requisicao_cod): bool {
    $pdo = get_pdo();
    
    // Get the exemplar code first
    $sql = "SELECT re_lexcod FROM requisicao WHERE re_cod = ? AND re_datadevolucao IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$requisicao_cod]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return false; // Loan not found or already returned
    }
    
    // Update requisicao with return date
    $updateReq = "UPDATE requisicao SET re_datadevolucao = CURRENT_DATE WHERE re_cod = ?";
    $stmt = $pdo->prepare($updateReq);
    $stmt->execute([$requisicao_cod]);
    
    // Mark exemplar as available again
    $updateEx = "UPDATE livro_exemplar SET ex_disponivel = 1 WHERE ex_cod = ?";
    $stmt = $pdo->prepare($updateEx);
    $stmt->execute([$result['re_lexcod']]);
    
    return true;
}

/**
 * Get all utentes (students/users)
 */
function get_utentes(): array {
    $pdo = get_pdo();
    $sql = "SELECT ut_cod, ut_nome, ut_email, ut_turma, ut_ano FROM utente ORDER BY ut_nome";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get all editoras
 */
function get_editoras(?string $idioma = null): array {
    $pdo = get_pdo();
    // Removida filtragem/coluna de idioma (ed_lang)
    $sql = "SELECT ed_cod, ed_nome FROM editora ORDER BY ed_nome";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get books aggregated with exemplar counts. When onlyAvailable=true, include only books with at least 1 available exemplar.
 */
function get_books_with_counts(?string $query = null, bool $onlyAvailable = false): array {
    $pdo = get_pdo();
    $sql = "SELECT l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao,
                   COUNT(e.ex_cod) AS total_exemplares,
                   SUM(CASE WHEN e.ex_disponivel = 1 THEN 1 ELSE 0 END) AS disponiveis
            FROM livros l
            LEFT JOIN livro_exemplar e ON e.ex_li_cod = l.li_cod";
    $where = [];
    $params = [];
    if ($query !== null && $query !== '') {
        $where[] = 'l.li_titulo LIKE :q';
        $params[':q'] = '%' . $query . '%';
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' GROUP BY l.li_cod, l.li_titulo, l.li_autor, l.li_ano, l.li_edicao';
    if ($onlyAvailable) {
        $sql .= ' HAVING disponiveis > 0';
    }
    $sql .= ' ORDER BY l.li_titulo';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all generos
 */
function get_generos(): array {
    $pdo = get_pdo();
    $sql = "SELECT ge_genero FROM genero ORDER BY ge_genero";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get all idiomas
 */
function get_idiomas(): array {
    $pdo = get_pdo();
    $sql = "SELECT id_idioma, id_pais FROM idioma ORDER BY id_idioma";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get all autores
 */
function get_autores(): array {
    $pdo = get_pdo();
    $sql = "SELECT au_cod, au_nome, au_pais FROM autor ORDER BY au_nome";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get all exemplares for a specific book with their status and loan details
 */
function get_exemplares_with_status(int $livro_cod): array {
    $pdo = get_pdo();
    $sql = "SELECT e.ex_cod, e.ex_estado, e.ex_disponivel, e.ex_permrequisicao,
                   CASE 
                       WHEN e.ex_disponivel = 0 AND IFNULL(e.ex_permrequisicao, 1) = 1 THEN 'emprestado'
                       WHEN e.ex_disponivel = 1 AND IFNULL(e.ex_permrequisicao, 1) = 1 THEN 'disponivel'
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
            ORDER BY e.ex_cod";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$livro_cod]);
    return $stmt->fetchAll();
}

/**
 * Get all books for selection
 */
function get_all_books(): array {
    $pdo = get_pdo();
    $sql = "SELECT li_cod, li_titulo, li_autor, li_ano, li_genero FROM livros ORDER BY li_titulo";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Get available exemplares for a specific book
 */
function get_available_exemplares_for_book(int $livro_cod): array {
    $pdo = get_pdo();
    $sql = "SELECT e.ex_cod, e.ex_estado, l.li_titulo, l.li_autor
            FROM livro_exemplar e
            JOIN livros l ON e.ex_li_cod = l.li_cod
            WHERE e.ex_li_cod = ? AND e.ex_disponivel = 1 AND IFNULL(e.ex_permrequisicao, 1) = 1
            ORDER BY e.ex_cod";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$livro_cod]);
    return $stmt->fetchAll();
}

/**
 * Fix exemplar status based on active loans
 */
function fix_exemplar_status(): void {
    $pdo = get_pdo();
    
    // Find exemplars that should be marked as borrowed (have active loans)
    $sql = "UPDATE livro_exemplar e 
            SET ex_disponivel = 0 
            WHERE e.ex_cod IN (
                SELECT DISTINCT r.re_lexcod 
                FROM requisicao r 
                WHERE r.re_datadevolucao IS NULL
            )";
    $pdo->exec($sql);
    
    // Find exemplars that should be marked as available (no active loans)
    $sql = "UPDATE livro_exemplar e 
            SET ex_disponivel = 1 
            WHERE e.ex_cod NOT IN (
                SELECT DISTINCT r.re_lexcod 
                FROM requisicao r 
                WHERE r.re_datadevolucao IS NULL
            )";
    $pdo->exec($sql);
}

?>
