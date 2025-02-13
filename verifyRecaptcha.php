<?php
$config = require '/home/dh_292vea/configuracion/config.php';
$secretKey = $config['recaptcha_secret_key'];
// $config = include('/home/dh_292vea/configuracion/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptchaResponse = $_POST['recaptchaResponse'];

    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
    $responseKeys = json_decode($response, true);

    if (intval($responseKeys["success"]) !== 1) {
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'reCAPTCHA verification succeeded.']);
    }
}
?>