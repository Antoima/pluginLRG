<?php
// Incluir config.php al inicio del archivo
$config = require '/home/dh_292vea/configuracion/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enviar Correo</title>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= $config['recaptcha_site_key'] ?>"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="src/css/styles.css">
</head>
<body>
    <!-- Spinner de carga -->
    <div id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
    </div>

    <div class="container mt-5">
        <!-- Título y formulario -->
        <div id="contentSection" style="display: none;">
            <h1 class="text-center mb-4">Enviar Correo</h1>
            <form id="sendEmailForm" class="mt-4">
                <input type="hidden" id="faceData" name="face_data">
                <div class="form-group">
                    <label for="to">Destinatario:</label>
                    <input type="email" class="form-control" id="to" name="to" required>
                </div>
                <div class="form-group">
                    <label for="subject">Asunto:</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="body">Mensaje:</label>
                    <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-paper-plane"></i> Enviar Correo
                </button>
            </form>
        </div>

        <!-- Sección de cámara -->
        <div id="cameraSection">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Verificación Facial</h5>
                    <div id="cameraPreview"></div>
                    <button type="button" id="captureButton" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-camera"></i> Capturar Rostro
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
    <!-- <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script> -->
    <script>
        // Definir la variable correctamente
        const recaptchaSiteKey = "<?= $config['recaptcha_site_key'] ?>";
    </script>
    <script src="src/js/facial-recognition.js"></script>
    <script src="src/js/sendEmail.js"></script>
</body>
</html>