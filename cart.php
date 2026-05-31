<?php
    include("dataBase.php");

    $idCliente = $_SESSION["id_utente"];

    /* SELECT: prodotti nel carrello */
    $query_carrelloprodotto = "SELECT p.id, p.nome, p.prezzo, cp.quantita, ca.img
                FROM carrelloProdotto cp
                JOIN prodotti p ON p.id = cp.idProdotto
                JOIN categorie ca ON ca.id = p.idCategoria
                WHERE cp.idCliente = '{$idCliente}'
                ORDER BY p.nome ASC
                ";

    /* SELECT: modalità di pagamento (id serve per l'ordine) */
    $query_modalitapagamento = "SELECT id, nome
                FROM modalitaPagamento
                ";

    /* SELECT: corrieri (id serve per l'ordine) */
    $query_corrieri = "SELECT id, nome, mail
                FROM corrieri
                ";

    /* SUBMIT ORDINE — intercettato prima del rendering HTML */
    $errore_ordine = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conferma_ordine'])) {
        try {
            $lat         = floatval($_POST['lat']);
            $lon         = floatval($_POST['lon']);
            $idCorriere  = intval($_POST['id_corriere']);
            $idPagamento = intval($_POST['id_pagamento']);

            /* 1. INSERT coordinate (tipo POINT spaziale MariaDB) */
            $query_ins_coordinate = "INSERT INTO coordinate (coordinate)
                        VALUES (ST_GeomFromText('POINT({$lat} {$lon})'))
                        ";
            $db_connection->query($query_ins_coordinate);
            $idCoordinate = $db_connection->insert_id;

            /* 2. INSERT ordine principale */
            $query_ins_ordine = "INSERT INTO ordini
                        (idCorriere, idCliente, idModalitaPagamento, idCoordinate, dataOrdine, dataConsegna)
                        VALUES
                        ('{$idCorriere}', '{$idCliente}', '{$idPagamento}', '{$idCoordinate}',
                         NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY))
                        ";
            $db_connection->query($query_ins_ordine);
            $idOrdine = $db_connection->insert_id;

            /* 3. INSERT prodotti dell'ordine da carrelloprodotto */
            $result_carrello_submit = $db_connection->query($query_carrelloprodotto);
            while ($riga = mysqli_fetch_assoc($result_carrello_submit)) {
                $idProdotto = intval($riga['id']);
                $quantita   = intval($riga['quantita']);

                $query_ins_ordineprodotto = "INSERT INTO ordineProdotto
                            (idOrdine, idProdotto, quantita)
                            VALUES
                            ('{$idOrdine}', '{$idProdotto}', '{$quantita}')
                            ";
                $db_connection->query($query_ins_ordineprodotto);
            }

            /* 4. Svuota il carrello del cliente */
            $query_del_carrello = "DELETE FROM carrelloProdotto
                        WHERE idCliente = '{$idCliente}'
                        ";
            $db_connection->query($query_del_carrello);

            /* 5. Redirect post-ordine */
            header("Location: ordine_confermato.php?id={$idOrdine}");
            exit;

        } catch (mysqli_sql_exception $e) {
            $errore_ordine = $e->getMessage();
        }
    }
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Carrello — MotosButter</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* DESIGN TOKENS */
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

    /* trama righe orizzontali */
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

    .header-title {
      flex: 1;
      text-align: center;
      font-family: var(--font-display);
      font-size: 1rem;
      color: var(--muted);
      font-style: italic;
      letter-spacing: .02em;
    }

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

    /* LAYOUT PRINCIPALE */
    .cart-page {
      padding-top: calc(var(--header-h) + 2.5rem);
      padding-bottom: 5rem;
      position: relative; z-index: 1;
    }

    .cart-inner {
      max-width: 1160px;
      margin: 0 auto;
      padding: 0 2rem;
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 2rem;
      align-items: start;
    }

    @media (max-width: 900px) {
      .cart-inner { grid-template-columns: 1fr; }
    }

    /* titolo pagina */
    .cart-heading {
      grid-column: 1 / -1;
      display: flex;
      align-items: baseline;
      gap: 1rem;
      margin-bottom: .25rem;
      animation: fade-up .4s ease both;
    }

    .cart-heading h1 {
      font-family: var(--font-display);
      font-size: clamp(1.8rem, 4vw, 2.6rem);
      line-height: 1.1;
    }
    .cart-heading h1 em { font-style: italic; color: var(--accent); }

    .cart-count {
      font-size: .8rem;
      font-weight: 500;
      color: var(--muted);
      letter-spacing: .06em;
      text-transform: uppercase;
      background: var(--surface-2);
      padding: .25rem .7rem;
      border-radius: 99px;
      border: 1px solid var(--border);
    }

    /* banner errore ordine */
    .order-error {
      grid-column: 1 / -1;
      padding: .9rem 1.2rem;
      background: var(--accent-bg);
      border: 1px solid var(--accent);
      border-radius: var(--radius);
      color: var(--accent);
      font-size: .875rem;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .order-error svg {
      width: 16px; height: 16px;
      stroke: currentColor; fill: none;
      stroke-width: 1.8; flex-shrink: 0;
    }

    /* COLONNA SINISTRA */
    .cart-left { display: flex; flex-direction: column; gap: 1.2rem; }

    .cart-section {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: fade-up .4s ease both;
    }

    .cart-section:nth-child(2) { animation-delay: .05s; }
    .cart-section:nth-child(3) { animation-delay: .10s; }
    .cart-section:nth-child(4) { animation-delay: .15s; }

    .section-header {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: 1rem 1.4rem;
      border-bottom: 1px solid var(--border);
    }

    .section-header svg {
      width: 16px; height: 16px;
      stroke: var(--accent); fill: none;
      stroke-width: 1.8;
      stroke-linecap: round; stroke-linejoin: round;
      flex-shrink: 0;
    }

    .section-header h2 {
      font-family: var(--font-body);
      font-size: .72rem;
      font-weight: 500;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--muted);
    }

    /* ── riga prodotto ── */
    .product-list { padding: .4rem 0; }

    .product-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: .85rem 1.4rem;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }
    .product-row:last-child { border-bottom: none; }
    .product-row:hover { background: var(--accent-bg); }

    .product-thumb {
      width: 60px; height: 60px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid var(--border);
      flex-shrink: 0;
      background: var(--surface-2);
    }

    .product-thumb-placeholder {
      width: 60px; height: 60px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--surface-2);
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
    }
    .product-thumb-placeholder svg {
      width: 22px; height: 22px;
      stroke: var(--faint); fill: none;
      stroke-width: 1.4;
    }

    .product-info { flex: 1; min-width: 0; }

    .product-name {
      font-size: .9rem;
      font-weight: 400;
      color: var(--ink);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: .18rem;
    }

    .product-meta {
      font-size: .75rem;
      color: var(--muted);
    }

    /* stepper quantità */
    .qty-control {
      display: flex;
      align-items: center;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      flex-shrink: 0;
    }

    .qty-btn {
      width: 30px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      background: var(--surface-2);
      border: none;
      color: var(--muted);
      cursor: pointer;
      font-size: 1.1rem;
      font-weight: 300;
      transition: background .15s, color .15s;
      user-select: none;
    }
    .qty-btn:hover { background: var(--accent-bg); color: var(--accent); }

    .qty-value {
      width: 36px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem;
      font-weight: 500;
      color: var(--ink);
      background: var(--surface);
      border-left: 1px solid var(--border);
      border-right: 1px solid var(--border);
      user-select: none;
    }

    .product-price {
      font-family: var(--font-display);
      font-size: 1rem;
      color: var(--ink);
      white-space: nowrap;
      flex-shrink: 0;
      min-width: 70px;
      text-align: right;
    }

    .remove-btn {
      width: 28px; height: 28px;
      display: flex; align-items: center; justify-content: center;
      background: transparent;
      border: none;
      color: var(--faint);
      cursor: pointer;
      border-radius: var(--radius);
      transition: color .15s, background .15s;
      flex-shrink: 0;
    }
    .remove-btn:hover { color: var(--accent); background: var(--accent-bg); }
    .remove-btn svg {
      width: 14px; height: 14px;
      stroke: currentColor; fill: none;
      stroke-width: 1.8;
    }

    /* carrello vuoto */
    .empty-cart {
      padding: 3.5rem 1.4rem;
      text-align: center;
      display: none;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
    }
    .empty-cart.visible { display: flex; }

    .empty-cart svg {
      width: 48px; height: 48px;
      stroke: var(--faint); fill: none;
      stroke-width: 1.2;
      stroke-linecap: round; stroke-linejoin: round;
    }

    .empty-cart strong {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 400;
      color: var(--ink);
    }

    .empty-cart p { font-size: .85rem; color: var(--muted); }

    .empty-cart a {
      margin-top: .5rem;
      padding: .65rem 1.8rem;
      background: var(--accent);
      color: #fff;
      border-radius: var(--radius);
      text-decoration: none;
      font-size: .875rem;
      font-weight: 500;
      transition: background .18s;
    }
    .empty-cart a:hover { background: var(--accent-dk); }

    /* INDIRIZZO DI SPEDIZIONE */
    .address-body { padding: 1.2rem 1.4rem; }

    .coord-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .8rem;
      margin-bottom: .8rem;
    }

    .field-group { display: flex; flex-direction: column; gap: .35rem; }

    .field-group label {
      font-size: .7rem;
      font-weight: 500;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .field-group input {
      padding: .6rem .85rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: var(--font-body);
      font-size: .875rem;
      color: var(--ink);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .field-group input::placeholder { color: var(--faint); }
    .field-group input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(192,57,43,.1);
    }
    .field-group input.input-error {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(192,57,43,.15);
    }

    .map-placeholder {
      margin-top: .4rem;
      height: 130px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      background: var(--surface-2);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      color: var(--muted);
      font-size: .8rem;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      position: relative;
      overflow: hidden;
    }
    .map-placeholder:hover { border-color: var(--accent); background: var(--accent-bg); }

    .map-placeholder::before {
      content: '';
      position: absolute; inset: 0;
      background-image: radial-gradient(circle, var(--faint) 1px, transparent 1px);
      background-size: 18px 18px;
      opacity: .5;
    }

    .map-placeholder svg {
      width: 18px; height: 18px;
      stroke: var(--accent); fill: none;
      stroke-width: 1.6;
      position: relative; z-index: 1;
    }
    .map-placeholder span { position: relative; z-index: 1; }

    /* METODO DI PAGAMENTO */
    .payment-body { padding: 1.2rem 1.4rem; display: flex; flex-direction: column; gap: .6rem; }

    .radio-option {
      display: flex;
      align-items: center;
      gap: .85rem;
      padding: .8rem 1rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      cursor: pointer;
      transition: border-color .18s, background .18s;
      position: relative;
    }
    .radio-option:hover { border-color: var(--accent); background: var(--accent-bg); }
    .radio-option.selected { border-color: var(--accent); background: var(--accent-bg); }

    .radio-option input[type="radio"] {
      position: absolute; opacity: 0; width: 0; height: 0;
    }

    .radio-dot {
      width: 16px; height: 16px;
      border-radius: 50%;
      border: 2px solid var(--border);
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: border-color .18s;
    }
    .radio-option.selected .radio-dot { border-color: var(--accent); }

    .radio-dot::after {
      content: '';
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--accent);
      transform: scale(0);
      transition: transform .15s;
    }
    .radio-option.selected .radio-dot::after { transform: scale(1); }

    .radio-icon {
      width: 32px; height: 32px;
      border-radius: 6px;
      background: var(--surface-2);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      border: 1px solid var(--border);
    }
    .radio-icon svg {
      width: 17px; height: 17px;
      stroke: var(--muted); fill: none;
      stroke-width: 1.6;
      stroke-linecap: round; stroke-linejoin: round;
    }
    .radio-option.selected .radio-icon svg { stroke: var(--accent); }

    .radio-label {
      flex: 1;
      font-size: .875rem;
      color: var(--ink);
      font-weight: 400;
    }
    .radio-sublabel {
      font-size: .73rem;
      color: var(--muted);
      display: block;
      margin-top: .1rem;
    }

    /* SCELTA CORRIERE */
    .courier-body { padding: 1.2rem 1.4rem; display: flex; flex-direction: column; gap: .6rem; }

    .courier-option {
      display: flex;
      align-items: center;
      gap: .85rem;
      padding: .8rem 1rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      cursor: pointer;
      transition: border-color .18s, background .18s;
      position: relative;
    }
    .courier-option:hover { border-color: var(--accent); background: var(--accent-bg); }
    .courier-option.selected { border-color: var(--accent); background: var(--accent-bg); }

    .courier-option input[type="radio"] {
      position: absolute; opacity: 0; width: 0; height: 0;
    }

    .courier-logo {
      width: 40px; height: 28px;
      border-radius: 4px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: .55rem;
      font-weight: 700;
      letter-spacing: .03em;
      color: var(--muted);
      flex-shrink: 0;
      text-transform: uppercase;
      text-align: center;
      padding: 0 2px;
      line-height: 1.2;
    }

    .courier-info { flex: 1; min-width: 0; }
    .courier-name {
      font-size: .875rem;
      color: var(--ink);
      font-weight: 400;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .courier-time {
      font-size: .73rem;
      color: var(--muted);
      margin-top: .1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* COLONNA DESTRA — Riepilogo ordine */
    .summary-form {
      position: sticky;
      top: calc(var(--header-h) + 1.5rem);
    }

    .cart-summary {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: fade-up .4s .2s ease both;
    }

    .summary-header {
      padding: 1rem 1.4rem;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: .6rem;
    }

    .summary-header h2 {
      font-size: .72rem;
      font-weight: 500;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .summary-body { padding: 1.2rem 1.4rem; }

    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: .5rem 0;
      font-size: .875rem;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
    }
    .summary-row:last-of-type { border-bottom: none; }

    .summary-row span:last-child { font-weight: 400; color: var(--ink); }
    .summary-row.shipping span:last-child { color: #2d7a4a; font-weight: 500; }

    .summary-total {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 2px solid var(--border);
    }

    .summary-total .label {
      font-size: .85rem;
      font-weight: 500;
      color: var(--ink);
      letter-spacing: .04em;
      text-transform: uppercase;
    }

    .summary-total .amount {
      font-family: var(--font-display);
      font-size: 1.6rem;
      color: var(--ink);
    }
    .summary-total .amount em { font-style: normal; color: var(--accent); }

    .btn-checkout {
      display: block;
      width: 100%;
      margin-top: 1.3rem;
      padding: .9rem 1rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: var(--font-body);
      font-size: .95rem;
      font-weight: 500;
      text-align: center;
      cursor: pointer;
      letter-spacing: .03em;
      transition: background .18s, transform .12s;
    }
    .btn-checkout:hover  { background: var(--accent-dk); }
    .btn-checkout:active { transform: scale(.98); }
    .btn-checkout:disabled {
      background: var(--border);
      color: var(--muted);
      cursor: not-allowed;
      transform: none;
    }

    .btn-continue {
      display: block;
      width: 100%;
      margin-top: .6rem;
      padding: .7rem 1rem;
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: var(--font-body);
      font-size: .85rem;
      text-align: center;
      text-decoration: none;
      transition: border-color .18s, color .18s;
    }
    .btn-continue:hover { border-color: var(--accent); color: var(--accent); }

    .summary-note {
      margin-top: 1.2rem;
      display: flex;
      align-items: flex-start;
      gap: .5rem;
      font-size: .73rem;
      color: var(--muted);
      line-height: 1.5;
    }
    .summary-note svg {
      width: 13px; height: 13px;
      stroke: var(--faint); fill: none;
      stroke-width: 1.8;
      flex-shrink: 0;
      margin-top: 1px;
    }

    /* animazioni */
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes slide-out {
      from { opacity: 1; transform: translateX(0); max-height: 100px; }
      to   { opacity: 0; transform: translateX(20px); max-height: 0; padding: 0; }
    }

    .product-row.removing { animation: slide-out .3s ease forwards; }

    /* responsive */
    @media (max-width: 600px) {
      .site-header { padding: 0 1rem; }
      .cart-inner  { padding: 0 1rem; }
      .coord-row   { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="site-header">
    <a class="logo" href="index.php">MotosButter</a>

    <span class="header-title">Il tuo carrello</span>

    <div class="header-actions">

      <a href="index.php" class="icon-btn" title="Torna allo shop" aria-label="Torna allo shop">
        <svg viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </a>

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

          <a href="spedizione.php" class="dropdown-item" role="menuitem">
            <span>Indirizzo di spedizione</span>
            <svg viewBox="0 0 24 24">
              <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </a>

          <a href="pagamento.php" class="dropdown-item" role="menuitem">
            <span>Modalità di pagamento</span>
            <svg viewBox="0 0 24 24">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
              <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
          </a>

          <hr class="dropdown-divider" />

          <?php if (!empty($_SESSION["id_utente"])): ?>
          <a href="logOut.php" class="dropdown-item" role="menuitem" style="color: var(--accent);">
            <span>Esci dall'account</span>
            <svg viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>


  <!-- CORPO PAGINA -->
  <main class="cart-page">
    <div class="cart-inner">

      <!-- titolo -->
      <div class="cart-heading">
        <h1>Riepilogo <em>Ordine</em></h1>
        <span class="cart-count" id="cart-badge">0 articoli</span>
      </div>

      <!-- banner errore ordine (se il POST fallisce) -->
      <?php if ($errore_ordine): ?>
      <div class="order-error">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Errore durante la creazione dell'ordine: <?= htmlspecialchars($errore_ordine) ?></span>
      </div>
      <?php endif; ?>


      <!-- COLONNA SINISTRA -->
      <div class="cart-left">

        <!-- 1. PRODOTTI -->
        <div class="cart-section">
          <div class="section-header">
            <svg viewBox="0 0 24 24">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <h2>Prodotti nel carrello</h2>
          </div>

          <div class="product-list" id="product-list">
            <?php
                try {
                    $result_carrelloprodotto = $db_connection->query($query_carrelloprodotto);
                    $totale_prodotti = 0;
                    if ($result_carrelloprodotto && mysqli_num_rows($result_carrelloprodotto) > 0) {
                        while ($row = mysqli_fetch_assoc($result_carrelloprodotto)) {
                            $nome    = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
                            $prezzo  = number_format($row['prezzo'], 2, ',', '.');
                            $qty     = (int)$row['quantita'];
                            $id_prod = (int)$row['id'];
                            $totale_prodotti += $row['prezzo'] * $qty;

                            // Immagine blob → data URI
                            if (!empty($row['img'])) {
                                $mime   = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $row['img']) ?: 'image/jpeg';
                                $b64    = base64_encode($row['img']);
                                $imgTag = '<img class="product-thumb" src="data:'.$mime.';base64,'.$b64.'" alt="'.$nome.'" />';
                            } else {
                                $imgTag = '<div class="product-thumb-placeholder">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>';
                            }

                            echo '
                            <div class="product-row" data-id="'.$id_prod.'" data-price="'.$row['prezzo'].'">
                              '.$imgTag.'
                              <div class="product-info">
                                <div class="product-name">'.$nome.'</div>
                                <div class="product-meta">€ '.$prezzo.' cad.</div>
                              </div>
                              <div class="qty-control">
                                <button class="qty-btn" type="button" onclick="changeQty(this,-1)">−</button>
                                <span class="qty-value">'.$qty.'</span>
                                <button class="qty-btn" type="button" onclick="changeQty(this,+1)">+</button>
                              </div>
                              <div class="product-price">€'.number_format($row['prezzo'] * $qty, 2, ',', '.').'</div>
                              <button class="remove-btn" type="button" onclick="removeRow(this)" title="Rimuovi">
                                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                              </button>
                            </div>';
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    echo '<p style="padding:1rem;color:var(--accent);">' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            ?>
          </div><!-- /product-list -->

          <!-- Carrello vuoto -->
          <?php if (!empty($_SESSION["id_utente"])): ?>
          <div class="empty-cart" id="empty-cart">
            <svg viewBox="0 0 24 24">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <strong>Il carrello è vuoto</strong>
            <p>Aggiungi prodotti al carrello per proseguire</p>
            <a href="index.php">Torna allo shop</a>
          </div>
          <?php else: ?>
          <div class="empty-cart visible" id="empty-cart">
            <svg viewBox="0 0 24 24">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <strong>Non sei autenticato</strong>
            <p>Accedi al tuo account per acquistare nel più burroso degli shop</p>
            <a href="logIn.php">Accedi</a>
          </div>
          <?php endif; ?>

        </div><!-- /cart-section prodotti -->


        <!-- 2. INDIRIZZO DI SPEDIZIONE -->
        <div class="cart-section">
          <div class="section-header">
            <svg viewBox="0 0 24 24">
              <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <h2>Indirizzo di spedizione</h2>
          </div>

          <div class="address-body">
            <div class="coord-row">
              <div class="field-group">
                <label for="lat">Latitudine</label>
                <input type="text" id="lat" name="lat"
                       placeholder="es. 44.137451"
                       value="<?= htmlspecialchars($_SESSION['lat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
              </div>
              <div class="field-group">
                <label for="lon">Longitudine</label>
                <input type="text" id="lon" name="lon"
                       placeholder="es. 12.243024"
                       value="<?= htmlspecialchars($_SESSION['lon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
              </div>
            </div>
          </div>
        </div>


        <!-- 3. METODO DI PAGAMENTO (dinamico da DB) -->
        <div class="cart-section">
          <div class="section-header">
            <svg viewBox="0 0 24 24">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
              <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <h2>Metodo di pagamento</h2>
          </div>

          <div class="payment-body">
            <?php
                try {
                    $result_pagamento = $db_connection->query($query_modalitapagamento);
                    $primo_pag = true;
                    if ($result_pagamento && mysqli_num_rows($result_pagamento) > 0) {
                        while ($row = mysqli_fetch_assoc($result_pagamento)):
                            $id_pag   = intval($row['id']);
                            $nome_pag = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
                            $checked  = $primo_pag ? 'checked' : '';
                            $selected = $primo_pag ? 'selected' : '';
                            $primo_pag = false;
            ?>
                <label class="radio-option <?= $selected ?>" id="pay-<?= $id_pag ?>">
                  <input type="radio" name="id_pagamento" value="<?= $id_pag ?>"
                         <?= $checked ?> onchange="selectPayment(this)" />
                  <span class="radio-dot"></span>
                  <span class="radio-label"><?= $nome_pag ?></span>
                </label>
            <?php
                        endwhile;
                    } else {
                        echo '<p style="padding:.8rem;color:var(--muted);font-size:.85rem;">Nessuna modalità di pagamento disponibile.</p>';
                    }
                } catch (mysqli_sql_exception $e) {
                    echo '<p style="padding:.8rem;color:var(--accent);font-size:.85rem;">' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            ?>
          </div>
        </div>


        <!-- 4. SCELTA CORRIERE (dinamica da DB) -->
        <div class="cart-section">
          <div class="section-header">
            <svg viewBox="0 0 24 24">
              <rect x="1" y="3" width="15" height="13" rx="1"/>
              <path d="M16 8h4l3 3v5h-7V8z"/>
              <circle cx="5.5" cy="18.5" r="2.5"/>
              <circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <h2>Scelta del corriere</h2>
          </div>

          <div class="courier-body">
            <?php
                try {
                    $result_corrieri = $db_connection->query($query_corrieri);
                    $primo_cor = true;
                    if ($result_corrieri && mysqli_num_rows($result_corrieri) > 0) {
                        while ($row = mysqli_fetch_assoc($result_corrieri)):
                            $id_cor   = intval($row['id']);
                            $nome_cor = htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8');
                            $mail_cor = htmlspecialchars($row['mail'], ENT_QUOTES, 'UTF-8');
                            $checked  = $primo_cor ? 'checked' : '';
                            $selected = $primo_cor ? 'selected' : '';
                            $primo_cor = false;
            ?>
                <label class="courier-option <?= $selected ?>" id="courier-<?= $id_cor ?>">
                  <input type="radio" name="id_corriere" value="<?= $id_cor ?>"
                         <?= $checked ?> onchange="selectCourier(this)" />
                  <span class="radio-dot"></span>
                  <span class="courier-info">
                    <span class="courier-name"><?= $nome_cor ?></span>
                    <span class="courier-time"><?= $mail_cor ?></span>
                  </span>
                </label>
            <?php
                        endwhile;
                    } else {
                        echo '<p style="padding:.8rem;color:var(--muted);font-size:.85rem;">Nessun corriere disponibile.</p>';
                    }
                } catch (mysqli_sql_exception $e) {
                    echo '<p style="padding:.8rem;color:var(--accent);font-size:.85rem;">' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            ?>
          </div>
        </div>

      </div>


      <!-- COLONNA DESTRA - RIEPILOGO -->
      <form class="summary-form" method="POST" action="cart.php"
            onsubmit="return prepareSubmit(event)">
        <input type="hidden" name="conferma_ordine" value="1" />
        <input type="hidden" name="lat"          id="lat-hidden" />
        <input type="hidden" name="lon"          id="lon-hidden" />
        <!-- I radio di pagamento e corriere dentro cart-left NON sono nel form: li clono via JS prima del submit (prepareSubmit) -->

        <aside class="cart-summary">
          <div class="summary-header">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:var(--accent);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round">
              <path d="M9 11l3 3L22 4"/>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            <h2>Riepilogo spese</h2>
          </div>

          <div class="summary-body">
            <div class="summary-row">
              <span>Subtotale</span>
              <span id="sum-subtotal">€ 0,00</span>
            </div>
            <div class="summary-row shipping">
              <span>Spedizione</span>
              <span id="sum-shipping">Gratuita</span>
            </div>
            <div class="summary-row" style="border-bottom:none;">
              <span>IVA inclusa (22%)</span>
              <span id="sum-vat">€ 0,00</span>
            </div>

            <div class="summary-total">
              <span class="label">Totale</span>
              <span class="amount" id="sum-total">€ <em>0</em>,00</span>
            </div>

            <button class="btn-checkout" id="btn-checkout" type="submit" disabled>
              Conferma ordine
            </button>
            <a href="index.php" class="btn-continue"><- Continua a fare acquisti</a>

            <p class="summary-note">
              <svg viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              Pagamento sicuro e crittografato. I tuoi dati non vengono condivisi con terze parti.
            </p>
          </div>
        </aside>
      </form>

    </div><!-- /cart-inner -->
  </main>


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
        <line x1="12" y1="1" x2="12" y2="3"/>
        <line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1"  y1="12" x2="3"  y2="12"/>
        <line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64"  y2="18.36"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>`;

    function applyTheme(dark) {
        html.setAttribute('data-theme', dark ? 'dark' : 'light');
        themeToggle.checked  = dark;
        themeIcon.innerHTML  = dark ? MOON_SVG : SUN_SVG;
        themeLbl.textContent = dark ? 'Modalità scura' : 'Modalità chiara';
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    }
    applyTheme(localStorage.getItem('theme') === 'dark');
    themeToggle.addEventListener('change', () => applyTheme(themeToggle.checked));


    /* 2. DROPDOWN IMPOSTAZIONI */
    const settingsBtn = document.getElementById('settings-btn');
    const dropdown    = document.getElementById('settings-dropdown');

    settingsBtn.addEventListener('click', e => {
        e.stopPropagation();
        const open = dropdown.classList.toggle('open');
        settingsBtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== settingsBtn) {
        dropdown.classList.remove('open');
        settingsBtn.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && dropdown.classList.contains('open')) {
        dropdown.classList.remove('open');
        settingsBtn.setAttribute('aria-expanded', 'false');
        settingsBtn.focus();
        }
    });


    /* 3. LOGICA CARRELLO */

    function selectPayment(radio) {
        document.querySelectorAll('.radio-option').forEach(el => el.classList.remove('selected'));
        radio.closest('.radio-option').classList.add('selected');
        updateSummary();
    }

    function selectCourier(radio) {
        document.querySelectorAll('.courier-option').forEach(el => el.classList.remove('selected'));
        radio.closest('.courier-option').classList.add('selected');
        updateSummary();
    }

    // Applica .selected al caricamento (ai radio già checked dal PHP)
    document.querySelectorAll('.radio-option input[type="radio"]:checked').forEach(r => {
        r.closest('.radio-option').classList.add('selected');
    });
    document.querySelectorAll('.courier-option input[type="radio"]:checked').forEach(r => {
        r.closest('.courier-option').classList.add('selected');
    });

    function changeQty(btn, delta) {
        const row    = btn.closest('.product-row');
        const qtyEl  = row.querySelector('.qty-value');
        let qty      = parseInt(qtyEl.textContent) + delta;
        if (qty < 1) qty = 1;
        qtyEl.textContent = qty;

        const unitPrice = parseFloat(row.dataset.price);
        row.querySelector('.product-price').textContent =
        '€' + (unitPrice * qty).toFixed(2).replace('.', ',');

        updateSummary();
    }

    function removeRow(btn) {
        const row = btn.closest('.product-row');
        row.classList.add('removing');
        row.addEventListener('animationend', () => {
        row.remove();
        checkEmpty();
        updateSummary();
        }, { once: true });
    }

    function checkEmpty() {
        const rows    = document.querySelectorAll('#product-list .product-row');
        const emptyEl = document.getElementById('empty-cart');
        if (emptyEl) emptyEl.classList.toggle('visible', rows.length === 0);
        document.getElementById('btn-checkout').disabled = rows.length === 0;
    }

    function updateSummary() {
        // Subtotale
        let subtotal = 0;
        document.querySelectorAll('#product-list .product-row').forEach(row => {
        const qty   = parseInt(row.querySelector('.qty-value').textContent);
        const price = parseFloat(row.dataset.price);
        subtotal   += qty * price;
        });

        const vat   = subtotal * 0.22;
        const total = subtotal;

        document.getElementById('sum-subtotal').textContent =
        '€ ' + subtotal.toFixed(2).replace('.', ',');
        document.getElementById('sum-vat').textContent =
        '€ ' + vat.toFixed(2).replace('.', ',');
        document.getElementById('sum-shipping').textContent = 'Gratuita';

        const [intPart, decPart] = total.toFixed(2).replace('.', ',').split(',');
        document.getElementById('sum-total').innerHTML =
        '€ <em>' + intPart + '</em>,' + decPart;

        // Badge articoli
        let totalQty = 0;
        document.querySelectorAll('#product-list .product-row .qty-value').forEach(el => {
        totalQty += parseInt(el.textContent);
        });
        document.getElementById('cart-badge').textContent =
        totalQty + (totalQty === 1 ? ' articolo' : ' articoli');

        checkEmpty();
    }


    /* 4. SUBMIT ORDINE 
        Copia i valori di lat/lon e dei radio (che stanno fuori dal <form>)
        negli hidden input prima di lasciare procedere il submit. */
    function prepareSubmit(e) {
        const lat = document.getElementById('lat').value.trim();
        const lon = document.getElementById('lon').value.trim();

        // Validazione coordinate
        if (!lat || !lon) {
        e.preventDefault();
        const latEl = document.getElementById('lat');
        const lonEl = document.getElementById('lon');
        latEl.classList.add('input-error');
        lonEl.classList.add('input-error');
        latEl.focus();
        latEl.addEventListener('input', () => latEl.classList.remove('input-error'), { once: true });
        lonEl.addEventListener('input', () => lonEl.classList.remove('input-error'), { once: true });
        return false;
        }

        // Copia coordinate negli hidden input del form
        document.getElementById('lat-hidden').value = lat;
        document.getElementById('lon-hidden').value = lon;

        // Copia il radio pagamento selezionato nel form
        const pagChecked = document.querySelector('.radio-option input[type="radio"]:checked');
        if (pagChecked) {
        const hidPag = document.createElement('input');
        hidPag.type  = 'hidden';
        hidPag.name  = 'id_pagamento';
        hidPag.value = pagChecked.value;
        e.target.appendChild(hidPag);
        }

        // Copia il radio corriere selezionato nel form
        const corChecked = document.querySelector('.courier-option input[type="radio"]:checked');
        if (corChecked) {
        const hidCor = document.createElement('input');
        hidCor.type  = 'hidden';
        hidCor.name  = 'id_corriere';
        hidCor.value = corChecked.value;
        e.target.appendChild(hidCor);
        }

        return true;
    }

    // Calcolo iniziale al caricamento
    updateSummary();

  </script>

</body>
</html>