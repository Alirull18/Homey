-- Homey Diet Planner Database Schema


CREATE DATABASE IF NOT EXISTS db_homey;
USE db_homey;

-- 1. Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT,
    gender ENUM('Male', 'Female'),
    weight_kg FLOAT,
    height_cm FLOAT,
    activity_level VARCHAR(100),
    goal_type ENUM('Deficit', 'Surplus', 'Maintain'),
    role ENUM('User', 'Admin') DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Meal Logs Table (Calorie Tracker)
CREATE TABLE meal_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_name VARCHAR(255) NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner', 'Snack'),
    calories INT NOT NULL,
    protein_g INT,
    carbs_g INT,
    fat_g INT,
    log_date DATE NOT NULL,
    log_time TIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Ingredients Table (Managed by Admin)
CREATE TABLE ingredients (
    ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    kcal_per_100g INT NOT NULL,
    protein_g FLOAT,
    carbs_g FLOAT,
    fat_g FLOAT
);

-- 4. Recipes Table (Community Recipes)
CREATE TABLE recipes (
    recipe_id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    meal_type VARCHAR(100),
    prep_time_min INT,
    instructions TEXT,
    calories INT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    is_featured_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Recipe Tags (Many-to-Many mapping for Recipe Tags)
CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE recipe_tags (
    recipe_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (recipe_id, tag_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);

-- 6. Hydration Logs Table
CREATE TABLE hydration_logs (
    hydro_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cups_drank INT DEFAULT 0,
    log_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert Default Tags
INSERT INTO tags (tag_name) VALUES ('High Protein'), ('Low Carb'), ('Vegetarian'), ('Quick'), ('High Fiber');
