<?php
// backend/endpoint/auth/index.php

// Mostrar errores directamente en pantalla para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el archivo controlador/index.php se carga correctamente
if (file_exists(__DIR__ . '/controlador/index.php')) {
    echo "Archivo controlador/index.php encontrado<br>";
} else {
    echo "Error: archivo controlador/index.php NO encontrado<br>";
}

// Intentar cargar el archivo
require_once __DIR__ . '/controlador/index.php';

echo "Archivo controlado cargado correctamente<br>";

// Continuamos con la ejecución si todo está bien
?>

<?php
// backend/endpoint/auth/index.php

require_once __DIR__ . '/controlador/index.php';


class GoogleAuthEndpoint
{
    private $controller;

    public function __construct()
    {
        $this->controller = new GoogleAuthController();
    }

    // Método para manejar el login
    public function handleLogin()
    {
        if (isset($_GET['code'])) {
            $code = $_GET['code']; // Obtener el código de autorización
            $this->controller->login($code); // Llamar al método login
        } else {
            // Si no hay código, devolver un error
            echo json_encode(['error' => 'Código de autorización no encontrado']);
        }
    }
}

// Manejar la solicitud
$googleAuthEndpoint = new GoogleAuthEndpoint();
$googleAuthEndpoint->handleLogin();
?>
