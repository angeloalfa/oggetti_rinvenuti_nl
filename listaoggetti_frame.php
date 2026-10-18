<?php
    require 'config/connect.php';
    $idrinvenimento=$_REQUEST['idrinvenimento'];
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Lista Oggetti</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href='css/all.css' rel='stylesheet' type='text/css'/>
    </head>
    <body>
        <table  width=100%>
            <tr class='tableheader'>
                <td width=20%>Tipo Oggetto</td>
                <td width=60%>Descrizione Oggetto</td>
                <td width=20%>Stato</td>
            </tr>
<?php
    $query="SELECT * FROM oggetti2.listaoggettiplus where idrinvenimento='$idrinvenimento'";
    $listaoggetti = mysqli_query($conn,$query);
    while($rows = mysqli_fetch_array($listaoggetti))  {
        $tipooggetto=$rows['tipooggetto'];
        $descrizioneoggetto=$rows['descrizioneoggetto'];
        $statoogetto=$rows['statooggetto'];
        echo "
            <tr class='tablerow'>
                <td>$tipooggetto</td>
                <td>$descrizioneoggetto</td>
                <td>$statoogetto</td>
            </tr>
            ";
    }
    echo "
        </table>
    </body>
</html>
        ";
?>

