<?php
require_once 'config/header.php';           
require 'config/connect.php';  //connessione al database 
require_once 'functions/function.php';
?>

<tr>
    <td>
        <table width="100%">
            <tr class='tabletitle'>
                <td colspan='6' align='center'>Visualizzazione le trasmissioni effettuate: </td>
            </tr>
            <tr><td><br/><br/></td></tr>
            <tr class='tableheader'>
                <td width="5%">ID</td>
                <td width="5%">Data e Ora</td>
                <td width="20%">Ufficio</td>
                <td width="10%">Citta'</td> 
                <td width="10%">Indirizzo</td>
                <td width="50%">Azioni</td>
            </tr>
            <?php
            $query = "SELECT * FROM oggetti2.trasmissioni ORDER BY idtrasmissioni DESC LIMIT 10";
            $listatrasmissioni = mysqli_query($conn,$query);
            while ($rows = mysqli_fetch_array($listatrasmissioni)) {
                $id = $rows['idtrasmissioni'];
                $data = $rows['data'];
                $ufficio = $rows['ufficio_trasm'];
                $citta_trasm = $rows['citta_trasm'];
                $tipo_uff_trasm = $rows['tipo_uff_trasm'];
                $indirizzo = $rows['indirizzo_trasm'];
                echo "
                            <tr class='tablerow'>
                                <td>$id</td>
                                <td>$data</td>
                                <td>$ufficio $tipo_uff_trasm</td>
                                <td>$citta_trasm</td>
                                <td>$indirizzo</td>
                                <td>azione</td>
                            </tr>
                ";
            }
            ?>
            <tr><td><br/><br/></td></tr>
            <tr>
                <td colspan='6' align='center'>
                    <a href='movimentazione_add.php?idrinvenimento=<?php echo $idrinvenimento ?>'><button>Aggiungi Movimentazione</button></a>
                    <a href='rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento ?>'><button>Indietro</button></a>                                        
                </td>
            </tr>

        </table>
    </td>
</tr>

<?php require_once 'config/footer.php'; ?>
