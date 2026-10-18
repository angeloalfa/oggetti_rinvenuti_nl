<?php

require 'config/connect.php';      //connessione al database 
require_once 'functions/function.php';

$idrinvenimento = $_POST['idrinvenimento'];

$query = "SELECT tipioggetti.tipooggetto,descrizioneoggetto 
            FROM oggetti2.listaoggetti
            LEFT JOIN oggetti2.tipioggetti ON tipioggetti.idtipioggetti=listaoggetti.tipooggetto
            WHERE idrinvenimento=$idrinvenimento";

$result = mysqli_query($conn,$query) or handle_error("Errore nella selezione del database in get_listaoggetti", $query);
?>

<table id='table_popup'>
    <tr><th colspan='2'>Rinvenimento nr.<?php echo $idrinvenimento ?></th></tr>
    <tr>
        <th>Tipo Oggetto</th>
        <th>Descrizione</th>
    </tr>
    <?php while ($rows = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?php echo $rows['tipooggetto'] ?></td>
        <td><?php echo $rows['descrizioneoggetto'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>