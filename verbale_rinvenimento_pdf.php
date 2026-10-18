<?php
// verbale_rinvenimento_pdf.php
// Nessun output prima del PDF: niente spazi o righe vuote prima di <?php e niente tag di chiusura finale.
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');   // controllo di accesso
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/connect.php');
require_once 'functions/function.php';
require_once 'functions/utility.php';

// ---------------------------------------------------------------------------
// PREPARAZIONE DEI DATI
// ---------------------------------------------------------------------------
$iddocumento_rinv = intval(isset($_REQUEST['iddocumento_rinv']) ? $_REQUEST['iddocumento_rinv'] : 0);

$result = mysqli_query($conn, "SELECT * FROM orme.documento WHERE iddocumento=$iddocumento_rinv");
$row = $result ? mysqli_fetch_assoc($result) : null;

if (!$row) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(404);
    exit('Documento non trovato.');
}

$idorganigramma = $row['idorganigramma'];
$idrinvenimento = $row['idprotocollo'];
$luogoverbale   = $row['luogo'];
$dataatto       = convertiDataAE($row['datariferimento']);
$contenuto      = htmlspecialchars_decode($row['contenuto'], ENT_QUOTES);

$ufficio   = get_ufficio($conn, $idorganigramma);
$operatore = get_stringa_dipendente($conn, get_perid($conn, $idorganigramma));

$luogo_data = ($luogoverbale !== '' ? $luogoverbale . ', ' : '') . $dataatto;

// ---------------------------------------------------------------------------
// GENERAZIONE PDF
// ---------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/tcpdf/config/lang/ita.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/tcpdf/tcpdf.php');

// Estensione di TCPDF con piè di pagina personalizzato
class MYPDF extends TCPDF {

    public function Footer() {
        $this->SetY(-50);

        $disciplinaCoseRitrovate = "<b>Disciplina sulle cose ritrovate: Diritti del rinvenitore – Obblighi del proprietario.</b>
                <hr/>
                    <ul>
                        <li>(Art. 927 C.C. Cose ritrovate) - <i>Chi trova una cosa mobile deve restituirla al proprietario, e, se non lo conosce, deve consegnarla senza ritardo al sindaco del luogo in cui l'ha trovata, indicando le circostanze del ritrovamento (647 c.p.).</i></li>
                        <li>(Art. 928 C.C. Pubblicazione del ritrovamento) - <i>Il sindaco rende noto la consegna per mezzo di pubblicazione nell'albo pretorio del comune, da farsi per due domeniche successive e da restare affissa per tre giorni ogni volta.</i></li>
                        <li>(Art. 929 C.C. Acquisto di proprietà della cosa ritrovata) - <i>Trascorso un anno dall'ultimo giorno della pubblicazione senza che si presenti il proprietario, la cosa oppure il suo prezzo, se le circostanze ne hanno richiesto la vendita, appartiene a chi l'ha trovata.  Così il proprietario come il ritrovatore, riprendendo la cosa o ricevendo il prezzo, devono pagare le spese occorse.</i></li>
                        <li>(Art. 930 C.C. Premio dovuto al ritrovatore) - <i>Il proprietario deve pagare a titolo di premio al ritrovatore, se questi lo richiede, il decimo della somma o del prezzo della cosa ritrovata.  Se tale somma o prezzo eccede le diecimila lire (5,17€), il premio per il sovrappiù è solo del ventesimo. Se la cosa non ha valore commerciale, la misura del premio è fissata dal giudice secondo il suo prudente apprezzamento.</i></li>
                        <li>(Art. 931 C.C. Equiparazione del possessore o detentore al proprietario) -  <i>Agli effetti delle disposizioni contenute negli articoli 927 e seguenti, al proprietario sono equiparati, secondo le circostanze, il possessore e il detentore.</i></li>
                    </ul>";
        $this->SetFont('helvetica', '', 6);
        $this->writeHTML($disciplinaCoseRitrovate, true, false, true, true, 'J');

        // Riga finale: testo a sinistra, numero di pagina a destra
        $w = $this->getPageWidth() - $this->lMargin - $this->rMargin;
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell($w / 2, 8, 'Documento generato dal Sistema Informativo OrMe', 0, 0, 'L');
        $this->Cell($w / 2, 8, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Metadati del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($operatore);
$pdf->SetTitle('Verbale di rinvenimento nr. ' . $idrinvenimento);
$pdf->SetSubject('Verbale di rinvenimento');

// Layout
$pdf->setPrintHeader(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, 60);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setLanguageArray($l);
$pdf->setFontSubsetting(true);
$pdf->setCellHeightRatio(1.3);          // interlinea più leggibile nel testo

$pdf->AddPage();

$larghezza = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;

// --- Intestazione ----------------------------------------------------------
$stemma = __DIR__ . '/images/stemma4.jpg';
if (is_file($stemma)) {
    $pdf->Image($stemma, 0, 8, 23, 0, 'JPG', '', 'T', true, 300, 'C', false, false, 0, false, false, false);
}

$pdf->SetY(38);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'POLIZIA DI STATO', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 6, "COMPARTIMENTO POLIZIA FERROVIARIA PER L'EMILIA ROMAGNA", 0, 1, 'C');
$pdf->Cell(0, 6, $ufficio, 0, 1, 'C');

// Linea di separazione
$y = $pdf->GetY() + 3;
$pdf->SetDrawColor(120, 120, 120);
$pdf->SetLineWidth(0.3);
$pdf->Line(PDF_MARGIN_LEFT, $y, $pdf->getPageWidth() - PDF_MARGIN_RIGHT, $y);
$pdf->SetY($y + 4);

// Numero rinvenimento a sinistra, luogo e data a destra, sulla stessa riga
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell($larghezza / 2, 6, 'Rinv. Nr. ' . $idrinvenimento, 0, 0, 'L');
$pdf->Cell($larghezza / 2, 6, $luogo_data, 0, 1, 'R');

// --- Corpo del verbale -----------------------------------------------------
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 11);
$pdf->writeHTML($contenuto, true, false, true, true, '');

// --- Firma -----------------------------------------------------------------
// Se non c'è spazio sufficiente, il blocco firma passa alla pagina successiva (mai da solo in fondo)
if ($pdf->GetY() + 35 > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
    $pdf->AddPage();
}

$bw = 80;                                                        // larghezza del blocco firma
$bx = $pdf->getPageWidth() - PDF_MARGIN_RIGHT - $bw;             // allineato al margine destro

$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell($bw, 5, "L'operatore", 0, 'C', false, 1, $bx);
$pdf->Ln(12);                                                    // spazio per la firma
$yl = $pdf->GetY();
$pdf->Line($bx + 5, $yl, $bx + $bw - 5, $yl);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell($bw, 5, $operatore, 0, 'C', false, 1, $bx);

$pdf->lastPage();

// Scarta qualsiasi output accidentale, altrimenti TCPDF rifiuta di inviare il PDF
while (ob_get_level()) { ob_end_clean(); }

$pdf->Output('verbale_rinvenimento_' . intval($idrinvenimento) . '.pdf', 'I');