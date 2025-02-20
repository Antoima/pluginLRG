<?php
// Incluir autoload de Composer
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Crear el logger
$logger = new Logger('backup_migration');

// Configurar el handler: escribir en un archivo de logs (log.txt)
$logger->pushHandler(new StreamHandler(__DIR__.'/logs/app.log', Logger::DEBUG));

// Agregar mensajes a los logs
$logger->info('Inicio del proceso de respaldo.');
$logger->debug('Token de origen recibido: ' . $sourceToken);
$logger->error('Error al migrar correo: [ID: ' . $messageId . ']');

// También puedes agregar más niveles según lo necesites
$logger->warning('Este es un mensaje de advertencia.');
$logger->critical('Este es un mensaje crítico.');

// Ejemplo de excepción en los logs
try {
    throw new Exception("Ocurrió un error inesperado.");
} catch (Exception $e) {
    $logger->error('Excepción capturada: ' . $e->getMessage());
}
?>
