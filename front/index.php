<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(#b39ddb, #9575cd);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        main {
            background-color: ghostwhite;
            padding: 35px 30px;
            width: 320px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        main h2 {
            margin-bottom: 25px;
            color: #6a1b9a;
            font-size: 1.8rem;
        }

        form input[type="text"],
        form input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 18px;
            border-radius: 10px;
            border: 1px solid #ba68c8;
            font-size: 1rem;
            outline: none;
            transition: border 0.3s, box-shadow 0.3s;
        }

        form input[type="text"]:focus,
        form input[type="password"]:focus {
            border-color: #7b1fa2;
            box-shadow: 0 0 0 2px rgba(123, 31, 162, 0.4);
        }

        form input[type="submit"] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 20px;
            background-color: #7b1fa2;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
        }

        form input[type="submit"]:hover {
            background-color: #6a1b9a;
            transform: translateY(2px);
            box-shadow: 0 -6px 12px rgba(0,0,0,0.15);
        }

        form p {
            display: none;
            color: #f90000;
            font-weight: 500;
            margin-top: 0px;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #6a1b9a;
            font-weight: 500;
            transition: color 0.3s;
        }

        a:hover {
            color: #7b1fa2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main>
        <img src="" alt="placeholder">
        <h2>Iniciar sesión</h2>
        <form action="contrl/procesar_login.php" method="POST">
            <input type="text" id="usuario" name="usuario" placeholder="Usuario:" required ><br><br>
            <input type="password" id="password" name="password" placeholder="Contraseña:" required><br><br>
            <p>Usuario o contraseña incorrectos</p>
            <input type="submit" value="Iniciar sesión">
        </form>
        <a href="lista_espera.php">Lista de espera</a>
    </main>
</body>
</html>


<?php
if (isset($_GET["error"]) && $_GET["error"] == "invalid") {
    echo "<script>
        document.querySelector('form p').style.display = 'block';
    </script>";
} else {
    echo "<script>
        document.querySelector('form p').style.display = 'none';
    </script>";
}
?>