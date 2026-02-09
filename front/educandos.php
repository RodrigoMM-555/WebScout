<?php 
include("inc/header.html")
?>

<style>
main{
    display: flex;
    flex-direction: row;
    justify-content: space-evenly;
    background: aliceblue;
}

/* ===== COLUMNA PERFIL ===== */
section.izquierda{
    background: #b3b3b3;
    width: 350px;
    padding: 30px;
    text-align: center;
}

section.izquierda p{
    margin: 20px 0;
    font-size: 18px;
}

section.izquierda {
    margin-bottom: 30px;
}
/* ===== COLUMNA HIJOS ===== */
section.derecha{
    background: #b3b3b3;
    width: 350px;
    padding: 30px;
    text-align: center;
}

section.derecha {
    margin-bottom: 30px;
}

/* ===== BOTONES HIJOS ===== */
.documentacion{
    padding: 15px;
    margin: 20px 0;
    font-size: 18px;
    cursor: pointer;
    background: white;
}

</style>

<main>
    <section class="izquierda">
        <h1>Hijo1</h1>
        <p>Sección</p>
        <p>Año</p>
        <p>DNI</p>
    </section>

    <section class="derecha">
        <h1>Documentación</h1>

        <div class="documentacion">algo</div>
        <div class="documentacion">algo</div>
        <div class="documentacion">algo</div>

    </section>
</main>

<?php 
include("inc/footer.html")
?>