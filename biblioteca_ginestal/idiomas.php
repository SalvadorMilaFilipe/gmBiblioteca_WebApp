<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();

// Não usar get_pdo() diretamente - usar funções específicas da API REST
$errors = [];
$success = null;

// Criar novo idioma
if (($_POST['action'] ?? '') === 'create') {
	$id_idioma = trim($_POST['id_idioma'] ?? '');
	if ($id_idioma === '') $errors[] = 'Nome do idioma é obrigatório.';
	if (!$errors) {
		try {
			execute_rest_insert('idioma', [
				'id_idioma' => $id_idioma
			]);
			$success = 'Idioma adicionado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao adicionar idioma: ' . $e->getMessage();
		}
	}
}

// Atualizar idioma
if (($_POST['action'] ?? '') === 'update') {
	$id_idioma_old = trim($_POST['id_idioma_old'] ?? '');
	$id_idioma_new = trim($_POST['id_idioma_new'] ?? '');
	if ($id_idioma_old === '') $errors[] = 'Idioma inválido.';
	if ($id_idioma_new === '') $errors[] = 'Novo nome é obrigatório.';
	if (!$errors) {
		try {
			execute_rest_update('idioma', [
				'id_idioma' => $id_idioma_new
			], ['id_idioma' => $id_idioma_old]);
			$success = 'Idioma atualizado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao atualizar idioma: ' . $e->getMessage();
		}
	}
}

// Eliminar idioma
if (($_POST['action'] ?? '') === 'delete') {
	$id_idioma = trim($_POST['id_idioma'] ?? '');
	if ($id_idioma === '') {
		$errors[] = 'Idioma inválido.';
	} else {
		try {
			execute_rest_delete('idioma', ['id_idioma' => $id_idioma]);
			$success = 'Idioma eliminado com sucesso!';
		} catch (Exception $e) {
			$errors[] = 'Erro ao eliminar idioma: ' . $e->getMessage();
		}
	}
}

// Listagem de idiomas usando API REST
$idiomas = [];
try {
	$idiomas = execute_rest_sql('idioma', ['id_idioma'], [], 'id_idioma');
} catch (Throwable $e) {
	$errors[] = 'Erro ao carregar idiomas: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Idiomas</title>
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
					<div class="card-header">Adicionar idioma</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label for="id_idioma" class="form-label">Nome do idioma</label>
								<input type="text" class="form-control" id="id_idioma" name="id_idioma" placeholder="Português" required>
								<div class="invalid-feedback">Informe o nome do idioma.</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Idiomas existentes</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Idioma</th>
									<th style="width: 120px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$idiomas): ?>
									<tr><td colspan="2" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($idiomas as $row): ?>
									<tr>
										<td>
											<form method="post" class="d-inline">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="id_idioma_old" value="<?= htmlspecialchars($row['id_idioma'], ENT_QUOTES, 'UTF-8') ?>">
												<div class="input-group">
													<input type="text" class="form-control form-control-sm" name="id_idioma_new" value="<?= htmlspecialchars($row['id_idioma'], ENT_QUOTES, 'UTF-8') ?>" required>
													<button type="submit" class="btn btn-outline-primary btn-sm">Atualizar</button>
												</div>
											</form>
										</td>
										<td>
											<form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja eliminar este idioma?');">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="id_idioma" value="<?= htmlspecialchars($row['id_idioma'], ENT_QUOTES, 'UTF-8') ?>">
												<button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
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


