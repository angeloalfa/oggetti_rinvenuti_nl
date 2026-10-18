<?php

    require_once 'config/header.php';

    //connessione al database            
    require 'config/connect.php';
    
    echo " 
                   <tr>
                      <td>
          ";
    
    $table_oggetti = "
        <table>
            <tr>
                <td>Nr.Rinvenimento</td>
                <td>Nr.Oggetto</td>
                <td>Tipo Oggetto</td>
                <td>Descrizione</td>
            </tr>";
    
    $mycheck = $_REQUEST['mycheck'];
    $single_value = explode(",",$mycheck);
    foreach ($single_value as $value) {
        $query = "SELECT * FROM oggetti2.listaoggettiplus WHERE idlistaoggetti=$value";
        $oggetto = mysqli_query($conn,$query);
        $row_oggetto = mysqli_fetch_array($oggetto);
        $table_oggetti .= "<tr><td>".$row_oggetto['idrinvenimento']."</td>";
        $table_oggetti .= "<td>".$value."</td>";
        $table_oggetti .= "<td>".$row_oggetto['tipooggetto']."</td>";
        $table_oggetti .= "<td>".$row_oggetto['descrizioneoggetto']."</td></tr>";
    }
    
    $table_oggetti .= "</table>";
    
    echo $table_oggetti;
    
    echo "
                      </td>
                   </tr>
         ";
    
    // ritorna alla pagina precedente
    //echo "<meta http-equiv='refresh' content='0;url=listaoggetti_edit.php?idrinvenimento=$idrinvenimento'/>";
    
    require_once 'config/footer.php';
?>
