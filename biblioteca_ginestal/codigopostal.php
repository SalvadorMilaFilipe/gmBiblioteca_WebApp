<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

ensure_schema();

$pdo = get_pdo();
$errors = [];
$success = null;

// Create
if (($_POST['action'] ?? '') === 'create') {
	$cod_postal = trim($_POST['cod_postal'] ?? '');
	$cod_localidade = trim($_POST['cod_localidade'] ?? '');
	if ($cod_postal === '') $errors[] = 'Código postal é obrigatório.';
	if ($cod_localidade === '') $errors[] = 'Localidade é obrigatória.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('INSERT INTO codigo_postal (cod_postal, cod_localidade) VALUES (?, ?)');
			$stmt->execute([$cod_postal, $cod_localidade]);
			$success = 'Código postal criado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao criar: ' . $e->getMessage();
		}
	}
}

// Update (cannot change primary key)
if (($_POST['action'] ?? '') === 'update') {
	$cod_postal_pk = trim($_POST['cod_postal_pk'] ?? '');
	$cod_localidade = trim($_POST['cod_localidade'] ?? '');
	if ($cod_postal_pk === '') $errors[] = 'Código postal inválido.';
	if ($cod_localidade === '') $errors[] = 'Localidade é obrigatória.';
	if (!$errors) {
		try {
			$stmt = $pdo->prepare('UPDATE codigo_postal SET cod_localidade = ? WHERE cod_postal = ?');
			$stmt->execute([$cod_localidade, $cod_postal_pk]);
			$success = 'Código postal atualizado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao atualizar: ' . $e->getMessage();
		}
	}
}

// Delete
if (($_POST['action'] ?? '') === 'delete') {
	$cod_postal_pk = trim($_POST['cod_postal_pk'] ?? '');
	if ($cod_postal_pk === '') {
		$errors[] = 'Código postal inválido.';
	} else {
		try {
			$stmt = $pdo->prepare('DELETE FROM codigo_postal WHERE cod_postal = ?');
			$stmt->execute([$cod_postal_pk]);
			$success = 'Código postal eliminado com sucesso!';
		} catch (PDOException $e) {
			$errors[] = 'Erro ao eliminar: ' . $e->getMessage();
		}
	}
}

// List
$lista = $pdo->query('SELECT cod_postal, cod_localidade FROM codigo_postal ORDER BY cod_postal')->fetchAll();
?>
<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Códigos Postais</title>
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

			<div class="col-12 col-lg-5">
				<div class="card shadow-sm">
					<div class="card-header">Novo código postal</div>
					<div class="card-body">
						<form method="post" class="needs-validation" novalidate>
							<input type="hidden" name="action" value="create">
							<div class="mb-3">
								<label for="cod_postal" class="form-label">Código postal</label>
								<input type="text" class="form-control" id="cod_postal" name="cod_postal" placeholder="1234-567" maxlength="8" required>
								<div class="invalid-feedback">Informe o código postal (ex: 1234-567).</div>
							</div>
							<div class="mb-3">
								<label for="cod_localidade" class="form-label">Localidade</label>
								<input type="text" class="form-control" id="cod_localidade" name="cod_localidade" required>
								<div class="invalid-feedback">Informe a localidade.</div>
							</div>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-7">
				<div class="card shadow-sm">
					<div class="card-header">Códigos postais</div>
					<div class="table-responsive">
						<table class="table table-striped mb-0">
							<thead>
								<tr>
									<th>Código postal</th>
									<th>Localidade</th>
									<th style="width: 160px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!$lista): ?>
									<tr><td colspan="3" class="text-center text-muted">Sem registos.</td></tr>
								<?php else: foreach ($lista as $row): ?>
									<tr>
										<td><code><?= htmlspecialchars($row['cod_postal'], ENT_QUOTES, 'UTF-8') ?></code></td>
										<td>
											<form method="post" class="row g-2 align-items-center">
												<input type="hidden" name="action" value="update">
												<input type="hidden" name="cod_postal_pk" value="<?= htmlspecialchars($row['cod_postal'], ENT_QUOTES, 'UTF-8') ?>">
												<div class="col">
													<input type="text" class="form-control" name="cod_localidade" value="<?= htmlspecialchars($row['cod_localidade'], ENT_QUOTES, 'UTF-8') ?>" required>
												</div>
												<div class="col-auto">
													<button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
												</div>
											</form>
										</td>
										<td>
											<form method="post" onsubmit="return confirm('Eliminar este código postal?');" style="display: inline;">
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="cod_postal_pk" value="<?= htmlspecialchars($row['cod_postal'], ENT_QUOTES, 'UTF-8') ?>">
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

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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

	// Máscara para código postal
	(function() {
		const codPostalInput = document.getElementById('cod_postal');
		
		if (codPostalInput) {
			// Aplicar máscara no input
			codPostalInput.addEventListener('input', function(e) {
				let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
				
				// Limita a 7 dígitos (4 + 3)
				if (value.length > 7) {
					value = value.substring(0, 7);
				}
				
				// Aplica a máscara: 1234-567
				if (value.length > 4) {
					value = value.substring(0, 4) + '-' + value.substring(4);
				}
				
				e.target.value = value;
			});

			// Prevenir entrada de caracteres não numéricos
			codPostalInput.addEventListener('keypress', function(e) {
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
			codPostalInput.addEventListener('blur', function(e) {
				const value = e.target.value;
				const pattern = /^\d{4}-\d{3}$/;
				
				if (value && !pattern.test(value)) {
					e.target.setCustomValidity('Formato inválido. Use: 1234-567');
				} else {
					e.target.setCustomValidity('');
				}
			});

			// Validação no submit
			const form = codPostalInput.closest('form');
			if (form) {
				form.addEventListener('submit', function(e) {
					const value = codPostalInput.value;
					const pattern = /^\d{4}-\d{3}$/;
					
					if (value && !pattern.test(value)) {
						codPostalInput.setCustomValidity('Formato inválido. Use: 1234-567');
						e.preventDefault();
						e.stopPropagation();
					} else {
						codPostalInput.setCustomValidity('');
					}
				});
			}
		}
	})();
	</script>
</body>
</html>


