<?php
require 'login_page_config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "DELETE FROM recipes WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        header("Location: recipes.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>
