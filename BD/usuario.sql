
--Creamos ususario
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
GRANT ALL PRIVILEGES ON reserva_pistas.* 
TO 'Uwebscout'@'localhost';
FLUSH PRIVILEGES;
