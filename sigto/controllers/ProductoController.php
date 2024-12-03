<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Carrito.php';
require_once __DIR__ . '/../controllers/OfertaController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php'; 

class ProductoController {

    public function create($data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'empresa' || !isset($_SESSION['idemp'])) {
            return "Acceso denegado. Solo las empresas pueden agregar productos.";
        }

        $producto = new Producto('write');
        $producto->setIdEmp($_SESSION['idemp']);
        $producto->setNombre($data['nombre']);
        $producto->setDescripcion($data['descripcion']);
        $producto->setEstado($data['estado']);
        $producto->setOrigen($data['origen']);
        $producto->setPrecio($data['precio']);
        $producto->setImagen($data['imagen']);

        // Verificar el tipo de stock y asignar el tipo correcto
        if ($data['tipo_stock'] === 'cantidad') {
            $producto->setTipoStock('cantidad');
            $producto->setStock($data['stock']);  // Establecer el stock si es por cantidad
        } elseif ($data['tipo_stock'] === 'unidad') {
            $producto->setTipoStock('unidad');
            $producto->setStock(null);  // No necesita stock en la tabla principal si es por unidad
        } else {
            return ['status' => 'error', 'message' => 'Tipo de stock inválido.'];
        }

        $skuGenerado = $producto->create();

        // Si es por unidad, insertar los códigos únicos en la tabla 'producto_unitario'
        if ($skuGenerado && $data['tipo_stock'] === 'unidad' && !empty($data['codigos_unitarios'])) {
            $codigos = explode(',', $data['codigos_unitarios']);
            $codigosRepetidos = [];

            foreach ($codigos as $codigoUnidad) {
                $codigoUnidad = trim($codigoUnidad);  // Eliminar espacios en blanco

                // Verificar si el código ya existe
                if ($producto->existeCodigoUnidad($codigoUnidad)) {
                    $codigosRepetidos[] = $codigoUnidad;
                } else {
                    $producto->agregarUnidad($skuGenerado, $codigoUnidad);  // Agregar si no existe
                }
            }

            // Si hay códigos repetidos, retornamos un mensaje de error
            if (!empty($codigosRepetidos)) {
                return ['status' => 'error', 'message' => 'Los siguientes códigos ya existen: ' . implode(', ', $codigosRepetidos)];
            }
        }

        return $skuGenerado ? ['status' => 'success', 'sku' => $skuGenerado] : ['status' => 'error', 'message' => 'Error al crear el producto.'];
    }

    public function readAll() {
        $producto = new Producto();
        $result = $producto->readAllProducts(); // Llamamos al método del modelo que recupera todos los productos
    
        if (!$result) {
            return false;
        }
    
        return $result;
    }

    public function asignarCategoria($sku, $idcat) {
        $producto = new Producto('write'); // Instanciar el modelo de Producto
        return $producto->asignarCategoria($sku, $idcat); // Llamar al método del modelo
    }

    public function readAllByEmpresa($idemp) {
        $producto = new Producto();
        $result = $producto->readByEmpresa($idemp);
    
        if (!$result) {
            return false; // Manejo de errores si no se obtienen productos
        }
    
        return $result;
    }

    public function readOne($sku) {
        $producto = new Producto();
        $producto->setSku($sku);
        return $producto->readOne();
    }

    public function readVisible() {
        $producto = new Producto();
        return $producto->readVisibleProducts(); // Llama a un método en el modelo
    }

    public function update($data) {
        $producto = new Producto('write');
        $producto->setSku($data['sku']);
        $producto->setIdEmp($_SESSION['idemp']);
        $producto->setNombre($data['nombre']);
        $producto->setDescripcion($data['descripcion']);
        $producto->setEstado($data['estado']);
        $producto->setOrigen($data['origen']);
        $producto->setPrecio($data['precio']);
    
        // Manejar la imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['imagen']['tmp_name'];
            $nombreImagen = basename($_FILES['imagen']['name']);
            $rutaDestino = __DIR__ . '/../assets/images/' . $nombreImagen;
            if (move_uploaded_file($tmp_name, $rutaDestino)) {
                $producto->setImagen($nombreImagen);
            }
        } else {
            $producto->setImagen($data['imagenActual']);
        }
        
         // Verificar el tipo de stock y actuar en consecuencia
    if ($data['tipo_stock'] === 'cantidad') {
        // Si es por cantidad, actualizar el stock
        $producto->setStock($data['stock']);
    } elseif ($data['tipo_stock'] === 'unidad') {
        // Si es por unidad, manejar los códigos unitarios
        if (!empty($data['codigos_unitarios'])) {
            // Separar los códigos ingresados por comas y eliminamos espacios en blanco
            $codigos = array_map('trim', explode(',', $data['codigos_unitarios']));
            $codigosRepetidos = [];

            foreach ($codigos as $codigoUnidad) {
                // Verificar si el código ya existe
                if ($producto->existeCodigoUnidad($codigoUnidad)) {
                    $codigosRepetidos[] = $codigoUnidad; // Guardamos los códigos repetidos para mostrar al usuario
                } else {
                    // Si no existe, agregar la unidad
                    $producto->agregarUnidad($data['sku'], $codigoUnidad);
                }
            }

            // Si hay códigos repetidos, retornamos un mensaje de error
            if (!empty($codigosRepetidos)) {
                return ['status' => 'error', 'message' => 'Los siguientes códigos ya existen: ' . implode(', ', $codigosRepetidos)];
            }
        }
    } else {
        return ['status' => 'error', 'message' => 'Tipo de stock inválido.'];
    }


 // Manejar la oferta
