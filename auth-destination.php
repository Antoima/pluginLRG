<?php
session_start();

if (isset($_GET['access_token'])) {
    // Almacenar el token de destino en localStorage vía JavaScript
    echo "<script>
        localStorage.setItem('destination_access_token', '" . $_GET['access_token'] . "');
        window.location.href = 'email_backup_migration.php';
    </script>";
    exit();
}

if (isset($_GET['error'])) {
    die("Error de autenticación: " . $_GET['error']);
}