<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query("SELECT * FROM sales ORDER BY id DESC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $total =
            ($data['bran_kg'] * $data['price_bran']) +
            ($data['broken_rice_kg'] * $data['price_broken']) +
            ($data['husk_bags'] * $data['price_husk']);

        $stmt = $pdo->prepare("
            INSERT INTO sales
            (bran_kg, broken_rice_kg, husk_bags,
             price_bran, price_broken, price_husk,
             total)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['bran_kg'],
            $data['broken_rice_kg'],
            $data['husk_bags'],
            $data['price_bran'],
            $data['price_broken'],
            $data['price_husk'],
            $total
        ]);

        echo json_encode(["message" => "บันทึกการขายสำเร็จ"]);
        break;

    case 'DELETE':
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'];

        $stmt = $pdo->prepare("DELETE FROM sales WHERE id=?");
        $stmt->execute([$id]);

        echo json_encode(["message" => "ลบข้อมูลสำเร็จ"]);
        break;
}
?>