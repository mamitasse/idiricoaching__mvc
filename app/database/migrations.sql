/* ================================
   RESET + CRÉATION BASE & TABLES
   ================================ */

-- (1) Crée (ou recrée) la base
DROP DATABASE IF EXISTS coaching_db;
CREATE DATABASE coaching_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE coaching_db;

-- (2) Nettoyage sécurisé des tables (ordre FK)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- (3) Table users
CREATE TABLE users (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name   VARCHAR(100)                NOT NULL,
  last_name    VARCHAR(100)                NOT NULL,
  email        VARCHAR(191)                NOT NULL UNIQUE,
  phone        VARCHAR(20)                 NOT NULL,
  address      VARCHAR(255)                NOT NULL,
  password     VARCHAR(255)                NOT NULL,
  role         ENUM('adherent','coach','admin') NOT NULL DEFAULT 'adherent',
  gender       ENUM('male','female','other')     NULL,
  age          TINYINT UNSIGNED            NULL,
  coach_id     INT UNSIGNED                NULL,             -- coach choisi par l’adhérent
  created_at   DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_users_coach
    FOREIGN KEY (coach_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE INDEX idx_users_role     ON users(role);
CREATE INDEX idx_users_coach_id ON users(coach_id);

-- (4) Table slots (créneaux d’un coach)
CREATE TABLE slots (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coach_id    INT UNSIGNED      NOT NULL,      -- FK vers users.id (role=coach)
  start_time  DATETIME          NOT NULL,
  end_time    DATETIME          NOT NULL,
  status      ENUM('available','blocked','deleted') NOT NULL DEFAULT 'available',
  created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_slots_coach
    FOREIGN KEY (coach_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT uq_slot UNIQUE (coach_id, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE INDEX idx_slots_coach_time ON slots(coach_id, start_time);

-- (5) Table reservations (1 slot = 1 réservation max)
CREATE TABLE reservations (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slot_id      INT UNSIGNED      NOT NULL,
  adherent_id  INT UNSIGNED      NOT NULL,
  status       ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  paid         TINYINT(1)        NOT NULL DEFAULT 0,
  created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_res_slot
    FOREIGN KEY (slot_id) REFERENCES slots(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT fk_res_user
    FOREIGN KEY (adherent_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT uq_reservation_slot UNIQUE (slot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE INDEX idx_res_adherent ON reservations(adherent_id);


/* ================================
   SEEDS (OPTIONNEL) — COACHS TEST
   - Mot de passe = "password"
   - Hash bcrypt ci-dessous (Laravel): $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
   ================================ */

INSERT INTO users (first_name,last_name,email,phone,address,password,role,gender,age,coach_id)
VALUES
  ('Nadia','Coach','idirinadia10@gmail.com','0658173004','77700 Chessy',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','coach','female',NULL,NULL),
  ('Sabrina','Coach','sabrina.idir@gmail.com','0762131406','95 sannoy',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','coach','female',NULL,NULL);

/* ================================
   FIN
   ================================ */
