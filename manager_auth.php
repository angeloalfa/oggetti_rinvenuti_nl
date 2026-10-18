<?php
require_once 'config/header.php';
require 'config/connect.php';  //connessione al database

$idautorizzazioni = $_REQUEST['id'];

$query_auth = "SELECT * FROM oggetti2.autorizzazioni WHERE idautorizzazioni=$idautorizzazioni";
$result_auth = mysqli_query($conn,$query_auth);
while ($rows_auth = mysqli_fetch_assoc($result_auth)) {
    $perid = $rows_auth['perid'];
    $operatore = $rows_auth['nome'];
    $livello = $rows_auth['livello'];
    $note = $rows_auth['note'];
}

echo " 
                   <tr>
                      <td>
          ";
?>
<script>
    function search_func() {
        // Effettua la ricerca
        $.ajax({
            type: 'POST',
            url: 'manager_auth_submit.php',
            data: $("#form").serialize(),
            success: function(html) {
                $('tbody#ajax_result').html(html);
                $('#statusbar').text("Aggiornamento effettuato con successo");
            },
            error: function() {
                $('#statusbar').text("Errore nella select del Database");
            }
        });
    }
    $(document).ready(function() {
        search_func();
        $("#form").submit(function(event) {
            search_func();
            event.preventDefault();
        });
        $('input[type="reset"]').click(function() {
            $(':input[type="text"]').each(function() {
                $(this).attr('value', '');
            });
        });
    });
</script>

<style>
    label,input {
        display: block;
        width: 150px;
        float: left;
        margin-bottom: 10px;
    }

    label {
        font-size: 14px;
        color: #3d5d96;
        text-align: right;
        width: 75px;
        padding-right: 20px;
    }

    br {
        clear: left;
    }
    #result {
        position: relative;
        width: 900px;
        top: -250px;
        left: 400px;
        border: solid thin #0080FF;
    }
</style>

<form id='form' name='datapost' method='post' action='manager_auth_submit.php'>
    <label for='perid'>PerID: </label>
    <input type='text' name='perid' id='perid' value='<?php echo $perid ?>'/>
    <br>
    <label for='operatore'>Operatore: </label>
    <input type='text' name='operatore' id='operatore' value='<?php echo $operatore ?>'/>
    <br>
    <label for='libello'>Livello: </label>
    <input type='text' name='livello' id='livello' value='<?php echo $livello ?>'/>
    <br>
    <label for='note'>Note: </label>
    <input type='text' name='note' id='note' value='<?php echo $note ?>'/>
    <br><br>
    <input type='submit' value='Salva'/>
    <input type='reset' value='Annulla'/>
</form>

<div id='result'>
    <table width='100%'>
        <thead>
            <tr class='tableheader'>
                <th width='10%'>PerID</th>
                <th width='30%'>Operatore</th>
                <th width='5%' align='center'>Livello</th>
                <th width='50%' align='center'>Note</th>
                <th width='5%' align='center'>Azioni</th>
            </tr>
        </thead>
        <tbody id='ajax_result'></tbody>
    </table>
</div>
<div id='statusbar'> </div>

<?php
echo "
                      </td>
                   </tr>
         ";
require_once 'config/footer.php';
?>
