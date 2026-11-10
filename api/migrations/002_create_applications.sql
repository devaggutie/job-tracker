CREATE TABLE applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    company VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    status ENUM('applied','interviewing','offer','rejected','accepted') NOT NULL DEFAULT 'applied',
    url VARCHAR(2048) DEFAULT NULL,
    salary_min INT UNSIGNED DEFAULT NULL,
    salary_max INT UNSIGNED DEFAULT NULL,
    date_applied DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    follow_up_date DATE DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_applications_user_status (user_id, status),
    KEY idx_applications_follow_up (follow_up_date),
    CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
