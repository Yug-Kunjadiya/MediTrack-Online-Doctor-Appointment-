<?php
require_once '../config/db.php'; // Includes session_start(), but not strictly needed for GET if public
header('Content-Type: application/json'); // Set content type to JSON

if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID is required.']);
    exit;
}

$doctor_id = (int)$_GET['doctor_id'];
$response = ['success' => false, 'average_rating' => 0, 'total_reviews' => 0, 'reviews' => []];

// Calculate Average Rating and Total Reviews
$sql_avg = "SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE doctor_id = ?";
$stmt_avg = mysqli_prepare($conn, $sql_avg);
mysqli_stmt_bind_param($stmt_avg, "i", $doctor_id);
mysqli_stmt_execute($stmt_avg);
$result_avg = mysqli_stmt_get_result($stmt_avg);
if ($row_avg = mysqli_fetch_assoc($result_avg)) {
    $response['average_rating'] = $row_avg['avg_rating'] ? round($row_avg['avg_rating'], 1) : 0;
    $response['total_reviews'] = (int)$row_avg['total_reviews'];
}
mysqli_stmt_close($stmt_avg);

// Fetch Reviews (e.g., latest 5 or all, add pagination later if needed)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5; // Default to 5 reviews
$sql_reviews = "SELECT r.rating, r.comment, r.created_at, u.name as user_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.doctor_id = ? 
                ORDER BY r.created_at DESC
                LIMIT ?";
$stmt_reviews = mysqli_prepare($conn, $sql_reviews);
mysqli_stmt_bind_param($stmt_reviews, "ii", $doctor_id, $limit);
mysqli_stmt_execute($stmt_reviews);
$result_reviews = mysqli_stmt_get_result($stmt_reviews);

$reviews_data = [];
while ($row = mysqli_fetch_assoc($result_reviews)) {
    $reviews_data[] = [
        'user_name' => htmlspecialchars($row['user_name']),
        'rating' => (int)$row['rating'],
        'comment' => nl2br(htmlspecialchars($row['comment'])), // nl2br to preserve line breaks
        'created_at' => date("M j, Y g:i A", strtotime($row['created_at']))
    ];
}
mysqli_stmt_close($stmt_reviews);

$response['success'] = true;
$response['reviews'] = $reviews_data;

mysqli_close($conn);
echo json_encode($response);
exit;
?>