<?php
/*
// en local
$server = 'localhost';
$login = 'root';
$mdp = '';
$db = 'projet_api';

try {
    $linkpdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8", $login, $mdp);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
*/
?>


<?php
// en ligne 
$server = 'mysql-beaujouripiceanu.alwaysdata.net';
$login = '350795';
$mdp = '$Iutinfo2024';
$db = 'beaujouripiceanu_cabinet_medical';

try {
    $linkpdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8", $login, $mdp);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
