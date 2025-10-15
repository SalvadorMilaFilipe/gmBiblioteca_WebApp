<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

// Ensure DB and schema
ensure_schema();
// Seeding removido para evitar repovoar editoras após eliminação

// Handle actions
$errors = [];
$success = null;

// Verificar se há mensagem de sucesso da URL
if (isset($_GET['success']) && $_GET['success'] === 'requisicao_criada') {
	$success = 'Requisição criada com sucesso!';
}

// Create new book
if (($_POST['action'] ?? '') === 'add_book') {
	$titulo = trim($_POST['titulo'] ?? '');
	$autor = trim($_POST['autor'] ?? '');
	$isbn = (int)($_POST['isbn'] ?? 0);
	$editora = (int)($_POST['editora'] ?? 0);
	$idioma = trim($_POST['idioma'] ?? '');
	$edicao = (int)($_POST['edicao'] ?? 0);
	$ano = (int)($_POST['ano'] ?? 0);
	$genero = trim($_POST['genero'] ?? '');
	$qtd_exemplares = (int)($_POST['qtd_exemplares'] ?? 1);
	if ($qtd_exemplares < 1) $qtd_exemplares = 1;
	if ($qtd_exemplares > 50) $qtd_exemplares = 50;
	
	if ($titulo === '') $errors[] = 'Título é obrigatório.';
	if ($autor === '') $errors[] = 'Autor é obrigatório.';
	if ($isbn <= 0) $errors[] = 'ISBN é obrigatório.';
	if ($editora <= 0) $errors[] = 'Editora é obrigatória.';
	if ($idioma === '') $errors[] = 'Idioma é obrigatório.';
	if ($edicao <= 0) $errors[] = 'Edição é obrigatória.';
	if ($ano <= 0) $errors[] = 'Ano é obrigatório.';
	if ($genero === '') $errors[] = 'Género é obrigatório.';
	
	if (!$errors) {
		try {
			$livro_cod = create_book($titulo, $autor, $isbn, $editora, $idioma, $edicao, $ano, $genero);
			// Create N exemplares for the book
			for ($i = 0; $i < $qtd_exemplares; $i++) {
				create_exemplar($livro_cod);
			}
			$success = 'Livro adicionado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao adicionar livro: ' . $e->getMessage();
		}
	}
}

// Create new loan
if (($_POST['action'] ?? '') === 'new_loan') {
	$exemplar_cod = (int)($_POST['exemplar_cod'] ?? 0);
	$utente_cod = (int)($_POST['utente_cod'] ?? 0);
	
	if ($exemplar_cod <= 0) $errors[] = 'Selecione um exemplar.';
	if ($utente_cod <= 0) $errors[] = 'Selecione um utente.';
	
	if (!$errors) {
		try {
			create_loan($utente_cod, $exemplar_cod);
			$success = 'Empréstimo criado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao criar empréstimo: ' . $e->getMessage();
		}
	}
}

// Return book
if (($_POST['action'] ?? '') === 'return_book') {
	$requisicao_cod = (int)($_POST['requisicao_cod'] ?? 0);
	if ($requisicao_cod <= 0) {
		$errors[] = 'Código de requisição inválido.';
	} else {
		try {
			if (return_book($requisicao_cod)) {
				$success = 'Livro devolvido com sucesso!';
			} else {
				$errors[] = 'Requisição não encontrada ou já devolvida.';
			}
		} catch (Exception $e) {
			$errors[] = 'Erro ao devolver livro: ' . $e->getMessage();
		}
	}
}

// Simple filters via query param 'view'
$view = $_GET['view'] ?? 'todos';
$q = trim($_GET['q'] ?? '');
// Total livros
$totalLivros = 0;
try { 
    if (is_using_postgresql()) {
        $totalLivros = (int)get_pdo()->query('SELECT COUNT(*) FROM livros')->fetchColumn(); 
    } else {
        $livros = get_all_books();
        $totalLivros = count($livros);
    }
} catch (Throwable $e) {}

