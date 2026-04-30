<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../int_Public/Dos-page/conex.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceuil client</title>
    <link rel="stylesheet" href="../Dos-css/acceuil.css">
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
        <?php
           include'../Dos-include/header.php'; 
        ?>
    
</body>
</html>