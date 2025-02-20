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
        $errorMessage = 'Error en cURL: ' . curl_error($ch);
        curl_close($ch);
        return $errorMessage;
    }

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si hay un error en la respuesta de la API de Gmail
    if (isset($data['error'])) {
        $errorMessage = 'Error en la API de Gmail: ' . $data['error']['message'] . ' (Código: ' . $data['error']['code'] . ')';
        curl_close($ch);
        return $errorMessage;
    } else {
        curl_close($ch);
        return 'Token válido, respuesta recibida de Gmail.';
    }
}

// Verificar el token de acceso (por ejemplo, token de la sesión o un valor específico)
$accessToken = $_POST['accessToken'] ?? null;

if ($accessToken) {
    // Llamar a la función para verificar el token
    $result = verifyToken($accessToken);
    echo $result;  // Enviar la respuesta al cliente
} else {
    echo "No se ha proporcionado un token de acceso.";
}
?>
