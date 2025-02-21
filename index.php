<?php
// index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal</title>
    
    <!-- Enlace al CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Enlace al CSS personalizado -->
    <link rel="stylesheet" href="src/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <!-- Tarjeta de Reconocimiento Facial -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-lg border-light">
                    <div class="card-body text-center">
                        <i class="bi bi-person-circle" style="font-size: 4rem; color: #007bff;"></i>
                        <h5 class="card-title mt-3">Reconocimiento Facial</h5>
                        <p class="card-text">Accede a la funcionalidad de reconocimiento facial para ver la detección en tiempo real.</p>
                        <a href="#" class="btn btn-primary">Ir al Reconocimiento</a>
                    </div>
                </div>
            </div>
            
            <!-- Tarjeta de Migración de Correos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-lg border-light">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope-fill" style="font-size: 4rem; color: #28a745;"></i>
                        <h5 class="card-title mt-3">Migración de Correos</h5>
                        <p class="card-text">Realiza la migración de tus correos electrónicos entre diferentes servicios.</p>
                        <a href="#" class="btn btn-success">Ir a la Migración</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enlace a los scripts de Bootstrap y JS personalizado -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="src/js/script.js"></script>
</body>
</html>
