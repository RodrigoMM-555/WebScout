<?php 
include("inc/header.html")
?>

<main>
    <!-- Carrusel de fotos -->
    <article class="carrusel">
        <img src="../img/lis.jpg" alt="placeholder">
        <img src="../img/SCOUT.png" alt="placeholder">

        <!-- Overlay oscuro -->
        <div class="overlay"></div>

        <!-- Texto centrado -->
        <div class="texto-carrusel">Grupo Scout Seeonee</div>
    </article>

     <article class="info">
        <!-- Imagen del calendario -->
        <img class="calendario" src="../img/calendario.jpg" alt="placeholder">
        <!-- Info varia -->
        <div>
            <p>Correo</p>
            <p>Telefono</p>
            <p>Direccion</p>
        </div>
    </article>
</main>

<style>
main {
    background: aliceblue;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Info */
article.info{
    display: flex;
    flex-direction: row;
    padding: 10px;
    border-radius: 10px;
}

article.info img{
    width: 800px;
}

/* Carrusel ancho completo */
.carrusel {
    width: 100%;
    height: 450px;
    overflow: hidden;
    position: relative;
}

/* Sección deslizante */
.carrusel section {
    display: flex;
    left: 0px;
    transition: all 1s;
    position: relative;
}

/* Imágenes */
.carrusel section img {
    width: 100%;
    height: 450px;
    object-fit: cover;
}

/* Overlay oscuro */
.carrusel .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4); /* fundido negro */
    pointer-events: none; /* que no interfiera con los botones */
    z-index: 10;
}

/* Texto centrado */
.carrusel .texto-carrusel {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 48px;
    font-weight: bold;
    text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
    pointer-events: none;
    z-index: 100;
}

/* Botones */
button {
    border: none;
    background: rgba(255,255,255,0.5);
    width: 50px;
    height: 50px;
    position: absolute;
    top: 50%;
    border-radius: 50px;
    font-size: 24px;
    line-height: 50px;
    text-align: center;
    cursor: pointer;
    z-index: 1000;
}

button:hover {
    background: rgba(255,255,255,1);
    transition: all 1s;
}

.carrusel button:first-of-type {
    left: 2%;
}

.carrusel button:last-of-type {
    right: 2%;
}
</style>

<script>
let contenedor = document.querySelector(".carrusel");
let contenido = document.querySelectorAll(".carrusel img")
contenido.forEach(function(elemento){
    elemento.remove()
})

let nuevo_contenedor = document.createElement("section")
nuevo_contenedor.style.left = 0
contenedor.appendChild(nuevo_contenedor)

contenido.forEach(function(elemento){
    nuevo_contenedor.appendChild(elemento)
})

let botonatras = document.createElement("button")
botonatras.textContent = "◀"
contenedor.appendChild(botonatras)
let botondelante = document.createElement("button")
botondelante.textContent = "▶"
contenedor.appendChild(botondelante)

var contador = 0;
function actualizarCarrusel() {
    let ancho = contenedor.offsetWidth;
    nuevo_contenedor.style.left = contador * -ancho + "px";
}

botondelante.onclick = function(){
    if (contador === contenido.length - 1) {
        contador = 0;
    } else {
        contador++;
    }
    actualizarCarrusel();
}
botonatras.onclick = function(){
    if (contador === 0) {
        contador = contenido.length - 1;
    } else {
        contador--;
    }
    actualizarCarrusel();
}

// Ajusta carrusel al cambiar tamaño de pantalla
window.addEventListener('resize', actualizarCarrusel);
</script>


<?php
include("inc/footer.html")
?>

