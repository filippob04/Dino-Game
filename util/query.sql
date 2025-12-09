-- dbName: saw_project
-- Tabella Utenti
CREATE TABLE IF NOT EXISTS `user` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    securePassword VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `stats` (
    user_id INT PRIMARY KEY,
    pt INT DEFAULT 0, 
    hs INT DEFAULT 0,
    gamesPlayed INT DEFAULT 0,
    bio VARCHAR(150),

    CONSTRAINT stats_fkey
    FOREIGN KEY (user_id) REFERENCES user(id) 
    ON DELETE CASCADE 
);

-- Admin di Sistema
CREATE ROLE IF NOT EXISTS 'adminRole';
GRANT ALL PRIVILEGES ON `saw_project`.* TO 'adminRole' WITH GRANT OPTION;

CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'sawProject2526BF';
GRANT 'adminRole' TO 'admin'@'localhost';
SET DEFAULT ROLE 'adminRole' FOR 'admin'@'localhost';

FLUSH PRIVILEGES;

-- Player Generico
CREATE ROLE IF NOT EXISTS 'playerRole';
GRANT SELECT, INSERT, DELETE, UPDATE ON `saw_project`.* TO 'playerRole';

CREATE USER IF NOT EXISTS 'player'@'localhost' IDENTIFIED BY 'userPassword';
GRANT 'playerRole' TO 'player'@'localhost';
SET DEFAULT ROLE 'playerRole' FOR 'player'@'localhost';

FLUSH PRIVILEGES;

-- Esterno
CREATE ROLE IF NOT EXISTS 'viewerRole';
GRANT SELECT ON `saw_project`.* TO 'viewerRole';

CREATE USER IF NOT EXISTS 'viewer'@'localhost' IDENTIFIED BY 'viewerPassword';
GRANT 'viewerRole' TO 'viewer'@'localhost';
SET DEFAULT ROLE 'viewerRole' FOR 'viewer'@'localhost';

FLUSH PRIVILEGES;

-- Creazione Utenti randomTest
INSERT INTO `user` (username, email, firstName, lastName, securePassword)
VALUES 
    ('r1', 'random.user1@test.it', 'Test', 'Uno', 'hash_x'),
    ('r2', 'random.user2@test.it', 'Test', 'Due', 'hash_y');
INSERT INTO `stats` (user_id, pt, gamesPlayed, bio)
VALUES 
    (
        (SELECT id FROM user WHERE username = 'r1'), -- Cerca l'ID di r1
        FLOOR(RAND() * 200), 
        FLOOR(RAND() * 200), 
        'Bio di Test 1'
    ),
    (
        (SELECT id FROM user WHERE username = 'r2'), -- Cerca l'ID di r2
        FLOOR(RAND() * 200), 
        FLOOR(RAND() * 200), 
        'Bio di Test 2'
    );
