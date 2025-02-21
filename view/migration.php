<?php
// migracion.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración de Correos</title>
    
    <!-- Enlace al CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Enlace al CSS personalizado -->
    <link rel="stylesheet" href="../src/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-light">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Migración de Correos</h3>
                        <form>
                            <div class="mb-3">
                                <label for="origenCorreo" class="form-label">Correo de Origen</label>
                                <input type="email" class="form-control" id="origenCorreo" placeholder="Correo electrónico de origen" required>
                            </div>
                            <div class="mb-3">
                                <label for="destinoCorreo" class="form-label">Correo de Destino</label>
                                <input type="email" class="form-control" id="destinoCorreo" placeholder="Correo electrónico de destino" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Iniciar Migración</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enlace a los scripts de Bootstrap y JS personalizado -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="../src/js/script.js"></script>
</body>
</html>
