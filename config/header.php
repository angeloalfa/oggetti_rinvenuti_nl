<?php
// Tutta la logica PHP prima di qualsiasi output HTML:
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/browsercheck.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/connect.php');

$currentFile = basename($_SERVER['SCRIPT_NAME']);
$versione    = "2.2.0";

// Evita la cache del CSS
$cssFile = $_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/css/all.css';
$cssVer  = file_exists($cssFile) ? filemtime($cssFile) : $versione;

// Operatore collegato con controllo null-coalescing per PHP 8.3
$cognome   = $_SESSION['cognome'] ?? '';
$nome      = $_SESSION['nome'] ?? '';
$operatore = trim($nome . ' ' . $cognome);
if (empty($operatore)) {
    $operatore = "Operatore";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Oggetti Rinvenuti</title>

    <!-- JQuery UI -->
    <script src="/applicazioni/commons/jquery/development-bundle/jquery-1.9.1.js"></script>
    <script src="/applicazioni/commons/jquery/development-bundle/ui/jquery-ui.custom.js"></script>
    <script src="/applicazioni/commons/jquery/development-bundle/ui/i18n/jquery.ui.datepicker-it.js"></script>
    <link rel="stylesheet" href="/applicazioni/commons/jquery/css/cupertino/jquery-ui-1.10.2.custom.css">
    
    <!-- Librerie JQuery esterne e script personali -->
    <script src="/applicazioni/commons/jquery/jquery/external/relCopy.js"></script>
    <script src="js/changelog.js"></script>
    
    <!-- CSS -->
    <link href='css/all.css?v=<?php echo $cssVer; ?>' rel='stylesheet' type='text/css' />
</head>

<body>
    <table width='100%' class='mastertable' align='center'>
        <tr valign=top>
            <td>
                <table width='100%' border='0' align='center' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='header-bar'>
                            <!-- 1. SINISTRA: Logo + Versione -->
                            <div class="header-left">
                                <div class="brand-logo" id="btn-about" title="Clicca per informazioni sull'applicazione" style="cursor: pointer;">
                                    <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                                        <circle cx="17" cy="17" r="3" fill="#3d5d96" stroke="#ffc233" stroke-width="2"/>
                                        <line x1="19.1" y1="19.1" x2="21" y2="21" stroke="#ffc233" stroke-width="2"/>
                                    </svg>
                                    <div class="brand-text">
                                        <span class="brand-title">Oggetti</span>
                                        <span class="brand-subtitle">Rinvenuti</span>
                                    </div>
                                </div>
                                <div id='version' title='Clicca per vedere il changelog'>
                                    v<?php echo htmlspecialchars($versione, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>

                            <!-- 2. CENTRO: Operatore collegato -->
                            <div class="header-center">
                                <div class="header-user" title="Operatore attualmente collegato">
                                    <svg class="header-user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <span class="header-user-name"><?php echo htmlspecialchars($operatore, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>

                            <!-- 3. DESTRA: Pulsanti di Azione -->
                            <div class="header-right">
                                <?php if ($currentFile != 'statistiche.php') { ?>
                                    <a class='btn header-btn header-btn-stats' href='statistiche.php' title='Visualizza report e grafici'>
                                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="20" x2="18" y2="10"></line>
                                            <line x1="12" y1="20" x2="12" y2="4"></line>
                                            <line x1="6" y1="20" x2="6" y2="14"></line>
                                        </svg>
                                        <span>Statistiche</span>
                                    </a>
                                <?php } ?>

                                <?php if ($currentFile != 'oggetti_rinvenuti_list.php') { ?>
                                    <a class='btn header-btn' href='oggetti_rinvenuti_list.php'>Ritorna alla lista</a>
                                <?php } ?>
                                
                                <a class='btn header-btn' href='/portale'>Ritorna al Portale</a>
                            </div>

                            <!-- HTML del Dialog Modale -->
                            <div id="about-dialog" title="Informazioni sull'applicazione" style="display:none;">
                                <div class="about-container">
                                    <p class="about-desc">
                                        <strong>Oggetti Rinvenuti</strong> è la piattaforma web istituzionale per la gestione, la tracciabilità e la catalogazione telematica del materiale rinvenuto negli ambiti ferroviari e di stazione.
                                    </p>

                                    <div class="about-rating-box">
                                        <h4>Invia un feedback sull'applicazione</h4>
                                        <div class="star-rating">
                                            <span class="star" data-value="1">&#9733;</span>
                                            <span class="star" data-value="2">&#9733;</span>
                                            <span class="star" data-value="3">&#9733;</span>
                                            <span class="star" data-value="4">&#9733;</span>
                                            <span class="star" data-value="5">&#9733;</span>
                                        </div>
                                        <div id="rating-status" class="rating-msg"></div>
                                    </div>

                                    <div class="about-copyright">
                                        <p>&copy; 2026 <br/> Realizzato integralmente dal <br/><strong>Settore Informatico del Compartimento Polizia Ferroviaria per l'Emilia Romagna</strong></p>
                                    </div>
                                </div>
                            </div>

                        </td>
                    </tr>