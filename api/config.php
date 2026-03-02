<?php
$host = "localhost";
$port = "5432";
$dbname = "rice_mill_db";
$user = "postgres";
$password = "10809";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(["error" => $e->getMessage()]));
}

header("Content-Type: application/json");
?>