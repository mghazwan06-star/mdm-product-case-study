-- Runs once, when the MySQL data volume is first created.
-- Tests use their own database so they never touch the app's data.
CREATE DATABASE IF NOT EXISTS workwize_test;
GRANT ALL PRIVILEGES ON workwize_test.* TO 'workwize'@'%';
