<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <script src="/applicazioni/commons/jquery/development-bundle/jquery-1.9.1"></script>
        <script src="/applicazioni/commons/jquery/development-bundle/ui/jquery.ui.core.js"></script>
    </head>
    <body>

        <?php
        require 'connect.php';

        $query = "SELECT * FROM oggetti2.rinvenimenti ORDER BY idrinvenimento DESC";
        $result = mysqli_query($conn,$query);
        $option = "";
        while ($rows = mysqli_fetch_array($result)) {
            $idrinvenimento = $rows['idrinvenimento'];
            $descr = $rows['brevedescrizioneoggetto'];
            $operatore = $rows['operatore'];
            $option .= "<option value='$idrinvenimento'>$idrinvenimento - $descr - $operatore</option>";
        }
        ?>

        <form id="testconfirmJS" name="testconfirmJS" method="post" onsubmit="return confirm('Attenzione! Vuoi realmente eliminare il rinvenimento:\n\n' + document.getElementById('idrinv').value + '\n\nSeleziona OK per cancellare o Annulla per annullare.')">
            <fieldset>
                <legend>Eliminazione rinvenimento</legend>
                <p><label for="idrinvenimento">Inserisci l'id del rinvenimento da eliminare: </label>
                    <select id="idrinv" name="idrinvenimento">
                        <option></option>
                        <?php echo $option; ?>
                    </select>
                </p>
                <p><input type="submit" value="Elimina" /></p>
            </fieldset>
        </form>

        <?php
        $idrinvenimento = $_POST['idrinvenimento'];
        if (isset($idrinvenimento)) {
            echo "<p class='message'>Il rinvenimento da eliminare &egrave;: " . $_POST['idrinvenimento'];
            echo "</p>";
            $query = "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento";
            $result = mysqli_query($conn,$query);
            while ($rows = mysqli_fetch_array($result)) {
                $iddenuncia = $rows['iddenuncia'];
            }
            $i = 0;
            $query = "DELETE FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento";
            if(!mysqli_query($conn,$query)) $i += 1;
            $query = "DELETE FROM oggetti2.listaoggetti WHERE idrinvenimento=$idrinvenimento";
            if(!mysqli_query($conn,$query)) $i += 1;
            $query = "DELETE FROM oggetti2.persone WHERE idrinvenimento=$idrinvenimento";
            if(!mysqli_query($conn,$query)) $i += 1;
            $query = "DELETE FROM oggetti2.movimentazionioggetti WHERE idrinvenimento=$idrinvenimento";
            if(!mysqli_query($conn,$query)) $i += 1;
            $query = "DELETE FROM oggetti2.rinvenimenti WHERE iddeuncia=$iddenuncia";
            if(!mysqli_query($conn,$query)) $i += 1;
            if ($i==0)  {
                echo "<br/><br/><p><b>Eliminazione avvenuta con successo</b></p>";
            }
        }
        ?>

        <script>
            $(function() {
                // jQuery UI Dialog   

                $('#dialog').dialog({
                    autoOpen: false,
                    width: 400,
                    modal: true,
                    resizable: false,
                    buttons: {
                        "Submit Form": function() {
                            document.testconfirmJQ.submit();
                        },
                        "Cancel": function() {
                            $(this).dialog("close");
                        }
                    }
                });
            });
        </script>

    </body>
</html>
