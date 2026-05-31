<?php
    include("dataBase.php");

    // VERIFICA LOGIN
    // Se l'utente non ha una sessione attiva, lo reindirizziamo alla pagina di login
    if (empty($_SESSION["id_utente"])) {
        header("Location: logIn.php");
        exit();
    }

    // RECUPERO DATI
    // Assicuriamoci che la richiesta sia in POST e che contenga l'id_prodotto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_prodotto'])) {
        
        $idCliente = intval($_SESSION["id_utente"]);
        $idProdotto = intval($_POST['id_prodotto']);

        try {
            // CONTROLLO PREESISTENZA NEL CARRELLO
            // Verifichiamo se il cliente ha già questo prodotto nel carrello
            $query_check = "SELECT quantita FROM carrelloProdotto 
                            WHERE idCliente = '{$idCliente}' AND idProdotto = '{$idProdotto}'";
            $result_check = $db_connection->query($query_check);

            if ($result_check && mysqli_num_rows($result_check) > 0) {
                // Il prodotto è già nel carrello: incrementiamo la quantità
                $row = mysqli_fetch_assoc($result_check);
                $nuovaQuantita = intval($row['quantita']) + 1;
                
                $query_update = "UPDATE carrelloProdotto 
                                 SET quantita = '{$nuovaQuantita}' 
                                 WHERE idCliente = '{$idCliente}' AND idProdotto = '{$idProdotto}'";
                $db_connection->query($query_update);
            } else {
                // Il prodotto non è nel carrello: lo aggiungiamo con quantità 1
                $query_insert = "INSERT INTO carrelloProdotto (idCliente, idProdotto, quantita) 
                                 VALUES ('{$idCliente}', '{$idProdotto}', 1)";
                $db_connection->query($query_insert);
            }

            // REDIRECT AL CARRELLO
            header("Location: cart.php");
            exit();

        } catch (mysqli_sql_exception $e) {
            // In caso di errore del database, mostriamo a schermo il problema
            // (In produzione potresti voler fare un redirect a una pagina di errore generica)
            die("Errore durante l'aggiunta al carrello: " . htmlspecialchars($e->getMessage()));
        }

    } else {
        // Se qualcuno prova ad accedere alla pagina direttamente via URL (GET) o senza dati,
        // lo rimandiamo alla home.
        header("Location: index.php");
        exit();
    }
?>