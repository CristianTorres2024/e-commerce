<?php
require_once __DIR__ . '/../config/Database.php';

class Oferta {
    private $conn;
    private $table_name = "ofertas"; // Asegúrate de que el nombre sea correcto

    private $idof;
    private $sku;
    private $porcentajeOferta; // Campo para el porcentaje de oferta
    private $precioOferta; // Campo para el precio con descuento
    private $fechaInicio;
    private $fechaFin;

    public function __construct($operation = 'read') {
        if ($operation === 'write') {
            $database = new Database('user'); // app_user para operaciones privadas
        } else {
            $database = new Database('guest'); // guest_user para lecturas públicas
        }
        $this->conn = $database->getConnection();
    }
    

    // Métodos para obtener y establecer los atributos
    public function getIdof() {
        return $this->idof;
    }

    public function setIdof($idof) {
        $this->idof = $idof;
    }

    public function getSku() {
        return $this->sku;
    }

    public function setSku($sku) {
        $this->sku = $sku;
    }

    public function getPorcentajeOferta() {
        return $this->porcentajeOferta;
    }

    public function setPorcentajeOferta($porcentajeOferta) {
        $this->porcentajeOferta = $porcentajeOferta;
    }

    public function getPrecioOferta() {
        return $this->precioOferta;
    }

    public function setPrecioOferta($precioOferta) {
        $this->precioOferta = $precioOferta;
    }

    public function getFechaInicio() {
        return $this->fechaInicio;
    }

    public function setFechaInicio($fechaInicio) {
        $this->fechaInicio = $fechaInicio;
    }

    public function getFechaFin() {
        return $this->fechaFin;
    }

    public function setFechaFin($fechaFin) {
        $this->fechaFin = $fechaFin;
    }

    // Método para crear una nueva oferta
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (sku, porcentaje_oferta, preciooferta, fecha_inicio, fecha_fin) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }
    
        $stmt->bind_param("iidss", $this->sku, $this->porcentajeOferta, $this->precioOferta, $this->fechaInicio, $this->fechaFin);
    
        if ($stmt->execute()) {
            return true;
        } else {
            echo "Error en la ejecución: " . $stmt->error;
            return false;
        }
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET porcentaje_oferta=?, preciooferta=?, fecha_inicio=?, fecha_fin=? 
                  WHERE sku=?";
        $stmt = $this->conn->prepare($query);
    
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conn->error;
            return false;
        }
    
        $stmt->bind_param("idssi", $this->porcentajeOferta, $this->precioOferta, $this->fechaInicio, $this->fechaFin, $this->sku);
    
        return $stmt->execute();
    }
    
    // Método para eliminar una oferta
    public function deleteBySku() {
        $query = "DELETE FROM " . $this->table_name . " WHERE sku = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->sku);
        return $stmt->execute();
    }

    // Método para leer una oferta por SKU
    public function readBySku() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE sku = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->sku);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>
