<?php
session_start();

// Función para verificar el token de acceso
function verifyToken($token) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages";
    $headers = [
        "Authorization: Bearer $token",
    ];

    // Inicializa cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Ejecutar la solicitud
    $response = curl_exec($ch);

    // Comprobación de errores de cURL
    if ($response === false) {
        echo 'Error en cURL: ' . curl_error($ch);
        exit;
    }

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si hay un error en la respuesta de la API de Gmail
    if (isset($data['error'])) {
        echo 'Error en la API de Gmail: ' . $data['error']['message'];
    } else {
        echo 'Token válido, respuesta recibida de Gmail.';
    }

    curl_close($ch);
}

// Verificar el token de acceso (por ejemplo, token de la sesión o un valor específico)
$accessToken = $_POST['accessToken'] ?? null;

if ($accessToken) {
    // Llamar a la función para verificar el token
    verifyToken($accessToken);
} else {
    echo "No se ha proporcionado un token de acceso.";
}
?>
