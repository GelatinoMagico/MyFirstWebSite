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
        html, body {
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            background-color: black;
        }

        canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <canvas id="glCanvas"></canvas>
        <script>
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
                z: 50,

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

                f: 1.0 / Math.tan((120 * Math.PI / 180) / 2),
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

            let i = 0;

            window.addEventListener("keydown", (e) => {
                switch(e.key) {
                    case "ArrowRight":
                        camera.x = camera.x + 50;
                        light.x = light.x + 50;
                        i++;
                        break;
                    case "ArrowLeft":
                        camera.x = camera.x - 50;
                        light.x = light.x - 50;
                        i--;
                        break;
                    case "Enter":
                        window.location.href = `productDetails.php?id_prodotto=${products[i].id}`;
                        break;
                    default:
                        return;
                }

                console.log(camera.getPos());
            })

            window.addEventListener('wheel', (event) => {
                if (event.deltaY > 0) {
                    camera.x = camera.x + 50;
                    light.x = light.x + 50;
                    i++;;
                } else if (event.deltaY < 0) {
                    camera.x = camera.x - 50;
                    light.x = light.x - 50;
                    i--;
                }
            });


            let isDragging = false;
            let sensitivity = 0.1;

            canvas.addEventListener("mousedown", (e) => {
                if (e.button === 0) isDragging = true;
            });

            window.addEventListener("mouseup", () => {
                isDragging = false;
            });

            canvas.addEventListener("mousemove", (e) => {
                if (isDragging) {
                    products[i].model.rotation += e.movementX * sensitivity;
                    products[i].model.setMatrix();
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
                for (const product of products) {
                    product.model.renderShadow();
                }

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
                for (const product of products) {
                    product.model.render();
                }
                
                // Chiede al browser di chiamare di nuovo questa funzione al prossimo frame
                requestAnimationFrame(render);
            }

            
            let products = [];

            async function main() {
                // Creazione modelli
                <?php
                    $id_categoria = intval($_GET['id_categoria']);
                    
                    if ($id_categoria > 0 && $id_categoria < 7) {
                        $result = $db_connection->query("SELECT id,modello FROM prodotti WHERE idCategoria = $id_categoria");
                    } else {
                        $result = $db_connection->query("SELECT id,modello FROM prodotti");
                    }

                    if (!$result) {
                        die("Query failed: " . $db_connection->error);
                    }

                    $i = 0;                    
                    while ($cat = $result->fetch_assoc()):
                        $model = json_encode($cat['modello'], JSON_UNESCAPED_SLASHES);
                        $id = $cat['id']
                    
                ?>
                products.push(new Product(<?php echo $id; ?>, await modelFromJson(<?php echo $model; ?>, 0, [<?php echo $i * 50; ?>, 0, 0])));
                <?php

                        $i++;
                    endwhile;

                    mysqli_close($db_connection);
                ?>

                // Chiamata ciclo di render
                render();
            }


            main();
        </script>
</body>