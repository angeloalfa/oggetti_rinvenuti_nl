<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/commons/config/config.php');

// Connessione al DB
try {
    $dbh = new PDO("mysql:host=$mysql_hostname;dbname=oggetti2", $mysql_username, $mysql_password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");

    $query_select = "SELECT idrinvenimento,operatore FROM oggetti2.rinvenimenti";

    $stmt = $dbh->prepare($query_select);
    $stmt->execute();

    while ($operatore = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $op = $operatore['operatore'];
        $operatore_array = explode('\.', $op);

        $operatore_str = end($operatore_array);
        $cognome_nome = trim(preg_replace('/te/', '', $operatore_str));

        $query = 'SELECT perid FROM anagrafica.anagrafica WHERE concat_ws(" ",cognome,nome) LIKE "%' . $cognome_nome . '%"';


        $stmt2 = $dbh->prepare($query);
        $stmt2->execute();
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($cognome_nome == 'DANGELO FRANCESCO') {
            $perid = 213806;
        } elseif ($cognome_nome == 'CIULLI MICHELE ANTONIO') {
            $perid = 220318;
        } else {
            $perid = $row['perid'];
        }
        
        $idrinv = $operatore['idrinvenimento'];
        
        echo $query_ins = "UPDATE oggetti2.rinvenimenti SET perid='$perid' WHERE idrinvenimento=$idrinv";
        $stmt3 = $dbh->prepare($query_ins);
        $stmt3->execute();
    }
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}     

