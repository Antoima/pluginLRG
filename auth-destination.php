<?php
session_start();

if (isset($_GET['access_token'])) {
    // Almacenar el token de destino en localStorage vía JavaScript
    echo "<script>
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');
    
    if (accessToken && window.opener) {
        window.opener.postMessage({
            action: 'destinationAuthenticated',
            accessToken: accessToken
        }, '*');
        window.close();
    } else {
        document.write('Error: Token no recibido.');
    }
</script>";
    exit();
}

if (isset($_GET['error'])) {
    die("Error de autenticación: " . $_GET['error']);
}