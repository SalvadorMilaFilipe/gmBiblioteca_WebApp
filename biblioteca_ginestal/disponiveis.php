<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();
ensure_defaults_and_capacity();

$errors = [];
$success = null;

// Criar novo livro
if (($_POST['action'] ?? '') === 'add_book') {
	$titulo = trim($_POST['titulo'] ?? '');
	$autores_string = $_POST['autores'][0] ?? '';
	$autores = $autores_string ? explode(',', $autores_string) : [];
	$isbn = trim($_POST['isbn'] ?? '');
	$editora = (int)($_POST['editora'] ?? 0);
	$idioma = trim($_POST['idioma'] ?? '');
	$edicao = (int)($_POST['edicao'] ?? 0);
	$ano = (int)($_POST['ano'] ?? 0);
	$genero = trim($_POST['genero'] ?? '');
	$qtd_exemplares = (int)($_POST['qtd_exemplares'] ?? 1);
	if ($qtd_exemplares < 1) $qtd_exemplares = 1;
	if ($qtd_exemplares > 50) $qtd_exemplares = 50;

	if ($titulo === '') $errors[] = 'Título é obrigatório.';
	if (empty($autores) || !is_array($autores)) $errors[] = 'Selecione pelo menos um autor.';
	if ($isbn === '') $errors[] = 'ISBN é obrigatório.';
	if ($isbn !== '' && (!is_numeric($isbn) || strlen($isbn) !== 13)) $errors[] = 'ISBN deve ter exatamente 13 dígitos numéricos.';
	if ($editora <= 0) $errors[] = 'Editora é obrigatória.';
	if ($idioma === '') $errors[] = 'Idioma é obrigatório.';
	if ($edicao <= 0) $errors[] = 'Edição é obrigatória.';
	if ($ano <= 0) $errors[] = 'Ano é obrigatório.';
	if ($genero === '') $errors[] = 'Género é obrigatório.';

	if (!$errors) {
		try {
			// Converter códigos de autores para inteiros
			$autores_cod = array_map('intval', $autores);
			
			// Obter nomes dos autores selecionados
			$autores_nomes = [];
			$autores_disponiveis = get_autores(); // Obter lista completa de autores
			foreach ($autores_cod as $autor_cod) {
				foreach ($autores_disponiveis as $autor) {
					if ((int)$autor['au_cod'] === $autor_cod) {
						$autores_nomes[] = $autor['au_nome'];
						break;
					}
				}
			}
			
			// Criar string de autores separados por vírgula
			$autor_string = implode(', ', $autores_nomes);
			
			$livro_cod = create_book($titulo, $autor_string, (int)$isbn, $editora, $idioma, $edicao, $ano, $genero);
			for ($i = 0; $i < $qtd_exemplares; $i++) {
				create_exemplar($livro_cod);
			}
			$success = 'Livro adicionado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao adicionar livro: ' . $e->getMessage();
		}
	}
}

// Tabela de exemplares disponíveis
$q = trim($_GET['q'] ?? '');
$searchType = $_GET['search_type'] ?? 'all';

// Usar a nova função de pesquisa se houver query
if ($q !== '') {
	$livrosDisponiveis = search_available_books($q, $searchType);
} else {
	$livrosDisponiveis = get_available_books();
}

