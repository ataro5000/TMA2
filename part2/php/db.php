
<?php
//establishes a PDO database connection, configures error mode to throw exceptions
$host = 'localhost';
$dbname = 'db_part2';
$username = 'root';
$password = '';

try {
   $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
   die("Could not connect to the database: " . $e->getMessage());
}