// Get data based on view
try {
    if ($view === 'disponiveis') {
        $lista = get_books_with_counts($q, true);
        $countDisponiveis = count($lista);
        $countRequisitados = count(get_borrowed_books());
        $countAtraso = count(get_overdue_books());
        $countExpirar = count(get_expiring_books());
    } elseif ($view === 'requisitados') {
		$lista = get_borrowed_books();
		$countDisponiveis = count(get_available_books());
		$countRequisitados = count($lista);
		$countAtraso = count(get_overdue_books());
		$countExpirar = count(get_expiring_books());
	} elseif ($view === 'atraso') {
		$lista = get_overdue_books();
		$countDisponiveis = count(get_available_books());
		$countRequisitados = count(get_borrowed_books());
		$countAtraso = count($lista);
		$countExpirar = count(get_expiring_books());
	} elseif ($view === 'expirar') {
		$lista = get_expiring_books();
		$countDisponiveis = count(get_available_books());
		$countRequisitados = count(get_borrowed_books());
		$countAtraso = count(get_overdue_books());
		$countExpirar = count($lista);
	} else {
        // All books aggregated
        $lista = get_books_with_counts($q, false);
        $countDisponiveis = count(get_books_with_counts(null, true));
		$countRequisitados = count(get_borrowed_books());
		$countAtraso = count(get_overdue_books());
		$countExpirar = count(get_expiring_books());
	}
} catch (Exception $e) {
	$errors[] = 'Erro ao carregar dados: ' . $e->getMessage();
	$lista = [];
	$countDisponiveis = $countRequisitados = $countAtraso = $countExpirar = 0;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Biblioteca Ginestal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
    :root { --bs-primary: #7b1113; --bs-primary-rgb: 123,17,19; }
    .navbar.bg-primary { background-color: #7b1113 !important; }
    
    /* Layout responsivo para as seções do dashboard */
    .dashboard-section {
        min-height: 200px;
    }
    
    @media (min-width: 768px) {
        .dashboard-section {
            min-height: 250px;
        }
    }
    
    @media (min-width: 992px) {
        .dashboard-section {
            min-height: 300px;
        }
    }
    
    /* Garantir que as seções ocupem todo o espaço disponível */
    .dashboard-container {
        height: calc(100vh - 120px); /* Altura total menos navbar e margens */
    }
    
    .dashboard-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    /* Estilo para as imagens do dashboard */
    .dashboard-image {
        height: 64px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        margin-bottom: 1rem;
    }
    
    /* Moldura estilo Canva para a imagem do Machado */
    .machado-frame {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        background: #fff;
        padding: 15px;
        
        /* Melhorar qualidade de renderização da moldura */
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
    }
    
    .machado-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        object-position: center;
        border-radius: 15px;
        display: block;
        
        /* Otimizações para melhor qualidade de imagem */
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
        image-rendering: high-quality;
        -ms-interpolation-mode: bicubic;
        
        /* Suavização e nitidez */
        filter: contrast(1.1) brightness(1.05) saturate(1.1);
        
        /* Anti-aliasing melhorado */
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        
        /* Renderização de alta qualidade */
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        
        /* Forçar aceleração de hardware */
        will-change: transform;
    }
    
    .machado-overlay {
        position: absolute;
        bottom: 15px;
        left: 15px;
        right: 15px;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        color: white;
        padding: 30px 20px 20px 20px;
        border-radius: 0 0 15px 15px;
        font-weight: bold;
        font-size: 1.2rem;
        text-align: center;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

	<main class="container-fluid vh-100 d-flex flex-column">
		<div class="row flex-grow-1 g-4">
			<!-- Sidebar -->
            <div class="col-12">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <!-- Imagem do Machado com moldura estilo Canva -->
            <div class="col-12 mb-4">
                <div class="machado-frame">
                    <img src="img/Ginestal_Machado.jpg" 
                         srcset="img/Ginestal_Machado.jpg 1x, img/Ginestal_Machado.jpg 2x" 
                         alt="Ginestal Machado" 
                         class="machado-image"
                         loading="eager"
                         decoding="sync">
                    <div class="machado-overlay">
                        Agrupamento Escolas Dr Ginestal Machado
                    </div>
                </div>
            </div>

            <!-- Tiles dashboard -->
            <div class="col-12 flex-grow-1 d-flex">
                <div class="row g-3 w-100">
                    <div class="col-12 col-md-6 col-lg-3 d-flex dashboard-section">
                        <a href="disponiveis.php" class="text-decoration-none w-100">
                            <div class="card shadow-sm h-100 w-100 dashboard-card">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <img src="img/Livros_Disponiveis_image.png" alt="Exemplares disponíveis" class="dashboard-image">
                                    <div class="fw-bold mb-2 fs-5">Livros Exemplares disponíveis</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 d-flex dashboard-section">
                        <a href="requisicoes.php" class="text-decoration-none w-100">
                            <div class="card shadow-sm h-100 w-100 dashboard-card">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <img src="img/Requisições_image.png" alt="Requisições" class="dashboard-image">
                                    <div class="fw-bold mb-2 fs-5">Requisições</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 d-flex dashboard-section">
                        <div class="card shadow-sm h-100 w-100 dashboard-card">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <div class="fw-bold mb-2 fs-5">Livros totais</div>
                                <div class="lead fs-4"><?= (int)$totalLivros ?></div>
                                <small class="text-muted">Livros existentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 d-flex dashboard-section">
                        <div class="card shadow-sm h-100 w-100 dashboard-card">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <img src="img/LogOut_image.png" alt="Logout" class="dashboard-image">
                                <div class="fw-bold mb-2 fs-5">Logout</div>
                                <button class="btn btn-outline-secondary" type="button" disabled>Em breve</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
	</main>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
	// Bootstrap client-side validation
	(() => {
		const forms = document.querySelectorAll('.needs-validation');
		Array.from(forms).forEach(form => {
			form.addEventListener('submit', event => {
				if (!form.checkValidity()) {
					event.preventDefault();
					event.stopPropagation();
				}
				form.classList.add('was-validated');
			}, false);
		});
	})();

    // Lógica de ligação idioma↔editora removida
	</script>
</body>
</html>


