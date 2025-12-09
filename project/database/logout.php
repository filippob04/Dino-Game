<?php
    session_start(); // Gestisco la sessione

    $_SESSION = array(); // svuota i dati di sessione (array)
    session_destroy();

    header("Location: loginForm.php"); // rederict al login
    exit();
