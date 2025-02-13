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
if (empty($_POST['recaptchaResponse'])) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => 'Falta el token de reCAPTCHA.']);
    exit;
}

$recaptchaResponse = $_POST['recaptchaResponse'];

// Verificar el token con el servidor de reCAPTCHA
$url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse";
$response = file_get_contents($url);

if ($response === false) {
    http_response_code(500); // Error del servidor
    echo json_encode(['success' => false, 'message' => 'Error al conectar con el servidor de reCAPTCHA.']);
    exit;
}

$responseKeys = json_decode($response, true);

// Verificar que la respuesta sea válida
if (json_last_error() !== JSON_ERROR_NONE || !isset($responseKeys['success'])) {
    http_response_code(500); // Error del servidor
    echo json_encode(['success' => false, 'message' => 'Respuesta inválida del servidor de reCAPTCHA.']);
    exit;
}

// Verificar si reCAPTCHA fue exitoso
if (intval($responseKeys["success"]) !== 1) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed.']);
    exit;
}

// Si todo está bien, devolver éxito
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'reCAPTCHA verification succeeded.']);
?>