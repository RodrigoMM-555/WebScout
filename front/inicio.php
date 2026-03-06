<?php 
/**
 * inicio.php — Página de inicio (landing tras login)
 * Muestra el carrusel de fotos, calendario trimestral e info de contacto.
 */
include("../inc/header.php")
?>

<main>
    <ul class="paño" aria-hidden="true"></ul>

    <!-- Cogemos als imagenes del carrusel -->
    <?php
    $imagenesCarrusel = glob(__DIR__ . '/../img/carrusel/*.{avif,webp,jpg,jpeg,png,gif}', GLOB_BRACE);
    natsort($imagenesCarrusel);
    $imagenesCarrusel = array_values($imagenesCarrusel);
    ?>
    <!-- Carrusel de fotos -->
    <article class="carrusel">
        <?php foreach ($imagenesCarrusel as $rutaImagen): ?>
            <img src="../img/carrusel/<?= htmlspecialchars(basename($rutaImagen), ENT_QUOTES, 'UTF-8') ?>" alt="Imagen del carrusel">
        <?php endforeach; ?>
        <!-- Difuminado y texto -->
        <div class="overlay"></div>
        <div class="texto-carrusel">Grupo Scout Seeonee</div>
    </article>

     <article class="info">
        <!-- Imagen del calendario -->
        <?php include("../inc/calendario.php") ?>

        <!-- Info varia -->
        <div>
            <p>Correo</p>
            <p>Telefono</p>
            <p>Direccion</p>
        </div>
    </article>
</main>

<!-- Script del carrusel de fotos -->
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
botonatras.className = "carrusel-btn carrusel-btn-atras";
contenedor.appendChild(botonatras)
let botondelante = document.createElement("button")
botondelante.textContent = "▶"
botondelante.className = "carrusel-btn carrusel-btn-delante";
contenedor.appendChild(botondelante)

let contador = 0;

function obtenerVisibles() {
    return window.matchMedia('(min-width: 992px)').matches ? 2 : 1;
}

function obtenerInicios() {
    const visibles = obtenerVisibles();
    const maxInicio = Math.max(0, contenido.length - visibles);
    const inicios = [];

    for (let i = 0; i <= maxInicio; i += visibles) {
        inicios.push(i);
    }

    if (inicios.length === 0) {
        inicios.push(0);
    }

    if (inicios[inicios.length - 1] !== maxInicio) {
        inicios.push(maxInicio);
    }

    return inicios;
}

function ajustarContador() {
    const inicios = obtenerInicios();
    let mejor = inicios[0];
    let distancia = Math.abs(contador - mejor);

    inicios.forEach(function(i) {
        const d = Math.abs(contador - i);
        if (d < distancia) {
            mejor = i;
            distancia = d;
        }
    });

    contador = mejor;
}

function actualizarCarrusel() {
    const visibles = obtenerVisibles();
    contenedor.style.setProperty('--carrusel-visibles', String(visibles));
    const ancho = contenedor.offsetWidth / visibles;
    nuevo_contenedor.style.left = contador * -ancho + "px";
}

botondelante.onclick = function(){
    const inicios = obtenerInicios();
    const posicionActual = inicios.indexOf(contador);
    if (posicionActual === -1 || posicionActual === inicios.length - 1) {
        contador = inicios[0];
    } else {
        contador = inicios[posicionActual + 1];
    }
    actualizarCarrusel();
}

botonatras.onclick = function(){
    const inicios = obtenerInicios();
    const posicionActual = inicios.indexOf(contador);
    if (posicionActual <= 0) {
        contador = inicios[inicios.length - 1];
    } else {
        contador = inicios[posicionActual - 1];
    }
    actualizarCarrusel();
}

// Ajusta carrusel al cambiar tamaño de pantalla
window.addEventListener('resize', function() {
    ajustarContador();
    actualizarCarrusel();
});

ajustarContador();
actualizarCarrusel();
</script>

<?php
include("../inc/footer.html")
?>

