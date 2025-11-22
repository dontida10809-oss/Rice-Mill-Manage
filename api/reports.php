<?php
require_once 'config.php';
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

switch ($action) {
    case 'dashboard':
        getDashboard();
        break;
    case 'stock':
        getStock();
        break;
    case 'reports':
        getReports();
        break;
    default:
        sendResponse(false, 'Action not found');
}
//ดึงข้อม฿ลdashboard
function getDashboard() {
    global $conn;

    $today = date('Y-m-d');
    
    // จำนวนประเภทข้าว
    $riceTypesSql = "SELECT COUNT(*) as count FROM rice_types";
    $riceTypesResult = $conn->query($riceTypesSql);
    $riceTypesCount = $riceTypesResult->fetch_assoc()['count'];
    
    // รับข้าวเปลือกวันนี้
    $todayReceivedSql = "SELECT COALESCE(SUM(weight), 0) as total 
                         FROM receives 
                         WHERE receive_date = '$today'";
    $todayReceivedResult = $conn->query($todayReceivedSql);
    $todayReceived = $todayReceivedResult->fetch_assoc()['total'];
    
    // ยอดขายวันนี้
    $todaySalesSql = "SELECT COALESCE(SUM(total_amount), 0) as total 
                      FROM sales 
                      WHERE sale_date = '$today'";
    $todaySalesResult = $conn->query($todaySalesSql);
    $todaySales = $todaySalesResult->fetch_assoc()['total'];
    
    // สต็อกรวม
    $totalStockSql = "SELECT COALESCE(SUM(quantity), 0) as total FROM stock";
    $totalStockResult = $conn->query($totalStockSql);
    $totalStock = $totalStockResult->fetch_assoc()['total'];
    
    $data = [
        'total_rice_types' => intval($riceTypesCount),
        'today_received' => floatval($todayReceived),
        'today_sales' => floatval($todaySales),
        'total_stock' => floatval($totalStock)
    ];
    
    sendResponse(true, 'ดึงข้อมูลสำเร็จ', $data);
}

//ดึงข้อมูลสต็อกปัจจุบัน
function getStock() {
    global $conn;
    
    $sql = "SELECT s.*, rt.name as rice_type_name 
            FROM stock s 
            LEFT JOIN rice_types rt ON s.rice_type_id = rt.id 
            ORDER BY s.product_type, rt.name";
    
    $result = $conn->query($sql);
    
    $stock = [];
    while ($row = $result->fetch_assoc()) {
        $stock[] = [
            'id' => $row['id'],
            'product_type' => $row['product_type'],
            'rice_type_id' => $row['rice_type_id'],
            'rice_type_name' => $row['rice_type_name'],
            'quantity' => floatval($row['quantity']),
            'updated_at' => $row['updated_at']
        ];
    }
    
    sendResponse(true, 'ดึงข้อมูลสำเร็จ', $stock);
}



    //ึงข้อมูลรายงานต่างๆ
function getReports() {
    global $conn;
    
    // รายงานรวม
    $totalReceivedSql = "SELECT COALESCE(SUM(weight), 0) as total FROM receives";
    $totalReceivedResult = $conn->query($totalReceivedSql);
    $totalReceived = $totalReceivedResult->fetch_assoc()['total'];
    
    $totalSalesSql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales";
    $totalSalesResult = $conn->query($totalSalesSql);
    $totalSales = $totalSalesResult->fetch_assoc()['total'];
    
    $riceStockSql = "SELECT COALESCE(SUM(quantity), 0) as total 
                     FROM stock 
                     WHERE product_type = 'rice'";
    $riceStockResult = $conn->query($riceStockSql);
    $riceStock = $riceStockResult->fetch_assoc()['total'];
    
    $byproductStockSql = "SELECT COALESCE(SUM(quantity), 0) as total 
                          FROM stock 
                          WHERE product_type IN ('broken', 'bran', 'husk')";
    $byproductStockResult = $conn->query($byproductStockSql);
    $byproductStock = $byproductStockResult->fetch_assoc()['total'];
    
    // รายงานแยกตามประเภทข้าว
    $byTypeSql = "SELECT 
                    rt.id,
                    rt.name,
                    COALESCE(SUM(r.weight), 0) as total_received,
                    COALESCE(SUM(r.white_rice), 0) as total_white_rice,
                    COALESCE(s.quantity, 0) as current_stock
                  FROM rice_types rt
                  LEFT JOIN receives r ON rt.id = r.rice_type_id
                  LEFT JOIN stock s ON rt.id = s.rice_type_id AND s.product_type = 'rice'
                  GROUP BY rt.id, rt.name, s.quantity
                  ORDER BY rt.name";
    
    $byTypeResult = $conn->query($byTypeSql);
    
    $byType = [];
    while ($row = $byTypeResult->fetch_assoc()) {
        $byType[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'total_received' => floatval($row['total_received']),
            'total_white_rice' => floatval($row['total_white_rice']),
            'current_stock' => floatval($row['current_stock'])
        ];
    }
    
    $data = [
        'summary' => [
            'total_received' => floatval($totalReceived),
            'total_sales' => floatval($totalSales),
            'rice_stock' => floatval($riceStock),
            'byproduct_stock' => floatval($byproductStock)
        ],
        'by_type' => $byType
    ];
    
    sendResponse(true, 'ดึงข้อมูลสำเร็จ', $data);
}

?>