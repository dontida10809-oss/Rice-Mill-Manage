<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getSales();
        break;
    case 'POST':
        addSale();
        break;
    default:
        sendResponse(false, 'Method not allowed');
}

// ดึงรายการขายข้าวเปลือกทั้งหมด
function getSales() {
    global $conn;
    
    $sql = "SELECT s.*, rt.name as rice_type_name 
    FROM sales s
    LEFT JOIN rice_types rt ON s.rice_type_id = rt.id
    ORDER BY s.sale_date DESC, s.created_at DESC
    LIMIT 100";

    $result = $conn->query($sql);
    
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }

    sendResponse(true, 'ดึงข้อมูลสำเร็จ', $sales);
}

// บันทึกการขายข้าวเปลือกใหม่
function addSale() {
    global $conn;

    $data = getJsonInput();

    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!validateRequired($data, ['sale_date', 'product_type', 'quantity', 'price_per_kg'])) {
        sendResponse(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    $saleDate = $conn->real_escape_string($data['sale_date']);
    $productType = $conn->real_escape_string($data['product_type']);
    $quantity = floatval($data['quantity']);
    $pricePerKg = floatval($data['price_per_kg']);
    $totalAmount = $quantity * $pricePerKg;
    
    $riceTypeId = null;
    if ($productType === 'rice') {
        if (!isset($data['rice_type_id'])) {
            sendResponse(false, 'กรุณาระบุประเภทข้าวสาร');
        }
        $riceTypeId = intval($data['rice_type_id']);
    }

    //ตรวงสอบสต็อกข้าวเปลือก
    if ($productType === 'rice') {
        $stockSql = "SELECT quantity FROM stock 
                     WHERE product_type = 'rice' AND rice_type_id = $riceTypeId";
    } else {
        $stockSql = "SELECT quantity FROM stock 
                     WHERE product_type = '$productType' AND rice_type_id IS NULL";
    }
    
    $stockResult = $conn->query($stockSql);
    
    if ($stockResult->num_rows === 0) {
        sendResponse(false, 'ไม่พบข้อมูลสต็อก');
    }
    
    $stock = $stockResult->fetch_assoc();
    $currentStock = floatval($stock['quantity']);
    
    if ($quantity > $currentStock) {
        sendResponse(false, "สต็อกไม่เพียงพอ (คงเหลือ: $currentStock กก.)");
    }

    // เริ่ม transaction
    $conn->begin_transaction();
    
    try {
        // บันทึกการขาย
        $riceTypeIdValue = $riceTypeId ? $riceTypeId : 'NULL';
        $sql = "INSERT INTO sales 
                (sale_date, product_type, rice_type_id, quantity, price_per_kg, total_amount) 
                VALUES 
                ('$saleDate', '$productType', $riceTypeIdValue, $quantity, $pricePerKg, $totalAmount)";
        
        if (!$conn->query($sql)) {
            throw new Exception('บันทึกข้อมูลล้มเหลว');
        }
        
        // ตัดสต็อก
        if ($productType === 'rice') {
            $updateStockSql = "UPDATE stock 
                              SET quantity = quantity - $quantity 
                              WHERE product_type = 'rice' AND rice_type_id = $riceTypeId";
        } else {
            $updateStockSql = "UPDATE stock 
                              SET quantity = quantity - $quantity 
                              WHERE product_type = '$productType' AND rice_type_id IS NULL";
        }
        
        if (!$conn->query($updateStockSql)) {
            throw new Exception('อัพเดทสต็อกล้มเหลว');
        }
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'บันทึกการขายสำเร็จ', ['total_amount' => $totalAmount]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        sendResponse(false, $e->getMessage());
    }

}



?>