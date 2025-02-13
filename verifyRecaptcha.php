<?php
// filepath: /C:/Users/Usuario/Documents/php-puro/dream-hots/pluginLRG/verifyRecaptcha.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptchaResponse = $_POST['recaptchaResponse'];
    $secretKey = '6Lckg9UqAAAAAIhUzJRTiINsZZ-wiUxDgxII7-jr';

    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
    $responseKeys = json_decode($response, true);

    if (intval($responseKeys["success"]) !== 1) {
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'reCAPTCHA verification succeeded.']);
    }
}
?>