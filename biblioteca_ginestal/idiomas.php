<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
ensure_schema();

$pdo = get_pdo();
$errors = [];
$success = null;

// Criar novo idioma
if (($_POST['action'] ?? '') === 'create') {
	$id_idioma = trim($_POST['id_idioma'] ?? '');
	$id_pais = trim($_POST['id_pais'] ?? '');
	if ($id_idioma === '') $errors[] = 'Código de idioma é obrigatório (ex: pt-PT).';
	if ($id_pais === '') $errors[] = 'País é obrigatório.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('INSERT INTO idioma (id_idioma, id_pais) VALUES (?, ?)');
			$stmt->execute([$id_idioma, $id_pais]);
			$success = 'Idioma adicionado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao adicionar idioma: ' . $e->getMessage();
		}
	}
}

// Listagem de idiomas
$idiomas = [];
try {
	$idiomas = $pdo->query('SELECT id_idioma, id_pais FROM idioma ORDER BY id_idioma')->fetchAll();
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
								<label for="id_idioma" class="form-label">Código do idioma</label>
								<input type="text" class="form-control" id="id_idioma" name="id_idioma" placeholder="pt-PT" required>
								<div class="invalid-feedback">Informe o código (ex: pt-PT, en-GB).</div>
							</div>
							<div class="mb-3">
								<label for="id_pais" class="form-label">País</label>
								<input type="text" class="form-control" id="id_pais" name="id_pais" placeholder="Portugal" required>
								<div class="invalid-feedback">Informe o país.</div>
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
									<th>Código</th>
									<th>País</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$idiomas): ?>
									<tr><td colspan="2" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($idiomas as $row): ?>
									<tr>
										<td><code><?= htmlspecialchars($row['id_idioma'], ENT_QUOTES, 'UTF-8') ?></code></td>
										<td><?= htmlspecialchars($row['id_pais'], ENT_QUOTES, 'UTF-8') ?></td>
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


