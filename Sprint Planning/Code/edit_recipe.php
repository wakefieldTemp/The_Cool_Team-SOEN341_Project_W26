<?php
require 'login_page_config.php';

if (!isset($_GET['id'])) {
    header("Location: recipes.php");
    exit();
}

$id = intval($_GET['id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];

    $sql = "UPDATE recipes SET name='$name' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: recipes.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

$sql = "SELECT * FROM recipes WHERE id=$id";
$result = $conn->query($sql);
$recipe = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Recipe</title>
</head>
<body>

<h2>Edit Recipe</h2>

<form method="POST">
    <label>Name:</label>
    <input type="text" name="name" value="<?php echo $recipe['name']; ?>" required>
    <br><br>
    <button type="submit">Update</button>
</form>

</body>
</html>
