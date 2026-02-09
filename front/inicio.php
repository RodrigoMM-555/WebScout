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

