<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();

$pdo = get_pdo();
$errors = [];
$success = null;

// Criar novo utente
if (($_POST['action'] ?? '') === 'create') {
	$ut_nome = trim($_POST['ut_nome'] ?? '');
	$ut_email = trim($_POST['ut_email'] ?? '');
	$ut_turma = trim($_POST['ut_turma'] ?? '');
	$ut_ano = (int)($_POST['ut_ano'] ?? 0);
	if ($ut_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($ut_email === '') $errors[] = 'Email é obrigatório.';
	if ($ut_turma === '') $errors[] = 'Turma é obrigatória.';
	if ($ut_ano < 7 || $ut_ano > 12) $errors[] = 'Ano deve ser entre 7º e 12º ano.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('INSERT INTO utente (ut_nome, ut_email, ut_turma, ut_ano) VALUES (?, ?, ?, ?)');
			$stmt->execute([$ut_nome, $ut_email, $ut_turma, $ut_ano]);
			$success = 'Utente registado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao registar utente: ' . $e->getMessage();
		}
	}
}

// Atualizar utente
if (($_POST['action'] ?? '') === 'update') {
	$ut_cod_pk = (int)($_POST['ut_cod_pk'] ?? 0);
	$ut_nome = trim($_POST['ut_nome'] ?? '');
	$ut_email = trim($_POST['ut_email'] ?? '');
	$ut_turma = trim($_POST['ut_turma'] ?? '');
	$ut_ano = (int)($_POST['ut_ano'] ?? 0);
	if ($ut_cod_pk <= 0) $errors[] = 'Utente inválido.';
	if ($ut_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($ut_email === '') $errors[] = 'Email é obrigatório.';
	if ($ut_turma === '') $errors[] = 'Turma é obrigatória.';
	if ($ut_ano < 7 || $ut_ano > 12) $errors[] = 'Ano deve ser entre 7º e 12º ano.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('UPDATE utente SET ut_nome = ?, ut_email = ?, ut_turma = ?, ut_ano = ? WHERE ut_cod = ?');
			$stmt->execute([$ut_nome, $ut_email, $ut_turma, $ut_ano, $ut_cod_pk]);
			$success = 'Utente atualizado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao atualizar utente: ' . $e->getMessage();
		}
	}
}

// Eliminar utente
if (($_POST['action'] ?? '') === 'delete') {
	$ut_cod_pk = (int)($_POST['ut_cod_pk'] ?? 0);
	if ($ut_cod_pk <= 0) {
		$errors[] = 'Utente inválido.';
	} else {
		try {
			$stmt = $pdo->prepare('DELETE FROM utente WHERE ut_cod = ?');
			$stmt->execute([$ut_cod_pk]);
			$success = 'Utente eliminado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao eliminar utente: ' . $e->getMessage();
		}
	}
}

