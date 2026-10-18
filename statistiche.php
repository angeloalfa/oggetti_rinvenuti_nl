<?php
// statistiche.php

require_once 'config/header.php';
require_once 'functions/function.php';
require_once 'config/connect.php';
require_once 'config/autorizza.php';

global $conn;

// --------------------------------------------------------------------------
// 1. PARAMETRIZZAZIONE DEI FILTRI
// --------------------------------------------------------------------------
$luogo_selezionato = isset($_REQUEST['luogoverbale']) ? trim($_REQUEST['luogoverbale']) : 'Tutti';
$mese_selezionato  = isset($_REQUEST['mese']) ? (int)$_REQUEST['mese'] : 0; // 0 = Tutti i mesi
$anno_selezionato  = isset($_REQUEST['anno']) ? (int)$_REQUEST['anno'] : (int)date('Y'); // Default anno corrente

// Costruzione clausola WHERE per i filtri principali (supporta YYYY-MM-DD e DD/MM/YYYY)
$where_clauses = ["1=1"];

if ($luogo_selezionato !== 'Tutti' && $luogo_selezionato !== '') {
    $where_clauses[] = "luogoverbale = '" . mysqli_real_escape_string($conn, $luogo_selezionato) . "'";
}

if ($mese_selezionato > 0) {
    $where_clauses[] = "(
        MONTH(datarinvenimento) = $mese_selezionato 
        OR MONTH(STR_TO_DATE(datarinvenimento, '%d/%m/%Y')) = $mese_selezionato
    )";
}

if ($anno_selezionato > 0) {
    $where_clauses[] = "(
        YEAR(datarinvenimento) = $anno_selezionato 
        OR YEAR(STR_TO_DATE(datarinvenimento, '%d/%m/%Y')) = $anno_selezionato
        OR datarinvenimento LIKE '%/$anno_selezionato'
        OR datarinvenimento LIKE '$anno_selezionato-%'
    )";
}

$where_sql = implode(' AND ', $where_clauses);

// --------------------------------------------------------------------------
// 2. ESTRAZIONE PREVENTIVA ANNI DISPONIBILI SUL DATABASE
// --------------------------------------------------------------------------
$anni_disponibili = [];

