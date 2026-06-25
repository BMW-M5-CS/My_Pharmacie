<?php
session_start();

$_SESSION = array();
session_destroy();
header("Location: ../../int_Public/Dos-page/conex.php");
exit();
?>