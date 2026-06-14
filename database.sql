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

-- Insert Default Ingredients (35 items)
INSERT INTO ingredients (name, category, kcal_per_100g, protein_g, carbs_g, fat_g) VALUES
('Chicken Breast', 'Meat & Poultry', 165, 31, 0, 3.6),
('Brown Rice', 'Grains', 100, 2.7, 24, 2),
('Whole Egg', 'Dairy & Eggs', 155, 13, 1.1, 11),
('Lean Beef', 'Meat & Poultry', 250, 26, 0, 15),
('Turkey Breast', 'Meat & Poultry', 135, 30, 0, 1),
('Salmon Fillet', 'Meat & Poultry', 208, 20, 0, 13),
('Canned Tuna', 'Meat & Poultry', 116, 26, 0, 1),
('Broccoli', 'Vegetables', 34, 2.8, 7, 0.4),
('Spinach', 'Vegetables', 23, 2.9, 3.6, 0.4),
('Carrot', 'Vegetables', 41, 0.9, 10, 0.2),
('Sweet Potato', 'Vegetables', 86, 1.6, 20, 0.1),
('Cucumber', 'Vegetables', 15, 0.7, 3.6, 0.1),
('Tomato', 'Vegetables', 18, 0.9, 3.9, 0.2),
('White Rice', 'Grains', 130, 2.7, 28, 0.3),
('Rolled Oats', 'Grains', 389, 16.9, 66, 6.9),
('Quinoa', 'Grains', 120, 4.4, 21, 1.9),
('Whole Wheat Bread', 'Grains', 247, 13, 41, 3.4),
('Greek Yogurt', 'Dairy & Eggs', 59, 10, 3.6, 0.4),
('Skimmed Milk', 'Dairy & Eggs', 35, 3.4, 5, 0.1),
('Cottage Cheese', 'Dairy & Eggs', 98, 11, 3.4, 4.3),
('Cheddar Cheese', 'Dairy & Eggs', 403, 25, 1.3, 33),
('Lentils', 'Legumes', 116, 9, 20, 0.4),
('Chickpeas', 'Legumes', 164, 8.9, 27, 2.6),
('Black Beans', 'Legumes', 132, 8.9, 24, 0.5),
('Firm Tofu', 'Legumes', 144, 17, 2.8, 8.7),
('Peanut Butter', 'Other', 588, 25, 20, 50),
('Almonds', 'Other', 579, 21, 22, 49),
('Avocado', 'Other', 160, 2, 9, 15),
('Olive Oil', 'Other', 884, 0, 0, 100),
('Apple', 'Other', 52, 0.3, 14, 0.2),
('Banana', 'Other', 89, 1.1, 23, 0.3),
('Blueberries', 'Other', 57, 0.7, 14, 0.3),
('Honey', 'Other', 304, 0.3, 82, 0),
('Chia Seeds', 'Other', 486, 17, 42, 31),
('Whey Protein Powder', 'Other', 390, 80, 6, 5);