// Listagem
$lista = [];
try {
	$lista = $pdo->query('SELECT ut_cod, ut_nome, ut_email, ut_turma, ut_ano FROM utente ORDER BY ut_nome')->fetchAll();
} catch (Throwable $e) {
	$errors[] = 'Erro ao carregar utentes: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Utentes</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
	:root { --bs-primary: #7b1113; --bs-primary-rgb: 123,17,19; }
	.navbar.bg-primary { background-color: #7b1113 !important; }
	
	/* Aumentar tamanho dos inputs na tabela de utentes */
	.table-responsive {
		overflow-x: auto;
		min-width: 100%;
	}
	
	.table td {
		padding: 8px 4px;
		vertical-align: middle;
	}
	
	.table input[type="text"],
	.table input[type="email"],
	.table input[type="number"],
	.table select {
		min-width: 120px;
		width: 100%;
		font-size: 0.9rem;
		padding: 6px 8px;
	}
	
	/* Ajustar colunas específicas para utentes */
	.table td:nth-child(1) input { min-width: 180px; } /* Nome */
	.table td:nth-child(2) input { min-width: 200px; } /* Email */
	.table td:nth-child(3) input { min-width: 120px; } /* Turma */
	.table td:nth-child(4) input { min-width: 100px; } /* Ano */
	.table td:nth-child(5) { min-width: 180px; } /* Ações */
	
	/* Garantir que a tabela não quebra o layout */
	.table {
		table-layout: auto;
		width: max-content;
		min-width: 100%;
	}
	
	/* Melhorar responsividade */
	@media (max-width: 1200px) {
		.table td:nth-child(1) input { min-width: 150px; }
		.table td:nth-child(2) input { min-width: 160px; }
		.table td:nth-child(3) input { min-width: 100px; }
		.table td:nth-child(4) input { min-width: 80px; }
	}
	
	@media (max-width: 992px) {
		.table td:nth-child(1) input { min-width: 120px; }
		.table td:nth-child(2) input { min-width: 140px; }
		.table td:nth-child(3) input { min-width: 80px; }
		.table td:nth-child(4) input { min-width: 70px; }
	}
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

			<div class="col-12 col-lg-5">
				<div class="card shadow-sm">
					<div class="card-header">Registar utente</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label for="ut_nome" class="form-label">Nome</label>
								<input type="text" class="form-control" id="ut_nome" name="ut_nome" required>
								<div class="invalid-feedback">Informe o nome.</div>
							</div>
							<div class="mb-3">
								<label for="ut_email" class="form-label">Email</label>
								<input type="email" class="form-control" id="ut_email" name="ut_email" required>
								<div class="invalid-feedback">Informe um email válido.</div>
							</div>
							<div class="mb-3">
								<label for="ut_turma" class="form-label">Turma</label>
								<input type="text" class="form-control" id="ut_turma" name="ut_turma" required>
								<div class="invalid-feedback">Informe a turma.</div>
							</div>
							<div class="mb-3">
								<label for="ut_ano" class="form-label">Ano</label>
								<input type="number" class="form-control" id="ut_ano" name="ut_ano" min="7" max="12" step="1" required>
								<div class="invalid-feedback">Ano deve ser entre 7º e 12º ano.</div>
								<div class="form-text">Ano escolar: 7º ao 12º ano</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Utentes existentes</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nome</th>
									<th>Email</th>
									<th>Turma</th>
									<th>Ano</th>
									<th style="width: 180px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$lista): ?>
									<tr><td colspan="6" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($lista as $row): ?>
									<tr>
										<td>
											<span class="badge bg-secondary"><?= (int)$row['ut_cod'] ?></span>
										</td>
										<td>
											<form method="post" class="row g-2 align-items-center">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="ut_cod_pk" value="<?= (int)$row['ut_cod'] ?>">
												<div class="col-12 col-md-6 col-lg-12 mb-2 mb-md-0">
													<input type="text" class="form-control" name="ut_nome" value="<?= htmlspecialchars($row['ut_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
												</div>
										</td>
										<td>
											<div class="col-12">
												<input type="email" class="form-control" name="ut_email" value="<?= htmlspecialchars($row['ut_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="col-12">
												<input type="text" class="form-control" name="ut_turma" value="<?= htmlspecialchars($row['ut_turma'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="col-12">
												<input type="number" class="form-control" name="ut_ano" min="7" max="12" step="1" value="<?= (int)($row['ut_ano'] ?? 7) ?>" required>
											</div>
										</td>
										<td>
											<div class="d-flex gap-2">
												<button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
											</div>
											</form>
											<form method="post" onsubmit="return confirm('Eliminar este utente?');" style="display:inline">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="ut_cod_pk" value="<?= (int)$row['ut_cod'] ?>">
												<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
											</form>
										</td>
									</tr>
								<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
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

	// Validação específica para ano escolar
	(function() {
		const anoInputs = document.querySelectorAll('input[name="ut_ano"]');
		
		anoInputs.forEach(input => {
			// Validação em tempo real
			input.addEventListener('input', function(e) {
				const value = parseInt(e.target.value);
				
				if (value < 7 || value > 12) {
					e.target.setCustomValidity('Ano deve ser entre 7º e 12º ano');
				} else {
					e.target.setCustomValidity('');
				}
			});

			// Validação no blur
			input.addEventListener('blur', function(e) {
				const value = parseInt(e.target.value);
				
				if (isNaN(value) || value < 7 || value > 12) {
					e.target.setCustomValidity('Ano deve ser entre 7º e 12º ano');
				} else {
					e.target.setCustomValidity('');
				}
			});

			// Prevenir entrada de valores inválidos
			input.addEventListener('keypress', function(e) {
				const value = parseInt(e.target.value + e.key);
				
				if (e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab' && e.key !== 'Enter') {
					if (isNaN(value) || value < 7 || value > 12) {
						e.preventDefault();
					}
				}
			});
		});
	})();
	</script>
</body>
</html>


