<?php
require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];
$idoggetto = $_REQUEST['idoggetto'];

$query = "SELECT * FROM oggetti2.listaoggettiplus where idrinvenimento='$idrinvenimento' AND idlistaoggetti='$idoggetto'";
$oggetto = mysqli_query($conn, $query);
$row_oggetto = mysqli_fetch_array($oggetto);
$tipooggetto = $row_oggetto['tipooggetto'];
$descrizioneoggetto = $row_oggetto['descrizioneoggetto'];
$statooggetto = $row_oggetto['statooggetto'];
$idstatooggetto = $row_oggetto['idstatooggetto'];
$noteoggetto = $row_oggetto['noteoggetto'];
$inviatoa = $row_oggetto['inviatoa'];
$select = mysqli_query($conn, "SELECT * FROM oggetti2.statooggetto");
$row_statooggetto = mysqli_fetch_all($select, MYSQLI_ASSOC);
?>



<tr>
    <td>
        <form name='datapost' method='post' action='oggetto_edit_submit.php?idrinvenimento=<?php echo $idrinvenimento?>&idoggetto=<?php echo $idoggetto?>'>
            <table align='center'>
                <tr>
                    <td class='tabletitle'>Dettagli oggetto: <?php echo $idoggetto ?> relativo al rinvenimento Nr: <?php echo $idrinvenimento ?></td>
                </tr>
            </table
            <br/><br/>
            <table>
                <tr>
                    <td class='tableinsertlabel' width=200>Tipo Oggetto: </td>
                    <td class='tablerowfh' width=590 colspan=3><?php echo $tipooggetto ?></td>
                </tr>
                <tr>
                    <td class='tableinsertlabel' width=200>Descrizione Oggetto: </td>
                    <td class='tablerowfh' width=590 colspan=3><?php echo $descrizioneoggetto ?></td>
                </tr>
                <tr>
                    <td class='tableinsertlabel'>Stato Oggetto: </td>
                    <td class='tablerowfh'>
                        <select name='statooggetto' id='selectvalue'>
                            <?php foreach ($row_statooggetto as $value): ?>
                                <option value='<?php echo $value["idstatooggetto"] ?>'><?php echo $value["statooggetto"] ?></option>
                            <?php endforeach ?>
                        </select>
                    </td>
                </tr>



                <tr class='tableinsertlabel visual1'>
                    <td width='20%'>Ufficio a cui trasmettere</td>
                    <td width='20%' class='tipo_uff_trasm'>Del</td>
                    <td width='60%'>Città</td>
                </tr>
                <tr class='visual1'>
                    <td class='tablerowfh'>
                        <select name='ufficio_trasm' id='ufficio_t'>
                            <option></option>
                            <?php foreach ($ufficio_trasmissione as $i => $value): ?>
                                <option value='<?php echo $i ?>'><?php echo $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class='tipo_uff_trasm'><input type='text' name='tipo_uff_trasm' id='tipo_uff_trasm' value='<?php echo $tipo_uff_trasm ?>'/></td>
                    <td><input type='text' name='citta_trasm'  id='citta' value='<?php echo $citta_trasm ?>' size='60'/></td>
                </tr>
                
                <tr class='tableinsertlabel visual1'>
                    <td width='70%' colspan="3">Indirizzo</td>
                </tr>

                <tr class='visual1'>
                    <td class='tablerowfh' colspan="3"><textarea rows='4' cols='110'name='indirizzo_trasm'><?php echo $indirizzo_trasm ?></textarea></td>
                </tr>


                <tr>
                    <td class='tableinsertlabel' width=200>Note Oggetto: </td>
                </tr>
                <tr>
                    <td class='tablerowfh' width=590 colspan=3>
                        <input type='text' size='160' name='noteoggetto' value='<?php echo $noteoggetto ?>'>
                    </td>
                </tr>
                <tr><td></td></tr><tr><td></td></tr>
            </table>
            <br/><br/>
            <table>
                <tr>
                    <td><button type='submit'>Salva</button></td>
                    <td><a href='listaoggetti_edit.php?idrinvenimento=$idrinvenimento'><button>Indietro</button></a></td>
                </tr>
            </table>
        </form>
    </td>
</tr>


<script>
    function inviato() {
        var val = $("select#selectvalue :selected").val();
        if (val == 4) {
            $('.visual1').show();
        } else {
            $('.visual1').hide();
        }
    }

    $(document).ready(function () {
        var state = '<?php echo $idstatooggetto?>';
        $("select#selectvalue").val(state);
        
        inviato();
        $("select").change(function () {
            inviato();
        });
    });
</script>


<?php require_once 'config/footer.php'; ?>