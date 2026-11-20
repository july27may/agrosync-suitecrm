CREATE TABLE IF NOT EXISTS agrosync_acuses_edicion (
    operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    actor_id VARCHAR(36) NOT NULL,
    finca_id VARCHAR(36) NOT NULL,
    fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    receipt MEDIUMTEXT NOT NULL,
    confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
