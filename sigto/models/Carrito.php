<?php  
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Usuario.php';

class Carrito {
    private $conn;
    private $carrito_table = "carrito";
    private $detalle_table = "detalle_carrito";

    private $idcarrito;
    private $idus;
    private $sku;
    private $cantidad;

    public function __construct() {
        $database = new Database('user');
        $this->conn = $database->getConnection();
    }

    public function setIdus($idus) {
        $this->idus = $idus;
    }

    public function setSku($sku) {
        $this->sku = $sku;
    }

    public function setCantidad($cantidad) {
        $this->cantidad = $cantidad;
    }

    // Método para agregar un producto al carrito
    public function addItem($idcarrito, $sku, $cantidad) {
        $query = "INSERT INTO " . $this->detalle_table . " (idcarrito, sku, cantidad) 
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }

        $stmt->bind_param("iii", $idcarrito, $sku, $cantidad);
        return $stmt->execute();
    }

    // Método para obtener los productos del carrito de un usuario
    public function getItemsByUser($idus) {
        $query = "SELECT dc.*, p.nombre, p.descripcion, p.imagen, 
                         IF(o.preciooferta IS NOT NULL AND NOW() BETWEEN o.fecha_inicio AND o.fecha_fin, o.preciooferta, p.precio) AS precio_actual,
                         (dc.cantidad * IF(o.preciooferta IS NOT NULL AND NOW() BETWEEN o.fecha_inicio AND o.fecha_fin, o.preciooferta, p.precio)) AS subtotal
                  FROM " . $this->detalle_table . " dc
                  INNER JOIN producto p ON dc.sku = p.sku
                  LEFT JOIN ofertas o ON p.sku = o.sku 
                  WHERE dc.idcarrito = (SELECT idcarrito FROM " . $this->carrito_table . " WHERE idus = ? AND estado = 'Activo')";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }

        $stmt->bind_param("i", $idus);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

// Método para actualizar la cantidad de un producto en el carrito y actualizar el total
public function updateQuantity($idcarrito, $sku, $cantidad) {
    $query = "UPDATE " . $this->detalle_table . " 
              SET cantidad = ?
              WHERE idcarrito = ? AND sku = ?";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
        echo "Error en la preparación de la consulta: " . $this->conn->error;
        return false;
    }

    $stmt->bind_param("iii", $cantidad, $idcarrito, $sku);

    if ($stmt->execute()) {
        // Calcular el nuevo total del carrito
        $totalCarrito = $this->recalcularTotalCarrito($idcarrito);

        // Actualizar el total en la tabla `carrito`
        $queryUpdateTotal = "UPDATE " . $this->carrito_table . " SET total = ? WHERE idcarrito = ?";
        $stmtUpdateTotal = $this->conn->prepare($queryUpdateTotal);

        if (!$stmtUpdateTotal) {
            echo "Error en la preparación de la consulta para actualizar el total: " . $this->conn->error;
            return false;
        }

        $stmtUpdateTotal->bind_param("di", $totalCarrito, $idcarrito);
        return $stmtUpdateTotal->execute();
    } else {
        return false;
    }
}

public function getSubtotalByUserAndSku($idus, $sku) {
    $query = "SELECT 
                 (dc.cantidad * IFNULL(
                     IF(
                         o.preciooferta IS NOT NULL 
                         AND NOW() BETWEEN o.fecha_inicio AND o.fecha_fin, 
                         o.preciooferta, 
                         p.precio
                     ), p.precio)  -- Usa p.precio como valor por defecto en caso de que todo falle
                 ) AS subtotal
              FROM detalle_carrito dc
              INNER JOIN producto p ON dc.sku = p.sku
              LEFT JOIN ofertas o ON p.sku = o.sku
              INNER JOIN carrito c ON dc.idcarrito = c.idcarrito
              WHERE c.idus = ? AND dc.sku = ?";

    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("ii", $idus, $sku);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['subtotal'] : 0;
}





