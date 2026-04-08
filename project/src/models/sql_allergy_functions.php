<?php
function addAllergy($allergy_name, $userId){
    global $conn;
    // Creat SQL query to add the allergy to the table
    $first_query = "SELECT allergy_id FROM allergies WHERE allergy = ?";
    $first_result = $conn->prepare($first_query);
    $first_result->bind_param('s', $allergy_name);
    $first_result->execute();
    $first_result->store_result();

    // Since there's two tables for allergies (allergies and user_allergies, we need to make sure that alelrgy doesn't already exist)
    // If it already exist, get its id
    if($first_result->num_rows > 0){
        $first_result->bind_result($allergy_id);
        $first_result->fetch();
    } else { // Otherwise add it to the allergies table
        $insert_query = "INSERT INTO allergies (allergy) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('s', $allergy_name);
        $insert_stmt->execute();
        $allergy_id = $conn->insert_id;
    }
    $first_result->close();
    // Then associate the allergy wih the user (using ids)
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

function deleteAllergy($allergy_id, $userId){
    global $conn;
    $delete_query = "DELETE FROM user_allergies WHERE user_id = ? AND allergy_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $userId, $allergy_id);
    $delete_stmt->execute();
}

function getAllergies($userId){
    global $conn;
    /* This section is for displaying the allergies and preferences
    Basically we get the information (ids) of allergies and preferences using a query with the user id*/
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
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>