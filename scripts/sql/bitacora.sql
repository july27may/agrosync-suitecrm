CREATE TABLE IF NOT EXISTS agrosync_bitacora (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    intento CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    operacion CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    relacionada CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    usuario VARCHAR(36) NOT NULL,
    finca VARCHAR(36) NOT NULL,
    modulo VARCHAR(32) NOT NULL,
    evento VARCHAR(16) NOT NULL,
    fase VARCHAR(12) NOT NULL,
    causa VARCHAR(48) NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE KEY bitacora_identidad (intento, fase),
    KEY bitacora_finca (finca, id),
    KEY bitacora_operacion (finca, operacion, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
