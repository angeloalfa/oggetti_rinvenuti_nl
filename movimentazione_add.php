<?php
require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];

$query = "SELECT posizione FROM oggetti2.movimentazionioggetti  WHERE idrinvenimento=$idrinvenimento ORDER BY idmovimentazionioggetto DESC LIMIT 1";
$result_query = mysqli_query($conn,$query);
$movimentazioni = mysqli_fetch_array($result_query);
echo $lastposizione = $movimentazioni['posizione'];
?>

<tr>
    <td>
        <form name='datapost' method='post' action='movimentazione_submit.php?idrinvenimento=<?php echo $idrinvenimento ?>'>
            <input type='hidden' name='lastposizione' value='<?php echo $lastposizione ?>'/>
            <table border='0' align='center'>           
                <tr>
                    <td class='tabletitle' colspan=4>Nuova movimentazione oggetto</td>
                </tr>
                <tr>
                    <td class='tableinsertlabel'>Posizione attuale: </td>
                    <td class='tablerowfh'><?php echo $lastposizione ?></td>
                    <td class='tableinsertlabel'>Nuova Posizione: </td>
                    <td class='tablerowfh'>
                        <select name='posizione' id='posizione'>
                            <option value=''></option>
                            <?php foreach ($posizione_rinvenimento as $posizione_rinvenimento_value): ?>
                                <option value='<?php echo $posizione_rinvenimento_value ?>'><?php echo $posizione_rinvenimento_value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class='tableinsertlabel'>Note: </td>
                    <td colspan='3' class='tablerowfh'>
                        <textarea rows='4' cols='80' name='notemovimentazioni' maxlength='300'></textarea>
                    </td>
                </tr>
            </table>
            <center><input type='submit' value='Salva'/></center>
        </form>
        <center>
            <a href='listamovimentazione.php?idrinvenimento=<?php echo $idrinvenimento ?>'><button>Annulla</button></a>
        </center>
    </td>
</tr>

<?php echo $lastposizione; ?>

<script>
    $(document).ready(function() {
        var lastpos = '<?php echo $lastposizione; ?>';
        console.log(lastpos);
        $('#posizione').val(lastpos);
    });
</script>

<?php require_once 'config/footer.php' ?>