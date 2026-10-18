<?php
require_once 'config/header.php';           
require 'config/connect.php';  //connessione al database 
require_once 'functions/function.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];
?>

<tr>
    <td>
        <table width="100%">
            <tr class='tabletitle'>
                <td colspan='6' align='center'>Visualizzazione Movimentazioni Rinvenimento: <?php echo $idrinvenimento ?></td>
            </tr>
            <tr><td><br/><br/></td></tr>
            <tr class='tableheader'>
                <td width="5%">Data</td>
                <td width="5%">Ora</td>
                <td width="20%">Operatore</td>
                <td width="10%">Posizione</td> 
                <td width="10%">Stato</td>
                <td width="50%">Note</td>
            </tr>
            <?php
            $query = "SELECT * FROM oggetti2.movimentazionioggetti where idrinvenimento='$idrinvenimento' ORDER BY idmovimentazionioggetto DESC";
            $listamovimenti = mysqli_query($conn,$query);
            while ($rows = mysqli_fetch_array($listamovimenti)) {
                $data = convertiDataAE($rows['datamovimentazione']);
                $ora = $rows['oramovimentazione'];
                $operatore = $rows['operatore'];
                $posizione = $rows['posizione'];
                $stato = $rows['statomovimentazione'];
                $note = $rows['notemovimentazione'];
                echo "
                            <tr class='tablerow'>
                                <td>$data</td>
                                <td>$ora</td>
                                <td>$operatore</td>
                                <td>$posizione</td>
                                <td>$stato</td>
                                <td>$note</td>
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
