<?php

    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $db_server = "localhost";
    $db_user = "root";
    $db_password = "";
    $db_name = "MotosBurro";

    try {
        $db_connection = mysqli_connect(
            $db_server,
            $db_user,
            $db_password,
            $db_name
        );
    }
    catch(mysqli_sql_exception) {
        echo"Connessione cacata";
    }
    
?>