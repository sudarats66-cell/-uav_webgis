<?php
include('conn.php'); // เชื่อมต่อฐานข้อมูล

// ================================
// insert_lampang_accommodation.php : เพิ่มข้อมูลที่พัก/ร้านคาเฟ่ใหม่
// ================================

// รับค่าจาก Frontend ผ่าน POST
$name          = $_POST['name']          ?? null;
$description   = $_POST['description']   ?? null;
$category      = $_POST['category']      ?? null;
$address       = $_POST['address']       ?? null;
$latitude      = $_POST['latitude']      ?? null;
$longitude     = $_POST['longitude']     ?? null;
$opening_hours = $_POST['opening_hours'] ?? null;
$rating        = $_POST['rating']        ?? null;
$facilities    = $_POST['facilities']    ?? null;

// ตรวจสอบค่าที่จำเป็น
if (!$name || !$latitude || !$longitude) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['status'=>'error','message'=>'ข้อมูลไม่ครบ (ต้องมี Name, Latitude, Longitude)']));
}

// ป้องกัน SQL Injection
$name          = pg_escape_string($conn, $name);
$description   = pg_escape_string($conn, $description ?? '');
$category      = pg_escape_string($conn, $category ?? '');
$address       = pg_escape_string($conn, $address ?? '');
$opening_hours = pg_escape_string($conn, $opening_hours ?? '');
$facilities    = pg_escape_string($conn, $facilities ?? '');
$rating        = is_numeric($rating) ? $rating : 'NULL';

// คำสั่ง SQL เพิ่มข้อมูล
$sql = "
INSERT INTO lampang_accommodations 
(Name, Description, Category, Address, Latitude, Longitude, Opening_hours, Rating, Facilities, geom)
VALUES (
    '$name',
    '$description',
    '$category',
    '$address',
    $latitude,
    $longitude,
    '$opening_hours',
    $rating,
    '$facilities',
    ST_SetSRID(ST_Point($longitude, $latitude), 4326)
);
";

// รัน SQL
$result = pg_query($conn, $sql);

if (!$result) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Insert failed: ' . pg_last_error($conn)
    ]));
}

// ส่งผลลัพธ์กลับ
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status'=>'success', 'message'=>'เพิ่มข้อมูลเรียบร้อยแล้ว']);

// ปิดการเชื่อมต่อ
pg_close($conn);
?>
