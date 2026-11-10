CREATE TABLE contacts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_contacts_application (application_id),
    CONSTRAINT fk_contacts_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
