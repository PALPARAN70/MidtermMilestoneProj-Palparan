-- Kusina ng Barangay — Midterm Milestone Project
-- Works on MySQL 8.0.16+ and MariaDB 10.2+ (XAMPP)
CREATE DATABASE IF NOT EXISTS kusina_ng_barangay
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kusina_ng_barangay;

-- 1. users
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(60)  NOT NULL,
    email         VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- 2. categories
CREATE TABLE categories (
    id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- 3. recipes
CREATE TABLE recipes (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED      NOT NULL,
    category_id   SMALLINT UNSIGNED NOT NULL,
    title         VARCHAR(120)      NOT NULL,
    description   VARCHAR(500)      NOT NULL,
    steps         TEXT              NOT NULL,          -- one step per line
    servings      TINYINT UNSIGNED  NOT NULL DEFAULT 4,
    cook_minutes  SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME          NULL DEFAULT NULL, -- NOT NULL = edited after posting
    PRIMARY KEY (id),
    KEY idx_recipes_created (created_at),
    KEY idx_recipes_category (category_id),
    CONSTRAINT chk_recipes_servings CHECK (servings BETWEEN 1 AND 50),
    CONSTRAINT chk_recipes_minutes  CHECK (cook_minutes BETWEEN 1 AND 1440),
    CONSTRAINT fk_recipes_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    -- a category that still has recipes cannot be deleted
    CONSTRAINT fk_recipes_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. ingredients (one row per ingredient)
CREATE TABLE ingredients (
    id         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    recipe_id  INT UNSIGNED      NOT NULL,
    position   SMALLINT UNSIGNED NOT NULL,             -- keeps the member's order
    quantity   VARCHAR(40)       NULL,                 -- e.g. "2 cups", "1 tbsp"
    name       VARCHAR(100)      NOT NULL,             -- e.g. "rice, washed"
    PRIMARY KEY (id),
    UNIQUE KEY uq_ingredients_order (recipe_id, position),
    CONSTRAINT fk_ingredients_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. comments
CREATE TABLE comments (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    recipe_id   INT UNSIGNED  NOT NULL,
    user_id     INT UNSIGNED  NOT NULL,
    body        VARCHAR(1000) NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_comments_recipe (recipe_id, created_at),
    CONSTRAINT fk_comments_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. favorites (composite PK = a member can save a recipe only once)
CREATE TABLE favorites (
    user_id     INT UNSIGNED NOT NULL,
    recipe_id   INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    KEY idx_favorites_recipe (recipe_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Starting categories
INSERT INTO categories (name) VALUES
    ('Ulam'),
    ('Sabaw'),
    ('Gulay'),
    ('Almusal'),
    ('Merienda'),
    ('Kakanin'),
    ('Panghimagas'),
    ('Pulutan'),
    ('Tipid Meals');
