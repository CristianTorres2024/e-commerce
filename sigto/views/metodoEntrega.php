<?php
// Iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idus'])) {
    echo "Error: Usuario no logueado.";
    exit;
}

require_once __DIR__ . '/../controllers/MetodopagoController.php';
require_once __DIR__ . '/../controllers/CentroReciboController.php';


$metodoDePagoController = new MetodoDePagoController();
$metodos_pago = $metodoDePagoController->obtenerMetodosDePagoActivos();
$paypalActivo = in_array('PayPal', $metodos_pago);

$centroReciboController = new CentroReciboController();
$centros_recibo = $centroReciboController->obtenerCentrosDeRecibo();


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/sigto/assets/css/style.css">
    <title>Seleccionar Método de Entrega y Pago</title>
</head>
<body>
<div class="contenedor">

<header>
<nav class="navbar navbar-expand-sm bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><img class="w-50" src="/sigto/assets/images/navbar logo 2.png" alt="OceanTrade"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse flex-row-reverse" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item mx-3">
                    <a class="nav-link nav-icon" href="/sigto/views/maincliente.php"><i class="bi bi-house-door"></i> Inicio</a>
                </li>
                <li class="nav-item mx-3">
                    <a class="nav-link nav-icon" href="/sigto/views/usuarioperfil.php"><i class="bi bi-person-circle"></i> Perfil</a>
                </li>
                <li class="nav-item mx-3">
                    <a class="nav-link nav-icon" href="/sigto/index.php?action=view_cart"><i class="bi bi-cart"></i> Carrito</a>
                </li>
                <li class="nav-item mx-3">
                    <a class="nav-link nav-icon" href="/sigto/index.php?action=logout"><i class="bi bi-door-open"></i>Salir</a>
                </li>
            </ul>
            <form id="search-form" action="/sigto/views/catalogo.php" method="GET" autocomplete="off">
                <input type="text" id="search-words" name="query" placeholder="Buscar productos..." onkeyup="showSuggestions(this.value)">
                <div id="suggestions"></div> <!-- Div para mostrar las sugerencias -->
            </form>
        </div>
    </div>
</nav>    
</header>

<main>
<div class="container mt-5">
    <div class="form-container">
        <h2>Seleccionar Método de Entrega</h2>
        <form id="form-entrega" action="/sigto/controllers/CompraController.php" method="POST">
            <input type="hidden" name="action" value="procesarCompra">

            <!-- Selección de Retiro en Pick up -->
            <div class="form-check">
                <input class="form-check-input" type="radio" name="metodo_entrega" id="pickUp" value="Recibo" required onclick="mostrarOpcionesEntrega()">
                <label class="form-check-label" for="pickUp">Retiro en Pick up</label>
            </div>

            <!-- Selección de Entrega a Domicilio -->
            <div class="form-check">
                <input class="form-check-input" type="radio" name="metodo_entrega" id="domicilio" value="Envio" required onclick="mostrarOpcionesEntrega()">
                <label class="form-check-label" for="domicilio">Entrega a Domicilio</label>
            </div>

            <!-- Opciones de Pick up dinámicas desde la tabla centrorecibo -->
            <div id="opciones-pickup" style="display: none; margin-top: 20px;">
                <h5>Seleccionar ubicación de Pick up:</h5>
                <?php foreach ($centros_recibo as $centro): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="ubicacion_pickup" id="pickup<?= $centro['idrecibo'] ?>" value="<?= $centro['idrecibo'] ?>" required>
                        <label class="form-check-label" for="pickup<?= $centro['idrecibo'] ?>">
                            <?= htmlspecialchars($centro['nombre']) ?> - Tel: <?= htmlspecialchars($centro['telefono']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Formulario para Entrega a Domicilio -->
            <div id="formulario-domicilio" style="display: none; margin-top: 20px;">
                <h5>Ingresar Dirección de Entrega</h5>
                <div class="form-group">
                    <label for="calle">Calle:</label>
                    <input type="text" class="form-control" id="calle" name="direccion[calle]" required>
                </div>
                <div class="form-group">
                    <label for="numero">Número de Puerta:</label>
                    <input type="text" class="form-control" id="numero" name="direccion[numero]" required>
                </div>
                <div class="form-group">
                    <label for="esquina">Esquina (opcional):</label>
                    <input type="text" class="form-control" id="esquina" name="direccion[esquina]">
                </div>
            </div>
            <!-- Botón Continuar -->
            <button type="button" id="boton-continuar" class="btn btn-primary mt-3" style="display: none;" onclick="validarCampos()">Continuar</button><br>

            <!-- Opciones de Pago -->
            <div id="opciones-pago" style="display: none; margin-top: 20px;">
                <h5>Seleccionar Método de Pago:</h5>
                <?php if ($paypalActivo): ?>
                    <div id="paypal-button-container"></div>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">El método de pago con PayPal está deshabilitado en este momento.</div>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>
</main>

<footer>
<div class="footer-container">
    <div class="footer-item">
        <p>Contacto</p>
        <a href="/sigto/views/nosotroscliente.php">Nosotros</a>
        <br>
        <a href="tel:+598 92345888">092345888</a>
        <br>
        <a href="mailto: oceantrade@gmail.com">oceantrade@gmail.com</a>
        <br>
        <a href="reclamoscliente.php">Reclamos</a>
    </div>
    <div class="footer-item">
        <p>Horario de Atención <br><br>Lunes a Viernes de 10hs a 18hs</p>
    </div>
    
    <div class="footer-redes">
        <a href="https://www.facebook.com/"><img class="redes" src="/sigto/assets/images/facebook logo.png" alt="Facebook"></a>
        <a href="https://twitter.com/home"><img class="redes" src="/sigto/assets/images/x.png" alt="Twitter"></a>
        <a href="https://www.instagram.com/akakurocode/"><img class="redes" src="/sigto/assets/images/ig logo.png" alt="Instagram"></a>
    </div>
</div>
<script src="/sigto/assets/js/searchbar.js"></script>
<script src="/sigto/assets/js/compra.js"></script>
<script src="https://www.paypal.com/sdk/js?client-id=AceErLCZ6XmVz4t3eQ-HNTR6L60MWTPws4Z8K2tdjiVaK05pJeuAhxWm2MEibyVMiCSQdqm10Y9ocAHm&currency=USD"></script>
<script src="/sigto/assets/js/pago.js"></script>
</footer>
</div>
</body>
</html>
