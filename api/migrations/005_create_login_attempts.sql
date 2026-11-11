CREATE TABLE login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_lookup (ip, email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
