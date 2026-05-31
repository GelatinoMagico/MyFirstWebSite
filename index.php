<?php

    include("dataBase.php");

?>


<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* DESIGN TOKENS — stessa palette di logIn.php / signIn.php
       Modalità LIGHT di default, DARK via [data-theme="dark"] */
    :root {
      --bg:        #f5f2ec;
      --surface:   #fffdf8;
      --surface-2: #edeae3;
      --ink:       #1a1612;
      --muted:     #7a7268;
      --faint:     #c5bfb6;
      --accent:    #c0392b;
      --accent-dk: #96281b;
      --accent-bg: rgba(192,57,43,.07);
      --border:    #ddd8cf;
      --shadow:    0 2px 24px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.05);
      --shadow-lg: 0 8px 40px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.07);
      --radius:    6px;
      --header-h:  64px;
      --font-display: 'DM Serif Display', Georgia, serif;
      --font-body:    'DM Sans', sans-serif;
    }

    [data-theme="dark"] {
      --bg:        #0d0a07;
      --surface:   #161109;
      --surface-2: #1f1810;
      --ink:       #f0e6d0;
      --muted:     #8a7d6a;
      --faint:     #3d3328;
      --accent:    #c0392b;
      --accent-dk: #96281b;
      --accent-bg: rgba(192,57,43,.10);
      --border:    #2a2018;
      --shadow:    0 2px 24px rgba(0,0,0,.45), 0 1px 4px rgba(0,0,0,.3);
      --shadow-lg: 0 8px 40px rgba(0,0,0,.55), 0 2px 8px rgba(0,0,0,.4);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font-body);
      font-weight: 300;
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
      transition: background .3s, color .3s;
    }

    /* trama righe orizzontali — identica a logIn/signIn */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: repeating-linear-gradient(
        0deg,
        transparent, transparent 39px,
        rgba(0,0,0,.04) 39px,
        rgba(0,0,0,.04) 40px
      );
      pointer-events: none;
      z-index: 0;
    }

    /* HEADER */
    .site-header {
      position: fixed;
      top: 0; left: 0; right: 0;
      height: var(--header-h);
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0 2rem;
      z-index: 300;
      transition: background .3s, border-color .3s;
    }

    /* filetto rosso sotto l'header — richiama la card auth */
    .site-header::after {
      content: '';
      position: absolute;
      bottom: -1px; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg,
        transparent 0%, var(--accent) 25%,
        var(--accent) 75%, transparent 100%);
      opacity: .3;
    }

    .logo {
      font-family: var(--font-display);
      font-size: 1.4rem;
      color: var(--ink);
      text-decoration: none;
      white-space: nowrap;
      flex-shrink: 0;
      transition: color .2s;
    }
    .logo:hover { color: var(--accent); }

    /* barra ricerca */
    .search-wrap {
      flex: 1;
      max-width: 420px;
      margin: 0 auto;
      position: relative;
    }

    .search-icon {
      position: absolute;
      left: .8rem; top: 50%;
      transform: translateY(-50%);
      width: 15px; height: 15px;
      stroke: var(--muted); fill: none;
      stroke-width: 1.8;
      stroke-linecap: round; stroke-linejoin: round;
      pointer-events: none;
      transition: stroke .2s;
    }

    .search-wrap:focus-within .search-icon { stroke: var(--accent); }

    #search-input {
      width: 100%;
      padding: .55rem .9rem .55rem 2.3rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 99px;
      font-family: var(--font-body);
      font-size: .875rem;
      color: var(--ink);
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .3s;
    }
    #search-input::placeholder { color: var(--faint); }
    #search-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(192,57,43,.12);
    }

    /* azioni destra header */
    .header-actions {
      display: flex;
      align-items: center;
      gap: .4rem;
      flex-shrink: 0;
      position: relative;
    }

    .icon-btn {
      display: flex; align-items: center; justify-content: center;
      width: 38px; height: 38px;
      border-radius: var(--radius);
      border: 1px solid transparent;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      text-decoration: none;
      transition: background .18s, color .18s, border-color .18s;
    }
    .icon-btn:hover {
      background: var(--accent-bg);
      color: var(--accent);
      border-color: rgba(192,57,43,.2);
    }
    .icon-btn svg {
      width: 20px; height: 20px;
      stroke: currentColor; fill: none;
      stroke-width: 1.6;
      stroke-linecap: round; stroke-linejoin: round;
    }

    /* dropdown impostazioni */
    .settings-wrap { position: relative; }

    .dropdown {
      position: absolute;
      top: calc(100% + .6rem); right: 0;
      width: 235px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      opacity: 0; visibility: hidden;
      transform: translateY(-8px);
      transition: opacity .2s, transform .2s, visibility .2s;
      z-index: 400;
    }
    .dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }

    .dropdown-header {
      padding: .6rem 1rem;
      font-size: .7rem;
      font-weight: 500;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--faint);
      border-bottom: 1px solid var(--border);
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      padding: .75rem 1rem;
      font-size: .85rem;
      color: var(--ink);
      text-decoration: none;
      cursor: pointer;
      border: none; background: none;
      width: 100%;
      font-family: var(--font-body);
      font-weight: 400;
      transition: background .15s, color .15s;
    }
    .dropdown-item:hover { background: var(--accent-bg); color: var(--accent); }
    .dropdown-item svg {
      width: 15px; height: 15px;
      stroke: currentColor; fill: none;
      stroke-width: 1.6;
      stroke-linecap: round; stroke-linejoin: round;
      opacity: .6; flex-shrink: 0;
    }

    .dropdown-divider { border: none; border-top: 1px solid var(--border); }

    /* toggle tema */
    .theme-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .75rem 1rem;
    }
    .theme-label {
      display: flex; align-items: center; gap: .5rem;
      font-size: .85rem; color: var(--ink);
    }
    .theme-label svg {
      width: 15px; height: 15px;
      stroke: var(--muted); fill: none;
      stroke-width: 1.6;
      stroke-linecap: round; stroke-linejoin: round;
    }

    .toggle-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
    .toggle-track {
      position: absolute; inset: 0;
      background: var(--border);
      border-radius: 99px; cursor: pointer;
      transition: background .25s;
    }
    .toggle-track::after {
      content: '';
      position: absolute;
      top: 3px; left: 3px;
      width: 14px; height: 14px;
      border-radius: 50%;
      background: var(--surface);
      box-shadow: 0 1px 3px rgba(0,0,0,.2);
      transition: transform .25s;
    }
    .toggle-switch input:checked + .toggle-track { background: var(--accent); }
    .toggle-switch input:checked + .toggle-track::after { transform: translateX(16px); }

    /* HERO */
    .hero {
      padding: calc(var(--header-h) + 4rem) 2rem 3.5rem;
      text-align: center;
      position: relative; z-index: 1;
    }

    .hero-eyebrow {
      font-size: .72rem;
      font-weight: 500;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: .9rem;
    }

    .hero h1 {
      font-family: var(--font-display);
      font-size: clamp(2rem, 5vw, 3.2rem);
      line-height: 1.1;
      color: var(--ink);
      margin-bottom: .75rem;
      animation: fade-up .5s ease both;
    }
    .hero h1 em { font-style: italic; color: var(--accent); }

    .hero-sub {
      font-size: .93rem;
      color: var(--muted);
      margin-bottom: 2rem;
      animation: fade-up .5s .08s ease both;
    }

    .hero-btns {
      display: flex;
      gap: .75rem;
      justify-content: center;
      flex-wrap: wrap;
      animation: fade-up .5s .15s ease both;
    }

    .btn-primary {
      padding: .75rem 2.2rem;
      background: var(--accent);
      color: #fff;
      border: 1px solid var(--accent);
      border-radius: var(--radius);
      font-family: var(--font-body);
      font-size: .9rem; font-weight: 500;
      text-decoration: none; letter-spacing: .03em;
      transition: background .18s, transform .12s;
    }
    .btn-primary:hover  { background: var(--accent-dk); border-color: var(--accent-dk); }
    .btn-primary:active { transform: scale(.97); }

    .btn-ghost {
      padding: .75rem 2.2rem;
      background: transparent;
      color: var(--ink);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: var(--font-body);
      font-size: .9rem; font-weight: 400;
      text-decoration: none; letter-spacing: .03em;
      transition: border-color .18s, color .18s, transform .12s;
    }
    .btn-ghost:hover  { border-color: var(--accent); color: var(--accent); }
    .btn-ghost:active { transform: scale(.97); }

    /* GRIGLIA CATEGORIE */
    .categories-section {
      padding: 0 2rem 4rem;
      position: relative; z-index: 1;
    }

    .section-label {
      font-size: .7rem;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 1.4rem;
      display: flex; align-items: center; gap: .75rem;
    }
    .section-label::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }

    .cat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.2rem;
    }

    .cat-card {
      position: relative;
      display: block;
      height: 300px;
      border-radius: 10px;
      overflow: hidden;
      text-decoration: none;
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s;
      animation: fade-up .45s ease both;
    }

    .cat-card:nth-child(1) { animation-delay: .05s; }
    .cat-card:nth-child(2) { animation-delay: .10s; }
    .cat-card:nth-child(3) { animation-delay: .15s; }
    .cat-card:nth-child(4) { animation-delay: .20s; }
    .cat-card:nth-child(5) { animation-delay: .25s; }
    .cat-card:nth-child(6) { animation-delay: .30s; }

    .cat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .cat-card:active { transform: translateY(-2px); }

    /* gradienti placeholder — sostituiti visivamente dall'img del DB */
    .cat-card:nth-child(1) { background: linear-gradient(145deg,#2a1508,#5a3018); }
    .cat-card:nth-child(2) { background: linear-gradient(145deg,#0a1a10,#1e4028); }
    .cat-card:nth-child(3) { background: linear-gradient(145deg,#1a0a0a,#4a1515); }
    .cat-card:nth-child(4) { background: linear-gradient(145deg,#08101a,#182840); }
    .cat-card:nth-child(5) { background: linear-gradient(145deg,#1a1208,#402e10); }
    .cat-card:nth-child(6) { background: linear-gradient(145deg,#100818,#2a1040); }

    .cat-card__img {
      position: absolute; inset: 0;
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform .4s cubic-bezier(.22,1,.36,1);
    }
    .cat-card:hover .cat-card__img { transform: scale(1.05); }

    /* velo scuro per leggibilità del testo */
    .cat-card::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(
        to top,
        rgba(0,0,0,.75) 0%,
        rgba(0,0,0,.20) 55%,
        rgba(0,0,0,.04) 100%
      );
      z-index: 1;
      transition: opacity .3s;
    }
    .cat-card:hover::before { opacity: .9; }

    /* etichetta sovrimpressione */
    .cat-card__label {
      position: absolute;
      bottom: 1.1rem; left: 1.2rem; right: 1.2rem;
      z-index: 2;
      font-family: var(--font-display);
      font-size: 1.35rem;
      color: #fff;
      line-height: 1.2;
      text-shadow: 0 1px 8px rgba(0,0,0,.55);
      transition: transform .3s;
    }
    .cat-card:hover .cat-card__label { transform: translateY(-3px); }

    /* nessun risultato */
    .no-results {
      display: none;
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem 1rem;
      color: var(--muted);
      font-size: .9rem;
    }
    .no-results strong {
      display: block;
      font-family: var(--font-display);
      font-size: 1.4rem;
      color: var(--ink);
      margin-bottom: .4rem;
      font-weight: 400;
    }

    /* animazioni */
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* responsive */
    @media (max-width: 600px) {
      .site-header  { padding: 0 1rem; }
      .hero         { padding-left: 1rem; padding-right: 1rem; }
      .categories-section { padding: 0 1rem 3rem; }
      .logo         { font-size: 1.15rem; }
    }
  </style>
</head>
<body>

    <!-- HEADER -->
    <header class="site-header">
    <a class="logo" href="index.php">MotosButter</a>

    <!-- BARRA DI RICERCA — filtra le card per data-name (js) -->
    <div class="search-wrap">
        <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="11" cy="11" r="7"/>
        <line x1="16.5" y1="16.5" x2="22" y2="22"/>
        </svg>
        <input
        type="search"
        id="search-input"
        placeholder="Cerca categoria…"
        autocomplete="off"
        aria-label="Cerca categorie"
        />
    </div>

    <div class="header-actions">   <!-- TOGGLE VARI -->

        <!-- CARRELLO -->
        <a href="cart.php" class="icon-btn" title="Carrello" aria-label="Carrello">
        <svg viewBox="0 0 24 24">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        </a>

        <!-- IMPOSTAZIONI -->
        <div class="settings-wrap">
        <button class="icon-btn" id="settings-btn" title="Impostazioni"
                aria-label="Impostazioni" aria-expanded="false" aria-haspopup="true">
            <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                    a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                    A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                    l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                    A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                    l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                    a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                    l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                    a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
        </button>

        <div class="dropdown" id="settings-dropdown" role="menu">

            <div class="dropdown-header">Impostazioni</div>

                <!-- TOGGLE DARK/LIGHT -->
                <div class="theme-row">
                    <span class="theme-label">
                        <svg id="theme-icon" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <span id="theme-label-text">Modalità chiara</span>
                    </span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="theme-toggle" aria-label="Attiva modalità scura" />
                        <span class="toggle-track"></span>
                    </label>
                </div>

                <hr class="dropdown-divider" />

                <!-- INDIRIZZO DI SPEDIZIONE -->
                <a href="cart.php" class="dropdown-item" role="menuitem">
                <span>Indirizzo di spedizione</span>
                <svg viewBox="0 0 24 24">
                    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                </a>

                <!-- MODALITA' DI PAGAMENTO -->
                <a href="cart.php" class="dropdown-item" role="menuitem">
                <span>Modalità di pagamento</span>
                <svg viewBox="0 0 24 24">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                </a>

                <hr class="dropdown-divider" />

                <!-- LOGOUT (solo se siamo loggati) -->
                <?php
                if(!empty($_SESSION["id_utente"])) {
                    echo'<a href="logOut.php" class="dropdown-item" role="menuitem"
                    style="color: var(--accent);">
                    <span>Esci dall\'account</span>
                    <svg viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    </a>';
                }
                ?>
                

            </div>
        </div>
    </div>
    </header>

    <!-- HERO (Benvenuto se non loggato, Benvenuto, Nome Cognome se loggato) --> 
    <section class="hero">
    <?php
        if(empty($_SESSION["nome_utente"]) || empty($_SESSION["cognome_utente"])) {
            echo"<p class='hero-eyebrow'>Benvenuto</p>
            <h1>Scopri le nostre<br><em>categorie</em></h1>
            <p class='hero-sub'>Esplora il catalogo o accedi al tuo account.</p>
            <div class='hero-btns'>
                <a href='signIn.php' class='btn-primary'>Registrati</a>
                <a href='logIn.php'  class='btn-ghost'>Accedi</a>
            </div>";
        }
        else {
            echo"<p class='hero-eyebrow'>Benvenuto, {$_SESSION['nome_utente']} {$_SESSION['cognome_utente']}</p>
            <h1>Scopri le nostre<br><em>categorie</em></h1>
            <p class='hero-sub'>Esplora il catalogo :)</p>";
        }
    ?>
    </section>


    <!-- GRIGLIA CATEGORIE -->
    <section class="categories-section">
    <p class="section-label">Categorie</p>

    <div class="cat-grid" id="cat-grid">
        <?php
        $result = $db_connection->query("SELECT id, nome, img FROM categorie ORDER BY nome ASC");

        while ($cat = $result->fetch_assoc()):
            $id      = $cat['id'];
            $nome    = htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8');
            $nomeRaw = htmlspecialchars($cat['nome'], ENT_NOQUOTES, 'UTF-8');
            
            // Converte il blob in data URI base64 — nessun file esterno necessario
            $imgSrc = '';
            if (!empty($cat['img'])) {
                $mime   = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $cat['img']) ?: 'image/jpeg';
                $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($cat['img']);
            }
        ?>
            <a href="products.php?id_categoria=<?= urlencode($id) ?>" class="cat-card" data-name="<?= $nomeRaw ?>">
                <img class="cat-card__img" src="<?= $imgSrc ?>" alt="<?= $nome ?>" />
                <span class="cat-card__label"><?= $nome ?></span>
            </a>
        <?php
        endwhile;
        $db_connection->close();
        ?>

        <!-- Messaggio nessun risultato (gestito via JS) -->
        <div class="no-results" id="no-results" aria-live="polite">
        <strong>Nessuna categoria trovata</strong>
        Prova con un termine diverso.
        </div>

    </div>
    </section>


    <!-- JAVASCRIPT -->
    <script>

    /* 1. DARK / LIGHT MODE */

    const html        = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon   = document.getElementById('theme-icon');
    const themeLbl    = document.getElementById('theme-label-text');

    const MOON_SVG = `<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>`;
    const SUN_SVG  = `
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1"     x2="12" y2="3"/>
        <line x1="12" y1="21"    x2="12" y2="23"/>
        <line x1="4.22" y1="4.22"  x2="5.64"  y2="5.64"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1"  y1="12" x2="3"  y2="12"/>
        <line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64"  y2="18.36"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>`;

    function applyTheme(dark) {
        html.setAttribute('data-theme', dark ? 'dark' : 'light');
        themeToggle.checked      = dark;
        themeIcon.innerHTML      = dark ? MOON_SVG : SUN_SVG;
        themeLbl.textContent     = dark ? 'Modalità scura' : 'Modalità chiara';
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        <?php
        //setcookie("DarkMode", true, (86400*30), "/"); non necessario, tutto gestito via js
        ?>
    }

    // ripristina preferenza salvata (o usa light di default)
    applyTheme(localStorage.getItem('theme') === 'dark');
    themeToggle.addEventListener('change', () => applyTheme(themeToggle.checked));


    /* 2. DROPDOWN IMPOSTAZIONI */

    const settingsBtn = document.getElementById('settings-btn');
    const dropdown    = document.getElementById('settings-dropdown');

    settingsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = dropdown.classList.toggle('open');
        settingsBtn.setAttribute('aria-expanded', String(open));
    });

    // chiudi cliccando fuori
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && e.target !== settingsBtn) {
        dropdown.classList.remove('open');
        settingsBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // chiudi con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dropdown.classList.contains('open')) {
        dropdown.classList.remove('open');
        settingsBtn.setAttribute('aria-expanded', 'false');
        settingsBtn.focus();
        }
    });


    /* 3. RICERCA CATEGORIE */

    const searchInput = document.getElementById('search-input');
    const cards       = document.querySelectorAll('.cat-card');
    const noResults   = document.getElementById('no-results');

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;

        cards.forEach(card => {
        const name    = (card.dataset.name || '').toLowerCase();
        const matches = !q || name.includes(q);
        card.style.display = matches ? '' : 'none';
        if (matches) visible++;
        });

        noResults.style.display = (visible === 0) ? 'block' : 'none';
    });

    </script>

</body>
</html>