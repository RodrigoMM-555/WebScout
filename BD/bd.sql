sudo mysql -u root -p

CREATE DATABASE WebScout;
USE WebScout;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    contraseña VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono INT NOT NULL,
    direccion VARCHAR(100) NOT NULL
);

CREATE TABLE educandos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    apellidos VARCHAR(100),
    año INT,
    seccion VARCHAR(100),
    dni VARCHAR(9),
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

CREATE TABLE avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255),
    secciones VARCHAR(255)
);

CREATE USER 
'Uwebscout'@'localhost' 
IDENTIFIED  BY 'Uwebscout5$';
GRANT USAGE ON *.* TO 'Uwebscout'@'localhost';
ALTER USER 'Uwebscout'@'localhost' 
REQUIRE NONE
WITH MAX_QUERIES_PER_HOUR 0 
MAX_CONNECTIONS_PER_HOUR 0 
MAX_UPDATES_PER_HOUR 0 
MAX_USER_CONNECTIONS 0;
GRANT ALL PRIVILEGES ON WebScout.* 
TO 'Uwebscout'@'localhost';
FLUSH PRIVILEGES;