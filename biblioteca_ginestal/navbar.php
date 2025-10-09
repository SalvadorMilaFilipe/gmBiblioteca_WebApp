<?php
declare(strict_types=1);
?>
<aside class="bg-primary text-white position-fixed top-0 start-0 vh-100 d-flex flex-column" style="width: 240px; z-index: 1030;">
	<div class="px-3 py-3 border-bottom border-white-25">
		<a class="navbar-brand text-white text-decoration-none" href="index.php">
			<div class="logo-circle">
				<img src="img/Ginestal_Logo.png" alt="Ginestal Logo">
			</div>
			<strong>Biblioteca Ginestal</strong>
		</a>
	</div>
	<nav class="nav flex-column p-2 overflow-auto flex-grow-1">
		<a href="index.php" class="nav-link text-white">Início</a>
		<a href="editoras.php" class="nav-link text-white">Editoras</a>
		<a href="utentes.php" class="nav-link text-white">Utentes</a>
		<a href="autor.php" class="nav-link text-white">Autores</a>
		<hr class="border-white-50">
		<a href="codigopostal.php" class="nav-link text-white">Códigos postais</a>
		<a href="idiomas.php" class="nav-link text-white">Idiomas</a>
		<a href="genero.php" class="nav-link text-white">Géneros</a>
	</nav>
	
	<!-- Rodapé com créditos -->
	<footer class="px-3 py-2 border-top border-white-25 mt-auto">
		<div class="d-flex justify-content-between align-items-center">
			<small class="text-white-50">
				&copy; Salvador Mila Filipe
			</small>
			<small class="text-white-50">
				Feito com  Cursor AI
			</small>
		</div>
	</footer>
</aside>

<style>
/* Garantir que o conteúdo não fica por baixo da sidebar */
body { margin-left: 240px; }
@media (max-width: 575.98px) {
	/* Em ecrãs muito pequenos mantemos a barra lateral visível e o conteúdo deslocado */
	body { margin-left: 240px; }
}

/* Círculo perfeito com moldura para o logo do Ginestal */
.logo-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fff, #f8f9fa);
    border: 2px solid #fff;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    flex-shrink: 0;
    aspect-ratio: 1;
}

.logo-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.logo-circle img {
    width: 35px;
    height: 35px;
    object-fit: contain;
    border-radius: 50%;
}

.navbar-brand {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    line-height: 1.2;
}

/* Rodapé com créditos */
footer {
    background: rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

footer small {
    font-size: 0.75rem;
    line-height: 1.2;
}

footer .text-danger {
    animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>


