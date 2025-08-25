<?php
include "db.php"; // adjust path to your DB connection

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_name = trim($_POST["category_name"]);

    if (empty($category_name)) {
        echo json_encode(["status" => "error", "message" => "Category name is required"]);
        exit;
    }

    // Check if category already exists
    $stmt = $conn->prepare("SELECT id FROM category WHERE cat_name = ?");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Category already exists"]);
    } else {
        $stmt = $conn->prepare("INSERT INTO category (cat_name) VALUES (?)");
        $stmt->bind_param("s", $category_name);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Category added successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add category"]);
        }
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
