<?php
// Iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/EmpresaController.php';
require_once __DIR__ . '/../controllers/OfertaController.php';

// Verificar si el SKU del producto fue pasado correctamente en la URL
if (!isset($_GET['id'])) {
    echo "Producto no especificado.";
    exit;
}

$sku = $_GET['id']; // Obtener el SKU del producto de la URL
$productoController = new ProductoController();
$producto = $productoController->readOne($sku); // Obtener el producto por SKU

if (!$producto) {
    echo "Producto no encontrado.";
    exit;
}

// Obtener la información de la empresa (vendedor)
$empresaController = new EmpresaController();
$empresa = $empresaController->readOne($producto['idemp']); // Obtener la empresa propietaria del producto

$ofertaController = new OfertaController();
$oferta = $ofertaController->readBySku($sku); // Obtener la oferta asociada al producto, si la hay

$fechaActual = date('Y-m-d'); // Obtener la fecha actual

// Verificar el tipo de stock (unidad o cantidad)
$cantidadDisponible = 0;
if ($producto['tipo_stock'] === 'unidad') {
    // Si es por unidad, contar cuántas unidades están disponibles
    $cantidadDisponible = $productoController->getCantidadDisponiblePorSku($producto['sku']);
} else {
    // Si es por cantidad, usar el valor del campo stock
    $cantidadDisponible = $producto['stock'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Producto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/sigto/assets/css/style.css">
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
                <?php 
                // Verificar el tipo de usuario en la sesión y mostrar el nav correspondiente
                if (isset($_SESSION['idus'])): // Si el cliente está logueado ?>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item mx-3">
                            <a class="nav-link nav-icon" href="/sigto/views/maincliente.php">
                                <i class="bi bi-house-door"></i> Inicio</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="nav-link nav-icon" href="/sigto/views/usuarioperfil.php">
                                <i class="bi bi-person-circle"></i> Perfil</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="nav-link nav-icon" href="/sigto/index?action=view_cart">
                                <i class="bi bi-cart"></i> Carrito</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="nav-link nav-icon" href="/sigto/index.php?action=logout">
                                <i class="bi bi-door-closed"></i> Salir</a>
                        </li>
                    </ul>
                <?php elseif (isset($_SESSION['idemp'])): // Si la empresa está logueada ?>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item mx-3">
                            <a class="text-white fs-4 text-decoration-none" href="/sigto/views/mainempresa.php"><i class="bi bi-house-door"></i> Inicio</a>
                        </li>
                        <li class="nav-item mx-3">
                        <a class="text-white fs-4 text-decoration-none" href="/sigto/views/empresaperfil.php"><i class="bi bi-building"></i> Perfil</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="text-white fs-4 text-decoration-none" href="/sigto/views/agregarproducto.php"><i class="bi bi-plus-circle"></i> Agregar</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="text-white fs-4 text-decoration-none" href="/sigto/index.php?action=logout"><i class="bi bi-door-closed"></i> Salir</a>
                        </li>
                    </ul>
                <?php else: // Si el usuario es un visitante ?>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item mx-3">
                            <a class="text-white fs-4 text-decoration-none" href="/sigto/views/mainvisitante.php"><i class="bi bi-house-door"></i> Inicio</a>
                        </li>
                        <li class="nav-item mx-3">
                            <a class="text-white fs-4 text-decoration-none" href="/sigto/views/loginUsuario.php"><i class="bi bi-door-open"></i> Ingresar</a>
                        </li>
                    </ul>
                <?php endif; ?>
                <form id="search-form" action="/sigto/views/catalogo.php" method="GET" autocomplete="off">
                    <input type="text" id="search-words" name="query" placeholder="Buscar productos..." onkeyup="showSuggestions(this.value)">
                    <div id="suggestions"></div> <!-- Div para mostrar las sugerencias -->
                </form>
            </div>
        </div>
    </nav>
</header>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-30 p-5 bg-white shadow rounded">
            <div class="row">
                <div class="col-md-6">
                    <!-- Mostrar la imagen del producto -->
                    <img src="/sigto/assets/images/<?php echo htmlspecialchars($producto['imagen']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                </div>
                <div class="col-md-6">
                    <!-- Mostrar los detalles del producto -->
                    <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                    <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>

                    <?php
                    // Verificar si el producto tiene una oferta activa
                    if ($oferta && isset($oferta['fecha_inicio'], $oferta['fecha_fin']) && $oferta['fecha_inicio'] <= $fechaActual && $oferta['fecha_fin'] >= $fechaActual) {
                        $precioOferta = $oferta['preciooferta'];
                        echo "<p><strong>Precio: </strong><del>US$" . htmlspecialchars($producto['precio']) . "</del></p>";
                        echo "<p><strong>Oferta: </strong>{$oferta['porcentaje_oferta']}%</p>";
                        echo "<p><strong>Precio con oferta: </strong>US$" . htmlspecialchars($precioOferta) . "</p>";
                    } else {
                        echo "<p><strong>Precio: </strong>US$" . htmlspecialchars($producto['precio']) . "</p>";
                        echo "<p><strong>No hay oferta disponible</strong></p>";
                    }
                    ?>

                    <!-- Select de Cantidad basado en la cantidad disponible -->
                    <form action="/sigto/index?action=add_to_cart" method="POST">
                        <input type="hidden" name="sku" value="<?php echo $producto['sku']; ?>">
                        <label for="cantidad">Cantidad:</label>
                        <select name="cantidad" class="form-control mb-2" style="width: 80px;">
                            <?php for ($i = 1; $i <= $cantidadDisponible; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-primary" <?php echo $cantidadDisponible <= 0 ? 'disabled' : ''; ?>>Comprar</button>
                    </form>

                    <!-- Botón de Contactar al Vendedor (WhatsApp) -->
                    <a href="https://api.whatsapp.com/send?phone=<?php echo htmlspecialchars($empresa['telefono']); ?>&text=Hola, estoy interesado en el producto <?php echo htmlspecialchars($producto['nombre']); ?>" class="btn btn-success mt-3">
                        Contactar al Vendedor
                    </a>
                    
                    <!-- Botón para volver a la página anterior -->
                    <a href="javascript:history.back()" class="btn btn-secondary mt-3">Volver</a>
                </div>
            </div>
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
            <a href="https://www.facebook.com/AkakuroCode/"><img class="redes" src="/sigto/assets/images/facebook logo.png" alt="Facebook"></a>
            <a href="https://x.com/AkakuroCode"><img class="redes" src="/sigto/assets/images/x.png" alt="Twitter"></a>
            <a href="https://www.instagram.com/akakurocode/"><img class="redes" src="/sigto/assets/images/ig logo.png" alt="Instagram"></a>
        </div>
    </div>
</footer>

<!-- Scripts necesarios -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="/sigto/assets/js/searchbar.js"></script>

</div> 
</body>
</html>
