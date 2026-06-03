CREATE TABLE IF NOT EXISTS licitacion_favorito (
  id_favorito INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_licitacion INT NOT NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_usuario_licitacion (id_usuario, id_licitacion),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_licitacion) REFERENCES licitacion(id_licitacion) ON DELETE CASCADE
);
