<?php
require_once 'config.php';

$result = pg_query($conn, "SELECT * FROM sales ORDER BY id DESC");

$data = [];

while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

sendResponse(true, "success", $data);
?>