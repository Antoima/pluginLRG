<?php
session_start();

if (isset($_GET['access_token'])) {
    // Almacenar el token de destino en localStorage vía JavaScript
    echo "<script>
        if (window.opener) {
            const params = new URLSearchParams(window.location.hash.substring(1));
            const accessToken = params.get('access_token');
            if (accessToken) {
                localStorage.setItem('destination_access_token', accessToken);
                window.opener.postMessage({ action: 'destinationAuthenticated' }, '*');
            }
            window.close();
        }
    </script>";
    exit();
}

if (isset($_GET['error'])) {
    die("Error de autenticación: " . $_GET['error']);
}