<?php
header("Content-Type: application/json");
include "db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare("
            INSERT INTO sales (product, quantity, price, total, sale_date)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['product'],
            $data['quantity'],
            $data['price'],
            $data['total'],
            $data['sale_date']
        ]);

        echo json_encode(["message" => "บันทึกการขายสำเร็จ"]);
        break;
}