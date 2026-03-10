<?php
require 'config.php';

$stmt = $pdo->query("
    SELECT 
        product,
        SUM(quantity) AS total_quantity,
        SUM(total) AS total_sales
    FROM sales
    GROUP BY product
");

echo json_encode($stmt->fetchAll());
?>