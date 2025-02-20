<?php
require_once 'log.php';

session_start();

// Mostrar el contenido de los tokens recibidos
$sourceToken = $_POST['accessToken'] ?? null;
$destinationToken = $_POST['destinationAccessToken'] ?? null;

var_dump($sourceToken, $destinationToken);  // Para debug
if (empty($sourceToken)) {
    $logger->error("Token no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token no proporcionado."]);
    exit();
}

if ($destinationEmail && empty($destinationToken)) {
    $logger->error("Token de destino no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de destino no proporcionado."]);
    exit();
}


// Función para obtener correos procesados
function getProcessedEmails() {
    // Verificamos si el archivo existe
    if (!file_exists('processed_emails.json')) {
        // Si el archivo no existe, crearlo vacío
        file_put_contents('processed_emails.json', json_encode([]));
    }

    // Leer el archivo y decodificar su contenido
    $processed = file_get_contents('processed_emails.json');
    return json_decode($processed, true) ?? [];  // Devuelve el contenido como array
}

// Función para guardar correo procesado
function saveProcessedEmail($emailId) {
    $processedEmails = getProcessedEmails();
    if (!in_array($emailId, $processedEmails)) {
        $processedEmails[] = $emailId;
    }
    file_put_contents('processed_emails.json', json_encode($processedEmails));
}

header('Content-Type: application/json');

$sourceToken = $_POST['accessToken'] ?? null;
if (empty($sourceToken)) {
    $logger->error("Token no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token no proporcionado."]);
    exit();
}

$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'];
$destinationEmail = $_POST['destinationEmail'];

if (empty($sourceToken)) {
    $logger->error("Token de origen no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de origen no proporcionado."]);
    exit();
}

if ($destinationEmail && empty($destinationToken)) {
    $logger->error("Token de destino no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de destino no proporcionado."]);
    exit();
}

if (!isset($_SESSION['processedEmails'])) {
    $_SESSION['processedEmails'] = [];
}

$_SESSION['progress'] = 0;
session_write_close();

try {
    $emails = getEmails($sourceToken);
    $totalEmails = count($emails);
    $logger->info("Correos obtenidos: " . $totalEmails);

    $mboxContent = "";
    foreach ($emails as $index => $email) {
        $emailId = $email['id'];

        if (in_array($emailId, getProcessedEmails())) {
            $logger->info("Correo ID $emailId ya procesado. Saltando...");
            continue;
        }

        $_SESSION['progress'] = 25 + (($index / $totalEmails) * 25);
        session_write_close();

        $logger->info("Procesando correo ID: $emailId");

        $messageId = $emailId;
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);

        if (curl_errno($ch)) {
            $logger->error("Error en cURL: " . curl_error($ch));
            throw new Exception("Error en cURL: " . curl_error($ch));
        }
        curl_close($ch);

        if (isset($emailData['raw'])) {
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
            $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
        } else {
            $logger->warning("No se encontró 'raw' en la respuesta para el mensaje ID: $messageId");
        }

        saveProcessedEmail($emailId);
    }

    $logger->info("Contenido del archivo de respaldo generado.");
    file_put_contents("backup.mbox", $mboxContent);

    if ($destinationEmail && $destinationToken) {
        foreach ($emails as $index => $email) {
            $emailId = $email['id'];

            if (in_array($emailId, getProcessedEmails())) {
                $logger->info("Correo ID $emailId ya procesado. Saltando...");
                continue;
            }

            $_SESSION['progress'] = 50 + (($index / $totalEmails) * 50);
            session_write_close();

            $messageId = $emailId;
            $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $emailData = json_decode(curl_exec($ch), true);

            if (curl_errno($ch)) {
                $logger->error("Error en cURL: " . curl_error($ch));
                throw new Exception("Error en cURL: " . curl_error($ch));
            }
            curl_close($ch);

            if (isset($emailData['raw'])) {
                $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));

                if (empty($rawEmail)) {
                    $logger->warning("Correo vacío para el mensaje: " . print_r($email, true));
                } else {
                    $emailDataToSend = [
                        "raw" => base64_encode($rawEmail),
                    ];

                    $ch = curl_init("https://www.googleapis.com/gmail/v1/users/me/messages/send");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $destinationToken",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailDataToSend));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);

                    if (curl_errno($ch)) {
                        $logger->error("Error en cURL al enviar correo: " . curl_error($ch));
                        throw new Exception("Error en cURL al enviar correo: " . curl_error($ch));
                    }

                    $logger->info("Respuesta al intentar enviar correo ID $emailId: " . $response);
                    $responseData = json_decode($response, true);
                    curl_close($ch);

                    if (isset($responseData['error'])) {
                        $logger->error("Error al migrar correo: " . print_r($responseData, true));
                        throw new Exception("Error al migrar correo: " . print_r($responseData, true));
                    }
                }
            }
        }
    }

    $_SESSION['progress'] = 100;
    session_write_close();
    $logger->info("Proceso completado exitosamente.");
    echo json_encode(["status" => "success", "message" => "Proceso completado."]);
} catch (Exception $e) {
    $logger->error("Excepción capturada: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
