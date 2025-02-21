<?php 
// backend/endpoint/auth/controlador/index.php

require_once __DIR__ . '/../servicio/index.php';  // Ruta al servicio

class GoogleAuthController
{
    private $googleAuthService;

    public function __construct()
    {
        $this->googleAuthService = new GoogleAuthService();
    }

    // Método para manejar el login y obtener el token
    public function login($code)
    {
        // Obtener el token de acceso utilizando el código de autorización
        $token = $this->googleAuthService->getAccessToken($code);
        if (isset($token['error'])) {
            echo json_encode(['error' => 'Error al obtener el token']);
            return;
        }

        // Obtener los correos de Gmail
        $userData = $this->googleAuthService->getGmailMessages($token['access_token']);
        if ($userData) {
            echo json_encode($userData); // Responder con los correos
        } else {
            echo json_encode(['error' => 'Token expirado o inválido']);
        }
    }
}
?>
