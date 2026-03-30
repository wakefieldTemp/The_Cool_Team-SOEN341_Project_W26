<?php
function addMealToSchedule($conn, $userId, $recipe_id, $day, $meal_type){
    //Big no no for two meals in the same day for the same meal_type
        $check = $conn->prepare("SELECT schedule_id FROM meal_schedule WHERE user_id=? AND (recipe_id=? OR (day_of_week=? AND meal_type=?))");
        $check->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO meal_schedule (user_id, recipe_id, day_of_week, meal_type) VALUES (?,?,?,?)");
            $ins->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
            $ins->execute();
        }
		else{ 
			$_SESSION['duplicate_error'] = "You already have this recipe in your schedule.";
		}
}

function deleteMealFromSchedule($conn, $userId, $schedule_id){
    $schedule_id = intval($_POST['schedule_id']);
    $del = $conn->prepare("DELETE FROM meal_schedule WHERE schedule_id=? AND user_id=?");
    $del->bind_param('ii', $schedule_id, $userId);
    $del->execute();
}

function getMealsForSchedule($conn, $userId){
    $meals_query = $conn->prepare(" 
        SELECT ms.schedule_id, ms.day_of_week, ms.meal_type, r.recipe_name
        FROM meal_schedule ms
        JOIN recipes r ON ms.recipe_id = r.recipe_id
        WHERE ms.user_id = ?
        ORDER BY FIELD(ms.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                FIELD(ms.meal_type,'Breakfast','Lunch','Dinner','Snack') ");
    $meals_query->bind_param('i', $userId);
    $meals_query->execute();
    return $meals_query->get_result();
}
?>