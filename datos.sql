-- =========================================================
--  CINEsesitasVerlas - Base de datos
--  Importar desde phpMyAdmin (opcional: el sistema también
--  crea la base y la tabla automáticamente).
-- =========================================================

CREATE DATABASE IF NOT EXISTS cinesitas
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE cinesitas;

CREATE TABLE IF NOT EXISTS funciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  genero VARCHAR(60) NOT NULL,
  sala VARCHAR(30) NOT NULL,
  horario DATETIME NOT NULL,
  precio DECIMAL(6,2) NOT NULL DEFAULT 0,
  poster VARCHAR(500) DEFAULT NULL,
  sinopsis TEXT,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO funciones (titulo, genero, sala, horario, precio, poster, sinopsis) VALUES
('Duna: Parte Dos', 'Ciencia ficción', 'Sala 1', '2026-10-19 19:30', 22.00, '', 'Paul Atreides se une a los Fremen para vengar la muerte de su familia y frenar una guerra santa.'),
('Oppenheimer', 'Drama', 'Sala 2', '2026-10-19 21:00', 20.00, '', 'El físico J. Robert Oppenheimer dirige el proyecto Manhattan y enfrenta las consecuencias de la bomba atómica.'),
('Intensa Mente 2', 'Animación', 'Sala 3', '2026-10-20 16:00', 18.00, '', 'Las emociones de Riley enfrentan nuevos sentimientos cuando ella inicia la secundaria.'),
('Godzilla y Kong: El Nuevo Imperio', 'Acción', 'Sala 4', '2026-10-20 18:30', 22.00, '', 'Godzilla y Kong chocan contra una amenaza oculta que desafía su existencia.'),
('Deadpool y Wolverine', 'Acción', 'Sala 1', '2026-10-20 20:45', 24.00, '', 'Wade Wilson regresa y se alía con Wolverine en una misión que cambiará su historia.'),
('El Conjuro: La Maldición', 'Terror', 'Sala 5', '2026-10-21 22:00', 20.00, '', 'Los Warren investigan un caso relacionado con un sacerdote y un demonio antiguo.'),
('Coraline', 'Terror', 'Sala 6', '2026-10-22 21:30', 16.00, '', 'Una niña descubre una puerta secreta que la lleva a un mundo parecido pero peligroso.'),
('La Sustancia', 'Terror', 'Sala 5', '2026-10-23 23:00', 18.00, '', 'Una actriz descubre un producto que le permite volver a ser joven… por un precio.'),
('Kung Fu Panda 4', 'Animación', 'Sala 3', '2026-10-24 15:30', 16.00, '', 'Po debe encontrar al próximo Guerrero Dragón mientras enfrenta a una hechicera.'),
('Wicked', 'Musical', 'Sala 2', '2026-10-25 17:00', 24.00, '', 'Dos brujas jóvenes forjan una amistad en la tierra de Oz antes de tomar caminos opuestos.'),
('Anora', 'Drama', 'Sala 4', '2026-10-26 20:00', 20.00, '', 'Una joven de Brooklyn contrae un matrimonio impulsivo con el hijo de un oligarca ruso.'),
('Trap', 'Suspense', 'Sala 6', '2026-10-27 21:00', 18.00, '', 'Un padre descubre que el concierto al que asiste con su hija es una trampa para captar a un asesino.'),
('Mufasa: El Rey León', 'Animación', 'Sala 3', '2026-11-01 14:00', 20.00, '', 'La historia de cómo Mufasa llegó a ser rey y conoció a su hermano Scar.'),
('Misión: Imposible – The Final Reckoning', 'Acción', 'Sala 1', '2026-11-02 19:00', 26.00, '', 'Ethan Hunt y su equipo persiguen un peligroso arma que amenaza al mundo.'),
('Cómo entrenar a tu dragón', 'Animación', 'Sala 2', '2026-11-03 16:30', 18.00, '', 'En la isla de Berk, un joven vikingo hace amistad con un temido dragón.'),
('Gladiador II', 'Drama', 'Sala 4', '2026-11-04 20:30', 22.00, '', 'Lucio entra al Coliseo para vengar a su familia y desafiar al emperador de Roma.');
