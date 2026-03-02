<?php
require 'config.php';

$stmt = $pdo->query("
    SELECT
        SUM(bran_kg) AS total_bran,
        SUM(broken_rice_kg) AS total_broken,
        SUM(husk_bags) AS total_husk,
        SUM(total) AS grand_total
    FROM sales
");

echo json_encode($stmt->fetch());
?>