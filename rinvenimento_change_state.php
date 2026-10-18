<?php

require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

echo " 
                   <tr>
                      <td>
          ";

$idrinvenimento = $_REQUEST['idrinvenimento'];

$query = "UPDATE oggetti2.rinvenimenti SET statorinvenimento='annullato' WHERE idrinvenimento=$idrinvenimento";
mysqli_query($conn,$query) or die("Query non valida:<br/>" . $query . "<br/>" . mysqli_error($conn));

echo "
                      </td>
                   </tr>
         ";

require_once 'config/footer.php';
?>
