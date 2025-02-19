<?php
error_reporting(0); // Desactivar errores
header('Content-Type: application/json'); // Asegurar el tipo de respuesta

$config = require '/home/dh_292vea/configuracion/config.php';
$secretKey = $config['recaptcha_secret_key'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$recaptchaResponse = filter_input(INPUT_POST, 'recaptchaResponse', FILTER_SANITIZE_STRING);
if (empty($recaptchaResponse)) {
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado.']);
    exit;
}

$url = "https://www.google.com/recaptcha/api/siteverify";
$data = ['secret' => $secretKey, 'response' => $recaptchaResponse];
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
    echo json_encode(['success' => false, 'message' => 'Error al conectar con reCAPTCHA.']);
    exit;
}

$responseKeys = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($responseKeys['success'])) {
    echo json_encode(['success' => false, 'message' => 'Respuesta inválida de reCAPTCHA.']);
    exit;
}

if ($responseKeys['success'] !== true) {
    $errorCodes = $responseKeys['error-codes'] ?? [];
    $errorMessage = 'Error de verificación reCAPTCHA.';
    if (in_array('timeout-or-duplicate', $errorCodes)) {
        $errorMessage = 'Token de reCAPTCHA expirado. Recarga la página.';
    } elseif (in_array('invalid-input-secret', $errorCodes)) {
        $errorMessage = 'Clave secreta inválida.';
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

// Solo para reCAPTCHA v3: Validar score
if (isset($responseKeys['score']) && $responseKeys['score'] < 0.5) {
    echo json_encode(['success' => false, 'message' => 'Score de reCAPTCHA demasiado bajo.']);
    exit;
}

// Respuesta exitosa
echo json_encode(['success' => true, 'message' => 'Verificación exitosa.']);
exit;
?>