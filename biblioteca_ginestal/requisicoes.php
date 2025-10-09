<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();
ensure_defaults_and_capacity();

$errors = [];
$success = null;

// Criar nova requisição
if (($_POST['action'] ?? '') === 'create_requisicao') {
	$utente_cod = (int)($_POST['utente_cod'] ?? 0);
	$exemplar_cod = (int)($_POST['exemplar_cod'] ?? 0);
	$data_requisicao = trim($_POST['data_requisicao'] ?? '');
	$data_devolucao = trim($_POST['data_devolucao'] ?? '');
	
	if ($utente_cod <= 0) $errors[] = 'Selecione um utente.';
	if ($exemplar_cod <= 0) $errors[] = 'Selecione um exemplar.';
	if ($data_requisicao === '') $errors[] = 'Data de requisição é obrigatória.';
	
	// Validar formato das datas
	if ($data_requisicao !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_requisicao)) {
		$errors[] = 'Data de requisição deve estar no formato YYYY-MM-DD.';
	}
	if ($data_devolucao !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_devolucao)) {
		$errors[] = 'Data de devolução deve estar no formato YYYY-MM-DD.';
	}
	
	// Validar que data de devolução não seja anterior à data de requisição
	if ($data_requisicao !== '' && $data_devolucao !== '' && $data_devolucao < $data_requisicao) {
		$errors[] = 'Data de devolução não pode ser anterior à data de requisição.';
	}
	
	if (!$errors) {
		try {
			create_loan_with_dates($utente_cod, $exemplar_cod, $data_requisicao, $data_devolucao ?: null);
			// Redirecionar para o index após sucesso
			header('Location: index.php?success=requisicao_criada');
			exit;
		} catch (Exception $e) {
			$errors[] = 'Erro ao criar requisição: ' . $e->getMessage();
		}
	}
}


// Obter dados
$utentes = [];
$livros = [];

