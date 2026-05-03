USE hnf_underground;

CREATE TABLE clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    membership_expires_at DATE DEFAULT NULL;
);

CREATE TABLE membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,

    membership_type ENUM('member', 'non_member', 'student_senior') NOT NULL DEFAULT 'non_member',
    pass_type ENUM('daily', 'monthly') NOT NULL,

    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (membership_type, pass_type)
);

INSERT INTO membership_plans (membership_type, pass_type, price, duration_days)
VALUES
('member', 'daily', 80, 1),
('member', 'monthly', 800, 30),
('non_member', 'daily', 100, 1),
('non_member', 'monthly', 1000, 30),
('student_senior', 'daily', 50, 1),
('student_senior', 'monthly', 500, 30)
ON DUPLICATE KEY UPDATE
price = VALUES(price),
duration_days = VALUES(duration_days);

CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    plan_id INT NOT NULL,
    subscription_start DATE NOT NULL,
    subscription_end DATE NOT NULL,
    subscription_token VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'expired', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_subscriptions_client
        FOREIGN KEY (client_id) REFERENCES clients(client_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_subscriptions_plan
        FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE subscriptions_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    client_id INT NOT NULL,
    plan_id INT NOT NULL,
    subscription_start DATE NOT NULL,
    subscription_end DATE NOT NULL,
    subscription_token VARCHAR(100) NOT NULL,
    status ENUM('active', 'expired', 'suspended') NOT NULL,
    renewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_subscriptions_history_subscription
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_subscriptions_history_client
        FOREIGN KEY (client_id) REFERENCES clients(client_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_subscriptions_history_plan
        FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_client
        FOREIGN KEY (client_id) REFERENCES clients(client_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uq_attendance_client_date
        UNIQUE (client_id, attendance_date)
);

CREATE TABLE other_products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,

    transaction_type ENUM(
        'subscription',
        'renewal',
        'product',
        'personal_training'
    ) NOT NULL,

    reference_id INT NOT NULL,
    client_id INT DEFAULT NULL,

    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    amount DECIMAL(10,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sales_client
        FOREIGN KEY (client_id) REFERENCES clients(client_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

