<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query("
            SELECT r.*, rt.name AS rice_type_name
            FROM receives r
            LEFT JOIN rice_types rt ON r.rice_type_id = rt.id
            ORDER BY r.id DESC
        ");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare("
            INSERT INTO receives 
            (rice_type_id, farmer, weight, white_rice, broken, bran, husk, receive_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['rice_type_id'],
            $data['farmer'],
            $data['weight'],
            $data['white_rice'],
            $data['broken'],
            $data['bran'],
            $data['husk'],
            $data['receive_date']
        ]);

        echo json_encode(["message" => "บันทึกการรับข้าวสำเร็จ"]);
        break;

    case 'DELETE':
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'];

        $stmt = $pdo->prepare("DELETE FROM receives WHERE id=?");
        $stmt->execute([$id]);

        echo json_encode(["message" => "ลบข้อมูลสำเร็จ"]);
        break;
}
?>