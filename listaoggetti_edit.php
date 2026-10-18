<?php
require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

echo " 
                   <tr>
                      <td>
    ";

$idrinvenimento = $_REQUEST['idrinvenimento'];
?>

<script type="text/javascript">
    function seleziona() {
        $(':checkbox').attr('checked', true);
    }
    function deseleziona() {
        location.reload();
    }
    function invia() {
        var mycheck = new Array();
        var idrinv = <?php echo $idrinvenimento ?>;
        $("#mycheck:checked").each(function() {
            mycheck.push($(this).val());
            location.href = "inviaBologna.php?idrinvenimento=" + idrinv + "&mycheck=" + mycheck;
        });
    }
</script>
<table align='center'>
    <tr>
        <td class='tabletitle'>Lista oggetti rinvenuti relativi al rinvenimento Nr: <?php echo $idrinvenimento ?></td>
    </tr>
</table>
<br/>
<table>
    <tr class='tableheader'>
        <td width=50>Id</td>
        <td width=140>Tipo Oggetto</td>
        <td width=600>Descrizione Oggetto</td>
        <td width=100>Stato</td>
        <td width=100>   </td>
    </tr>

    <?php
    $query = "SELECT * FROM oggetti2.listaoggettiplus where idrinvenimento='$idrinvenimento'";
    $listaoggetti = mysqli_query($conn,$query);
    while ($rows = mysqli_fetch_array($listaoggetti)) {
        $id = $rows['idlistaoggetti'];
        $tipooggetto = $rows['tipooggetto'];
        $descrizioneoggetto = $rows['descrizioneoggetto'];
        $statooggetto = $rows['statooggetto'];
        echo "
            <tr class='tablerow'>
                <td>$id</td>
                <td>$tipooggetto</td>
                <td>$descrizioneoggetto</td>
                <td>$statooggetto</td>
                <td>
                   <a href='oggetto_edit.php?idrinvenimento=$idrinvenimento&idoggetto=$id'>
                       <button type='button'>Modifica</button>
                   </a>
                </td>
            </tr>
        ";
    }
    ?>
</table>
<br/>
<table>
    <tr>
        <td>
            <a href='rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento ?>'>
                <button>Indietro</button>
            </a>
        </td>
    </tr>
</table>

</td>
</tr>

<?php require_once 'config/footer.php'; ?>
