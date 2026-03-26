-- ==============================================================
-- WebScout — Esquema de base de datos
-- ==============================================================
-- Ejecutar como administrador de MySQL:
--   mysql -u root -p < bd.sql
--
-- NOTA: telefono debería ser VARCHAR(20) para soportar prefijos
-- internacionales y ceros iniciales. contraseña debería ser
-- VARCHAR(255) para cubrir el máximo de password_hash().
-- ==============================================================

-- Crear y activar la base de datos
CREATE DATABASE WebScout;
USE WebScout;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    contraseña VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(100) NOT NULL,
    nombre2 VARCHAR(100),
    apellidos2 VARCHAR(100),
    email2 VARCHAR(100),
    telefono2 VARCHAR(20),
    rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    cambio_contraseña BOOLEAN DEFAULT TRUE
);

-- Tabla de educandos, foreign key a usuarios n a 1
CREATE TABLE educandos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    seccion VARCHAR(100),
    dni VARCHAR(9) NOT NULL,
    permisos INT DEFAULT 0,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
);

-- Tabla de avisos
CREATE TABLE avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    secciones VARCHAR(255) NOT NULL,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME,
    circular VARCHAR(255) NOT NULL,
    lugar VARCHAR(255),
    municipio VARCHAR(255),
    provincia VARCHAR(255),
    responsable VARCHAR(255),
    tipo ENUM('campamento','reunion','excursion','sabado','otro')
);

-- Tabla intermedia avisos-educandos -> asistencia a eventos
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_aviso INT NOT NULL,
    id_educando INT NOT NULL,
    asistencia ENUM('pendiente','si','no') DEFAULT 'pendiente',
    fecha_respuesta DATETIME NULL,
    UNIQUE KEY unique_asistencia (id_aviso, id_educando),
    FOREIGN KEY (id_aviso) REFERENCES avisos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_educando) REFERENCES educandos(id) ON DELETE CASCADE
);


CREATE TABLE lista_espera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_nino VARCHAR(150) NOT NULL,
    apellidos_nino VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    nombre_contacto VARCHAR(150) NOT NULL,
    telefono_contacto VARCHAR(20) NOT NULL,
    correo_contacto VARCHAR(150) NOT NULL,
    direccion_contacto VARCHAR(255) NOT NULL,

    hermano_en_grupo BOOLEAN DEFAULT FALSE,
    relacion_con_miembro BOOLEAN DEFAULT FALSE,
    familia_antiguo_scouter BOOLEAN DEFAULT FALSE,
    estuvo_en_grupo BOOLEAN DEFAULT FALSE,

    explicacion_relacion TEXT,
    comentarios TEXT,

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

ALTER TABLE usuarios ADD COLUMN nombre2 VARCHAR(100);
ALTER TABLE usuarios ADD COLUMN apellidos2 VARCHAR(100);
ALTER TABLE usuarios ADD COLUMN email2 VARCHAR(100);
ALTER TABLE usuarios ADD COLUMN telefono2 VARCHAR(20);
ALTER TABLE usuarios ADD COLUMN rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario';
ALTER TABLE usuarios ADD CONSTRAINT uq_usuarios_email UNIQUE (email);

ALTER TABLE educandos ADD permisos INT DEFAULT 0;

ALTER TABLE lista_espera ADD COLUMN apellidos_nino VARCHAR(150) NOT NULL;

ALTER TABLE usuarios ADD COLUMN cambio_contraseña BOOLEAN DEFAULT TRUE;
ALTER TABLE lista_espera ADD COLUMN direccion_contacto VARCHAR(255) NOT NULL;