$ofertaController = new OfertaController();
$dataOferta = [
    'sku' => $data['sku'],
    'porcentaje_oferta' => (float)$data['oferta'], // Asegúrate de que sea un número
    'preciooferta' => (float)$data['precio'] - ((float)$data['precio'] * ((float)$data['oferta'] / 100)), // Conversión explícita
    'fecha_inicio' => $data['fecha_inicio'],
    'fecha_fin' => $data['fecha_fin']
];


// Verificar si ya existe una oferta
$ofertaActual = $ofertaController->readBySku($data['sku']);

if (!empty($ofertaActual)) {
    // Si ya existe, actualizar la oferta
    $resultado = $ofertaController->update($dataOferta);
    if ($resultado['status'] !== 'success') {
        return "Error al actualizar la oferta.";
    }
} else {
    // Si no existe, validar datos y crear la oferta
    if (!empty($data['oferta']) && $data['oferta'] > 0 && !empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
        $resultado = $ofertaController->create($dataOferta);
        if ($resultado['status'] !== 'success') {
            return "Error al crear la oferta.";
        }
    } else {
        return "Error: Los datos de la nueva oferta son incompletos.";
    }
}


    
        // Actualizar producto
        if ($producto->update()) {
            return "Producto actualizado exitosamente.";
        } else {
            return "Error al actualizar producto.";
        }
    }


    
    
    // Método para obtener la cantidad de productos disponibles por SKU
    public function getCantidadDisponiblePorSku($sku) {
        $producto = new Producto();
        return $producto->getCantidadDisponiblePorSku($sku); // Usar el método del modelo para obtener cantidad disponible
    }
    
    public function addToCart($idus, $sku, $cantidad) {
        $carrito = new Carrito();
        $carrito->setIdus($idus);
        $carrito->setSku($sku);
        $carrito->setCantidad($cantidad);

        // Verificar si el producto ya está en el carrito del usuario
        $itemExistente = $carrito->getItemByUserAndSku();

        if ($itemExistente) {
            // Si el producto ya está en el carrito, actualizar la cantidad sumando la nueva
            $nuevaCantidad = $itemExistente['cantidad'] + $cantidad;
            return $carrito->updateQuantity($idus, $sku, $nuevaCantidad);

        } else {
            // Si el producto no está en el carrito, añadirlo como un nuevo ítem
            return $carrito->addItem();
        }
    }  

    // Método para el borrado lógico (ocultar producto)
    public function softDelete($sku) {
        $producto = new Producto('write');
        $producto->setSku($sku);
        if ($producto->softDelete()) {
            return "Producto ocultado exitosamente.";
        } else {
            return "Error al ocultar el producto.";
        }
    }
    
    // Método para restaurar el producto
    public function restore($sku) {
        $producto = new Producto('write');
        $producto->setSku($sku);
        if ($producto->restore()) {
            return "Producto restaurado exitosamente.";
        } else {
            return "Error al restaurar el producto.";
        }
    }

    public function handleRequest() {
        if (isset($_GET['action']) && isset($_GET['sku'])) {
            $action = $_GET['action'];
            $sku = $_GET['sku'];
    
            if ($action === 'desactivar') {
                return $this->softDelete($sku);
            } elseif ($action === 'activar') {
                return $this->restore($sku);
            }
        }
    }
    
    public function searchByName($term) {
        $producto = new Producto();
        return $producto->searchByName($term);
    }
    
}
?>
