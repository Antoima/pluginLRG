<?php
// index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Migración de Correos</title>

    <!-- Enlace al CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Enlace al CSS personalizado -->
    <link rel="stylesheet" href="../src/css/styles.css">
    <link rel="stylesheet" href="../src/migration/src/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <!-- Tarjeta para Gmail -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-lg border-light">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope-fill" style="font-size: 4rem; color: #db4437;"></i>
                        <h5 class="card-title mt-3">Gmail</h5>
                        <p class="card-text">Migra tus correos desde o hacia tu cuenta de Gmail de forma rápida y segura.</p>
                        <a href="view/migration.php?service=gmail" class="btn btn-danger">Migrar con Gmail</a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Outlook -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-lg border-light">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope-at" style="font-size: 4rem; color: #0078d4;"></i>
                        <h5 class="card-title mt-3">Outlook</h5>
                        <p class="card-text">Migra tus correos desde o hacia tu cuenta de Outlook de forma sencilla.</p>
                        <a href="view/migration.php?service=outlook" class="btn btn-primary">Migrar con Outlook</a>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Yahoo -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-lg border-light">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope" style="font-size: 4rem; color: #6e389d;"></i>
                        <h5 class="card-title mt-3">Yahoo</h5>
                        <p class="card-text">Realiza la migración de correos desde o hacia tu cuenta de Yahoo.</p>
                        <a href="view/migration.php?service=yahoo" class="btn btn-purple">Migrar con Yahoo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enlace a los scripts de Bootstrap y JS personalizado -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
