<?php // Do not put any HTML above this line

session_start();
unset($_SESSION['name']);
unset($_SESSION['user_id']);
header('Location: index.php');
  
?>
