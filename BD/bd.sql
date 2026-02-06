sudo mysql -u root -p

CREATE DABASE WebScout

USE WebScout

CREATE TABLE usuario(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    contraseña VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono INT NOT NULL,
    direccion VARCHAR(100) NOT NULL
);

CREATE TABLE educandos(
    id AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    apellidos VARCHAR(100),
    año INT,
    seccion VARCHAR(100),
    dni VARCHAR(9)
);

CREATE TABLE avisos(
    id AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255),
    secciones VARCHAR(255),
    
);
