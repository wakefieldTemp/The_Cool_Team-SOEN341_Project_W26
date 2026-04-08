<?php 
      include __DIR__ . '/../controllers/preference_post.php';
      include __DIR__ . '/../controllers/allergy_post.php'; 
      include __DIR__ . '/../controllers/calories_post.php'; 
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="<?= BASE_URL ?> /public/css/profile_page_style.css">
</head>

<body>

<header class="site-header">
    <div class="brand">
        <img class="logo" src="<?= BASE_URL ?> /public/images/logo.jpg" alt="Logo">

        <div class="title">The Cool Team App</div>
    </div>

    <div class="back-button-container">
        <button class="btn btn-primary" onclick="window.location.href='<?= BASE_URL ?>/src/views/main_menu.php'">
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
                <!-- Here we use the ids we got at the beginning and fetch them from the tables and display them -->
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
        <!-- ===== Calorie Goal ===== -->
        <div class="card">
            <h3>Daily Calorie Goal</h3>

            <?php if ($current_goal): ?>
                <div class="list-item">
                    <span>Current goal: <strong><?= $current_goal ?> kcal</strong></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="row">
                <input type="number" name="daily_goal" placeholder="e.g. 2000"
                    min="500" max="9999" required
                    value="<?= $current_goal ?? '' ?>">
                <button type="submit" name="set_goal" class="btn btn-primary">
                    <?= $current_goal ? 'Update Goal' : 'Set Goal' ?>
                </button>
            </form>
        </div>
    </section>
</main>

</body>
</html>
