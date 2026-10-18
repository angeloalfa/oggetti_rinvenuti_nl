<?php
require_once 'config/header.php';

require 'config/connect.php'; //connessione al database
require_once 'functions/function.php';
require_once 'functions/utility.php';

echo " 
                   <tr>
                      <td>
          ";

$idrinvenimento = $_REQUEST['idrinvenimento'];
$idtrasmissione = $_REQUEST['idtramissione'];
?>

<script type="text/javascript">
    document.write('<div id="loading">Attendi la creazione della pagina ...<br/><br/><img src="images/loader2.gif" id="Image1" border="0" style="width:81px;height:92px;"/></div>');
    window.onload = function() {
        document.getElementById("loading").style.display = "none";
    };
</script>

<br/>
<center>
    <iframe src="verbale_trasmissione_pdf.php?idrinvenimento=<?php echo $idrinvenimento ?>&idtrasmissione=<?php echo $idtrasmissione ?>" height="720" width="800" frameborder="0"></iframe>
</center>
<?php
echo "
                      </td>
                   </tr>
         ";
require_once 'config/footer.php';
?>