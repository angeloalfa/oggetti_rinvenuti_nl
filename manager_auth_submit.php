<?php

require 'config/connect.php';      //connessione al database 
require_once 'functions/function.php';

$perid = $_REQUEST['perid'];
$operatore = $_REQUEST['operatore'];
$livello = $_REQUEST['livello'];
$note = $_REQUEST['note'];

// Aggiorna o inserisce un record
$query = "SELECT * FROM oggetti2.autorizzazioni WHERE perid=$perid";
$result = mysqli_query($conn,$query);
$row = mysqli_fetch_assoc($result);
$idautorizzazioni = $row['idautorizzazioni'];
if (isset($idautorizzazioni))    {
    $query_update = "UPDATE oggetti2.autorizzazioni SET perid='$perid', nome='$operatore', livello='$livello', note='$note' WHERE idautorizzazioni='$idautorizzazioni'";
    mysqli_query($conn,$query_update);
}
else    {
    $query_insert = "INSERT INTO oggetti2.autorizzazioni (perid,nome,livello,note) VALUES ('$perid','$operatore','$livello','$note')";
    mysqli_query($conn,$query_insert);
}

$query_auth = "SELECT * FROM oggetti2.autorizzazioni";
$result_auth = mysqli_query($conn,$query_auth);
while ($rows_auth = mysqli_fetch_assoc($result_auth)) {
    $idautorizzazioni = $rows_auth['idautorizzazioni'];
    $perid = $rows_auth['perid'];
    $operatore = $rows_auth['nome'];
    $livello = $rows_auth['livello'];
    $note = $rows_auth['note'];

    echo "
    <tr class='tableheader'>
        <td>" . $perid . "</td>
        <td>" . $operatore . "</td>
        <td align='center'>" . $livello . "</td>
        <td align='center'>" . $note . "</td>
        <td align='center'>
            <a href='manager_auth.php?id=$idautorizzazioni'>
                <button type='button'>Modifica</button>
            </a>
        </td>
    </tr>
    ";
}
?>
