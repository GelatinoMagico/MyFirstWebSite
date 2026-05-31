<?php

    include("dataBase.php");

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        $feed_back = "";
        try {
            $form = $_POST;
            // Gestione del checkbox: se esiste vale 1, altrimenti 0
            $mailspam = isset($form["mailspam"]) ? 1 : 0;
            //password hashata
            $password = password_hash($form["password"], PASSWORD_DEFAULT);

            // Controllo se il cellulare è stato effettivamente inserito e non è vuoto
            if (isset($form["cellulare"]) && !empty(trim($form["cellulare"]))) {
                $query = "INSERT INTO CLIENTI (nome, cognome, mail, cellulare, pw, mailspam) 
                        VALUES ('{$form["nome"]}', '{$form["cognome"]}', '{$form["mail"]}', '{$form["cellulare"]}', '{$password}', $mailspam)";
            } else {
                $query = "INSERT INTO CLIENTI (nome, cognome, mail, pw, mailspam) 
                        VALUES ('{$form["nome"]}', '{$form["cognome"]}', '{$form["mail"]}', '{$password}',  $mailspam)";
            }
            
            $db_connection->query($query);
            $feed_back = "<div style='text-align: center; color: #27ae60; background: #e8f8f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Registrazione andata a buon fine :D</div>";

        } catch (mysqli_sql_exception $e) {
            if (substr($e, 22, 15) == "Duplicate entry"){
                $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Registrazione non andata a buon fine :(( (email/cellulare già registrati)</div>";
            }else {
                $feed_back = "<div style='text-align: center; color: #c0392b; background: #fdf6f5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 500;'>Registrazione non andata a buon fine :((</div>";
            }
        }
    }

    mysqli_close($db_connection);

?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrazione</title>
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

    /* quadretti di background */
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

    /* form fields */
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

    label .opt {
      font-weight: 300;
      text-transform: none;
      letter-spacing: 0;
      color: #aaa;
      font-size: .75rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
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

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(192,57,43,.12);
    }

    input::placeholder { color: #bbb; }

    /* checkbox row */
    .check-row {
      display: flex;
      align-items: flex-start;
      gap: .75rem;
      background: #fdf6f5;
      border: 1px solid #f0d8d5;
      border-radius: var(--radius);
      padding: .85rem 1rem;
      cursor: pointer;
    }

    input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-top: 1px;
      flex-shrink: 0;
      accent-color: var(--accent);
      cursor: pointer;
    }

    .check-label {
      font-size: .85rem;
      color: var(--ink);
      line-height: 1.45;
      cursor: pointer;
      user-select: none;
    }

    .check-label strong { font-weight: 500; }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 1.8rem 0;
    }

    /* submit */
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
    <h1 class="brand">Registrati</h1>
    <p class="subtitle">Crea il tuo account per continuare</p>

    <form action="signIn.php" method="post">

        <!-- NOME -->
        <div class="field">
        <label for="nome">Nome <span class="req">*</span></label>
        <input
            type="text"
            id="nome"
            name="nome"
            placeholder="Mario"
            required
            autocomplete="given-name"
        />
        </div>

        <!-- COGNOME -->
        <div class="field">
        <label for="cognome">Cognome <span class="req">*</span></label>
        <input
            type="text"
            id="cognome"
            name="cognome"
            placeholder="Rossi"
            required
            autocomplete="family-name"
        />
        </div>

        <!-- MAIL -->
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

        <!-- PASSWORD --><!-- sempre hashata a 60 -->
        <div class="field">
        <label for="password">Password <span class="req">*</span></label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
            maxlength="60" 
        />
        </div>

        <!-- CELLULARE (facoltativo, solo numeri italiani) -->
        <div class="field">
        <label for="cellulare">
            Cellulare <span class="opt">(facoltativo)</span>
        </label>

        <!-- obbliga il formato +39 seguito da 9 o 10 cifre -->
        <input
            type="tel"
            id="cellulare"
            name="cellulare"
            placeholder="+39 3XX XXX XXXX"
            pattern="^\+39[0-9]{9,10}$"
            title="Inserisci un numero italiano nel formato +39XXXXXXXXX"
            autocomplete="tel"
        />
        </div>

        <hr class="divider" />

        <!-- CONSENSO MAILSPAM -->
        <div class="field">
        <label class="check-row" for="mailspam">
            <input
            type="checkbox"
            id="mailspam"
            name="mailspam"
            value="1"
            checked
            />
            <span class="check-label">
            Iscrivimi alla newsletter<br>
            Acconsento a ricevere comunicazioni promozionali via e-mail.
            Posso disiscrivermi in qualsiasi momento.
            </span>
        </label>
        </div>

        <button type="submit">Crea account →</button>

        <!-- INSERIMENTO FEEDBACK DOPO SUBMIT -->
        <?php                 
            if (!empty($feed_back)) {
                echo $feed_back;
                /*if (!empty($e)) {
                    echo $e;
                }*/ //testing
            }
        ?>

        <!-- TASTO LOGIN -->
        <p class="login-link">
        Hai già un account? <a href="logIn.php">Accedi</a>
        </p>

        <!-- TASTO HOME -->
        <p class="home-link">
            <a href="index.php"><- Torna alla Home del sito</a>
        </p>

    </form>
    </div>

</body>
</html>