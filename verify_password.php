<?php
session_start();

include 'php/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$oldPassword = $data['password'];

$email = $_SESSION['email'];

$sql = "SELECT password_hash FROM staff WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($currentPassword);
$stmt->fetch();
$stmt->close();

if (password_verify($oldPassword, $currentPassword)) {
    echo json_encode(['isValid' => true]);
} else {
    echo json_encode(['isValid' => false]);
}

$conn->close();
?>