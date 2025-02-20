<?php
// Incluir config.php al inicio del archivo
$config = require '/home/dh_292vea/configuracion/config.php';
$googleClientId = $config['google_client_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Backup & Migration Tool</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        // Variables globales para JavaScript
        const GOOGLE_CLIENT_ID = "<?php echo $googleClientId; ?>";
        const GOOGLE_CLIENT_SECRET = "<?php echo $config['google_client_secret']; ?>";
        const REDIRECT_URI = "<?php echo urlencode('https://pl.luisguevara.net/auth-destination.php'); ?>";
    </script>
    <style>
        .container { margin-top: 50px; }
        .form-group { margin-bottom: 20px; }
        /* Se eliminó la barra de progreso */
        .spinner-container {
            display: none;
            text-align: center;
            margin-top: 30px;
        }
        .authenticated {
            border: 2px solid #28a745 !important;
            background-color: #f8fff9 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Email Backup & Migration Tool</h1>
        <form id="backupMigrationForm">
            <div class="form-group">
                <label for="sourceEmail">Source Email Account (Backup from)</label>
                <input type="email" class="form-control" id="sourceEmail" name="sourceEmail" required readonly>
            </div>
            <div class="form-group">
                <label for="destinationEmail">Destination Email Account (Migrate to) (Optional)</label>
                <div class="input-group">
                    <input type="email" class="form-control" id="destinationEmail" name="destinationEmail" readonly>
                    <div class="input-group-append">
                        <button type="button" id="authDestinationBtn" class="btn btn-primary">
                            Autenticar
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Start Backup/Migration</button>
        </form>

        <!-- Spinner de carga (se mostrará mientras se procesa el respaldo/migración) -->
        <div id="spinner-container" class="spinner-container">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p>Procesando... Esto puede tardar un momento.</p>
        </div>

        <div id="downloadBackup" class="text-center mt-4" style="display: none;">
            <a href="backup.mbox" class="btn btn-success btn-lg" download>
                <i class="fas fa-download"></i> Descargar Respaldo
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js"></script>
    <script src="src/js/dashboard.js"></script>
</body>
</html>