public function recalcularTotalCarrito($idcarrito) {
    $query = "SELECT SUM(cantidad * IFNULL(o.preciooferta, p.precio)) AS total 
              FROM " . $this->detalle_table . " d
              INNER JOIN producto p ON d.sku = p.sku
              LEFT JOIN ofertas o ON p.sku = o.sku 
                  AND o.fecha_inicio <= NOW() 
                  AND o.fecha_fin >= NOW()
              WHERE d.idcarrito = ?";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
        echo "Error en la preparación de la consulta: " . $this->conn->error;
        return 0;
    }

    $stmt->bind_param("i", $idcarrito);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total = $row && $row['total'] !== null ? $row['total'] : 0;

    // Actualizar el total en la tabla `carrito`
    $updateTotalQuery = "UPDATE " . $this->carrito_table . " SET total = ? WHERE idcarrito = ?";
    $updateStmt = $this->conn->prepare($updateTotalQuery);

    if ($updateStmt) {
        $updateStmt->bind_param("di", $total, $idcarrito);
        $updateStmt->execute();
    }

    return $total;
}



// Método para eliminar un producto o reducir su cantidad en detalle_carrito
public function removeItem($idcarrito, $sku) {
    $queryDelete = "DELETE FROM " . $this->detalle_table . " WHERE idcarrito = ? AND sku = ?";
    $stmtDelete = $this->conn->prepare($queryDelete);
    $stmtDelete->bind_param("ii", $idcarrito, $sku);
    return $stmtDelete->execute();
}

public function removeAllItems($idcarrito) {
    $query = "DELETE FROM detalle_carrito WHERE idcarrito = ?";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
        echo "Error en la preparación de la consulta: " . $this->conn->error;
        return false;
    }

    $stmt->bind_param("i", $idcarrito);
    return $stmt->execute();
}


public function getItemByUserAndSku($idus, $sku) {
    $query = "SELECT * FROM " . $this->detalle_table . " 
              WHERE idcarrito = (SELECT idcarrito FROM " . $this->carrito_table . " WHERE idus = ? AND estado = 'Activo') 
              AND sku = ?";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
        echo "Error en la preparación de la consulta: " . $this->conn->error;
        return false;
    }

    $stmt->bind_param("ii", $idus, $sku);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

