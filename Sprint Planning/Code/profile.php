<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

if(isset($_POST['add_allergy'])) {
    $allergy_name = trim($_POST['allergy_name']);

    $first_query = "SELECT allergy_id FROM allergies WHERE allergy = ?";
    $first_result = $conn->prepare($first_query);
    $first_result->bind_param('s', $allergy_name);
    $first_result->execute();
    $first_result->store_result();

    if($first_result->num_rows > 0){
        $first_result->bind_result($allergy_id);
        $first_result->fetch();
    } else {
        $insert_query = "INSERT INTO allergies (allergy) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('s', $allergy_name);
        $insert_stmt->execute();
        $allergy_id = $conn->insert_id;
    }
    $first_result->close();
    $exist_query = "SELECT * FROM user_allergies WHERE user_id = ? AND allergy_id = ?";
    $exist_result = $conn->prepare($exist_query);
    $exist_result->bind_param('ii', $userId, $allergy_id);
    $exist_result->execute();
    $exist_result->store_result();

    if($exist_result->num_rows > 0){
        $exist_result->close();
        echo "<script>alert('Allergy already exists in your profile.');</script>";
    }
    else{
        $exist_result->close();
        $second_query = "INSERT INTO user_allergies (user_id, allergy_id) VALUES (?, ?)";
        $second_result = $conn->prepare($second_query);
        $second_result->bind_param('ii', $userId, $allergy_id);
        $second_result->execute();
    }

}

if(isset($_POST['delete_allergy'])) {
    $allergy_id = $_POST['allergy_id'];
    $delete_query = "DELETE FROM user_allergies WHERE user_id = ? AND allergy_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $userId, $allergy_id);
    $delete_stmt->execute();
}

//beginning of dp

if(isset($_POST['add_preference'])) {
    $preference_name = trim($_POST['preference_name']);

    $first_query = "SELECT preference_id FROM diet_preferences WHERE preference = ?";
    $first_result = $conn->prepare($first_query);
    $first_result->bind_param('s', $preference_name);
    $first_result->execute();
    $first_result->store_result();

    if($first_result->num_rows > 0){
        $first_result->bind_result($preference_id);
        $first_result->fetch();
    } else {
        $insert_query = "INSERT INTO diet_preferences (preference) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('s', $preference_name);
        $insert_stmt->execute();
        $preference_id = $conn->insert_id;
    }
    $first_result->close();
    $exist_query = "SELECT * FROM user_preferences WHERE user_id = ? AND preference_id = ?";
    $exist_result = $conn->prepare($exist_query);
    $exist_result->bind_param('ii', $userId, $preference_id);
    $exist_result->execute();
    $exist_result->store_result();

    if($exist_result->num_rows > 0){
        $exist_result->close();
        echo "<script>alert('Diet preference already exists in your profile.');</script>";
    }
    else{
        $exist_result->close();
        $second_query = "INSERT INTO user_preferences (user_id, preference_id) VALUES (?, ?)";
        $second_result = $conn->prepare($second_query);
        $second_result->bind_param('ii', $userId, $preference_id);
        $second_result->execute();
    }

}

if(isset($_POST['delete_preference'])) {
    $preference_id = $_POST['preference_id'];
    $delete_query = "DELETE FROM user_preferences WHERE user_id = ? AND preference_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $userId, $preference_id);
    $delete_stmt->execute();
}
// end of dp

$sql_query = "
    SELECT al.allergy_id, al.allergy
    FROM user_allergies ual
    JOIN allergies al ON ual.allergy_id = al.allergy_id
    WHERE ual.user_id = ?
    ORDER BY al.allergy
";

$stmt = $conn->prepare($sql_query);
$stmt->bind_param('i', $userId);  // 'i' for integer
$stmt->execute();
$result = $stmt->get_result();
$allergies = $result->fetch_all(MYSQLI_ASSOC);

$sql_query = "
    SELECT dp.preference_id, dp.preference
    FROM user_preferences udp
    JOIN diet_preferences dp ON udp.preference_id = dp.preference_id
    WHERE udp.user_id = ?
    ORDER BY dp.preference
";

$stmt = $conn->prepare($sql_query);
$stmt->bind_param('i', $userId);  // 'i' for integer
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_all(MYSQLI_ASSOC);

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="profile_page_style.css">
</head>

<body>

<header class="site-header">
    <div class="brand">
        <img class="logo" src="logo.jpg" alt="Logo">

        <div class="title">The Cool Team App</div>
    </div>

    <div class="back-button-container">
        <button class="btn btn-primary" onclick="window.location.href='main_menu.php'">
            Back to Main Page
        </button>
    </div>
</header>

<!-- ===== PAGE CONTENT ===== -->
<main class="page">
    <section class="card">

        <h2>Your Profile</h2>

        <div class="profile-info">
            <div class="profile-name">Name: <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            <div class="profile-email">Email: <?php echo htmlspecialchars($_SESSION['email']); ?></div>
        </div>

        <!-- ===== Allergies ===== -->
        <div class="card" style="margin-top: 16px;">
            <h3 style="margin-bottom: 12px;">Allergies</h3>

            <div class="row" style="margin-bottom: 12px;">
                <form method="POST" class="row">
                    <input type="text" name="allergy_name" placeholder="Allergy name" required>
                    <button type="submit" name="add_allergy" class="btn btn-primary">Add Allergy</button>
                </form>
            </div>

            <div class="list">
                <?php if (empty($allergies)): ?>
                    <div class="list-item">
                        <span>No allergies yet</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($allergies as $allergy): ?>
                        <div class="list-item">
                            <span><?php echo htmlspecialchars($allergy['allergy']); ?></span>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="allergy_id" value="<?php echo (int)$allergy['allergy_id']; ?>">
                                <button type="submit" name="delete_allergy" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== Dietary Preferences ===== -->
        <div class="card" style="margin-top: 16px;">
            <h3 style="margin-bottom: 12px;">Dietary Preferences</h3>

            <div class="row" style="margin-bottom: 12px;">
                <form method="POST" class="row">
                    <input type="text" name="preference_name" placeholder="Preference name" required>
                    <button type="submit" name="add_preference" class="btn btn-primary">Add Dietary Preference</button>
                </form>
            </div>

            <div class="list">
                <?php if (empty($preferences)): ?>
                    <div class="list-item">
                        <span>No dietary preferences</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($preferences as $preference): ?>
                        <div class="list-item">
                            <span><?php echo htmlspecialchars($preference['preference']); ?></span>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="preference_id" value="<?php echo (int)$preference['preference_id']; ?>">
                                <button type="submit" name="delete_preference" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </section>
</main>

</body>
</html>