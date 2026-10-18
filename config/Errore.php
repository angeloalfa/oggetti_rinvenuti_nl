<?php
require_once 'config/header.php';

if (isset($_REQUEST['error_message'])) {
    $error_message = preg_replace("/\\\\/", '', $_REQUEST['error_message']);
} else {
    $error_message = "Qualcosa è andato storto," .
            " ecco perché sei finito qui.";
}

if (isset($_REQUEST['system_error_message'])) {
    $system_error_message = preg_replace("/\\\\/", '', $_REQUEST['system_error_message']);
} else {
    $system_error_message = "Nessun messaggio di errore a livello di sistema è stato segnalato.";
}
?> 
<link href='css/error.css' rel='stylesheet' type='text/css'/>
<tr>
    <td>
        <h1>Uh oh... sorry!</h1>
        <div id="content">
            <p class='errortext'><img src="images/errore.png" class="error" alt="Errore" width="200" height="200"/></p>
            <p class='error'><?php echo $error_message; ?>
                </p>
            <p class='errortext'>Non preoccuparti gli sviluppatori sono stati avvisati di questo problema 
                e stanno già facendo qualcosa per risolverlo, nel caso tu voglia commentare
                questo disservizio o informarli di altri problemi o consigli o suggerimenti
                non esitare ad aprire un ticket nell'apposito spazio sul portale.</p>
            <p class='errortext'>Nel frattempo, se vuoi tornare indietro alla pagina che ha causato il
                problema, puoi farlo:</p>
            <div class="link"><a class="link" href="javascript:history.go(-1);">cliccando qui</a></div>
            <p class='errortext'>Se si verifica lo stesso problema, però, si consiglia di riprovare più tardi. 
                Scommetto che avremo capito il problema e avremo messo a posto le cose.</p>
            <p class='errortext'>Grazie ancora ... Ci vediamo presto.
                Siamo davvero spiacenti per l'inconveniente.</p>
            <?php
                debug_print("<hr />");
                debug_print("<p class='errortext'>Il seguente messaggio a livello di sistema è stato ricevuto: <b>{$system_error_message}</b></p>");
            ?>
        </div>
    </td>
</tr>


<?php require_once 'config/footer.php' ?>