$res_anni_db = mysqli_query($conn, "
    SELECT DISTINCT 
        COALESCE(NULLIF(YEAR(datarinvenimento),0), YEAR(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as anno_val 
    FROM oggetti2.rinvenimenti 
    WHERE datarinvenimento IS NOT NULL AND datarinvenimento != '' 
    HAVING anno_val IS NOT NULL AND anno_val > 2000 
    ORDER BY anno_val DESC
");

if ($res_anni_db) {
    while ($r_a = mysqli_fetch_assoc($res_anni_db)) {
        $a_int = (int)$r_a['anno_val'];
        if ($a_int > 0) {
            $anni_disponibili[] = $a_int;
        }
    }
}

$anno_corrente = (int)date('Y');
if (empty($anni_disponibili)) {
    for ($a = $anno_corrente; $a >= $anno_corrente - 6; $a--) {
        $anni_disponibili[] = $a;
    }
} else if (!in_array($anno_corrente, $anni_disponibili)) {
    array_unshift($anni_disponibili, $anno_corrente);
}

// --------------------------------------------------------------------------
// 3. QUERY CONTEGGI KPI: TOTALE, RICONSEGNATI E TRASMESSI
// --------------------------------------------------------------------------
// Totale Rinvenimenti
$sql_totale = "SELECT COUNT(*) as tot FROM oggetti2.rinvenimenti WHERE $where_sql";
$res_tot    = mysqli_query($conn, $sql_totale);
$row_tot    = mysqli_fetch_assoc($res_tot);
$tot_rinvenimenti = (int)($row_tot['tot'] ?? 0);

// Conteggio Riconsegnati
$sql_riconsegnati = "SELECT COUNT(*) as tot FROM oggetti2.rinvenimenti 
                     WHERE $where_sql AND (statorinvenimento LIKE '%riconsegnat%' OR statorinvenimento LIKE '%restituit%')";
$res_ric = mysqli_query($conn, $sql_riconsegnati);
$row_ric = mysqli_fetch_assoc($res_ric);
$tot_riconsegnati = (int)($row_ric['tot'] ?? 0);

// Conteggio Trasmessi
$sql_trasmessi = "SELECT COUNT(*) as tot FROM oggetti2.rinvenimenti 
                   WHERE $where_sql AND (statorinvenimento LIKE '%trasmess%' OR statorinvenimento LIKE '%trasmit%')";
$res_tra = mysqli_query($conn, $sql_trasmessi);
$row_tra = mysqli_fetch_assoc($res_tra);
$tot_trasmessi = (int)($row_tra['tot'] ?? 0);

// --------------------------------------------------------------------------
// 4. QUERY GRAFICI SORGENTI (STATI E TIPOLOGIE)
// --------------------------------------------------------------------------
// 4.1 Ripartizione per Stato Rinvenimento
$sql_stati = "SELECT statorinvenimento, COUNT(*) as totale 
              FROM oggetti2.rinvenimenti 
              WHERE $where_sql AND statorinvenimento IS NOT NULL AND statorinvenimento != ''
              GROUP BY statorinvenimento";
$res_stati = mysqli_query($conn, $sql_stati);

$labels_stati = [];
$data_stati   = [];
while ($r = mysqli_fetch_assoc($res_stati)) {
    $labels_stati[] = trim($r['statorinvenimento']);
    $data_stati[]   = (int)$r['totale'];
}

// 4.2 Top 5 Tipologie Oggetti
$sql_tipologie = "SELECT brevedescrizioneoggetto, COUNT(*) as quantita 
                  FROM oggetti2.rinvenimenti 
                  WHERE $where_sql AND brevedescrizioneoggetto IS NOT NULL AND brevedescrizioneoggetto != '' 
                  GROUP BY brevedescrizioneoggetto 
                  ORDER BY quantita DESC LIMIT 5";
$res_tipo = mysqli_query($conn, $sql_tipologie);

$labels_tipo = [];
$data_tipo   = [];
while ($r = mysqli_fetch_assoc($res_tipo)) {
    $labels_tipo[] = html_entity_decode($r['brevedescrizioneoggetto'], ENT_QUOTES, 'UTF-8');
    $data_tipo[]   = (int)$r['quantita'];
}

// --------------------------------------------------------------------------
// 5. QUERY ADATTIVA PER IL GRAFICO TEMPORALE
// --------------------------------------------------------------------------
$trend_labels       = [];
$trend_riconsegnati = [];
$trend_trasmessi    = [];

$mesi_nomi = [
    0  => 'Tutti i mesi',
    1  => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
    5  => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
    9  => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
];

$mesi_brevi = [1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu', 7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic'];

if ($mese_selezionato > 0 && $anno_selezionato > 0) {
    // === 5.A MODALITÀ GIORNALIERA (Mese + Anno specifici) ===
    $num_giorni = cal_days_in_month(CAL_GREGORIAN, $mese_selezionato, $anno_selezionato);
    
    for ($g = 1; $g <= $num_giorni; $g++) {
        $trend_labels[$g]       = "Giorno " . $g;
        $trend_riconsegnati[$g] = 0;
        $trend_trasmessi[$g]    = 0;
    }

    $sql_g_ric = "SELECT 
                    COALESCE(NULLIF(DAY(datarinvenimento),0), DAY(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as giorno, 
                    COUNT(*) as tot 
                  FROM oggetti2.rinvenimenti 
                  WHERE $where_sql AND (statorinvenimento LIKE '%riconsegnat%' OR statorinvenimento LIKE '%restituit%')
                  GROUP BY giorno";
    $res_g_ric = mysqli_query($conn, $sql_g_ric);
    while ($r = mysqli_fetch_assoc($res_g_ric)) {
        $g = (int)$r['giorno'];
        if (isset($trend_riconsegnati[$g])) $trend_riconsegnati[$g] = (int)$r['tot'];
    }

    $sql_g_tra = "SELECT 
                    COALESCE(NULLIF(DAY(datarinvenimento),0), DAY(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as giorno, 
                    COUNT(*) as tot 
                  FROM oggetti2.rinvenimenti 
                  WHERE $where_sql AND (statorinvenimento LIKE '%trasmess%' OR statorinvenimento LIKE '%trasmit%')
                  GROUP BY giorno";
    $res_g_tra = mysqli_query($conn, $sql_g_tra);
    while ($r = mysqli_fetch_assoc($res_g_tra)) {
        $g = (int)$r['giorno'];
        if (isset($trend_trasmessi[$g])) $trend_trasmessi[$g] = (int)$r['tot'];
    }

    $titolo_grafico_trend = "Andamento Giornaliero - " . $mesi_nomi[$mese_selezionato] . " " . $anno_selezionato;

} else {
    // === 5.B MODALITÀ MENSILE O ANNUALE ===
    if ($anno_selezionato > 0) {
        // Singolo anno selezionato -> mostra l'andamento dei 12 mesi
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf("%02d", $m);
            $trend_labels[$key]       = $mesi_brevi[$m] . " " . $anno_selezionato;
            $trend_riconsegnati[$key] = 0;
            $trend_trasmessi[$key]    = 0;
        }

        $sql_m_ric = "SELECT 
                        COALESCE(NULLIF(MONTH(datarinvenimento),0), MONTH(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as m, 
                        COUNT(*) as tot 
                      FROM oggetti2.rinvenimenti 
                      WHERE $where_sql AND (statorinvenimento LIKE '%riconsegnat%' OR statorinvenimento LIKE '%restituit%')
                      GROUP BY m";
        $res_m_ric = mysqli_query($conn, $sql_m_ric);
        while ($r = mysqli_fetch_assoc($res_m_ric)) {
            $key = sprintf("%02d", (int)$r['m']);
            if (isset($trend_riconsegnati[$key])) $trend_riconsegnati[$key] = (int)$r['tot'];
        }

        $sql_m_tra = "SELECT 
                        COALESCE(NULLIF(MONTH(datarinvenimento),0), MONTH(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as m, 
                        COUNT(*) as tot 
                      FROM oggetti2.rinvenimenti 
                      WHERE $where_sql AND (statorinvenimento LIKE '%trasmess%' OR statorinvenimento LIKE '%trasmit%')
                      GROUP BY m";
        $res_m_tra = mysqli_query($conn, $sql_m_tra);
        while ($r = mysqli_fetch_assoc($res_m_tra)) {
            $key = sprintf("%02d", (int)$r['m']);
            if (isset($trend_trasmessi[$key])) $trend_trasmessi[$key] = (int)$r['tot'];
        }

        $titolo_grafico_trend = "Andamento Mensile - Anno " . $anno_selezionato;

    } else {
        // === TUTTI GLI ANNI E TUTTI I MESI -> Mostra l'andamento espresso per ANNO ===
        $anni_lista = array_reverse($anni_disponibili);
        foreach ($anni_lista as $a_item) {
            if ($a_item > 0) {
                $trend_labels[$a_item]       = "Anno " . $a_item;
                $trend_riconsegnati[$a_item] = 0;
                $trend_trasmessi[$a_item]    = 0;
            }
        }

        $sql_y_ric = "SELECT 
                        COALESCE(NULLIF(YEAR(datarinvenimento),0), YEAR(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as y, 
                        COUNT(*) as tot 
                      FROM oggetti2.rinvenimenti 
                      WHERE $where_sql AND (statorinvenimento LIKE '%riconsegnat%' OR statorinvenimento LIKE '%restituit%')
                      GROUP BY y";
        $res_y_ric = mysqli_query($conn, $sql_y_ric);
        while ($r = mysqli_fetch_assoc($res_y_ric)) {
            $y_val = (int)$r['y'];
            if (isset($trend_riconsegnati[$y_val])) {
                $trend_riconsegnati[$y_val] = (int)$r['tot'];
            }
        }

        $sql_y_tra = "SELECT 
                        COALESCE(NULLIF(YEAR(datarinvenimento),0), YEAR(STR_TO_DATE(datarinvenimento, '%d/%m/%Y'))) as y, 
                        COUNT(*) as tot 
                      FROM oggetti2.rinvenimenti 
                      WHERE $where_sql AND (statorinvenimento LIKE '%trasmess%' OR statorinvenimento LIKE '%trasmit%')
                      GROUP BY y";
        $res_y_tra = mysqli_query($conn, $sql_y_tra);
        while ($r = mysqli_fetch_assoc($res_y_tra)) {
            $y_val = (int)$r['y'];
            if (isset($trend_trasmessi[$y_val])) {
                $trend_trasmessi[$y_val] = (int)$r['tot'];
            }
        }

        $titolo_grafico_trend = "Andamento Annuale Storico (Tutti gli Anni)";
    }
}
?>

<!-- CONTENUTO PRINCIPALE -->
<tr>
    <td>
        <div class="stats-container">
            
            <div class="stats-header">
                <h2>Statistiche & Analytics Rinvenimenti</h2>
                <p>Analisi quantitativa basata sul Luogo Verbale, sullo stato di rinvenimento e sulla tipologia del materiale</p>
            </div>

            <!-- BARRA FILTRI -->
            <form method="GET" action="statistiche.php" class="toolbar-search" style="margin-bottom: 20px;">
                <label>Luogo Verbale:
                    <select name="luogoverbale" onchange="this.form.submit()">
                        <option value="Tutti">Tutti i Luoghi</option>
                        <?php
                        $res_luoghi = mysqli_query($conn, "SELECT DISTINCT luogoverbale FROM oggetti2.rinvenimenti WHERE luogoverbale IS NOT NULL AND luogoverbale != '' ORDER BY luogoverbale ASC");
                        while ($r_l = mysqli_fetch_array($res_luoghi)) {
                            $l_val = htmlspecialchars($r_l['luogoverbale'], ENT_QUOTES);
                            $selected = ($r_l['luogoverbale'] === $luogo_selezionato) ? " selected='selected'" : "";
                            echo "<option value='$l_val'$selected>$l_val</option>\n";
                        }
                        ?>
                    </select>
                </label>

                <label>Mese:
                    <select name="mese" onchange="this.form.submit()">
                        <?php
                        foreach ($mesi_nomi as $num => $nome_mese) {
                            $sel_m = ($num == $mese_selezionato) ? " selected='selected'" : "";
                            echo "<option value='$num'$sel_m>$nome_mese</option>\n";
                        }
                        ?>
                    </select>
                </label>

                <label>Anno:
                    <select name="anno" onchange="this.form.submit()">
                        <option value="0" <?php echo ($anno_selezionato == 0) ? "selected='selected'" : ""; ?>>Tutti gli anni</option>
                        <?php
                        foreach ($anni_disponibili as $a_val) {
                            $sel_a = ($a_val == $anno_selezionato) ? " selected='selected'" : "";
                            echo "<option value='$a_val'$sel_a>$a_val</option>\n";
                        }
                        ?>
                    </select>
                </label>

                <button type="submit">Filtra</button>
            </form>

            <!-- SCHEDE KPI: TOTALE, RICONSEGNATI E TRASMESSI -->
            <div class="kpi-grid">
                <div class="kpi-card kpi-blue">
                    <span class="kpi-title">Totale Rinvenimenti</span>
                    <span class="kpi-num"><?php echo $tot_rinvenimenti; ?></span>
                </div>
                <div class="kpi-card kpi-green">
                    <span class="kpi-title">Oggetti Riconsegnati</span>
                    <span class="kpi-num"><?php echo $tot_riconsegnati; ?></span>
                </div>
                <div class="kpi-card kpi-red">
                    <span class="kpi-title">Oggetti Trasmessi</span>
                    <span class="kpi-num"><?php echo $tot_trasmessi; ?></span>
                </div>
            </div>

            <!-- GRAFICI PRINCIPALI -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Ripartizione per Stato Rinvenimento</h3>
                    <div class="chart-wrapper">
                        <canvas id="chartStati"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Top 5 Tipologie Oggetti</h3>
                    <div class="chart-wrapper">
                        <canvas id="chartTipologie"></canvas>
                    </div>
                </div>
            </div>

            <!-- GRAFICO TEMPORALE ADATTIVO -->
            <div class="chart-card" style="margin-top: 24px;">
                <h3><?php echo htmlspecialchars($titolo_grafico_trend); ?></h3>
                <div class="chart-wrapper" style="height: 320px;">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>

        </div>
    </td>
</tr>

<script src="/applicazioni/oggetti_rinvenuti/js/vendor/chart.umd.js"></script>

<script>
$(document).ready(function() {
    if (typeof Chart !== 'undefined') {
        // 1. Doughnut Chart: Stati
        const ctxStati = document.getElementById('chartStati').getContext('2d');
        new Chart(ctxStati, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels_stati); ?>,
                datasets: [{
                    data: <?php echo json_encode($data_stati); ?>,
                    backgroundColor: ['#3de20a', '#ffbb00', '#c62828', '#3580f1', '#8e24aa', '#3949ab'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 2. Bar Chart: Tipologie
        const ctxTipologia = document.getElementById('chartTipologie').getContext('2d');
        new Chart(ctxTipologia, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_tipo); ?>,
                datasets: [{
                    label: 'Quantità Oggetti',
                    data: <?php echo json_encode($data_tipo); ?>,
                    backgroundColor: '#2f6fb5',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } }
            }
        });

        // 3. Line Chart: Trend Temporale
        const ctxTrend = document.getElementById('chartTrend').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_values($trend_labels)); ?>,
                datasets: [
                    {
                        label: 'Oggetti Riconsegnati',
                        data: <?php echo json_encode(array_values($trend_riconsegnati)); ?>,
                        borderColor: '#2e9e44',
                        backgroundColor: 'rgba(46, 158, 68, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Oggetti Trasmessi',
                        data: <?php echo json_encode(array_values($trend_trasmessi)); ?>,
                        borderColor: '#f47c20',
                        backgroundColor: 'rgba(244, 124, 32, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'config/footer.php'; ?>