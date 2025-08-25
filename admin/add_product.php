<?php
header("Content-Type: application/json");

// DB connection
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $stock = $_POST['stock'];
    $specification = $_POST['specification'];
    $description = $_POST['description'];

    // Handle Variants
    $variants = [];
    if (!empty($_POST['variant_name']) && !empty($_POST['variant_value'])) {
        foreach ($_POST['variant_name'] as $key => $variant_name) {
            $variant_value = $_POST['variant_value'][$key];
            $variants[] = ["name" => $variant_name, "value" => $variant_value];
        }
    }
    $variants_json = json_encode($variants);

    // Handle Image Upload
    $uploadDir = "uploads/products/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $images = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $imageName) {
            $tmpName = $_FILES['images']['tmp_name'][$key];
            $newName = uniqid() . "_" . basename($imageName);
            $targetFile = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                $images[] = $targetFile;
            }
        }
    }
    $images_json = json_encode($images);

    // Insert into DB
    $sql = "INSERT INTO products (name, price, category, quantity, stock, specification, description, variants, images)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsdissss", $product_name, $price, $category, $quantity, $stock, $specification, $description, $variants_json, $images_json);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
