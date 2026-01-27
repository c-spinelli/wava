CREATE DATABASE IF NOT EXISTS wava_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wava_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,

  age INT NULL,
  height_cm INT NULL,
  weight_kg DECIMAL(5,2) NULL,

  goal_water_ml INT NOT NULL DEFAULT 2000,
  goal_protein_g INT NOT NULL DEFAULT 100,
  goal_exercise_minutes INT NOT NULL DEFAULT 30,
  goal_sleep_hours DECIMAL(4,2) NOT NULL DEFAULT 8,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE day_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  log_date DATE NOT NULL,

  water_ml INT DEFAULT 0,
  protein_g INT DEFAULT 0,
  sleep_hours DECIMAL(4,2) NULL,
  energy_level INT NULL,
  notes TEXT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY unique_user_day (user_id, log_date),
  CONSTRAINT fk_daylogs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

CREATE TABLE workouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  day_log_id INT NOT NULL,

  workout_type VARCHAR(50) NOT NULL,
  minutes INT NOT NULL,
  notes TEXT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_workouts_daylog
    FOREIGN KEY (day_log_id) REFERENCES day_logs(id)
    ON DELETE CASCADE
);
