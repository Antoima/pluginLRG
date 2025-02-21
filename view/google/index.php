<?php
// migracion.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración de Correos</title>

    <!-- Enlace al CSS de Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Enlace a los iconos de Google (para los botones de Google) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Enlace al CSS personalizado -->
    <link rel="stylesheet" href="../../src/css/styles.css">
</head>
<body class="bg-gray-100">

    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg">
            <h3 class="text-3xl font-semibold text-center text-gray-800 mb-6">Migración de Correos</h3>

            <!-- Etiqueta para el primer botón (Correo de Origen) -->
            <label class="block text-gray-700 text-lg font-medium mb-2">Autenticar con Google - Origen</label>
            <button type="button" class="w-full py-3 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-300 mb-4 flex items-center justify-center">
                <i class="bi bi-google mr-3 text-lg"></i>
                <span>Autenticar con Google</span>
            </button>

            <!-- Etiqueta para el segundo botón (Correo de Destino) -->
            <label class="block text-gray-700 text-lg font-medium mb-2">Autenticar con Google - Destino</label>
            <button type="button" class="w-full py-3 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-300 mb-4 flex items-center justify-center">
                <i class="bi bi-google mr-3 text-lg"></i>
                <span>Autenticar con Google</span>
            </button>

            <!-- Botón de inicio de migración (ahora con Google Auth) -->
            <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-md hover:bg-green-600 transition duration-300 mb-4">
                Iniciar Migración
            </button>

        </div>
    </div>

</body>
</html>
