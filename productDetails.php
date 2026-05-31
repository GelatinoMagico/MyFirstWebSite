<?php
    include("dataBase.php");

    $id_prodotto = intval($_GET['id_prodotto']);
    $result = $db_connection->query("SELECT id, nome, prezzo, descrizione, modello, idFornitore FROM prodotti WHERE id = $id_prodotto");

    if (!$result) {
        die("Query failed: " . $db_connection->error);
    }

    $prodotto = $result->fetch_assoc();

	$idFornitore = $prodotto['idFornitore'];
	$result = $db_connection->query("SELECT nome FROM fornitori WHERE id = $idFornitore");

    if (!$result) {
        die("Query failed: " . $db_connection->error);
    }

    $fornitore = $result->fetch_assoc()['nome'];

    mysqli_close($db_connection);
?>

<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($prodotto['nome']) ?> - Dettagli Prodotto</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet" />

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

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

    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
      transition: background .3s, color .3s;
    }

    /* HEADER */
    .site-header {
      position: fixed; top: 0; left: 0; right: 0;
      height: var(--header-h); background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; padding: 0 2rem; z-index: 300;
    }
    .logo {
      font-family: var(--font-display); font-size: 1.4rem; color: var(--ink);
      text-decoration: none;
    }
    .header-actions { margin-left: auto; display: flex; gap: .4rem; }
    
    .icon-btn {
      width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
      border-radius: var(--radius); border: 1px solid transparent; background: transparent;
      color: var(--muted); cursor: pointer;
    }
    .icon-btn svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 1.6; }

    /* LAYOUT VERTICALE */
    .product-layout {
      position: relative;
      z-index: 1;
      max-width: 1600px; /* Massimo spazio per il canvas */
      margin: calc(var(--header-h) + 2rem) auto 4rem;
      padding: 0 2rem;
      display: flex;
      flex-direction: column; /* Stack verticale */
      gap: 3rem;
      animation: fade-up .5s ease both;
    }

    /* CANVAS A TUTTA LARGHEZZA */
    .canvas-container {
      width: 100%;
      height: 75vh; /* Molto alto, quasi tutto il viewport */
      min-height: 500px;
      background: linear-gradient(145deg, var(--surface), var(--surface-2));
      border: 1px solid var(--border);
      border-radius: 12px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    #glCanvas {
      width: 100%;
      height: 100%;
      display: block;
      outline: none;
      cursor: grab;
    }
    
    #glCanvas:active { cursor: grabbing; }

    .canvas-badge {
      position: absolute;
      top: 1.5rem; right: 1.5rem;
      background: var(--bg);
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 0.75rem;
      padding: 0.4rem 1rem;
      border-radius: 99px;
      pointer-events: none;
    }

    /* INFO SOTTO IL CANVAS */
    .product-info {
      width: 100%;
      max-width: 900px; /* Centrato e leggibile */
      margin: 0 auto;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .product-manufacturer {
      font-size: .9rem;
      font-weight: 500;
      letter-spacing: .15em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 0.5rem;
    }

    .product-title {
      font-family: var(--font-display);
      font-size: clamp(2.5rem, 5vw, 4rem);
      line-height: 1.1;
      margin-bottom: 1rem;
    }

    .product-price {
      font-size: 2rem;
      font-weight: 500;
      color: var(--accent);
      margin-bottom: 2rem;
    }

    .product-desc {
      font-size: 1.1rem;
      line-height: 1.8;
      color: var(--muted);
      margin-bottom: 3rem;
    }

    .btn-buy {
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      padding: 1.2rem 4rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-size: 1.1rem;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: all .2s;
      box-shadow: 0 10px 20px rgba(192,57,43,.2);
    }

    .btn-buy:hover { 
      background: var(--accent-dk); 
      transform: translateY(-3px);
      box-shadow: 0 15px 30px rgba(192,57,43,.3);
    }

    @keyframes fade-up {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <header class="site-header">
    <a class="logo" href="index.php">MotosButter</a>
    <div class="header-actions">
        </div>
  </header>

  <main class="product-layout">
    
    <div class="canvas-container">
      <div class="canvas-badge">Modello 3D Interattivo</div>
      <canvas id="glCanvas"></canvas>
    </div>

    <div class="product-info">
      <div class="product-manufacturer"><?= htmlspecialchars($fornitore) ?></div>
      <h1 class="product-title"><?= htmlspecialchars($prodotto['nome']) ?></h1>
      <div class="product-price">€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></div>
      
      <p class="product-desc">
        <?= nl2br(htmlspecialchars($prodotto['descrizione'])) ?>
      </p>

      <form action="addToCart.php" method="POST">
        <input type="hidden" name="id_prodotto" value="<?= $prodotto['id'] ?>">
        <button type="submit" class="btn-buy">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          Aggiungi al Carrello
        </button>
      </form>
    </div>

  </main>

  <script>
    /* INIZIALIZZAZIONE CANVAS 3D (WebGL2) */
	const canvas = document.getElementById("glCanvas");
	const gl = canvas.getContext("webgl2");

	if (!gl) {
	alert("WebGL non supportato");
	}


	// Main program
	// Definizione shaders
	let vertexShaderSource = `#version 300 es

			precision highp float;
			precision highp int;

			uniform mat4 u_projectionViewMatrix;
			uniform mat4 u_modelMatrix;
			uniform mat4 u_lightSpaceMatrix;
			uniform mat3 u_normalMatrix;

			layout(location = 0) in vec3 a_position;
			layout(location = 1) in vec3 a_normal;

			out vec3 v_normal;
			out vec4 v_worldPos;
			out vec4 v_lightSpacePos;

			void main() {
					// Calcolo della normale reale
					v_normal = u_normalMatrix * a_normal;

					// Calcolo posizione nel mondo
					v_worldPos = u_modelMatrix * vec4(a_position, 1.0);
					
					// Output posizione
					gl_Position = u_projectionViewMatrix * v_worldPos;

					// Calcolo posizione del vertice rispetto alla LUCE (per calcolare l'ombra)
					v_lightSpacePos = u_lightSpaceMatrix * v_worldPos;
			}
	`;

	let fragmentShaderSource = `#version 300 es

			precision highp float;

			uniform sampler2D u_shadowMap;
			uniform vec3 u_color;
			uniform vec3 u_lightPos;

			in vec4 v_worldPos;
			in vec3 v_normal;
			in vec4 v_lightSpacePos;

			out vec4 out_color;

			void main() {
					vec3 normal = normalize(v_normal);

					// Calcolo distanza dalla luce
					vec3 lightDir = normalize(u_lightPos - v_worldPos.xyz);


					// ILLUMINAZIONE BASE
					// Luce diffusa
					vec3 diffuse = max(dot(normal, lightDir), 0.0) * vec3(1.0, 1.0, 1.0); // Colore della luce
					
					// Luce ambientale (per evitare che le ombre siano neri assoluti)
					vec3 ambient = vec3(0.2, 0.2, 0.2);


					// CALCOLO OMBRA
					// Conversione coordinate dalla prospettiva (Perspective Divide)
					vec3 projCoords = v_lightSpacePos.xyz / v_lightSpacePos.w;
					
					// Trasformazione dell'intervallo da [-1, 1] (clip space) a [0, 1] (texture space)
					projCoords = projCoords * 0.5 + 0.5;

					float currentDepth = projCoords.z;

					// BIAS DINAMICO: Regola la tolleranza in base all'inclinazione della superficie
					float bias = max(0.005 * (1.0 - dot(normal, lightDir)), 0.001);

					// PCF (Percentage Closer Filtering): legge un blocco di pixel 5x5 attorno al punto facendo la media
					float shadow = 0.0;
					vec2 texelSize = 1.0 / vec2(4096.0);

					for(int x = -2; x <= 2; ++x) {
							for(int y = -2; y <= 2; ++y) {
									float pcfDepth = texture(u_shadowMap, projCoords.xy + vec2(x, y) * texelSize).r;
									shadow += currentDepth - bias > pcfDepth ? 1.0 : 0.0;
							}
					}
					shadow /= 25.0;


					// CALCOLO FINALE
					vec3 lighting = (ambient + (1.0 - shadow) * diffuse) * u_color;

					out_color = vec4(lighting, 1.0);
			}
	`;

	// Compilazione shader
	const vertexShader = gl.createShader(gl.VERTEX_SHADER);
	gl.shaderSource(vertexShader, vertexShaderSource);
	gl.compileShader(vertexShader);

	const fragmentShader = gl.createShader(gl.FRAGMENT_SHADER);
	gl.shaderSource(fragmentShader, fragmentShaderSource);
	gl.compileShader(fragmentShader);

	// Creazione del programma
	const program = gl.createProgram();
	gl.attachShader(program, vertexShader);
	gl.attachShader(program, fragmentShader);

	gl.linkProgram(program);
	if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
			console.error(gl.getProgramInfoLog(program));
	}


	// Shadow program
	// Definizione shaders
	vertexShaderSource = `#version 300 es

			layout(location = 0) in vec3 a_position;

			uniform mat4 u_modelMatrix;
			uniform mat4 u_lightSpaceMatrix; 

			void main() {
					// Posizione finale del vertice rispetto alla luce
					gl_Position = u_lightSpaceMatrix * u_modelMatrix * vec4(a_position, 1.0);
			}
	`;

	fragmentShaderSource = `#version 300 es

			precision lowp float;

			void main() {}
	`;

	// Compilazione shader
	const ShadowVertexShader = gl.createShader(gl.VERTEX_SHADER);
	gl.shaderSource(ShadowVertexShader, vertexShaderSource);
	gl.compileShader(ShadowVertexShader);

	const ShadowFragmentShader = gl.createShader(gl.FRAGMENT_SHADER);
	gl.shaderSource(ShadowFragmentShader, fragmentShaderSource);
	gl.compileShader(ShadowFragmentShader);

	// Creazione del programma
	const shadowProgram = gl.createProgram();
	gl.attachShader(shadowProgram, ShadowVertexShader);
	gl.attachShader(shadowProgram, ShadowFragmentShader);

	gl.linkProgram(shadowProgram);
	if (!gl.getProgramParameter(shadowProgram, gl.LINK_STATUS)) {
			console.error(gl.getProgramInfoLog(shadowProgram));
	}



	// Crea il framebuffer per le ombre
	const shadowFramebuffer = gl.createFramebuffer();
	gl.bindFramebuffer(gl.FRAMEBUFFER, shadowFramebuffer);

	// Crea la texture di profondità
	const shadowDepthTexture = gl.createTexture();
	gl.bindTexture(gl.TEXTURE_2D, shadowDepthTexture);

	// Configura la texture per memorizzare dati di profondità
	gl.texImage2D(gl.TEXTURE_2D, 0, gl.DEPTH_COMPONENT16, 4096, 4096, 0, gl.DEPTH_COMPONENT, gl.UNSIGNED_SHORT, null
	);

	// Filtri per la texture
	gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
	gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
	gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
	gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);

	// Attacca la texture al framebuffer come componente di profondità
	gl.framebufferTexture2D(
			gl.FRAMEBUFFER, gl.DEPTH_ATTACHMENT, 
			gl.TEXTURE_2D, shadowDepthTexture, 0
	);

	// Posizione nel main program 
	const shadowMapLocation = gl.getUniformLocation(program, "u_shadowMap");

	// Configurazione dei draw/read buffer
	gl.drawBuffers([gl.NONE])
	gl.readBuffer(gl.NONE)



	// Creazione matrici
	// Inizializzazione camera
	const camera = {
			x: 0,
			y: 10,
			z: 35,

			pitch: 0,
			yaw: 0,

			f: 1.0 / Math.tan((80 * Math.PI / 180) / 2),
			n: 0.1,
			
			projectionViewMatrixLocation: gl.getUniformLocation(program, "u_projectionViewMatrix"),

			projectionViewMatrix: new Float32Array(16),

			getPos: function() {
					return `x: ${this.x}, y: ${this.y}, z: ${this.z}, pitch: ${this.pitch}, yaw: ${this.yaw}`;
			},

			// Matrice di proiezione-vista
			setProjectionViewMatrix(aspectRatio) {
					// Fattori di scala della Prospettiva
					const fA = this.f / aspectRatio;
					const n2 = -2 * this.n;

					// Calcolo seni e coseni
					const cosP = Math.cos(this.pitch * Math.PI / 180);
					const sinP = Math.sin(this.pitch * Math.PI / 180);
					const cosY = Math.cos(this.yaw * Math.PI / 180);
					const sinY = Math.sin(this.yaw * Math.PI / 180);

					// Vettori direzionali (Assi locali)
					// Asse X (Destra)
					const ux = cosY;
					// uy = 0
					const uz = sinY;

					// Asse Y (Su)
					const vx = -sinP * sinY;
					const vy = cosP;
					const vz = sinP * cosY;

					// Asse Z (Indietro)
					const nx = -cosP * sinY;
					const ny = -sinP;
					const nz = cosP * cosY;

					// Componenti di traslazione della vista
					const tx = -(this.x * ux + this.z * uz); 
					const ty = -(this.x * vx + this.y * vy + this.z * vz);
					const tz = -(this.x * nx + this.y * ny + this.z * nz);

					// Assegnazione diretta (P x V)
					// Colonna 1
					this.projectionViewMatrix[0]  = fA * ux;
					this.projectionViewMatrix[1]  = this.f * vx;
					this.projectionViewMatrix[2]  = -nx;
					this.projectionViewMatrix[3]  = -nx;

					// Colonna 2
					this.projectionViewMatrix[4]  = 0; // fA * uy
					this.projectionViewMatrix[5]  = this.f * vy;
					this.projectionViewMatrix[6]  = -ny;
					this.projectionViewMatrix[7]  = -ny;

					// Colonna 3
					this.projectionViewMatrix[8]  = fA * uz;
					this.projectionViewMatrix[9]  = this.f * vz;
					this.projectionViewMatrix[10] = -nz;
					this.projectionViewMatrix[11] = -nz;

					// Colonna 4
					this.projectionViewMatrix[12] = fA * tx;
					this.projectionViewMatrix[13] = this.f * ty;
					this.projectionViewMatrix[14] = -tz + n2; 
					this.projectionViewMatrix[15] = -tz;
			}
	}


	// Inizializza la fonte di luce
	const light = {
			x: 0,
			y: 25,
			z: 12,

			pitch: -90,
			yaw: 0,

			f: 1.0 / Math.tan((80 * Math.PI / 180) / 2),
			n: 0.1,

			positionLocation: gl.getUniformLocation(program, "u_lightPos"),

			spaceMatrixLocation: gl.getUniformLocation(program, "u_lightSpaceMatrix"),
			shadowSpaceMatrixLocation: gl.getUniformLocation(shadowProgram, "u_lightSpaceMatrix"),

			spaceMatrix: new Float32Array(16),

			// Matrice di proiezione-vista ombre
			setSpaceMatrix: function(aspectRatio) {
					// Calcolo seni e coseni
					const cosP = Math.cos(this.pitch * Math.PI / 180);
					const sinP = Math.sin(this.pitch * Math.PI / 180);
					const cosY = Math.cos(this.yaw * Math.PI / 180);
					const sinY = Math.sin(this.yaw * Math.PI / 180);
					
					const fA = this.f / aspectRatio;

					// Vettori direzionali (Assi locali)
					// Asse X (Destra)
					const ux = cosY;
					// uy = 0
					const uz = sinY;

					// Asse Y (Su locale)
					const vx = -sinP * sinY;
					const vy = cosP;
					const vz = sinP * cosY;

					// Asse Z (Indietro)
					const nx = -cosP * sinY;
					const ny = -sinP;
					const nz = cosP * cosY;

					// Componenti di traslazione della vista
					const tx = -(this.x * ux + this.z * uz); 
					const ty = -(this.x * vx + this.y * vy + this.z * vz);
					const tz = -(this.x * nx + this.y * ny + this.z * nz);

					// Assegnazione diretta (P * V)
					// Colonna 1
					this.spaceMatrix[0] = fA * ux;
					this.spaceMatrix[1] = this.f * vx;
					this.spaceMatrix[2] = -nx;
					this.spaceMatrix[3] = -nx;

					// Colonna 2
					this.spaceMatrix[4] = 0; // fA * uy
					this.spaceMatrix[5] = this.f * vy;
					this.spaceMatrix[6] = -ny;
					this.spaceMatrix[7] = -ny;

					// Colonna 3
					this.spaceMatrix[8] = fA * uz;
					this.spaceMatrix[9] = this.f * vz;
					this.spaceMatrix[10] = -nz;
					this.spaceMatrix[11] = -nz;

					// Colonna 4 (Traslazione combinata alla prospettiva)
					this.spaceMatrix[12] = fA * tx;
					this.spaceMatrix[13] = this.f * ty;
					this.spaceMatrix[14] = -tz - (2 * this.n);
					this.spaceMatrix[15] = -tz;
			}
	}


	// Matrice di modello
	const modelMatrixLocation = gl.getUniformLocation(program, "u_modelMatrix");
	const shadowModelMatrixLocation = gl.getUniformLocation(shadowProgram, "u_modelMatrix");


	// Matrice delle normali
	const normalMatrixLocation = gl.getUniformLocation(program, "u_normalMatrix");



	class Model{
			constructor(vertices, normals, color, scale, rotation, translation, baseRotation=0, baseTranslation=[0, 0, 0]) {
					this.numFaces = vertices.length / 3
					
					// VAO
					// Creazione buffer
					this.VAO = gl.createVertexArray();
					gl.bindVertexArray(this.VAO);
					
					// Collegamento buffer vertici al VAO
					// Creazione buffer
					this.verttexBuffer = gl.createBuffer();
					gl.bindBuffer(gl.ARRAY_BUFFER, this.verttexBuffer);
					gl.bufferData(gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW);

					// Collegamento buffer-shader
					this.vertexBufferLocation = gl.getAttribLocation(program, "a_position");
					gl.enableVertexAttribArray(this.vertexBufferLocation);
					gl.vertexAttribPointer(this.vertexBufferLocation, 3, gl.FLOAT, false, 0, 0);

					// Collegamento buffer normali al VAO
					// Creazione buffer
					this.normalsBuffer = gl.createBuffer();
					gl.bindBuffer(gl.ARRAY_BUFFER, this.normalsBuffer);
					gl.bufferData(gl.ARRAY_BUFFER, normals, gl.STATIC_DRAW);

					// Collegamento buffer-shader
					this.normalsBufferLocation = gl.getAttribLocation(program, "a_normal");
					gl.enableVertexAttribArray(this.normalsBufferLocation);
					gl.vertexAttribPointer(this.normalsBufferLocation, 3, gl.FLOAT, false, 0, 0);

					// Unbinding VAO
					gl.bindVertexArray(null);


					// Colore
					this.color = color;
					this.colorLocation = gl.getUniformLocation(program, "u_color");


			// Costruzione matrice di modello
					this.scale = scale;

					this.baseRotation = baseRotation;
					this.baseTranslation = baseTranslation;
					
					this.rotation = rotation;
					this.translation = translation;

					this.modelMatrix = new Float32Array(16);
					this.normalMatrix = new Float32Array(9);
					this.setMatrix();
			}
					

			setMatrix(scale, rotation, translation) {
					// Model matrix
					const angle = (this.rotation + this.baseRotation) * Math.PI / 180;
					const cosY = Math.cos(angle);
					const sinY = Math.sin(angle);
					const s = this.scale;

					// Colonna 1 (Asse X locale scalato e ruotato)
					this.modelMatrix[0] = s * cosY;
					this.modelMatrix[1] = 0;
					this.modelMatrix[2] = -s * sinY;
					this.modelMatrix[3] = 0;

					// Colonna 2 (Asse Y locale scalato)
					this.modelMatrix[4] = 0;
					this.modelMatrix[5] = s;
					this.modelMatrix[6] = 0;
					this.modelMatrix[7] = 0;

					// Colonna 3 (Asse Z locale scalato e ruotato)
					this.modelMatrix[8] = s * sinY;
					this.modelMatrix[9] = 0;
					this.modelMatrix[10] = s * cosY;
					this.modelMatrix[11] = 0;

					// Colonna 4 (Traslazione posizionale)
					this.modelMatrix[12] = this.translation[0] + this.baseTranslation[0];
					this.modelMatrix[13] = this.translation[1] + this.baseTranslation[1];
					this.modelMatrix[14] = this.translation[2] + this.baseTranslation[2];
					this.modelMatrix[15] = 1;

					// Normal Matrix
					// Colonna 1 (Asse X ruotato)
					this.normalMatrix[0] = cosY;
					this.normalMatrix[1] = 0;
					this.normalMatrix[2] = -sinY;

					// Colonna 2 (Asse Y)
					this.normalMatrix[3] = 0;
					this.normalMatrix[4] = 1;
					this.normalMatrix[5] = 0;

					// Colonna 3 (Asse Z ruotato)
					this.normalMatrix[6] = sinY;
					this.normalMatrix[7] = 0;
					this.normalMatrix[8] = cosY;
			}


			render() {
					// Setta il VAO del modello
					gl.bindVertexArray(this.VAO);

					// Setta il colore del modello
					gl.uniform3fv(this.colorLocation, this.color);

					// Setta la matrice di modello e delle normali del modello
					gl.uniformMatrix4fv(modelMatrixLocation, false, this.modelMatrix);
					gl.uniformMatrix3fv(normalMatrixLocation, false, this.normalMatrix);

					// Disegna il modello
					gl.drawArrays(gl.TRIANGLES, 0, this.numFaces);
			}


			renderShadow() {
					// Setta il VAO del modello
					gl.bindVertexArray(this.VAO);

					// Setta la matrice di modello del modello
					gl.uniformMatrix4fv(shadowModelMatrixLocation, false, this.modelMatrix);

					// Disegna il modello
					gl.drawArrays(gl.TRIANGLES, 0, this.numFaces);
			}
	}


	async function modelFromJson(src, rotation, translation) {
			const response = await fetch(src); 
			
			// Controlla se la risposta è andata a buon fine (status 200-299)
			if (!response.ok) {
			throw new Error(`Errore di rete: ${response.status}`);
			}
			
			// Estrazione JSON dalla risposta
			const data = await response.json();
			
			// Passaggio del modello
			return new Model(
					await new Float32Array(data.vertices),
					await new Float32Array(data.normals),
					await new Float32Array(data.color), 
					await data.scale, 
					rotation, 
					translation,
					baseRotation = await data.rotation,
					baseTranslation = await new Float32Array(data.translation)
			);
	}


	class Product {
			constructor(id, model) {
					this.id = id;
					this.model = model;
			}
	}


	// Attivazione event litener
	window.addEventListener('resize', () => {
			console.log("Canvas ridimensionato a:", canvas.width, "x", canvas.height, "y");
	});

	window.addEventListener("keydown", (e) => {
			cameraAngle = camera.yaw * Math.PI / 180;

			switch(e.key) {
					case "w":
							camera.z += -Math.cos(cameraAngle);
							camera.x += Math.sin(cameraAngle);
							break;
					case "s":
							camera.z += Math.cos(cameraAngle);
							camera.x += -Math.sin(cameraAngle);
							break;					
					case "d":
							camera.z += Math.sin(cameraAngle);
							camera.x += Math.cos(cameraAngle);
							break;
					case "a":
							camera.z += -Math.sin(cameraAngle);
							camera.x += -Math.cos(cameraAngle);
							break;
					case "e":
							camera.y = camera.y + 1;
							break;
					case "q":
							camera.y = camera.y - 1;
							break;
					default:
							return;
			}

			console.log(camera.getPos());
	})


	let isDraggingLeft = false;
	let isDraggingRight = false;
	let sensitivity = 0.1;

	canvas.addEventListener("mousedown", (e) => {
		if (e.button === 0) isDraggingLeft = true;
		if (e.button === 2) isDraggingRight = true;
	});

	window.addEventListener("mouseup", () => {
		isDraggingLeft = false;
		isDraggingRight = false;
	});

	canvas.addEventListener("mousemove", (e) => {
    if (isDraggingRight) {
        camera.yaw -= e.movementX * sensitivity;
        camera.pitch += e.movementY * sensitivity;

        // Limita il pitch per evitare che la telecamera si capovolga (flip)
        camera.pitch = Math.max(-89, Math.min(89, camera.pitch));
    }

	if (isDraggingLeft) {
        product.model.rotation += e.movementX * sensitivity;
		product.model.setMatrix();
    }
	});


	// Attivazione controlli
	// Attivazione z-buffer
	gl.enable(gl.DEPTH_TEST);
	gl.depthFunc(gl.LEQUAL);

	// Attiva il controllo che scarta la parte posteriore delle facce
	gl.enable(gl.CULL_FACE);



	// Disegno a schermo
	let aspectRatio = 0.0;

	function render() {
			// Aggiornamento dimensioni interne del canvas
			canvas.width  = canvas.clientWidth;
			canvas.height = canvas.clientHeight;


			// Aggiornamento matrici
			aspectRatio = canvas.width/canvas.height;
			camera.setProjectionViewMatrix(aspectRatio);
			light.setSpaceMatrix(aspectRatio);


			// Ombre
			// Attiva il framebuffer dell'ombra
			gl.bindFramebuffer(gl.FRAMEBUFFER, shadowFramebuffer);
			gl.viewport(0, 0, 4096, 4096); // Dimensione della texture
			gl.clear(gl.DEPTH_BUFFER_BIT);

			// Passa al programma delle ombre
			gl.useProgram(shadowProgram);

			// Aggiorna uniform
			gl.uniformMatrix4fv(light.shadowSpaceMatrixLocation, false, light.spaceMatrix);

			// Disegno ombre
			product.model.renderShadow()

			// Disattiva il frame buffer delle ombre
			gl.bindFramebuffer(gl.FRAMEBUFFER, null); // Schermo


			// Disegno effettivo
			gl.viewport(0, 0, canvas.width, canvas.height);
			gl.clearColor(0.0, 0.0, 0.0, 1.0);
			gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
			
			// Passa al programma principale
			gl.useProgram(program);

			// Aggiorna uniform
			gl.uniformMatrix4fv(light.spaceMatrixLocation, false, light.spaceMatrix);
			gl.uniformMatrix4fv(camera.projectionViewMatrixLocation, false, camera.projectionViewMatrix);
			gl.uniform3fv(light.positionLocation, [light.x, light.y, light.z]);

			// Passa la texture dell'ombra allo shader
			gl.activeTexture(gl.TEXTURE0);
			gl.bindTexture(gl.TEXTURE_2D, shadowDepthTexture);
			gl.uniform1i(shadowMapLocation, 0);
			
			// Disegno
			product.model.render()
			
			// Chiede al browser di chiamare di nuovo questa funzione al prossimo frame
			requestAnimationFrame(render);
	}



	// Creazione modello
	let product;

	async function main() {
		product = new Product(<?php echo $prodotto['id']; ?>, await modelFromJson(<?php echo json_encode($prodotto['modello'], JSON_UNESCAPED_SLASHES); ?>, 0, [0, 0, 0]));
			
		render();
	}


	main();
  </script>

</body>
</html>