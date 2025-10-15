<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

ensure_schema();

// Não usar get_pdo() diretamente - usar funções específicas da API REST
$errors = [];
$success = null;

// Criar novo género
if (($_POST['action'] ?? '') === 'create') {
	$ge_genero = trim($_POST['ge_genero'] ?? '');
	if ($ge_genero === '') $errors[] = 'Género é obrigatório.';
	if (!$errors) {
		try {
			execute_rest_insert('genero', [
				'ge_genero' => $ge_genero
			]);
			$success = 'Género adicionado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao adicionar género: ' . $e->getMessage();
		}
	}
}

// Atualizar género (renomear)
if (($_POST['action'] ?? '') === 'update') {
	$ge_genero_pk = trim($_POST['ge_genero_pk'] ?? '');
	$ge_genero_new = trim($_POST['ge_genero_new'] ?? '');
	if ($ge_genero_pk === '') $errors[] = 'Género inválido.';
	if ($ge_genero_new === '') $errors[] = 'Novo nome é obrigatório.';
	if (!$errors) {
		try {
			execute_rest_update('genero', [
				'ge_genero' => $ge_genero_new
			], ['ge_genero' => $ge_genero_pk]);
			$success = 'Género atualizado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao atualizar género: ' . $e->getMessage();
		}
	}
}

// Eliminar género
if (($_POST['action'] ?? '') === 'delete') {
	$ge_genero_pk = trim($_POST['ge_genero_pk'] ?? '');
	if ($ge_genero_pk === '') {
		$errors[] = 'Género inválido.';
	} else {
		try {
			execute_rest_delete('genero', ['ge_genero' => $ge_genero_pk]);
			$success = 'Género eliminado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao eliminar género: ' . $e->getMessage();
		}
	}
}

// Listagem usando API REST
$lista = [];
try {
	$lista = execute_rest_sql('genero', ['ge_genero'], [], 'ge_genero');
} catch (Throwable $e) {
	$errors[] = 'Erro ao carregar géneros: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Géneros</title>
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
					<div class="card-header">Adicionar género</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label for="ge_genero" class="form-label">Género</label>
								<input type="text" class="form-control" id="ge_genero" name="ge_genero" required>
								<div class="invalid-feedback">Informe o género.</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Géneros existentes</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Género</th>
									<th style="width: 200px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$lista): ?>
									<tr><td colspan="2" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($lista as $row): ?>
									<tr>
										<td>
											<form method="post" class="row g-2 align-items-center">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="ge_genero_pk" value="<?= htmlspecialchars($row['ge_genero'], ENT_QUOTES, 'UTF-8') ?>">
												<div class="col-12">
													<input type="text" class="form-control" name="ge_genero_new" value="<?= htmlspecialchars($row['ge_genero'], ENT_QUOTES, 'UTF-8') ?>" required>
												</div>
										</td>
										<td>
											<div class="d-flex gap-2">
												<button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
											</div>
											</form>
											<form method="post" onsubmit="return confirm('Eliminar este género?');" style="display:inline">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="ge_genero_pk" value="<?= htmlspecialchars($row['ge_genero'], ENT_QUOTES, 'UTF-8') ?>">
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