try {
	$utentes = get_pdo()->query('SELECT ut_cod, ut_nome, ut_turma, ut_ano FROM utente ORDER BY ut_nome')->fetchAll();
	$livros = get_all_books();
} catch (Exception $e) {
	$errors[] = 'Erro ao carregar dados: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Requisições - Biblioteca Ginestal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
	<style>
	:root { --bs-primary: #7b1113; --bs-primary-rgb: 123,17,19; }
	.navbar.bg-primary { background-color: #7b1113 !important; }
	.select2-container--bootstrap-5 .select2-selection {
		min-height: 38px;
	}
	</style>
</head>
<body>
	<?php include __DIR__ . '/navbar.php'; ?>

	<main class="container my-4">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8 col-lg-6">
				<?php if ($errors): ?>
					<div class="alert alert-danger">
						<ul class="mb-0">
							<?php foreach ($errors as $err): ?>
								<li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				
				<!-- Formulário de Requisição -->
				<div class="card shadow-sm">
					<div class="card-header text-center">
						<h4 class="mb-0">Nova Requisição</h4>
					</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create_requisicao">
							
							<!-- Seleção do Utente com Pesquisa Integrada -->
							<div class="mb-3">
								<label for="utente_cod" class="form-label">ID do Utente</label>
								<select class="form-select" id="utente_cod" name="utente_cod" required data-search="true">
									<option value="">Digite para pesquisar utente...</option>
									<?php foreach ($utentes as $utente): ?>
										<option value="<?= (int)$utente['ut_cod'] ?>" data-nome="<?= htmlspecialchars($utente['ut_nome'], ENT_QUOTES, 'UTF-8') ?>">
											ID: <?= (int)$utente['ut_cod'] ?> - <?= htmlspecialchars($utente['ut_nome'], ENT_QUOTES, 'UTF-8') ?> 
											(<?= htmlspecialchars($utente['ut_turma'], ENT_QUOTES, 'UTF-8') ?> - <?= (int)$utente['ut_ano'] ?>º ano)
										</option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione um utente.</div>
								<div class="form-text">Digite para pesquisar utentes por nome</div>
							</div>
							
							<!-- Seleção do Livro -->
							<div class="mb-3">
								<label for="livro_cod" class="form-label">Livro</label>
								<select class="form-select" id="livro_cod" name="livro_cod" required>
									<option value="">Selecionar livro...</option>
									<?php foreach ($livros as $livro): ?>
										<option value="<?= (int)$livro['li_cod'] ?>">
											<?= htmlspecialchars($livro['li_titulo'], ENT_QUOTES, 'UTF-8') ?> 
											(<?= htmlspecialchars($livro['li_autor'], ENT_QUOTES, 'UTF-8') ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione um livro.</div>
							</div>
							
							<!-- Seleção do Exemplar -->
							<div class="mb-3">
								<label for="exemplar_cod" class="form-label">ID do Exemplar</label>
								<select class="form-select" id="exemplar_cod" name="exemplar_cod" required disabled>
									<option value="">Primeiro selecione um livro</option>
								</select>
								<div class="invalid-feedback">Selecione um exemplar.</div>
								<div class="form-text">Os exemplares disponíveis aparecerão após selecionar um livro.</div>
							</div>
							
							<!-- Data de Requisição -->
							<div class="mb-3">
								<label for="data_requisicao" class="form-label">Data de Requisição</label>
								<input type="date" class="form-control" id="data_requisicao" name="data_requisicao" 
									   value="<?= date('Y-m-d') ?>" required>
								<div class="invalid-feedback">Informe a data de requisição.</div>
							</div>
							
							<!-- Data de Devolução -->
							<div class="mb-3">
								<label for="data_devolucao" class="form-label">Data de Devolução (Opcional)</label>
								<input type="date" class="form-control" id="data_devolucao" name="data_devolucao">
								<div class="form-text">Deixe em branco se o livro ainda não foi devolvido.</div>
							</div>
							
							<div class="d-grid gap-2">
								<button type="submit" class="btn btn-primary btn-lg">Criar Requisição</button>
								<a href="index.php" class="btn btn-outline-secondary">Voltar ao Início</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
	// Validação Bootstrap
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

	// Inicializar Select2 para pesquisa de utentes
	$(document).ready(function() {
		$('#utente_cod').select2({
			theme: 'bootstrap-5',
			placeholder: 'Digite para pesquisar utente...',
			allowClear: true,
			width: '100%',
			language: {
				noResults: function() {
					return "Nenhum utente encontrado";
				},
				searching: function() {
					return "Pesquisando...";
				}
			}
		});
	});

	// Sistema de filtros para livros e exemplares
	(function() {
		const livroSelect = document.getElementById('livro_cod');
		const exemplarSelect = document.getElementById('exemplar_cod');
		
		if (livroSelect && exemplarSelect) {
			// Quando um livro é selecionado, carregar exemplares disponíveis
			livroSelect.addEventListener('change', function() {
				const livroCod = this.value;
				
				// Limpar exemplares anteriores
				exemplarSelect.innerHTML = '<option value="">Carregando exemplares...</option>';
				exemplarSelect.disabled = true;
				
				if (livroCod) {
					// Fazer requisição AJAX para obter exemplares
					fetch('get_exemplares_livro.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'livro_cod=' + encodeURIComponent(livroCod)
					})
					.then(response => response.json())
					.then(data => {
						exemplarSelect.innerHTML = '<option value="">Selecionar exemplar...</option>';
						
						if (data.length === 0) {
							exemplarSelect.innerHTML = '<option value="">Nenhum exemplar disponível</option>';
						} else {
							data.forEach(exemplar => {
								const option = document.createElement('option');
								option.value = exemplar.ex_cod;
								option.textContent = `ID: ${exemplar.ex_cod} - ${exemplar.li_titulo} (${exemplar.ex_estado})`;
								exemplarSelect.appendChild(option);
							});
						}
						
						exemplarSelect.disabled = false;
					})
					.catch(error => {
						console.error('Erro ao carregar exemplares:', error);
						exemplarSelect.innerHTML = '<option value="">Erro ao carregar exemplares</option>';
						exemplarSelect.disabled = false;
					});
				} else {
					exemplarSelect.innerHTML = '<option value="">Primeiro selecione um livro</option>';
					exemplarSelect.disabled = true;
				}
			});
		}
	})();

	// Validação de datas
	(function() {
		const dataRequisicao = document.getElementById('data_requisicao');
		const dataDevolucao = document.getElementById('data_devolucao');
		
		if (dataRequisicao && dataDevolucao) {
			function validarDatas() {
				const dataReq = dataRequisicao.value;
				const dataDev = dataDevolucao.value;
				
				if (dataReq && dataDev && dataDev < dataReq) {
					dataDevolucao.setCustomValidity('Data de devolução não pode ser anterior à data de requisição');
				} else {
					dataDevolucao.setCustomValidity('');
				}
			}
			
			dataRequisicao.addEventListener('change', validarDatas);
			dataDevolucao.addEventListener('change', validarDatas);
		}
	})();
	</script>
</body>
</html>
