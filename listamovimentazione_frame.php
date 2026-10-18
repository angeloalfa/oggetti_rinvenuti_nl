<?php
require 'config/connect.php'; //connessione al database            
require_once 'functions/function.php';
$idrinvenimento = $_REQUEST['idrinvenimento'];
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Lista Oggetti</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href='css/all.css' rel='stylesheet' type='text/css'/>
    </head>
    <body>
        <table width="100%">
            <tr class='tableheader'>
                <td width=5%>Data</td>
                <td width=5%>Ora</td>
                <td width=20%>Operatore</td>
                <td width=10%>Posizione</td>
                <td width=10%>Stato</td>
                <td width=50%>Note</td>
            </tr>
            <?php
            $query = "SELECT * FROM oggetti2.movimentazionioggetti where idrinvenimento='$idrinvenimento' order by idmovimentazionioggetto desc";
            $listamovimenti = mysqli_query($conn,$query);
            while ($rows = mysqli_fetch_array($listamovimenti)) {
                $data = convertiDataAE($rows['datamovimentazione']);
                $ora = $rows['oramovimentazione'];
                $operatore = $rows['operatore'];
                $posizione = $rows['posizione'];
                $stato = $rows['statomovimentazione'];
                $note = $rows['notemovimentazione'];
                echo "
            <tr class='tablerow'>
                <td>$data</td>
                <td>$ora</td>
                <td>$operatore</td>
                <td>$posizione</td>
                <td>$stato</td>
                <td>$note</td>
            </tr>
            ";
            }
            echo "
        </table>
    </body>
</html>
         ";
?>
