<?php
error_reporting(0);
header('Content-Type: application/json');

$config = require '/home/dh_292vea/configuracion/config.php';
$secretKey = $config['recaptcha_secret_key'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $recaptchaResponse = $_POST['recaptchaResponse'] ?? null;
    if (!$recaptchaResponse) {
        throw new Exception("Token no proporcionado");
    }

    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = ['secret' => $secretKey, 'response' => $recaptchaResponse];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        throw new Exception("Error al conectar con reCAPTCHA");
    }

    $responseKeys = json_decode($result, true);
    
    if (!$responseKeys['success']) {
        throw new Exception("Verificación reCAPTCHA fallida");
    }

    echo json_encode(['success' => true, 'message' => 'Verificación exitosa']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
?>