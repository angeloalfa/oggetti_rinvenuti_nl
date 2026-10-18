<?php
    require_once 'config/header.php';
           
    require 'config/connect.php';    //connessione al database 
    
    $query_add = $_REQUEST['query_add'];
    if ($query_add=="'tutti'")
        echo $query_add='';
    if ($query_add!='')
        $query_add = "AND inviatoa=".$query_add;

?>

<tr>
    <td>
        <script>
            $(document).ready(function() {
                $("select").change(function() {
                    var add = $("select").val();
                    window.location.href = "inviare.php?query_add='"+add+"'";
                });
            });
        </script>
        <script type="text/javascript">
            function seleziona() {
                $(':checkbox').attr('checked', true);
            }
            function deseleziona() {
                location.reload();
            }
            function invia() {
                var mycheck = new Array();
                $("#mycheck:checked").each(function() {
                    mycheck.push($(this).val());
                    location.href = "verbale_invio.php?mycheck="+mycheck;
                });
            }
        </script>
        <table>
            <tr><td class='tabletitle' width=800 colspan='2'>Lista oggetti da inviare</td></tr>
            <tr><td>  </td></tr>
            <tr class='tableheader'>
                <td width=100>Scegli Invio a: </td>
                <td>
                    <select id='selectvalue'>
                        <option></option>
                        <option value='tutti'>Tutti</option>
                        <?php
                        $query = "SELECT DISTINCT(inviatoa) FROM oggetti2.listaoggettiplus";
                        $listainvii = mysqli_query($conn,$query);
                        while ($rows = mysqli_fetch_array($listainvii)) {
                            $invio = $rows['inviatoa'];
                            if ($invio != '')
                                echo "<option value='$invio'>$invio</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <table>
            <td><button name='seleziona' onclick='javascript:seleziona()'>Seleziona Tutti</button></td>
            <td><button name='deseleziona' onclick='javascript:deseleziona()'>Deseleziona Tutti</button></td>
        </table>
        <table>
            <tr class='tableheader'>
                <td width=30>  </td>
                <td width=30>Id</td>
                <td width=140>Tipo Oggetto</td>
                <td width=450>Descrizione Oggetto</td>
                <td width=100>Inviare a</td>
            </tr>

            <?php
            $query = "SELECT * FROM oggetti2.listaoggettiplus WHERE statooggetto='da inviare' " . $query_add;
            $listaoggetti = mysqli_query($conn,$query);
            while ($rows = mysqli_fetch_array($listaoggetti)) {
                $id = $rows['idlistaoggetti'];
                $tipooggetto = $rows['tipooggetto'];
                $descrizioneoggetto = $rows['descrizioneoggetto'];
                $statooggetto = $rows['statooggetto'];
                $inviatoa = $rows['inviatoa'];
                echo "
                    <tr class='tablerow'>
                        <td><input type='checkbox' id='mycheck'  name='mycheck[]' value='$id'/></td>
                        <td>$id</td>
                        <td>$tipooggetto</td>
                        <td>$descrizioneoggetto</td>
                        <td>$inviatoa</td>
                    </tr>";
            }
            echo "
        </table>
        <br/>
        <table>
            <tr>
                <td><button name='Invia' onclick='javascript:invia()'>Crea Verbale Invio</button></td>
            </tr>
        </table>
        <br/>
        <table>
            <tr>
                <td><a href='rinvenimento_view.php?idrinvenimento=$idrinvenimento'><button>Indietro</button></a></td>
            </tr>
        </table>
    </td>
</tr>";

require_once 'config/footer.php';
?>
