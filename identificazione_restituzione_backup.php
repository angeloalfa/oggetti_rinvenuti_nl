
<?php
require_once 'config/header.php';
require 'config/connect.php';           //connessione al database
require_once 'functions/function.php';

// Recupero dati dalla form precedente
$idrinvenimento = $_REQUEST['idrinvenimento'];

?>


<!--Inserimento nuova riga nella tabella principale-->
<tr><td><h1>Identificazione del ritirante dell'oggetto <?php echo $idrinvenimento ?></h1></td></tr>
<tr>
    <td>
        
        <form id='datapost'>
            <span class='collapse'>
                <table width='800' border='0' align='center'>
                    <tr>
                        <td class='tableinsertlabel' width=120>Cognome: </td>
                        <td class='tablerowfh' width=680>
                            <input type='text' class='required' id='cognome' name='cognome' size='45' maxlength='45' required/>
                        </td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Nome: </td>
                        <td class='tablerowfh' width=680>
                            <input type='text' id='nome' class='required' name='nome' size='45' maxlength='45' required>
                        </td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Data di Nascita: </td>
                        <td class='tablerowfh' width=680><input type='text' class='required2 datepicker' name='datanascita' id='datanascita' size='10' placeholder='gg/mm/aaaa'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Luogo di Nascita: </td>
                        <td class='tablerowfh' width=680><input type='text' class='required2 luogo' name='luogonascita' id='luogonascita' size='45' maxlength='45'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Residenza: </td>
                        <td class='tablerowfh' width=680><input type='text' name='residenza' id='residenza' class='luogo' size='60' maxlength='60'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Via: </td>
                        <td class='tablerowfh' width=680><input type='text' name='via' id='via' class='luogo' size='60' maxlength='60'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Documento: </td>
                        <td class='tablerowfh' width=680><input type='text' name='documento' id='documento' size='40' maxlength='45'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Numero/Matricola: </td>
                        <td class='tablerowfh' width=680><input type='text' name='documentonumero' id='documentonumero' size='20' maxlength='45'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Rilasciato da: </td>
                        <td class='tablerowfh' width=680><input type='text' name='rilasciatoda' id='rilasciatoda' class='luogo' size='40' maxlength='45'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Rilasciato il: </td>
                        <td class='tablerowfh' width=680><input type='text' class='datepicker' name='rilasciatoil' id='rilasciatoil' size='10' placeholder='gg/mm/aaaa'></td>
                    </tr>
                    <tr>
                        <td class='tableinsertlabel' width=120>Telefono: </td>
                        <td class='tablerowfh' width=680><input type='text' name='telefono' id='telefono' size='20' maxlength='45'></td>
                    </tr>
                </table>
            </span>
            
            <br/><br/>

            <table width='880' border='0' align='center'>
                <tr height=26>
                    <td class='tableinsertlabel' align='center'>
                        <input type='hidden' name='idrinvenimento' id='idrinvenimento'>
                        <input type='submit' id="submitBtn" value='Avanti'/>
                    </td>
                </tr>
            </table>

        </form>
        <div id='statusbar'> </div>

    </td>
</tr>
<!--Chiusura riga nella tabella principale-->

<script src="js/input_format.js"></script>
<script>
    $(document).ready(function() {
        $('#nome').upperFirstAll();
        $('#cognome').upperCase();
        $('#documento').upperFirstAll();
        $('#documentonumero').upperCase();
        $('#telefono').onlyNumber();
        document.getElementById('idrinvenimento').value = "<?php echo $idrinvenimento; ?>";

        // Cattura l'evento di clic sul pulsante "Invia"
        $('#submitBtn').click(function(e) {
            e.preventDefault(); // Previene l'invio standard del form

            // Serializza i dati del form
            var formData = $('#datapost').serialize();

            // Invia i dati tramite AJAX
            $.ajax({
                type: 'POST',
                url: 'identificazione_restituzione_submit.php',
                data: formData,
                success: function(response) {
                    $('#statusbar').html('<div style="color: green;">Dati aggiornati con successo! Reindirizzamento in corso...</div>');

                    // Ritardo prima del reindirizzamento
                    setTimeout(function() {
                        window.location.href = 'verbale_restituzione_init.php?idrinvenimento=<?php echo $idrinvenimento; ?>';
                    }, 2000); // Aspetta 2 secondi
                },
                error: function() {
                    $('#statusbar').html('<div style="color: red;">Errore durante l\'invio del modulo.</div>');
                }
            });
        });
    });
    $(function() {
        $(".datepicker")
            .attr('readonly', 'readonly')
            .datepicker({
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-100:c+50'
            })
    });
   
</script>

<?php require_once 'config/footer.php';?>