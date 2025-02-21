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
