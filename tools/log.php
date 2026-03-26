<?php
class Registro {
    public $registro;

    public function __construct() {
        $this->registro = [];

        $this->registro['servidor'] = $_SERVER;
        $this->registro['get'] = $_GET;
        $this->registro['post'] = $_POST;
        $this->registro['sesion'] = $_SESSION;
    }

    // Método para convertir a JSON
    public function aJSON() {
        return json_encode(
            $this->registro,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    // Método para guardar en archivo
    public function guardarArchivo($carpeta = "log") {
        // Usar BASE_PATH si está definida, si no, usar __DIR__
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        $rutaLog = $base . "/" . $carpeta;
        if (!is_dir($rutaLog)) {
            mkdir($rutaLog, 0777, true);
        }
        $archivo = $rutaLog . "/" . date('U') . ".json";
        file_put_contents($archivo, $this->aJSON());
        return $archivo;
    }
}

// Uso
$registro = new Registro();
$registro->guardarArchivo();
?>