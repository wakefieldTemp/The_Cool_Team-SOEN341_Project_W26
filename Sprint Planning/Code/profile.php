<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

// Pour Charles, tu va copier cette section la, mais utilsie les noms des tablse de preferences instead
if(isset($_POST['add_allergy'])) {
    $allergy_name = trim($_POST['allergy_name']);

    $first_query = "SELECT allergy_id FROM allergies WHERE allergy = ?";
    $first_stmt = $conn->prepare($first_query);
    $first_stmt->bind_param('s', $allergy_name);
    $first_stmt->execute();
    $first_stmt->store_result();

    if($first_stmt->num_rows > 0){
        $first_stmt->bind_result($allergy_id);
        $first_stmt->fetch();
    } else {
        $insert_query = "INSERT INTO allergies (allergy) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('s', $allergy_name);
        $insert_stmt->execute();
        $allergy_id = $conn->insert_id;
    }

    $first_stmt->close();
    $second_query = "INSERT INTO user_allergies (user_id, allergy_id) VALUES (?, ?)";
    $second_stmt = $conn->prepare($second_query);
    $second_stmt->bind_param('ii', $userId, $allergy_id);
    $second_stmt->execute();
}

if(isset($_POST['delete_allergy'])) {
    $allergy_id = $_POST['allergy_id'];
    $delete_query = "DELETE FROM user_allergies WHERE user_id = ? AND allergy_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $userId, $allergy_id);
    $delete_stmt->execute();
}
//************************************************************************************************** */

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
<html lang ="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="profile_page_style.css">
</head>


<body>

    <div class="profile-container">
        <h2>Your Profile</h2>
        <?php
            echo "<p class='profile-name'> Name: " . $_SESSION['name'] . "</p>";
            echo "<p class='profile-email'> Email: " . $_SESSION['email'] . "</p>";
        ?>
        <!-- Il y a aussi la section des inputs et buttons des allergies, legit jsute copy paste it over ce quil y a deja pour rpeferences, mais jsute change pour avoir les bons noms -->
        <div class="allergies-section">
        <table class="allergies-table">
            <thead>
                <tr>
                    <th><h3>Allergies</h3></th>
                    <th>
                        <h3>
                        <form method="POST" style="display:inline;">
                            <input type="text" name="allergy_name" placeholder="Allergy name" required>
                            <button type="submit" name="add_allergy">Add Allergy</button>
                        </form>
                        </h3>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allergies as $allergy): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($allergy['allergy']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="allergy_id" value="<?php echo $allergy['allergy_id']; ?>">
                                <button type="submit" name="delete_allergy">Delete</button>                                          
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="preferences-section">
            <h3>Dietary Preferences</h3>
            <?php if (empty($preferences)): ?>
                <p class="no-preferences">No dietary preferences</p>
            <?php else: ?>
                <table class="preferences-table">
                    <tbody>
                        <?php foreach ($preferences as $preference): ?>
                            <tr>
                                <td><?php echo ($preference['preference']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>