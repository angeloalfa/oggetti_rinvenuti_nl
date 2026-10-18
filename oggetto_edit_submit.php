<?php
// oggetto_edit_submit.php
    require_once 'config/header.php';             
    require 'config/connect.php';   //connessione al database 
    require_once 'functions/function.php';    
    
    $idrinvenimento=$_REQUEST['idrinvenimento'];
    $idoggetto=$_REQUEST['idoggetto'];
    $idstatooggetto=$_REQUEST['statooggetto'];
    $noteoggetto=$_REQUEST['noteoggetto'];
    $idufficio_trasm = $_REQUEST['ufficio_trasm'];
    $tipo_uff_trasm = $_REQUEST['tipo_uff_trasm'];
    $citta_trasm = $_REQUEST['citta_trasm'];
    $indirizzo_trasm = $_REQUEST['indirizzo_trasm'];
    
    $noteoggetto .= "Trasmesso a: ".$ufficio_trasmissione[$idufficio_trasm]." ".$tipo_uff_trasm." ".$citta_trasm." ".$indirizzo_trasm;
    
    echo " 
                   <tr>
                      <td>
          ";
    
    $query = "UPDATE oggetti2.listaoggetti 
              SET idstatooggetto='$idstatooggetto', noteoggetto='$noteoggetto'
              WHERE idrinvenimento='$idrinvenimento' AND idlistaoggetti='$idoggetto'";
    // Questo replace serve quando un campo e vuoto, in modo da sotituirlo con NULL
    $query = str_replace("''", "NULL", $query);
    mysqli_query($conn,$query) or die("Query non valida: " .$query . "<br>" . mysqli_error($conn));

    echo "
                      </td>
                   </tr>
         ";    
    
    require_once 'config/footer.php';
    
    echo "<meta http-equiv='refresh' content='0;url=listaoggetti_edit.php?idrinvenimento=$idrinvenimento'/>";
?>
