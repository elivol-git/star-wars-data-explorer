CREATE USER IF NOT EXISTS 'planets_user'@'%' IDENTIFIED BY 'Planets@2024!Secure';
GRANT ALL PRIVILEGES ON planets.* TO 'planets_user'@'%';
FLUSH PRIVILEGES;
