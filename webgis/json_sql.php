<?php
include('conn.php');

// ================================
// json_lampang_accommodations.php : ดึงข้อมูลทั้งหมดเป็น GeoJSON
// ================================

$sql = "
    SELECT 
        id, 
        Name, 
        Description,
        Category,
        Address,
        Latitude,
        Longitude,
        Opening_hours,
        Rating,
        Facilities,
        ST_AsGeoJSON(geom) AS geojson
    FROM lampang_accommodations
    ORDER BY id DESC;
";

$result = pg_query($conn, $sql);
if (!$result) {
    die(json_encode([
        'status' => 'error',
        'message' => pg_last_error($conn)
    ]));
}

// สร้าง FeatureCollection
$geojson = [
    'type' => 'FeatureCollection',
    'features' => []
];

while ($row = pg_fetch_assoc($result)) {
    $geojson['features'][] = [
    'type' => 'Feature',
    'geometry' => json_decode($row['geojson']),
    'properties' => [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'category' => $row['category'],
        'address' => $row['address'],
        'latitude' => (float)$row['latitude'],
        'longitude' => (float)$row['longitude'],
        'opening_hours' => $row['opening_hours'],
        'rating' => isset($row['rating']) ? (float)$row['rating'] : null,
        'facilities' => $row['facilities']
    ]
];

}

// ส่ง JSON response
header('Content-Type: application/json; charset=utf-8');
echo json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

// ปิด connection
pg_close($conn);
?>
