<?php
$dbHost = '10.10.0.239';
$dbUsername = 'sistemas';
$dbPassword = 'rmv*7653Gums';
$dbName = 'transportegene03';


$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// if($conexao->connect_errno) {
//    echo "Erro";
// }  
// else {
//    echo "Conexão efetuada";
// }
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$host = "10.10.0.239";
$user = "sistemas";
$pass = "rmv*7653Gums";
$dbname = "transportegene03";
$port = 3306;

try {
    //Conexão com a porta
    //$conn = new PDO("mysql:host=$host;port=$port;dbname=" . $dbname, $user, $pass);

    //Conexão sem a porta
    $conn = new PDO("mysql:host=$host;dbname=" . $dbname, $user, $pass);
    // echo "Conexão com banco de dados realizado com sucesso.";
} catch (PDOException $err) {
    // echo "Erro: Conexão com banco de dados não realizado com sucesso. Erro gerado " . $err->getMessage();
}