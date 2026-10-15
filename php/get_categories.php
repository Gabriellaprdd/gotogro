<?php

include 'config.php';

$query = "SELECT DISTINCT category FROM product";

$result = $conn->query($query);

if ($result) {
    $categories = [];

    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    echo json_encode($categories);

} else {
    echo "Error: " . $conn->error;
}

$conn->close();

?>