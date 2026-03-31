<?php
session_start();
require_once __DIR__ . '/../../config/login_page_config.php';
session_unset();
session_destroy();
header("Location: " . BASE_URL . "/index.php");
exit();
?>