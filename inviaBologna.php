<?php

    require_once 'config/header.php';

    require 'config/connect.php'; //connessione al database            
    
    echo " 
                   <tr>
                      <td>
          ";
    
    $idrinvenimento=$_REQUEST['idrinvenimento'];
    
    // Recupero gli id degli oggetti selezionati in listaoggetti_edit.php e per ognuno di essi aggiorno il valore ne DB
    // impostando lo statooggetto a 4 che indica "inviato a" e OR Bologna al campo "inviatoa" che indica gli oggetti rinvenuti del
    // Comune di Bologna
    $mycheck = $_REQUEST['mycheck'];
    $single_value = explode(",",$mycheck);
    foreach ($single_value as $value) {
        $query = "UPDATE oggetti2.listaoggetti 
                    SET statooggetto='4', inviatoa='OR Comune di Bologna'
                    WHERE idlistaoggetti='$value'";
        mysqli_query($conn,$query);
    }
    
    echo "
                      </td>
                   </tr>
         ";
    
    // ritorna alla pagina precedente
    echo "<meta http-equiv='refresh' content='0;url=listaoggetti_edit.php?idrinvenimento=$idrinvenimento'/>";
    
    require_once 'config/footer.php';
?>
