<?php
/*
// en local
$server = 'localhost';
$login = 'root';
$mdp = '';
$db = 'api-authentification_utilisateurs';

try {
    $linkpdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8", $login, $mdp);
} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
*/
?>


<?php
    // en ligne 
    $server = 'mysql-api-authentification.alwaysdata.net';
    $login = '350886';
    $mdp = '$iutinfo';
    $db = 'api-authentification_utilisateurs';

    try {
        $linkpdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8", $login, $mdp);
    } catch (PDOException $e) {
        die('Erreur : ' . $e->getMessage());
    }
?>
