<?php

    include("dataBase.php");

    $feed_back = "";

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        try {
            $form = $_POST;
            $mail = trim($form["mail"] ?? '');
            $password_inserita = $form["password"] ?? '';

            if (!empty($mail) && !empty($password_inserita)) {
                $query = "SELECT id, nome, cognome, pw FROM Clienti WHERE mail = '{$mail}'"; //query per trovare la password hashata della mail
                $result = $db_connection -> query($query);    //oggetto
                if(mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);       //array associativo
                    if(password_verify($password_inserita, $row["pw"])) {
                        $feed_back = "<div style='text-align: center; color: #27ae60; background: #e8f8f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Accesso eseguito :D</div>";
                        if(session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION["id_utente"] = $row["id"];
                        $_SESSION["nome_utente"] = $row["nome"];
                        $_SESSION["cognome_utente"] = $row["cognome"];
                        $_SESSION["mail_utente"] = $mail;
                        if(!empty($row["cellulare"])) {
                            $_SESSION["cellulare_utente"] = $row["cellulare"];
                        }
                        header("Location: index.php");
                        exit();
                    } else {
                        $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>BUZZZ! Password errata INTRUSOOO!!</div>";
                    }
                } else {
                    $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Email non registrata :/</div>";
                }
            } else {
                $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Inserisci mail e password >:(</div>";
            }

        } catch (mysqli_sql_exception $e) {
            $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Errore T.T</div>";
        }
    }

    mysqli_close($db_connection);

?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accedi</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg:        #f5f2ec;
      --surface:   #fffdf8;
      --ink:       #1a1612;
      --muted:     #7a7268;
      --accent:    #c0392b;
      --accent-dk: #96281b;
      --border:    #ddd8cf;
      --radius:    6px;
      --shadow:    0 2px 24px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.05);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        repeating-linear-gradient(
          0deg,
          transparent,
          transparent 39px,
          rgba(0,0,0,.04) 39px,
          rgba(0,0,0,.04) 40px
        );
      pointer-events: none;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 3rem 2.8rem;
      width: 100%;
      max-width: 480px;
      position: relative;
      animation: rise .5s ease both;
    }

    @keyframes rise {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 2.5rem; right: 2.5rem;
      height: 3px;
      background: var(--accent);
      border-radius: 0 0 3px 3px;
    }

    .brand {
      font-family: 'DM Serif Display', serif;
      font-size: 1.85rem;
      letter-spacing: -.5px;
      margin-bottom: .3rem;
    }

    .subtitle {
      font-size: .875rem;
      color: var(--muted);
      margin-bottom: 2.2rem;
    }

    .field {
      margin-bottom: 1.3rem;
    }

    label {
      display: block;
      font-size: .78rem;
      font-weight: 500;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: .45rem;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: .7rem .9rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: 'DM Sans', sans-serif;
      font-size: .95rem;
      color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(192,57,43,.12);
    }

    input::placeholder { color: #bbb; }

    button[type="submit"] {
      width: 100%;
      padding: .85rem 1rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: 'DM Sans', sans-serif;
      font-size: .95rem;
      font-weight: 500;
      cursor: pointer;
      letter-spacing: .03em;
      transition: background .18s, transform .12s;
      margin-bottom: 1rem;
    }

    button[type="submit"]:hover  { background: var(--accent-dk); }
    button[type="submit"]:active { transform: scale(.98); }

    .login-link {
      text-align: center;
      margin-top: 1.2rem;
      font-size: .82rem;
      color: var(--muted);
    }

    .login-link a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover { text-decoration: underline; }

    .home-link {
        text-align: center;
        margin-top: 1rem;       
        font-size: .82rem;      
        color: var(--muted);
    }

    .home-link a {
        color: var(--muted);   
        text-decoration: none;
        font-weight: 500;
        transition: color .18s; 
    }

    .home-link a:hover {
        color: var(--accent);   
        text-decoration: underline;
    }

    .req { color: var(--accent); margin-left: 2px; }
  </style>
</head>
<body>

    <div class="card">
    <h1 class="brand">Accedi</h1>
    <p class="subtitle">Bentornato! Inserisci le tue credenziali</p>

    <form action="" method="post">

        <!-- EMAIL -->
        <div class="field">
        <label for="mail">Indirizzo e-mail <span class="req">*</span></label>
        <input
            type="email"
            id="mail"
            name="mail"
            placeholder="mario.rossi@esempio.it"
            required
            autocomplete="email"
        />
        </div>

        <!-- PASSWORD -->
        <div class="field">
        <label for="password">Password <span class="req">*</span></label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
        />
        </div>

        <button type="submit">Accedi →</button>

        <!-- INSERIMENTO FEEDBACK DOPO SUBMIT -->
        <?php
            if (!empty($feed_back)) {
                echo $feed_back;
            }
        ?>

        <!-- TASTO LOGIN -->
        <p class="login-link">
        Non hai ancora un account? <a href="signIn.php">Registrati</a>
        </p>

        <!-- TASTO HOME -->
        <p class="home-link">
            <a href="index.php"><- Torna alla Home del sito</a>
        </p>

    </form>
    </div>

</body>
</html>