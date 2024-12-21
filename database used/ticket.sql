CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    available_stock INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    movie_name VARCHAR(100) NOT NULL,
    quantity_sold INT NOT NULL,
    total_sale DECIMAL(10, 2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

CREATE TABLE cash (
    cash_id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    cash_in DECIMAL(10, 2) NOT NULL,
    cash_out DECIMAL(10, 2) NOT NULL,
    cash_on_hand DECIMAL(10, 2) NOT NULL,
    shift_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

CREATE TABLE ticket_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tickets_available INT NOT NULL
);

