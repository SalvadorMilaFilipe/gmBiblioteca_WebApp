<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

ensure_schema();

$pdo = get_pdo();
$errors = [];
$success = null;

// Criar autor
if (($_POST['action'] ?? '') === 'create') {
	$au_nome = trim($_POST['au_nome'] ?? '');
	$au_pais = trim($_POST['au_pais'] ?? '');
	if ($au_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($au_pais === '') $errors[] = 'País é obrigatório.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('INSERT INTO autor (au_nome, au_pais) VALUES (?, ?)');
			$stmt->execute([$au_nome, $au_pais]);
			$success = 'Autor criado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao criar autor: ' . $e->getMessage();
		}
	}
}

// Atualizar autor
if (($_POST['action'] ?? '') === 'update') {
	$au_cod_pk = (int)($_POST['au_cod_pk'] ?? 0);
	$au_nome = trim($_POST['au_nome'] ?? '');
	$au_pais = trim($_POST['au_pais'] ?? '');
	if ($au_cod_pk <= 0) $errors[] = 'Autor inválido.';
	if ($au_nome === '') $errors[] = 'Nome é obrigatório.';
	if ($au_pais === '') $errors[] = 'País é obrigatório.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('UPDATE autor SET au_nome = ?, au_pais = ? WHERE au_cod = ?');
			$stmt->execute([$au_nome, $au_pais, $au_cod_pk]);
			$success = 'Autor atualizado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao atualizar autor: ' . $e->getMessage();
		}
	}
}

// Eliminar autor
if (($_POST['action'] ?? '') === 'delete') {
	$au_cod_pk = (int)($_POST['au_cod_pk'] ?? 0);
	if ($au_cod_pk <= 0) {
		$errors[] = 'Autor inválido.';
	} else {
		try {
			$stmt = $pdo->prepare('DELETE FROM autor WHERE au_cod = ?');
			$stmt->execute([$au_cod_pk]);
			$success = 'Autor eliminado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao eliminar autor: ' . $e->getMessage();
		}
	}
}

// Listagem
$lista = [];
try {
	$lista = $pdo->query('SELECT au_cod, au_nome, au_pais FROM autor ORDER BY au_nome')->fetchAll();
} catch (Throwable $e) {
	$errors[] = 'Erro ao carregar autores: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Autores</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

			<div class="col-12 col-lg-5">
				<div class="card shadow-sm">
					<div class="card-header">Novo autor</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label for="au_nome" class="form-label">Nome</label>
								<input type="text" class="form-control" id="au_nome" name="au_nome" required>
								<div class="invalid-feedback">Informe o nome do autor.</div>
							</div>
							<div class="mb-3">
								<label for="au_pais" class="form-label">País</label>
								<input type="text" class="form-control" id="au_pais" name="au_pais" required>
								<div class="invalid-feedback">Informe o país.</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Autores</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Nome</th>
									<th>País</th>
									<th style="width: 160px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$lista): ?>
									<tr><td colspan="3" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($lista as $row): ?>
									<tr>
										<td>
											<form method="post" class="row g-2 align-items-center">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="au_cod_pk" value="<?= (int)$row['au_cod'] ?>">
												<div class="col-12 col-md-6 col-lg-12 mb-2 mb-md-0">
													<input type="text" class="form-control" name="au_nome" value="<?= htmlspecialchars($row['au_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
												</div>
										</td>
										<td>
											<div class="col-12">
												<input type="text" class="form-control" name="au_pais" value="<?= htmlspecialchars($row['au_pais'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
											</div>
										</td>
										<td>
											<div class="d-flex gap-2">
												<button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
											</div>
											</form>
											<form method="post" onsubmit="return confirm('Eliminar este autor?');" style="display:inline">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="au_cod_pk" value="<?= (int)$row['au_cod'] ?>">
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


