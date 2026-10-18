<?php

require 'config/connect.php';      //connessione al database 
require_once 'functions/function.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];
$visto = $_REQUEST['visto'];

if ($idrinvenimento != '' && $visto != '') {
    echo $query = "UPDATE oggetti2.rinvenimenti SET visto='$visto' WHERE idrinvenimento=$idrinvenimento";
    mysqli_query($conn,$query) or die("Query non valida:<br/>" . $query . "<br/>" . mysqli_error($conn));
}
mysqli_close($conn);
?>
