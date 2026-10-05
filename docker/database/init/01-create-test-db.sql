-- Create the test database
CREATE DATABASE IF NOT EXISTS `o3shop-test`;

-- Grant all privileges on the test database to the user
GRANT ALL PRIVILEGES ON `o3shop-test`.* TO 'o3shop'@'%';


-- Flush privileges to ensure they take effect
FLUSH PRIVILEGES;
