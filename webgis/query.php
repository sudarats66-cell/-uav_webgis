<?php
include('conn.php');

// ================================
// query_lampang_accommodations.php : ค้นหาร้าน/ที่พักในรัศมี (เมตร)
// ================================

// รับค่าพิกัดจาก Frontend (ผ่าน GET)
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;
$distance_meters = isset($_GET['distance']) ? floatval($_GET['distance']) : 500; // ค่าเริ่มต้น 500 m

if ($lat == 0 && $lng == 0) {
    die(json_encode(['status'=>'error','message'=>'กรุณาส่งค่าพิกัด lat/lng']));
}

// SQL: ค้นหาในรัศมี
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
        ST_AsGeoJSON(geom) AS geojson,
        ROUND(
            ST_Distance(
                geom::geography,
                ST_SetSRID(ST_MakePoint($lng, $lat), 4326)::geography
            )
        ) AS distance_m
    FROM lampang_accommodations
    WHERE ST_DWithin(
        geom::geography,
        ST_SetSRID(ST_MakePoint($lng, $lat), 4326)::geography,
        $distance_meters
    )
    ORDER BY distance_m ASC;
";

$result = pg_query($conn, $sql);
if (!$result) {
    die(json_encode(['status'=>'error', 'message'=>pg_last_error($conn)]));
}

// แปลงผลลัพธ์เป็น GeoJSON
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
            'name' => $row['Name'],
            'description' => $row['Description'],
            'category' => $row['Category'],
            'address' => $row['Address'],
            'latitude' => (float)$row['Latitude'],
            'longitude' => (float)$row['Longitude'],
            'opening_hours' => $row['Opening_hours'],
            'rating' => isset($row['Rating']) ? (float)$row['Rating'] : null,
            'facilities' => $row['Facilities'],
            'distance_m' => (float)$row['distance_m']
        ]
    ];
}

// ส่งออกเป็น JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

pg_close($conn);
?>
