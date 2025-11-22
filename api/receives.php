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

//ดึงรายการรับข้าวเปลือกแห้งทั้งหมด
function getReceives() {
    global $conn;
    
    $sql = "SELECT r.*, rt.name as rice_type_name 
    FROM receives r
    LEFT JOIN rice_types rt ON r.rice_type_id = rt.id
    ORDER BY r.receive_date DESC, r.created_at DESC
    LIMIT 100";

    $result = $conn->query($sql);
    
    $receives = [];
        while ($row = $result->fetch_assoc()) {
            $receives[] = $row;
        }

        sendResponse(true, 'ดึงข้อมูลสำเร็จ', $receives);
}

//บันทึกการรับข้างมาใหม่
function addReceives() {
    global $conn;
    $data = getJsonInput();

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!validateRequired($data, ['rice_type_id', 'farmer_name', 'rice_typea_id', 'weight'])) {
        sendResponse(false, 'ข้อมูลไม่ครบถ้วน กรุณากรอกให้ครบถ้วน');
    }
    $receiveDate = $conn->real_escape_string($data['receive_date']);
    $farmerName = $conn->real_escape_string($data['farmer_name']);
    $riceTypeId = intval($data['rice_type_id']);
    $weight = floatval($data['weight']);

    // ดึงข้อมูลประเภทข้าว
    $riceTypeSql = "SELECT rate FROM rice_types WHERE id = $riceTypeId";
    $riceTypeResult = $conn->query($riceTypeSql);
    
    if ($riceTypeResult->num_rows === 0) {
        sendResponse(false, 'ไม่พบประเภทข้าวที่เลือก');
    }
    
    $riceType = $riceTypeResult->fetch_assoc();
    $rate = floatval($riceType['rate']);

    // คำนวณ
    $millShare = $weight * ($rate / 100);
    $farmerShare = $weight - $millShare;
    
    // ถ้าอัตราการได้ผลผลิต: 60% ข้าวสาร, 10% ปลายข้าว, 20% รำ, 10% แกลบ
    $whiteRice = $millShare * 0.6;
    $brokenRice = $millShare * 0.1;
    $bran = $millShare * 0.2;
    $husk = $millShare * 0.1;
    
    // เริ่ม transaction
    $conn->begin_transaction();

     // 
    try { 
        $sql = "INSERT INTO receives (receive_date, farmer_name, rice_type_id, weight, mill_share, farmer_share, white_rice, broken_rice, bran, husk) //ตั้งค่าต่างๆ
                VALUES ('$receiveDate', '$farmerName', $riceTypeId, $weight, $millShare, $farmerShare, $whiteRice, $brokenRice, $bran, $husk)"; //ตั้งค่าต่างๆ
    

        if (!$conn->query($sql)) {
            throw new Exception('บันทึกข้อมูลล้มเหลว');
        }

        // อัพเดทสต็อกข้าวสาร
        $updateRiceSql = "INSERT INTO stock (product_type, rice_type_id, quantity) 
                          VALUES ('rice', $riceTypeId, $whiteRice) 
                          ON DUPLICATE KEY UPDATE quantity = quantity + $whiteRice";
        
                           if (!$conn->query($updateRiceSql)) {
                               throw new Exception('อัพเดทสต็อกข้าวสารล้มเหลว');
        }

        //อัพเดทสต็อกปลายข้าว
        $updateBrokenSql = "UPDATE stock SET quantity = quantity + $brokenRice 
                           WHERE product_type = 'broken' AND rice_type_id IS NULL";
        
                            if (!$conn->query($updateBrokenSql)) {
                                 throw new Exception('อัพเดทสต็อกปลายข้าวล้มเหลว');
        }

        // อัพเดทสต็กรำ
        $updateBranSql = "UPDATE stock SET quantity = quantity + $bran 
                          WHERE product_type = 'bran' AND rice_type_id IS NULL";

                          if (!$conn->query($updateBranSql)) {
                            throw new Exception('อัพเดทสต็กรำล้มเหลว');
                          }
        // อัพเดทสต็อกแกลบ
        $updateHuskSql = "UPDATE stock SET quantity = quantity + $husk 
        WHERE produce_type = 'husk' AND rice_type_id IS NULL";

                            if (!$conn->query($updateHuskSql)) {
                                throw new Exception('อัพเดทสต็อกแกลบล้มเหลว');
                            }
                            // Commit transaction
        $conn->commit();
        
        $result = [
            'white_rice' => $whiteRice,
            'broken_rice' => $brokenRice,
            'bran' => $bran,
            'husk' => $husk,
            'mill_share' => $millShare,
            'farmer_share' => $farmerShare
        ];
        
        sendResponse(true, 'บันทึกการรับข้าวเปลือกสำเร็จ', $result);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        sendResponse(false, $e->getMessage());
    }

    
    }
    //ลบการรับข้าวเปลือก
function deleteReceives() { 
 global $conn;  
    $data = getJsonInput();
    
        if (!validateRequired($data, ['id'])) {
            sendResponse(false, 'ข้อมูลไม่ครบถ้วน กรุณากรอกให้ครบถ้วน');
        }
    
        $id = intval($data['id']);
    
        // ลบการรับข้าวเปลือก
        $sql = "DELETE FROM receives WHERE id = $id";
    
        if ($conn->query($sql)) {
            sendResponse(true, 'ลบการรับข้าวเปลือกสำเร็จ');
        } else {
            sendResponse(false, 'ลบการรับข้าวเปลือกไม่สำเร็จ : ' . $conn->error);
        }
 
}
    

?>