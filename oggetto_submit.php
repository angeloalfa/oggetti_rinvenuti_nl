<?php
    include_once 'config/header.php';           
    require 'config/connect.php'; //connessione al database
    require_once 'functions/function.php';
    
    // Richiesta delle variabile inviate da oggetto_add.php
    $idrinvenimento=$_REQUEST['idrinvenimento'];
    
     // ricava data, ora e operatore corrente
    $time=time();
    $datainserimento=date('d/m/Y', $time);
    $orainserimento=date('H:i', $time);
    $cognomeoperatore=addslashes($_SESSION['cognome']);
    $nomeoperatore=$_SESSION['nome'];
    $operatore=$cognomeoperatore." ".$nomeoperatore;
    
    $luogorinvenimento=$_REQUEST['luogorinvenimento'];
    $datarinvenimento=$_REQUEST['datarinvenimento'];
    $brevedescrizioneoggetto=$_REQUEST['brevedescrizioneoggetto'];
    
    // Aggiornamento del DB rinvenimenti
    $query = "UPDATE oggetti2.rinvenimenti 
              SET luogorinvenimento='$luogorinvenimento', datarinvenimento='$datarinvenimento', brevedescrizioneoggetto='$brevedescrizioneoggetto', statorinvenimento='ingresso'
              WHERE idrinvenimento='$idrinvenimento'";
    $query = clean_query_update($query);
    mysqli_query($conn,$query) or die("Query non valida: " .$query . "<br>" . mysqli_error($conn));
    
    // indice maggiore dei campi tipooggetto, descrizione 
    $maxidoggetto=$_REQUEST['maxidoggetto'];
    
    // Aggiornamento del DB lista oggetti con l'inserimento di tutti gli oggetti presenti
    for($i=1;$i<=$maxidoggetto;$i++)  {
        ${tipooggetto.$i}=$_REQUEST['tipooggetto'.$i];
        ${descroggetto.$i}=$_REQUEST['descroggetto'.$i];
        if(${tipooggetto.$i}!='' && ${descroggetto.$i}!='') {
            $query= "INSERT INTO oggetti2.listaoggetti (idrinvenimento,tipooggetto,descrizioneoggetto,statooggetto) VALUES('$idrinvenimento','${tipooggetto.$i}','${descroggetto.$i}','in carico')";
            $query = clean_query_insert($query);
            mysqli_query($conn,$query) or die("Query non valida: " .$query . "<br>" . mysqli_error($conn));
        }
    }
    
    // Creazione di un nuovo record nel DB movimentazione oggetto
    $query = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,posizione,datamovimentazione,oramovimentazione,statomovimentazione) 
                 VALUES('$idrinvenimento','$operatore','Cassaforte','$datainserimento','$orainserimento','in carico')";
    $query = clean_query_insert($query);
    mysqli_query($conn,$query) or die("Query non valida: " .$query . "<br>" . mysqli_error($conn));
  
    echo "<meta http-equiv='refresh' content='0;url=oggetti_rinvenuti_list.php'/>";
?>
