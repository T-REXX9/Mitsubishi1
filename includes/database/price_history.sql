CREATE TABLE IF NOT EXISTS price_history (
    id int(11) NOT NULL AUTO_INCREMENT,
    vehicle_id int(11) NOT NULL,
    old_price decimal(12,2) DEFAULT NULL,
    new_price decimal(12,2) DEFAULT NULL,
    change_date timestamp DEFAULT current_timestamp(),
    change_note text DEFAULT NULL,
    changed_by varchar(100) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY vehicle_id (vehicle_id),
    CONSTRAINT price_history_ibfk_1 FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
