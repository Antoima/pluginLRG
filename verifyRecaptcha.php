<?php
// Cargar la configuración
$config = require '/home/dh_292vea/configuracion/config.php';
$secretKey = $config['recaptcha_secret_key'];

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Verificar que se haya enviado el token de reCAPTCHA
$recaptchaResponse = filter_input(INPUT_POST, 'recaptchaResponse', FILTER_SANITIZE_STRING);
if (empty($recaptchaResponse)) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => 'Falta el token de reCAPTCHA.']);
    exit;
}

// Verificar el token con el servidor de reCAPTCHA
$url = "https://www.google.com/recaptcha/api/siteverify";
$data = [
    'secret' => $secretKey,
    'response' => $recaptchaResponse,
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
    ],
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(500); // Error del servidor
    echo json_encode(['success' => false, 'message' => 'Error al conectar con el servidor de reCAPTCHA.']);
    exit;
}

$responseKeys = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($responseKeys['success'])) {
    http_response_code(500); // Error del servidor
    echo json_encode(['success' => false, 'message' => 'Respuesta inválida del servidor de reCAPTCHA.']);
    exit;
}

// Verificar si reCAPTCHA fue exitoso
if ($responseKeys['success'] !== true) {
    $errorCodes = $responseKeys['error-codes'] ?? [];
    $errorMessage = 'reCAPTCHA verification failed.';
    if (in_array('timeout-or-duplicate', $errorCodes)) {
        $errorMessage = 'El token de reCAPTCHA ha expirado. Por favor, recarga la página.';
    } elseif (in_array('invalid-input-secret', $errorCodes)) {
        $errorMessage = 'Clave secreta de reCAPTCHA inválida.';
    }

    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

// Si estás usando reCAPTCHA v3, verifica el puntaje
if (isset($responseKeys['score']) && $responseKeys['score'] < 0.5) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA score too low.']);
    exit;
}

// Si todo está bien, devolver éxito
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'reCAPTCHA verification succeeded.']);
?>