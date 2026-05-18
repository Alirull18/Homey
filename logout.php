<?php
// // Cookies remove
session_start();
session_destroy();
header("Location: login.php");
exit;
?>
