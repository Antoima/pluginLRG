<?php 
// backend/endpoint/auth/servicio/index.php

require_once __DIR__ . '/../../../../vendor/autoload.php';  // Ruta a autoload de Composer

class GoogleAuthService
{
    private $client;

    public function __construct()
    {
        // Cargar la configuración de Google desde google_config.php
        $googleConfig = require __DIR__ . '/../config/index.php';  // Ruta al archivo de configuración

        // Configurar el cliente de Google OAuth 2.0 con las credenciales obtenidas
        $this->client = new Google_Client();
        $this->client->setClientId($googleConfig['client_id']); 
        $this->client->setClientSecret($googleConfig['client_secret']); 
        $this->client->setRedirectUri($googleConfig['redirect_uri']); 
        $this->client->addScope(Google_Service_Gmail::GMAIL_READONLY); // Ámbito para solo lectura
    }

    // Método para obtener el token de acceso utilizando el código de autorización
    public function getAccessToken($code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        return $token;
    }

    // Método para obtener los correos de Gmail
    public function getGmailMessages($accessToken)
    {
        $this->client->setAccessToken($accessToken);
        if ($this->client->isAccessTokenExpired()) {
            return null; // El token ha expirado
        }

        $service = new Google_Service_Gmail($this->client);
        $messages = $service->users_messages->listUsersMessages('me');  // Obtener mensajes

        $messageList = [];
        foreach ($messages->getMessages() as $message) {
            $msg = $service->users_messages->get('me', $message->getId());
            $messageList[] = $msg;
        }

        return $messageList;  // Devuelve los correos obtenidos
    }
}
?>