// Agrupar por livro e contar exemplares disponíveis
$agrupados = [];
foreach ($livrosDisponiveis as $row) {
	$livroId = (int)$row['li_cod'] ?? 0;
	if (!isset($agrupados[$livroId])) {
		$agrupados[$livroId] = [
			'li_cod' => $livroId,
			'li_titulo' => $row['li_titulo'],
			'li_autor' => $row['li_autor'],
			'li_ano' => $row['li_ano'],
			'li_edicao' => $row['li_edicao'],
			'li_genero' => $row['li_genero'],
			'count' => 0,
		];
	}
	$agrupados[$livroId]['count']++;
}
$agrupados = array_values($agrupados);
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Exemplares disponíveis</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<style>
	:root { --bs-primary: #7b1113; --bs-primary-rgb: 123,17,19; }
	.navbar.bg-primary { background-color: #7b1113 !important; }
	</style>
</head>
<body>
	<?php include __DIR__ . '/navbar.php'; ?>

	<main class="container my-4">
		
		<div class="row g-4">
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

			<div class="col-12 col-lg-8">
				<div class="card shadow-sm">
					<div class="card-header">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<strong>Exemplares disponíveis</strong>
							<span class="badge bg-primary"><?= count($agrupados) ?> livro(s) encontrado(s)</span>
						</div>
						<div class="row g-2" id="searchForm">
							<div class="col-md-4">
								<select class="form-select" name="search_type" id="searchType">
									<option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>Todos os campos</option>
									<option value="titulo" <?= $searchType === 'titulo' ? 'selected' : '' ?>>Título</option>
									<option value="autor" <?= $searchType === 'autor' ? 'selected' : '' ?>>Autor</option>
									<option value="genero" <?= $searchType === 'genero' ? 'selected' : '' ?>>Género</option>
								</select>
							</div>
							<div class="col-md-8">
								<input class="form-control" type="search" name="q" id="searchInput" 
									   placeholder="Digite sua pesquisa..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
							</div>
						</div>
						<?php if ($q !== ''): ?>
							<div class="mt-2">
								<small class="text-muted">
									Pesquisando por "<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" 
									em <?= $searchType === 'all' ? 'todos os campos' : $searchType ?>
									<a href="?" class="ms-2 text-decoration-none">Limpar pesquisa</a>
								</small>
							</div>
						<?php endif; ?>
					</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Género</th>
                                    <th>Ano</th>
                                    <th>Edição</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
							<tbody>
                                <?php if (!$agrupados): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Sem resultados.</td></tr>
								<?php else: foreach ($agrupados as $g): ?>
									<tr>
										<td><?= htmlspecialchars((string)$g['li_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= htmlspecialchars((string)$g['li_autor'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= htmlspecialchars((string)$g['li_genero'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= htmlspecialchars((string)$g['li_ano'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= htmlspecialchars((string)$g['li_edicao'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
											<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exemplaresModal" 
													onclick="mostrarExemplares(<?= (int)$g['li_cod'] ?>, '<?= htmlspecialchars((string)$g['li_titulo'], ENT_QUOTES, 'UTF-8') ?>')">
												Ver mais
											</button>
										</td>
									</tr>
								<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-4">
				<div class="card shadow-sm">
					<div class="card-header">Novo livro</div>
					<div class="card-body">
						<?php $editoras = get_editoras(); $generos = get_generos(); $idiomas = get_idiomas(); $autores = get_autores(); ?>
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="add_book">
							<div class="mb-3">
								<label for="titulo" class="form-label">Título</label>
								<input type="text" class="form-control" id="titulo" name="titulo" required>
								<div class="invalid-feedback">Informe o título.</div>
							</div>
							<div class="mb-3">
								<label for="autores" class="form-label">Autor(es)</label>
								<select class="form-select" id="autores">
									<option value="">Selecione um autor...</option>
									<?php foreach ($autores as $autor): ?>
										<option value="<?= (int)$autor['au_cod'] ?>" data-nome="<?= htmlspecialchars($autor['au_nome'], ENT_QUOTES, 'UTF-8') ?>" data-pais="<?= htmlspecialchars($autor['au_pais'], ENT_QUOTES, 'UTF-8') ?>">
											<?= htmlspecialchars($autor['au_nome'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($autor['au_pais'], ENT_QUOTES, 'UTF-8') ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<div class="form-text">Selecione autores para adicionar à lista</div>
								
								<!-- Área dos autores selecionados -->
								<div class="mt-3" id="autores-selecionados-container">
									<div class="text-muted text-center py-2" id="nenhum-autor">
										Nenhum autor selecionado
									</div>
								</div>
								
								<!-- Campo hidden para enviar os autores selecionados -->
								<input type="hidden" id="autores-hidden" name="autores[]" value="">
								<div class="invalid-feedback">Selecione pelo menos um autor.</div>
							</div>
							<div class="mb-3">
								<label for="isbn" class="form-label">ISBN</label>
								<input type="text" class="form-control" id="isbn" name="isbn" maxlength="13" placeholder="9781234567890" required>
								<div class="invalid-feedback">ISBN deve ter exatamente 13 dígitos.</div>

							</div>
							<div class="mb-3">
								<label for="editora" class="form-label">Editora</label>
								<select class="form-select" id="editora" name="editora" required>
									<option value="">Selecionar...</option>
									<?php foreach ($editoras as $ed): ?>
										<option data-lang="<?= htmlspecialchars($ed['ed_lang'] ?? '', ENT_QUOTES, 'UTF-8') ?>" value="<?= (int)$ed['ed_cod'] ?>"><?= htmlspecialchars($ed['ed_nome'], ENT_QUOTES, 'UTF-8') ?></option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione uma editora.</div>
							</div>
							<div class="mb-3">
								<label for="idioma" class="form-label">Idioma</label>
								<select class="form-select" id="idioma" name="idioma" required>
									<option value="">Selecionar...</option>
									<?php foreach ($idiomas as $id): ?>
										<option value="<?= htmlspecialchars($id['id_idioma'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($id['id_idioma'], ENT_QUOTES, 'UTF-8') ?></option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione um idioma.</div>
							</div>
							<div class="mb-3">
								<label for="edicao" class="form-label">Edição</label>
								<input type="number" class="form-control" id="edicao" name="edicao" required>
								<div class="invalid-feedback">Informe a edição.</div>
							</div>
							<div class="mb-3">
								<label for="ano" class="form-label">Ano</label>
								<select class="form-select" id="ano" name="ano" required>
									<option value="">Selecionar ano...</option>
									<?php 
									$anoAtual = (int)date('Y');
									for ($ano = $anoAtual; $ano >= 1900; $ano--): 
									?>
										<option value="<?= $ano ?>"><?= $ano ?></option>
									<?php endfor; ?>
								</select>
								<div class="invalid-feedback">Selecione um ano.</div>
							</div>
							<div class="mb-3">
								<label for="genero" class="form-label">Género</label>
								<select class="form-select" id="genero" name="genero" required>
									<option value="">Selecionar...</option>
									<?php foreach ($generos as $gen): ?>
										<option value="<?= htmlspecialchars($gen['ge_genero'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($gen['ge_genero'], ENT_QUOTES, 'UTF-8') ?></option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione um género.</div>
							</div>
							<div class="mb-3">
								<label for="qtd_exemplares" class="form-label">Quantidade de exemplares</label>
								<input type="number" class="form-control" id="qtd_exemplares" name="qtd_exemplares" value="1" min="1" max="50" required>
								<div class="form-text">Máx. 50 por registo.</div>
							</div>
							<button type="submit" class="btn btn-primary w-100">Salvar</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Modal para mostrar exemplares -->
	<div class="modal fade" id="exemplaresModal" tabindex="-1" aria-labelledby="exemplaresModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exemplaresModalLabel">Exemplares Disponíveis</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<h6 id="livroTitulo"></h6>
						<small class="text-muted" id="livroInfo"></small>
					</div>
					<div id="listaExemplares">
						<!-- Lista de exemplares será preenchida via JavaScript -->
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal para editar estado do exemplar -->
	<div class="modal fade" id="editarExemplarModal" tabindex="-1" aria-labelledby="editarExemplarModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="editarExemplarModalLabel">Editar Estado do Exemplar</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="editarExemplarForm">
						<input type="hidden" id="exemplarCod" name="exemplar_cod">
						
						<div class="mb-3">
							<label for="novoEstado" class="form-label">Novo Estado</label>
							<select class="form-select" id="novoEstado" name="novo_estado" required>
								<option value="">Selecione um estado...</option>
								<option value="disponivel">Disponível</option>
								<option value="emprestado">Emprestado</option>
								<option value="indisponivel">Indisponível</option>
							</select>
						</div>

						<!-- Campos para empréstimo (aparecem quando estado = emprestado) -->
						<div id="camposEmprestimo" style="display: none;">
							<div class="mb-3">
								<label for="utenteCod" class="form-label">ID do Utente</label>
								<input type="number" class="form-control" id="utenteCod" name="utente_cod" min="1">
								<div class="form-text">Digite o ID do utente que está a requisitar o exemplar</div>
							</div>
							<div class="mb-3">
								<label for="dataRequisicao" class="form-label">Data de Requisição</label>
								<input type="date" class="form-control" id="dataRequisicao" name="data_requisicao">
							</div>
						</div>

						<!-- Campos para indisponível (aparecem quando estado = indisponível) -->
						<div id="camposIndisponivel" style="display: none;">
							<div class="mb-3">
								<label for="descricaoIndisponivel" class="form-label">Descrição do Motivo</label>
								<textarea class="form-control" id="descricaoIndisponivel" name="descricao_indisponivel" rows="3" placeholder="Ex: Livro danificado, em reparação, etc."></textarea>
							</div>
						</div>

						<!-- Campo para data de mudança de estado -->
						<div class="mb-3">
							<label for="dataMudanca" class="form-label">Data da Mudança de Estado</label>
							<input type="date" class="form-control" id="dataMudanca" name="data_mudanca" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="button" class="btn btn-primary" onclick="salvarEstadoExemplar()">Salvar Alterações</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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

	// Filtro de editoras por idioma removido - todas as editoras ficam sempre visíveis

	// Pesquisa dinâmica em tempo real
	(function() {
		const searchInput = document.getElementById('searchInput');
		const searchType = document.getElementById('searchType');
		const searchForm = document.getElementById('searchForm');
		const tbody = document.querySelector('tbody');
		const badgeCount = document.querySelector('.badge.bg-primary');
		let searchTimeout;
		let isLoading = false;

		// Atualizar placeholder baseado no tipo de pesquisa
		function updatePlaceholder() {
			const type = searchType.value;
			const placeholders = {
				'all': 'Digite para pesquisar em todos os campos...',
				'titulo': 'Digite o título do livro...',
				'autor': 'Digite o nome do autor...',
				'genero': 'Digite o género do livro...'
			};
			searchInput.placeholder = placeholders[type] || placeholders['all'];
		}

		// Mostrar loading na tabela
		function showLoading() {
			if (tbody) {
				tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Pesquisando...</span></div></td></tr>';
			}
		}

		// Pesquisa via AJAX
		function performSearch() {
			if (isLoading) return;
			
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(() => {
				const query = searchInput.value.trim();
				const searchTypeValue = searchType.value;
				
				// Mostrar loading
				showLoading();
				isLoading = true;
				
				// Fazer requisição AJAX
				fetch('search_books_ajax.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: `q=${encodeURIComponent(query)}&search_type=${encodeURIComponent(searchTypeValue)}`
				})
				.then(response => response.json())
				.then(data => {
					updateTable(data);
					isLoading = false;
				})
				.catch(error => {
					console.error('Erro na pesquisa:', error);
					if (tbody) {
						tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Erro ao pesquisar livros</td></tr>';
					}
					isLoading = false;
				});
			}, 300); // Reduzido para 300ms para resposta mais rápida
		}

		// Atualizar tabela com resultados
		function updateTable(data) {
			if (!tbody) return;
			
			// Atualizar contador
			if (badgeCount) {
				badgeCount.textContent = `${data.length} livro(s) encontrado(s)`;
			}
			
			// Limpar tabela
			tbody.innerHTML = '';
			
			if (data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sem resultados.</td></tr>';
				return;
			}
			
			// Adicionar linhas dos resultados
			data.forEach(livro => {
				const row = document.createElement('tr');
				row.innerHTML = `
					<td>${escapeHtml(livro.li_titulo)}</td>
					<td>${escapeHtml(livro.li_autor)}</td>
					<td>${escapeHtml(livro.li_genero)}</td>
					<td>${escapeHtml(livro.li_ano)}</td>
					<td>${escapeHtml(livro.li_edicao)}</td>
					<td>
						<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exemplaresModal" 
								onclick="mostrarExemplares(${livro.li_cod}, '${livro.li_titulo.replace(/'/g, "\\'")}')">
							Ver mais
						</button>
					</td>
				`;
				tbody.appendChild(row);
			});
		}

		// Função para escapar HTML
		function escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		// Event listeners
		if (searchInput) {
			searchInput.addEventListener('input', performSearch);
			searchInput.addEventListener('keypress', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					clearTimeout(searchTimeout);
					performSearch();
				}
			});
		}

		if (searchType) {
			searchType.addEventListener('change', function() {
				updatePlaceholder();
				// Fazer nova pesquisa quando mudar o tipo
				performSearch();
			});
			updatePlaceholder(); // Inicializar placeholder
		}

		// Auto-focus no campo de pesquisa se estiver vazio
		if (searchInput && !searchInput.value) {
			searchInput.focus();
		}
	})();

	// Máscara e validação para ISBN
	(function() {
		const isbnInput = document.getElementById('isbn');
		
		if (isbnInput) {
			// Aplicar máscara no input - apenas números
			isbnInput.addEventListener('input', function(e) {
				let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
				
				// Limita a 13 dígitos
				if (value.length > 13) {
					value = value.substring(0, 13);
				}
				
				e.target.value = value;
			});

			// Prevenir entrada de caracteres não numéricos
			isbnInput.addEventListener('keypress', function(e) {
				// Permite: backspace, delete, tab, escape, enter
				if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
					// Permite: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
					(e.keyCode === 65 && e.ctrlKey === true) ||
					(e.keyCode === 67 && e.ctrlKey === true) ||
					(e.keyCode === 86 && e.ctrlKey === true) ||
					(e.keyCode === 88 && e.ctrlKey === true)) {
					return;
				}
				// Permite apenas números
				if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
					e.preventDefault();
				}
			});

			// Validação personalizada
			isbnInput.addEventListener('blur', function(e) {
				const value = e.target.value;
				
				if (value && (value.length !== 13 || !/^\d{13}$/.test(value))) {
					e.target.setCustomValidity('ISBN deve ter exatamente 13 dígitos numéricos');
				} else {
					e.target.setCustomValidity('');
				}
			});

			// Validação no submit
			const form = isbnInput.closest('form');
			if (form) {
				form.addEventListener('submit', function(e) {
					const value = isbnInput.value;
					
					if (value && (value.length !== 13 || !/^\d{13}$/.test(value))) {
						isbnInput.setCustomValidity('ISBN deve ter exatamente 13 dígitos numéricos');
						e.preventDefault();
						e.stopPropagation();
					} else {
						isbnInput.setCustomValidity('');
					}
				});
			}
		}
	})();

	// Sistema de seleção de autores com caixa de selecionados
	(function() {
		const autoresSelect = document.getElementById('autores');
		const autoresSelecionadosContainer = document.getElementById('autores-selecionados-container');
		const nenhumAutor = document.getElementById('nenhum-autor');
		const autoresHidden = document.getElementById('autores-hidden');
		const autoresSelecionadosList = new Set();

		if (autoresSelect && autoresSelecionadosContainer) {
			// Adicionar autor selecionado
			function adicionarAutor(autorCod, nome, pais) {
				if (autoresSelecionadosList.has(autorCod)) {
					return; // Autor já selecionado
				}

				autoresSelecionadosList.add(autorCod);

				// Criar elemento do autor selecionado
				const autorElement = document.createElement('div');
				autorElement.className = 'd-flex align-items-center mb-1 p-1 bg-white border rounded';
				autorElement.setAttribute('data-autor-cod', autorCod);
				
				autorElement.innerHTML = `
					<button type="button" class="btn btn-sm btn-outline-danger me-1" onclick="removerAutor(${autorCod})" style="padding: 1px 4px; border-radius: 2px; font-size: 10px; min-width: 20px;">
						<svg width="8" height="8" viewBox="0 0 16 16" fill="currentColor">
							<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
						</svg>
					</button>
					<span class="fw-medium" style="font-size: 14px;">${nome}</span>
				`;

				// Esconder mensagem "Nenhum autor selecionado" se for o primeiro
				if (autoresSelecionadosList.size === 1) {
					nenhumAutor.style.display = 'none';
				}

				// Adicionar o elemento
				autoresSelecionadosContainer.appendChild(autorElement);

				// Atualizar campo hidden
				atualizarCampoHidden();

				// Remover opção do select
				const option = autoresSelect.querySelector(`option[value="${autorCod}"]`);
				if (option) {
					option.style.display = 'none';
				}

				// Resetar seleção
				autoresSelect.value = '';
			}

			// Remover autor selecionado
			window.removerAutor = function(autorCod) {
				autoresSelecionadosList.delete(autorCod);

				// Remover elemento visual
				const autorElement = autoresSelecionadosContainer.querySelector(`[data-autor-cod="${autorCod}"]`);
				if (autorElement) {
					autorElement.remove();
				}

				// Mostrar mensagem "Nenhum autor selecionado" se não houver mais autores
				if (autoresSelecionadosList.size === 0) {
					nenhumAutor.style.display = 'block';
				}

				// Atualizar campo hidden
				atualizarCampoHidden();

				// Mostrar opção no select novamente
				const option = autoresSelect.querySelector(`option[value="${autorCod}"]`);
				if (option) {
					option.style.display = 'block';
				}
			};

			// Atualizar campo hidden com os códigos dos autores selecionados
			function atualizarCampoHidden() {
				const codigos = Array.from(autoresSelecionadosList);
				autoresHidden.value = codigos.join(',');
			}

			// Event listener para seleção de autor
			autoresSelect.addEventListener('change', function() {
				const selectedOption = this.options[this.selectedIndex];
				if (selectedOption.value && !autoresSelecionadosList.has(selectedOption.value)) {
					const nome = selectedOption.getAttribute('data-nome');
					const pais = selectedOption.getAttribute('data-pais');
					adicionarAutor(selectedOption.value, nome, pais);
				}
			});

			// Validação personalizada
			function validarAutores() {
				if (autoresSelecionadosList.size === 0) {
					autoresSelect.setCustomValidity('Selecione pelo menos um autor');
					return false;
				} else {
					autoresSelect.setCustomValidity('');
					return true;
				}
			}

			// Validação no submit
			const form = autoresSelect.closest('form');
			if (form) {
				form.addEventListener('submit', function(e) {
					if (!validarAutores()) {
						e.preventDefault();
						e.stopPropagation();
					}
				});
			}

			// Validação em tempo real
			autoresSelect.addEventListener('change', validarAutores);
		}
	})();

	// Função para mostrar exemplares no modal
	window.mostrarExemplares = function(livroCod, titulo) {
		// Atualizar título do modal
		document.getElementById('livroTitulo').textContent = titulo;
		document.getElementById('livroTitulo').setAttribute('data-livro-cod', livroCod);
		
		// Mostrar loading
		const listaExemplares = document.getElementById('listaExemplares');
		listaExemplares.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
		
		// Fazer requisição AJAX para obter exemplares com status
		fetch('get_exemplares.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'livro_cod=' + encodeURIComponent(livroCod)
		})
		.then(response => response.json())
		.then(data => {
			// Atualizar informações do livro
			const livroInfo = document.getElementById('livroInfo');
			livroInfo.textContent = `Código do Livro: ${livroCod} | Total de Exemplares: ${data.length}`;
			
			// Limpar lista anterior
			listaExemplares.innerHTML = '';
			
			// Criar lista de exemplares
			if (data.length === 0) {
				listaExemplares.innerHTML = '<div class="text-center text-muted py-3">Nenhum exemplar disponível</div>';
			} else {
				data.forEach((exemplar, index) => {
					const exemplarDiv = document.createElement('div');
					exemplarDiv.className = 'card mb-2';
					
					// Determinar estado e cor do badge baseado no status
					let estado, corBadge;
					switch (exemplar.status) {
						case 'disponivel':
							estado = 'Disponível';
							corBadge = 'bg-success';
							break;
						case 'emprestado':
							estado = 'Emprestado';
							corBadge = 'bg-warning';
							break;
						case 'indisponivel':
						default:
							estado = 'Indisponível';
							corBadge = 'bg-danger';
							break;
					}
					
					// Determinar se está atrasado (mais de 14 dias)
					let isAtrasado = false;
					let dataDevolucaoTexto = '';
					let dataDevolucaoClass = '';
					
					if (exemplar.status === 'emprestado' && exemplar.re_datarequisicao) {
						const dataRequisicao = new Date(exemplar.re_datarequisicao);
						const hoje = new Date();
						const diasEmprestado = Math.floor((hoje - dataRequisicao) / (1000 * 60 * 60 * 24));
						isAtrasado = diasEmprestado > 14;
						
						// Calcular data de devolução esperada (14 dias após requisição)
						const dataDevolucaoEsperada = new Date(dataRequisicao);
						dataDevolucaoEsperada.setDate(dataDevolucaoEsperada.getDate() + 14);
						
						dataDevolucaoTexto = dataDevolucaoEsperada.toLocaleDateString('pt-PT');
						dataDevolucaoClass = isAtrasado ? 'text-danger fw-bold' : 'text-muted';
					}
					
					exemplarDiv.innerHTML = `
						<div class="card-body py-2">
							<div class="row align-items-center">
								<div class="col-md-6">
									<h6 class="card-title mb-1">${titulo}#${index + 1}</h6>
									<div class="d-flex align-items-center gap-2 mb-2">
										<span class="badge bg-secondary">ID: ${exemplar.ex_cod}</span>
										<small class="text-muted">Estado: ${exemplar.ex_estado || 'N/A'}</small>
									</div>
									${exemplar.status === 'emprestado' ? `
										<div class="mt-2 p-2 bg-light rounded">
											<div class="row">
												<div class="col-6">
													<small class="text-muted d-block">ID Utente:</small>
													<span class="badge bg-info">${exemplar.utente_cod || 'N/A'}</span>
												</div>
												<div class="col-6">
													<small class="text-muted d-block">Data Requisição:</small>
													<small>${exemplar.re_datarequisicao ? new Date(exemplar.re_datarequisicao).toLocaleDateString('pt-PT') : 'N/A'}</small>
												</div>
											</div>
											<div class="row mt-1">
												<div class="col-6">
													<small class="text-muted d-block">Data Devolução:</small>
													<small class="${dataDevolucaoClass}">${dataDevolucaoTexto}</small>
													${isAtrasado ? '<span class="badge bg-danger ms-2">ATRASADO</span>' : ''}
												</div>
												<div class="col-6">
													<small class="text-muted d-block">Status:</small>
													<small class="text-muted">${isAtrasado ? 'Atrasado' : 'No prazo'}</small>
												</div>
											</div>
											<div class="row mt-1">
												<div class="col-12">
													<small class="text-muted d-block">Nome:</small>
													<strong>${exemplar.utente_nome || 'N/A'}</strong>
												</div>
											</div>
											<div class="row mt-1">
												<div class="col-12">
													<small class="text-muted d-block">Email:</small>
													<small>${exemplar.utente_email || 'N/A'}</small>
												</div>
											</div>
										</div>
									` : ''}
								</div>
								<div class="col-md-6 text-end">
									<div class="d-flex flex-column align-items-end gap-2">
										<span class="badge ${corBadge} fs-6">${estado}</span>
										${isAtrasado ? '<span class="badge bg-danger">ATRASADO</span>' : ''}
										<button type="button" class="btn btn-sm btn-outline-primary" 
												onclick="editarExemplar(${exemplar.ex_cod}, '${exemplar.status}', '${exemplar.utente_cod || ''}', '${exemplar.utente_nome || ''}', '${exemplar.utente_email || ''}', '${exemplar.re_datarequisicao || ''}')">
											<i class="bi bi-pencil"></i> Editar
										</button>
									</div>
								</div>
							</div>
						</div>
					`;
					listaExemplares.appendChild(exemplarDiv);
				});
			}
		})
		.catch(error => {
			console.error('Erro ao carregar exemplares:', error);
			listaExemplares.innerHTML = '<div class="text-center text-danger py-3">Erro ao carregar exemplares</div>';
		});
	};

	// Função para abrir modal de edição do exemplar
	window.editarExemplar = function(exemplarCod, statusAtual, utenteCod, utenteNome, utenteEmail, dataRequisicao) {
		// Preencher campos do formulário
		document.getElementById('exemplarCod').value = exemplarCod;
		document.getElementById('novoEstado').value = statusAtual;
		document.getElementById('utenteCod').value = utenteCod || '';
		document.getElementById('dataRequisicao').value = dataRequisicao ? dataRequisicao.split('T')[0] : '';
		document.getElementById('dataMudanca').value = new Date().toISOString().split('T')[0];
		
		// Mostrar/ocultar campos baseado no estado atual
		toggleCamposEstado(statusAtual);
		
		// Mostrar modal
		const modal = new bootstrap.Modal(document.getElementById('editarExemplarModal'));
		modal.show();
	};

	// Função para alternar campos baseado no estado selecionado
	function toggleCamposEstado(estado) {
		const camposEmprestimo = document.getElementById('camposEmprestimo');
		const camposIndisponivel = document.getElementById('camposIndisponivel');
		
		if (estado === 'emprestado') {
			camposEmprestimo.style.display = 'block';
			camposIndisponivel.style.display = 'none';
		} else if (estado === 'indisponivel') {
			camposEmprestimo.style.display = 'none';
			camposIndisponivel.style.display = 'block';
		} else {
			camposEmprestimo.style.display = 'none';
			camposIndisponivel.style.display = 'none';
		}
	}

	// Event listener para mudança de estado
	document.getElementById('novoEstado').addEventListener('change', function() {
		toggleCamposEstado(this.value);
	});

	// Função para salvar estado do exemplar
	window.salvarEstadoExemplar = function() {
		const form = document.getElementById('editarExemplarForm');
		const formData = new FormData(form);
		
		// Validações
		const novoEstado = formData.get('novo_estado');
		if (!novoEstado) {
			alert('Selecione um estado válido.');
			return;
		}
		
		if (novoEstado === 'emprestado') {
			const utenteCod = formData.get('utente_cod');
			const dataRequisicao = formData.get('data_requisicao');
			if (!utenteCod || !dataRequisicao) {
				alert('Para empréstimo, é necessário informar o ID do utente e a data de requisição.');
				return;
			}
		}
		
		// Enviar dados via AJAX
		fetch('update_exemplar_status.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Fechar modal
				const modal = bootstrap.Modal.getInstance(document.getElementById('editarExemplarModal'));
				modal.hide();
				
				// Recarregar lista de exemplares
				const livroCod = document.getElementById('livroTitulo').getAttribute('data-livro-cod');
				const livroTitulo = document.getElementById('livroTitulo').textContent;
				mostrarExemplares(livroCod, livroTitulo);
				
				// Mostrar mensagem de sucesso
				alert('Estado do exemplar atualizado com sucesso!');
			} else {
				alert('Erro ao atualizar estado: ' + (data.error || 'Erro desconhecido'));
			}
		})
		.catch(error => {
			console.error('Erro:', error);
			alert('Erro ao comunicar com o servidor.');
		});
	};
	</script>
</body>
</html>


