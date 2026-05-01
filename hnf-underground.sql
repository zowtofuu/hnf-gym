USE hnf_underground;

CREATE TABLE clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

CREATE TABLE sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    subscription_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    sale_type ENUM('new_subscription', 'renewal') NOT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sales_client
        FOREIGN KEY (client_id) REFERENCES clients(client_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_sales_subscription
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_sales_plan
        FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);