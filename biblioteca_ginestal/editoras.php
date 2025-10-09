<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();
// Removido o seeding automático para que eliminações sejam permanentes nesta página

$pdo = get_pdo();
$errors = [];
$success = null;

// Ações CRUD
if (($_POST['action'] ?? '') === 'create') {
	$ed_nome = trim($_POST['ed_nome'] ?? '');
	$ed_morada = trim($_POST['ed_morada'] ?? '');
	$ed_email = trim($_POST['ed_email'] ?? '');
	$ed_codpostal = trim($_POST['ed_codpostal'] ?? '');
	$ed_tlm = trim($_POST['ed_tlm'] ?? '');

	if ($ed_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($ed_morada === '') $errors[] = 'Morada é obrigatória.';
	if ($ed_email === '') $errors[] = 'Email é obrigatório.';
	if ($ed_codpostal === '') $errors[] = 'Código postal é obrigatório.';
	if ($ed_tlm === '') $errors[] = 'Telemóvel é obrigatório.';

	if (!$errors) {
		try {
			$stmt = $pdo->prepare('INSERT INTO editora (ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$ed_nome, $ed_morada, $ed_email, $ed_codpostal, $ed_tlm]);
			$success = 'Editora criada com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao criar editora: ' . $e->getMessage();
		}
	}
}

if (($_POST['action'] ?? '') === 'update') {
	$ed_cod = (int)($_POST['ed_cod'] ?? 0);
	$ed_nome = trim($_POST['ed_nome'] ?? '');
	$ed_morada = trim($_POST['ed_morada'] ?? '');
	$ed_email = trim($_POST['ed_email'] ?? '');
	$ed_codpostal = trim($_POST['ed_codpostal'] ?? '');
	$ed_tlm = trim($_POST['ed_tlm'] ?? '');

	if ($ed_cod <= 0) $errors[] = 'Editora inválida.';
	if ($ed_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($ed_morada === '') $errors[] = 'Morada é obrigatória.';
	if ($ed_email === '') $errors[] = 'Email é obrigatório.';
	if ($ed_codpostal === '') $errors[] = 'Código postal é obrigatório.';
	if ($ed_tlm === '') $errors[] = 'Telemóvel é obrigatório.';

	if (!$errors) {
		try {
			$stmt = $pdo->prepare('UPDATE editora SET ed_nome = ?, ed_morada = ?, ed_email = ?, ed_codpostal = ?, ed_tlm = ? WHERE ed_cod = ?');
			$stmt->execute([$ed_nome, $ed_morada, $ed_email, $ed_codpostal, $ed_tlm, $ed_cod]);
			$success = 'Editora atualizada com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao atualizar editora: ' . $e->getMessage();
		}
	}
}

if (($_POST['action'] ?? '') === 'delete') {
	$ed_cod = (int)($_POST['ed_cod'] ?? 0);
	if ($ed_cod <= 0) {
		$errors[] = 'Editora inválida.';
	} else {
		try {
			$stmt = $pdo->prepare('DELETE FROM editora WHERE ed_cod = ?');
			$stmt->execute([$ed_cod]);
			$success = 'Editora eliminada com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao eliminar editora: ' . $e->getMessage();
		}
	}
}

// Eliminar todas
if (($_POST['action'] ?? '') === 'delete_all') {
	try {
		$pdo->exec('DELETE FROM editora');
		$success = 'Todas as editoras foram eliminadas.';
	} catch (PDOException $e) {
		$errors[] = 'Erro ao eliminar todas as editoras: ' . $e->getMessage();
	}
}

// Dados para listagens
$editoras = [];
$codigosPostais = [];
try {
	$editoras = $pdo->query('SELECT ed_cod, ed_nome, ed_morada, ed_email, ed_codpostal, ed_tlm FROM editora ORDER BY ed_nome')->fetchAll();
	$codigosPostais = $pdo->query('SELECT cod_postal, cod_localidade FROM codigo_postal ORDER BY cod_postal')->fetchAll();
} catch (Throwable $e) {
	$errors[] = 'Erro ao carregar dados: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Editoras</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
	:root { --bs-primary: #7b1113; --bs-primary-rgb: 123,17,19; }
	.navbar.bg-primary { background-color: #7b1113 !important; }
	
	/* Aumentar tamanho dos inputs na tabela de editoras */
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
	.table select {
		min-width: 120px;
		width: 100%;
		font-size: 0.9rem;
		padding: 6px 8px;
	}
	
	/* Ajustar colunas específicas */
	.table td:nth-child(1) input { min-width: 150px; } /* Nome */
	.table td:nth-child(2) input { min-width: 200px; } /* Morada */
	.table td:nth-child(3) input { min-width: 180px; } /* Email */
	.table td:nth-child(4) select { min-width: 160px; } /* Código postal */
	.table td:nth-child(5) input { min-width: 140px; } /* Telemóvel */
	.table td:nth-child(6) { min-width: 180px; } /* Ações */
	
	/* Garantir que a tabela não quebra o layout */
	.table {
		table-layout: auto;
		width: max-content;
		min-width: 100%;
	}
	
	/* Melhorar responsividade */
	@media (max-width: 1200px) {
		.table td:nth-child(1) input { min-width: 120px; }
		.table td:nth-child(2) input { min-width: 150px; }
		.table td:nth-child(3) input { min-width: 140px; }
		.table td:nth-child(4) select { min-width: 120px; }
		.table td:nth-child(5) input { min-width: 110px; }
	}
	
	@media (max-width: 992px) {
		.table td:nth-child(1) input { min-width: 100px; }
		.table td:nth-child(2) input { min-width: 120px; }
		.table td:nth-child(3) input { min-width: 120px; }
		.table td:nth-child(4) select { min-width: 100px; }
		.table td:nth-child(5) input { min-width: 100px; }
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
				<div class="d-flex justify-content-end mb-2">
					<form method="post" onsubmit="return confirm('Tem a certeza que pretende eliminar TODAS as editoras?');">
						<input type="hidden" name="action" value="delete_all">
						<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar todas</button>
					</form>
				</div>
				<div class="card shadow-sm">
					<div class="card-header">Nova editora</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label class="form-label" for="ed_nome">Nome</label>
								<input type="text" class="form-control" id="ed_nome" name="ed_nome" required>
								<div class="invalid-feedback">Informe o nome da editora.</div>
							</div>
							<div class="mb-3">
								<label class="form-label" for="ed_morada">Morada</label>
								<input type="text" class="form-control" id="ed_morada" name="ed_morada" required>
								<div class="invalid-feedback">Informe a morada.</div>
							</div>
							<div class="mb-3">
								<label class="form-label" for="ed_email">Email</label>
								<input type="email" class="form-control" id="ed_email" name="ed_email" required>
								<div class="invalid-feedback">Informe um email válido.</div>
							</div>
							<div class="mb-3">
								<label class="form-label" for="ed_codpostal">Código postal</label>
								<select class="form-select" id="ed_codpostal" name="ed_codpostal" required>
									<option value="">Selecionar...</option>
									<?php foreach ($codigosPostais as $cp): ?>
										<option value="<?= htmlspecialchars($cp['cod_postal'], ENT_QUOTES, 'UTF-8') ?>">
											<?= htmlspecialchars($cp['cod_postal'] . ' - ' . $cp['cod_localidade'], ENT_QUOTES, 'UTF-8') ?>
										</option>
									<?php endforeach; ?>
								</select>
								<div class="invalid-feedback">Selecione um código postal.</div>
							</div>
							<div class="mb-3">
								<label class="form-label" for="ed_tlm">Telemóvel</label>
								<input type="text" class="form-control" id="ed_tlm" name="ed_tlm" required>
								<div class="invalid-feedback">Informe o número de telemóvel.</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Lista de editoras</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Nome</th>
									<th>Morada</th>
									<th>Email</th>
									<th>Código postal</th>
									<th>Telemóvel</th>
									<th style="width: 180px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$editoras): ?>
									<tr><td colspan="6" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($editoras as $ed): ?>
									<tr>
										<td>
											<form method="post" class="row g-2 align-items-center">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="ed_cod" value="<?= (int)$ed['ed_cod'] ?>">
												<div class="col-12 col-md-6 col-lg-12 mb-2 mb-md-0">
													<input type="text" class="form-control" name="ed_nome" value="<?= htmlspecialchars($ed['ed_nome'], ENT_QUOTES, 'UTF-8') ?>" required>
												</div>
										</td>
										<td>
											<div class="col-12">
												<input type="text" class="form-control" name="ed_morada" value="<?= htmlspecialchars($ed['ed_morada'], ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="col-12">
												<input type="email" class="form-control" name="ed_email" value="<?= htmlspecialchars($ed['ed_email'], ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="col-12">
												<select class="form-select" name="ed_codpostal" required>
													<option value="">Selecionar...</option>
													<?php foreach ($codigosPostais as $cp): ?>
														<option value="<?= htmlspecialchars($cp['cod_postal'], ENT_QUOTES, 'UTF-8') ?>" <?= ($cp['cod_postal'] === $ed['ed_codpostal']) ? 'selected' : '' ?>>
															<?= htmlspecialchars($cp['cod_postal'] . ' - ' . $cp['cod_localidade'], ENT_QUOTES, 'UTF-8') ?>
														</option>
													<?php endforeach; ?>
												</select>
											</div>
										</td>
										<td>
											<div class="col-12">
												<input type="text" class="form-control" name="ed_tlm" value="<?= htmlspecialchars($ed['ed_tlm'], ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="d-flex gap-2">
												<button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
											</div>
											</form>
											<form method="post" onsubmit="return confirm('Eliminar esta editora?');" style="display:inline">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="ed_cod" value="<?= (int)$ed['ed_cod'] ?>">
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
	</script>
</body>
</html>


