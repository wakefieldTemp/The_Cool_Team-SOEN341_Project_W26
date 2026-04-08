<?php

function addPreference($userId, $preference_name) {
    global $conn;
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

function deletePreference($preference_id, $userId) {
    global $conn;
    $delete_query = "DELETE FROM user_preferences WHERE user_id = ? AND preference_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $userId, $preference_id);
    $delete_stmt->execute();
}

function getPreferences($userId) {
    global $conn;
    $sql_query = "
        SELECT dp.preference_id, dp.preference
        FROM user_preferences udp
        JOIN diet_preferences dp ON udp.preference_id = dp.preference_id
        WHERE udp.user_id = ?
        ORDER BY dp.preference
    ";

    // Calorie goal stuff
    $stmt = $conn->prepare($sql_query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}