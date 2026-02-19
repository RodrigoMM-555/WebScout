sudo mysql -u root -p

-- Crear y activar la base de datos
CREATE DATABASE WebScout;
USE WebScout;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    contraseña VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono INT NOT NULL,
    direccion VARCHAR(100) NOT NULL
);

-- Tabla de educandos, foreign key a usuarios n a 1
CREATE TABLE educandos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    apellidos VARCHAR(100),
    anio INT,
    seccion VARCHAR(100),
    dni VARCHAR(9),
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- Tabla de avisos
CREATE TABLE avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255),
    secciones VARCHAR(255),
    fecha_hora DATETIME,
    circular VARCHAR(255),
    lugar VARCHAR(255),
    municipio VARCHAR(255),
    provincia VARCHAR(255),
    responsable VARCHAR(255)
);

-- Crear el usuario y asignar permisos
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


-- Modificaciones
ALTER TABLE avisos ADD COLUMN fecha_hora DATETIME;
ALTER TABLE avisos ADD COLUMN CIRCULAR VARCHAR(255);
ALTER TABLE avisos ADD COLUMN responsable VARCHAR(255);
ALTER TABLE educandos CHANGE COLUMN `año` `anio` INT;
ALTER TABLE avisos ADD COLUMN lugar VARCHAR(255);
ALTER TABLE avisos ADD COLUMN municipio VARCHAR(255);
ALTER TABLE avisos ADD COLUMN provincia VARCHAR(255);
