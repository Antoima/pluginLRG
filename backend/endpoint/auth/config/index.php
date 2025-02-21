<?php
// backend/endpoint/auth/config/index.php

// Cargar la configuración global del archivo 'config.php' desde la carpeta 'configuracion'
$config = require '/home/dh_292vea/configuracion/config.php';

return [
    'client_id' => $config['google_client_id'],  // Google Client ID
    'client_secret' => $config['google_client_secret'],  // Google Client Secret
    'redirect_uri' => $config['google_redirect_uri'],  // URI de redirección
];
?>
