<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

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
        <div class="allergies-section">
            <h3>Allergies</h3>
            <?php if (empty($allergies)): ?>
                <p class="no-allergies">No allergies</p>
            <?php else: ?>
                <table class="allergies-table">
                    <tbody>
                        <?php foreach ($allergies as $allergy): ?>
                            <tr>
                                <td><?php echo ($allergy['allergy']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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