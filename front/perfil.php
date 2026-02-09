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
.hijo{
    padding: 15px;
    margin: 20px 0;
    font-size: 18px;
    cursor: pointer;
}

.hijo1{
    background: #ffe066;
}

.hijo2{
    background: #4c6ef5;
}

.hijo3{
    background: #00c46a;
}
</style>

<main>
    <section class="izquierda">
        <h1>Perfil</h1>
        <p>Nombre y apellidos</p>
        <p>Telefono</p>
        <p>Email</p>
        <p>Domicilio</p>
    </section>

    <section class="derecha">
        <h1>Hijos</h1>

        <div class="hijo hijo1">hijo1</div>
        <div class="hijo hijo2">hijo2</div>
        <div class="hijo hijo3">hijo3</div>

    </section>
</main>

<?php 
include("inc/footer.html")
?>