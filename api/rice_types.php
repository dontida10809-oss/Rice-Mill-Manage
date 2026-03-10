<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query("SELECT * FROM rice_types ORDER BY id ASC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO rice_types (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        echo json_encode(["message" => "เพิ่มประเภทข้าวสำเร็จ"]);
        break;

    case 'PUT':
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'];

        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE rice_types SET name=? WHERE id=?");
        $stmt->execute([$data['name'], $id]);
        echo json_encode(["message" => "แก้ไขสำเร็จ"]);
        break;

    case 'DELETE':
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'];

        $stmt = $pdo->prepare("DELETE FROM rice_types WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "ลบสำเร็จ"]);
        break;
}
?>