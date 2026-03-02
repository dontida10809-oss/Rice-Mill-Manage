<?php
require_once 'config.php';

$customer = $_POST['customer'];
$amount = $_POST['amount'];

$query = "INSERT INTO sales (customer, amount) VALUES ($1, $2)";
$result = pg_query_params($conn, $query, [$customer, $amount]);

if ($result) {
    sendResponse(true, "Added successfully");
} else {
    sendResponse(false, "Insert failed");
}
?>