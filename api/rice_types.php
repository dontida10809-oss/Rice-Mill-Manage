<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getRiceTypes();
        break;
    case 'POST':
        addRiceType();
        break;
    case 'DELETE':
        deleteRiceType();
        break;
    default:
        sendResponse(false, 'Method not allowed');
}

//ดึงรายการประเภทข้าวทั้งหมด
function getRiceTypes() {
    global $conn;
    
    $sql = "SELECT id, name, description FROM rice_types";
    $result = $conn->query($sql);
    
    $riceTypes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $riceTypes[] = $row;
        }
    }
    
    sendResponse(true, 'ดึงข้อมูลสำเร็จ', $riceTypes);
}

//เพิ่มประเภทข้าวใหม่
function addRiceType() {
    global $conn;
    $data = getJsonInput();

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!validateRequired($data, ['name', 'description'])) {
        sendResponse(false, 'ข้อมูลไม่ครบถ้วน กรุณากรอกให้ครบถ้วน');
    }
    $name = $conn->real_escape_string($data['name']);
    $rate = floatval($data['rate']);
    $price = floatval($data['price']);
    $description = isset($data['description']) ? $conn->real_escape_string($data['description']) : '';

    // ตรวจสอบว่ามีชื่อซ้ำหรือไม่
    $checkSql = "SELECT id FROM rice_types WHERE name = '$name'";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        sendResponse(false, 'มีประเภทข้าวนี้อยู่แล้ว');
    }

    // เพิ่มข้อมูลประเภทข้าวใหม่
    $sql = "INSERT INTO rice_types (name, rate, price, description) VALUES ('$name', '$rate', '$price',  '$description')";

    if ($conn->query($sql)) {
        $insertId = $conn->insert_id;

        // สร้างสต็อกของข้าวที่เพิ่มมาใหม่
        $stockSql = "INSERT INTO rice_stock (rice_type_id, quantity) VALUES ($insertId, 0)";
        $conn->query($stockSql);
        sendResponse(true, 'เพิ่มประเภทข้าวสำเร็จ');
    } else {
        sendResponse(false, 'เพิ่มประเภทข้าวไม่สำเร็จ');
    }
}

    /*ลบประเภทข้าว*/
function deleteRiceType() {
    global $conn;
    $data = getJsonInput();

    if (!validateRequired($data, ['id'])) {
        sendResponse(false, 'ข้อมูลไม่ครบถ้วน กรุณากรอกให้ครบถ้วน');
    }

    if (!isset($data['id'])) {
        sendResponse(false, 'กรุณาระบุ ID');
    }

    $id = intval($data['id']);

    // ตรวจสอบข้อมูลที่จำเป็นว่ายังใช้งานอยู่ไหม
    $checkSql = "SELECT COUNT(*) as count FROM receives WHERE rice_type_id = $id";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        sendResponse(false, 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานอยู่');
    }

    // ลบสต็อก
    $deleteStockSql = "DELETE FROM stock WHERE rice_type_id = $id";
    $conn->query($deleteStockSql);


    // ลบประเภทข้าว
    $sql = "DELETE FROM rice_types WHERE id = $id";

    if ($conn->query($sql)) {
        sendResponse(true, 'ลบประเภทข้าวสำเร็จ');
    } else {
        sendResponse(false, 'ลบประเภทข้าวไม่สำเร็จ : ' . $conn->error);
    }
}

?>