public function getCantidadProducto($idcarrito, $sku) {
    $query = "SELECT cantidad FROM " . $this->detalle_table . " WHERE idcarrito = ? AND sku = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("ii", $idcarrito, $sku);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ? $row['cantidad'] : 0;
}

    // Método para obtener el idcarrito activo de un usuario
    public function getActiveCartIdByUser($idus) {
        $query = "SELECT idcarrito FROM " . $this->carrito_table . " WHERE idus = ? AND estado = 'Activo'";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }

        $stmt->bind_param("i", $idus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row ? $row['idcarrito'] : null;
    }

    public function getPrecioProducto($sku) { 
        $query = "SELECT p.precio, 
                         IF(o.preciooferta IS NOT NULL AND NOW() BETWEEN o.fecha_inicio AND o.fecha_fin, o.preciooferta, p.precio) AS precio_final
                  FROM producto p 
                  LEFT JOIN ofertas o ON p.sku = o.sku
                  WHERE p.sku = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }
    
        $stmt->bind_param("i", $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Retornar un array con 'precio_final' como clave, o false si no se encuentra el SKU
        return $row ? ['precio_final' => $row['precio_final']] : false;
    }
    
    
    public function createCart($idus) {
        $query = "INSERT INTO " . $this->carrito_table . " (idus, fechacrea, fechamod, estado, total) VALUES (?, NOW(), NOW(), 'Activo', 0)";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }
    
        $stmt->bind_param("i", $idus);
        if ($stmt->execute()) {
            $idcarrito = $this->conn->insert_id; // Devuelve el idcarrito recién creado
    
            // Recalcula el total del carrito y actualiza la base de datos
            $total = $this->recalcularTotalCarrito($idcarrito);
            $updateTotalQuery = "UPDATE " . $this->carrito_table . " SET total = ? WHERE idcarrito = ?";
            $updateStmt = $this->conn->prepare($updateTotalQuery);
            
            if ($updateStmt) {
                $updateStmt->bind_param("di", $total, $idcarrito);
                $updateStmt->execute();
            }
    
            return $idcarrito;
        } else {
            return false;
        }
    }
    

    public function getTotalByUser($idus) {
        $query = "SELECT total FROM " . $this->carrito_table . " WHERE idus = ? AND estado = 'Activo'";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return 0;
        }
    
        $stmt->bind_param("i", $idus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        return $row ? $row['total'] : 0; // Retorna el total o 0 si no se encuentra
    }
    
    public function obtenerIdCarrito($idus) {
        $query = "SELECT idcarrito FROM " . $this->carrito_table . " WHERE idus = ? AND estado = 'Activo' LIMIT 1";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return null;
        }
    
        $stmt->bind_param("i", $idus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        return $row ? $row['idcarrito'] : null; // Devuelve el idcarrito si se encuentra, o null si no existe
    }
    

    public function obtenerProductosDelCarrito($idCarrito) {
        // Consulta para obtener productos con `codigo_unidad` e `idemp`
        $queryConCodigo = "SELECT d.sku, pu.codigo_unidad, d.cantidad, p.idemp
                           FROM detalle_carrito d
                           JOIN (
                               SELECT pu.codigo_unidad, pu.sku,
                                      @row_num := IF(@sku = pu.sku, @row_num + 1, 1) AS row_num,
                                      @sku := pu.sku
                               FROM producto_unitario pu
                               CROSS JOIN (SELECT @row_num := 0, @sku := '') AS vars
                               WHERE pu.estado = 'Disponible'
                               ORDER BY pu.sku, pu.codigo_unidad
                           ) AS pu ON d.sku = pu.sku AND pu.row_num <= d.cantidad
                           JOIN producto p ON d.sku = p.sku
                           WHERE d.idcarrito = ?";
    
        $stmtConCodigo = $this->conn->prepare($queryConCodigo);
        if (!$stmtConCodigo) {
            echo "Error en la preparación de la consulta con codigo_unidad: " . $this->conn->error;
            return false;
        }
    
        $stmtConCodigo->bind_param("i", $idCarrito);
        $stmtConCodigo->execute();
        $resultConCodigo = $stmtConCodigo->get_result();
        $productosConCodigo = $resultConCodigo->fetch_all(MYSQLI_ASSOC);
    
        // Consulta para obtener productos sin `codigo_unidad` e `idemp`
        $querySinCodigo = "SELECT d.sku, NULL as codigo_unidad, d.cantidad, p.idemp
                           FROM detalle_carrito d
                           LEFT JOIN producto_unitario pu ON d.sku = pu.sku AND pu.estado = 'Disponible'
                           JOIN producto p ON d.sku = p.sku
                           WHERE d.idcarrito = ? AND pu.codigo_unidad IS NULL";
    
        $stmtSinCodigo = $this->conn->prepare($querySinCodigo);
        if (!$stmtSinCodigo) {
            echo "Error en la preparación de la consulta sin codigo_unidad: " . $this->conn->error;
            return false;
        }
    
        $stmtSinCodigo->bind_param("i", $idCarrito);
        $stmtSinCodigo->execute();
        $resultSinCodigo = $stmtSinCodigo->get_result();
        $productosSinCodigo = $resultSinCodigo->fetch_all(MYSQLI_ASSOC);
    
        // Combinar ambos resultados
        $productos = array_merge($productosConCodigo, $productosSinCodigo);
    
        return $productos;
    }
    
    
    
    
    }
