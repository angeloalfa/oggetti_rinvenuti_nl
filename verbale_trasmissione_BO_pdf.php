<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/connect.php');
require_once 'functions/function.php';
require_once 'functions/utility.php';


////Mi connetto al database
//$conn = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_dbname) or die('Error connecting to mysql');
//mysqli_set_charset($conn,'utf8');

// Recupera la lista degli idrinvenimento da trasmettere selezionati nella schermata home
$lista_idrinv_da_inviare = $_SESSION['lista_idrinv_da_inviare'];

// Data e anno nel momento della creazione del verbale di trasmissione
$time = time();
$data_trasmissione = date('d/m/Y', $time);
// estrapolo l'anno dalla data
$anno = date('Y', $time);

$datanascita = convertiDataAE($datanascita);

$oggetto = '
    <table width="100%">
        <tr valign="bottom">
            <td align="left" valign="top" width="10%">Oggetto:</td>
            <td align="justify" valign="top" width="90%">
                Rinvenimento oggetti / Trasmissioni
            </td>
        </tr>
   </table>
';

$destinatari = '
    <table width="100%">
        <tr>
            <td width="70%">AL COMUNE (Ufficio Oggetti Rinvenuti) di</td>
            <td width="30%" align="right">BOLOGNA</td>
        </tr>
        <tr>
            <td><u>e, per conoscenza</u></td>
            <td></td>
        </tr>
        <tr>
            <td>AL COMPARTIMENTO POLFER PER L\'EMILIA ROMAGNA</td>
            <td align="right">BOLOGNA</td>
        </tr>
        <tr>
            <td>(Squadra di polizia Giudiziaria)</td>
        </tr>
    </table>
';

$idorganigramma = $_SESSION['idorganigramma'];
$estensore = get_stringa_dipendente($conn,get_perid($conn,$idorganigramma));

$introduzione = '<div style="text-align:justify;">Per la custodia e l\'eventuale restituzione all\'avente diritto, si trasmettono gli oggetti 
                        rinvenuti nella stazione ferroviaria di Bologna Centrale e consegnati a quest\'ufficio di seguito elencati.</div>
                 <style>
                    table#lista_oggetti  {
                        width:100%;
                        border-collapse:collapse;
                    }
                    table,th, td    {
                        border: 1px solid black;
                    }
                </style>
                    <table id="lista_oggetti">
                        <tr>
                            <th width="10%" align="center">Rinv.Nr.</th>
                            <th width="30%" align="center">Cognome Nome</th>
                            <th width="45%" align="center">oggetto</th>
                            <th width="15%" align="center">data rinvenimento</th>
                        </tr>';
foreach ($lista_idrinv_da_inviare as $key => $idrinv_value) {

    $cognome = $nome = '';  // Resetto le variabili cognome e nome ad ogni ciclo
    // Recupero dalla tabella rinvenimenti tutti i campi e assegno ogni valore ad 
    // una variabile che ha lo stesso nome del campo nel DB
    $query = "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento='$idrinv_value'";
    $result = mysqli_query($conn,$query) or handle_error("Errore nella select di oggetti2.rinvenimenti in rinvenimento_view", $query);
    while ($rows = mysqli_fetch_assoc($result)) {
        foreach ($rows as $x => $x_value) {
            ${$x} = $x_value;
        }
    }

    // Recupero i dati del proprietario del rinvenimento
    $query_prop = "SELECT * FROM oggetti2.persone WHERE idpersona='$idproprietario'";
    $result_prop = mysqli_query($conn,$query_prop) or handle_error("Errore nella select di oggetti2.rinvenimenti in rinvenimento_view", $query_prop);
    while ($rows_prop = mysqli_fetch_assoc($result_prop)) {
        foreach ($rows_prop as $x => $x_value) {
            ${$x} = $x_value;
        }
    }
    if ($cognome == '') {
        $cognome = 'Sconosciuto';
    }
    $introduzione .= '      <tr>
                                <td>' . $idrinv_value . '</td>
                                <td>' . $cognome . ' ' . $nome . '</td>
                                <td>' . $brevedescrizioneoggetto . '</td>
                                <td>' . convertiDataAE($datarinvenimento) . '</td>
                            </tr>';
}

$introduzione .= '      </table>
                    <div style="font-size: 6;">Estensore:<br><b>' . $estensore . '</b></div>';

$firma = '
    <table width="100%" cellpadding="0px">
        <tr>
            <td width="60%"></td>
            <td width="35%" align="center">
                <span style="font-size:8; line-height:4.5px;">
                    <em>
                        D\'Ordine del Dirigente<br>
                        Il Responsabile del Settore Operativo<br>
                        Comm. C. della Polizia di Stato<br>
                    </em>
                    <b>Sabrina TRIVISANO</b>
                </span>
            </td>
            <td width="5%"></td>
        </tr>
    </table>
';
// -----------------------------------------------------------------------------
// Fine della generazione del verbale di trasmissione
//Generazione PDF
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/tcpdf/config/lang/ita.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/tcpdf/tcpdf.php');

// Estensione della classe TCPDF per la creazione di un Footer personalizzato
class MYPDF extends TCPDF {

    public function Footer() {
        // Posizione a 15 mm dal bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Documento generato dal Sistema Informativo OrMe Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->Cell(0, 10, '', 0, false, 'L', 0, '', 0, false, 'T', 'M');
    }

}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
//$pdf->SetAuthor($cognome);
$pdf->SetTitle('Trasmissione');

// set default header data
$pdf->setFooterData($tc = array(0, 64, 0), $lc = array(0, 64, 128));

