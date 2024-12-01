<?php 
require_once __DIR__ . '/../controllers/ProductoController.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];

    $productoController = new ProductoController();
    $productos = $productoController->searchByName($query); // Recuperar todos los productos según el nombre

    // Filtrar duplicados basado en el nombre
    $productosFiltrados = [];
    $names = [];

    foreach ($productos as $producto) {
        if (!in_array($producto['nombre'], $names)) {
            $productosFiltrados[] = $producto;
            $names[] = $producto['nombre']; // Añadir nombre al array para evitar duplicados
        }
    }

    // Mostrar los productos filtrados con la función onclick para seleccionar el producto
    foreach ($productosFiltrados as $producto) {
        echo '<div onclick="submitSearch(\'' . htmlspecialchars($producto['nombre']) . '\')">' . htmlspecialchars($producto['nombre']) . '</div>';
    }
}
?>
