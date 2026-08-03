-- Food Court Module Schema
-- NextGen Smart University Platform
--
-- Two tables are renamed from the feature document, for reasons that leave no
-- real alternative:
--   Order   -> FoodOrder     ORDER is a reserved word in SQL
--   Payment -> OrderPayment  Payment already exists in the Finance module

USE nextgen_university;


CREATE TABLE IF NOT EXISTS Restaurant (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id BIGINT UNSIGNED NOT NULL,
    restaurant_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    phone VARCHAR(30) NOT NULL,
    opening_time TIME NULL,
    closing_time TIME NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_restaurant_owner (owner_id),
    KEY idx_restaurant_status (status),

    CONSTRAINT fk_restaurant_owner
        FOREIGN KEY (owner_id) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS FoodCategory (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    restaurant_id BIGINT UNSIGNED NOT NULL,
    category_name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_category_restaurant_name (restaurant_id, category_name),
    KEY idx_category_restaurant (restaurant_id),

    CONSTRAINT fk_category_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES Restaurant (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS MenuItem (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    restaurant_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(8,2) NOT NULL,
    image_path VARCHAR(255) NULL,
    availability ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    preparation_time SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_menu_restaurant (restaurant_id),
    KEY idx_menu_category (category_id),
    KEY idx_menu_availability (availability),

    CONSTRAINT fk_menu_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES Restaurant (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_menu_category
        FOREIGN KEY (category_id) REFERENCES FoodCategory (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_menu_price
        CHECK (price > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS FoodOrder (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    restaurant_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Online Banking', 'Credit Card', 'Debit Card', 'E-Wallet')
        NOT NULL DEFAULT 'Cash',
    payment_status ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
    order_status ENUM('Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled')
        NOT NULL DEFAULT 'Pending',
    cancellation_reason VARCHAR(255) NULL,
    ordered_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_order_number (order_number),
    KEY idx_order_customer (customer_id),
    KEY idx_order_restaurant (restaurant_id),
    KEY idx_order_status (order_status),
    KEY idx_order_placed (ordered_at),

    CONSTRAINT fk_order_customer
        FOREIGN KEY (customer_id) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_order_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES Restaurant (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_order_total
        CHECK (total_amount >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS OrderItem (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price DECIMAL(8,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_order_item_order (order_id),
    KEY idx_order_item_menu (menu_item_id),

    CONSTRAINT fk_order_item_order
        FOREIGN KEY (order_id) REFERENCES FoodOrder (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_order_item_menu
        FOREIGN KEY (menu_item_id) REFERENCES MenuItem (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_order_item_quantity
        CHECK (quantity > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS OrderPayment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_reference VARCHAR(100) NOT NULL,
    payment_method ENUM('Cash', 'Online Banking', 'Credit Card', 'Debit Card', 'E-Wallet')
        NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'verified', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_order_payment_reference (payment_reference),
    UNIQUE KEY uq_order_payment_order (order_id),
    KEY idx_order_payment_status (payment_status),

    CONSTRAINT fk_order_payment_order
        FOREIGN KEY (order_id) REFERENCES FoodOrder (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_order_payment_amount
        CHECK (amount > 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS RestaurantReview (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    restaurant_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_review_order (order_id),
    KEY idx_review_restaurant (restaurant_id),
    KEY idx_review_customer (customer_id),

    CONSTRAINT fk_review_restaurant
        FOREIGN KEY (restaurant_id) REFERENCES Restaurant (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_review_order
        FOREIGN KEY (order_id) REFERENCES FoodOrder (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_review_customer
        FOREIGN KEY (customer_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT chk_review_rating
        CHECK (rating BETWEEN 1 AND 5)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