// set header and footer fonts
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 30);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// set default font subsetting mode
$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 14, '', true);

// Non stampare l'header
$pdf->setPrintHeader(false);

// Aggiunge una pagina relativa al verbale di trasmissione
$pdf->AddPage();

$image_file1 = 'images/stemma4.jpg';
$pdf->Image($image_file1, 0, 8, 23, 0, 'JPG', '', 'T', true, 300, 'C', false, false, 0, false, false, false);

$pdf->SetFont('helvetica', 'B', 20);
//Posizione
$pdf->SetXY(11, 38);
// Title
$pdf->Cell(0, 0, 'POLIZIA DI STATO', 0, 2, 'C', false, '', 0, false, 'C', 'C');
// Seconda riga
$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(11, 45);
$pdf->Cell(0, 0, 'COMPARTIMENTO POLIZIA FERROVIARIA PER L\'EMILIA ROMAGNA', 0, false, 'C', 0, '', 0, false, 'M', 'M');
$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(11, 50);
$pdf->Cell(0, 0, 'Settore Operativo Polfer Bologna Centrale', 0, false, 'C', 0, '', 0, false, 'M', 'M');
//$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(10, 65);
$pdf->Cell(0, 0, 'Bologna, ' . $data_trasmissione, 0, false, 'R', 0, '', 0, false, 'M', 'M');
//$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(15, 70);
$pdf->Cell(0, 0, 'Categ.Q.1/5 - ' . $anno, 0, false, 'L', 0, '', 0, false, 'M', 'M');

$pdf->Ln(10);
$pdf->writeHTML($oggetto, true, false, true, true, '');
$pdf->Ln(10);
$pdf->writeHTML($destinatari, true, false, true, true, '');
$pdf->Ln(20);
$pdf->writeHTML($introduzione, true, false, true, true, '');
$pdf->writeHTML($firma, true, false, true, true, '');
// ----------------------------------------------------------------------------

foreach ($lista_idrinv_da_inviare as $key => $idrinv_value) {
    // Recupero dalla tabella rinvenimenti tutti i campi e assegno ogni valore ad 
    // una variabile che ha lo stesso nome del campo nel DB
    $query = "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento='$idrinv_value'";
    $result = mysqli_query($conn,$query) or handle_error("Errore nella select di oggetti2.rinvenimenti in rinvenimento_view", $query);
    while ($rows = mysqli_fetch_assoc($result)) {
        foreach ($rows as $x => $x_value) {
            ${$x} = $x_value;
        }
    }

    // Recupero i dati del proprietario del rinvenimento
    $query_prop = "SELECT * FROM oggetti2.persone WHERE idpersona='$idproprietario'";
    $result_prop = mysqli_query($conn,$query_prop) or handle_error("Errore nella select di oggetti2.rinvenimenti in rinvenimento_view", $query_prop);
    while ($rows_prop = mysqli_fetch_assoc($result_prop)) {
        foreach ($rows_prop as $x => $x_value) {
            ${$x} = $x_value;
        }
    }

    // Iniziamo la generazione dei campi per il verbale di rinvenimento associato
    $query = "SELECT * FROM orme.documento WHERE iddocumento=$iddocumento_rinv";
    $result = mysqli_query($conn,$query);
    while ($row = mysqli_fetch_array($result)) {
        $idorganigramma_VR = $row['idorganigramma'];
        $luogoverbale_VR = $row['luogo'];
        $dataatto_VR = convertiDataAE($row['datariferimento']);
        $contenuto_VR = $row['contenuto'];
    }

    $contenuto_VR = htmlspecialchars_decode($contenuto_VR, ENT_QUOTES);

    // Lo metto qua perche' altrimenti non va la connessione a mysql
//    require_once 'functions/utility.php';

    $ufficio_VR = get_ufficio($conn,$idorganigramma_VR);
    $operatore_VR = get_stringa_dipendente($conn,get_perid($conn,$idorganigramma_VR));
    // Aggiunge una pagina relativa al verbale di rinvenimento
    $pdf->AddPage();
    $image_file1 = 'images/stemma4.jpg';
    $pdf->Image($image_file1, 0, 8, 23, 0, 'JPG', '', 'T', true, 300, 'C', false, false, 0, false, false, false);

    $pdf->SetFont('helvetica', 'B', 20);
    //Posizione
    $pdf->SetXY(11, 38);
    // Title
    $pdf->Cell(0, 0, 'POLIZIA DI STATO', 0, 2, 'C', false, '', 0, false, 'C', 'C');
    // Seconda riga
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(11, 45);
    $pdf->Cell(0, 0, 'COMPARTIMENTO POLIZIA FERROVIARIA PER L\'EMILIA ROMAGNA', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(11, 50);
    $pdf->Cell(0, 0, $ufficio_VR, 0, false, 'C', 0, '', 0, false, 'M', 'M');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(10, 65);
    $pdf->Cell(0, 0, $luogoverbale_VR . ', ' . $dataatto_VR, 0, false, 'R', 0, '', 0, false, 'M', 'M');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(15, 70);
    $pdf->Cell(0, 0, 'Rinv. Nr. ' . $idrinvenimento, 0, false, 'L', 0, '', 0, false, 'M', 'M');
    $pdf->Ln(10);
    $pdf->writeHTML($contenuto_VR, true, false, true, true, '');
    $pdf->Ln(10);
    $pdf->writeHTML("<p>L'originale è custodito agli atti dell'Ufficio trasmittente.</p>", true, false, true, true, '');
}

$pdf->lastPage();

// Close and output PDF document
$pdf->Output('trasmissioni_BO.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
?>
