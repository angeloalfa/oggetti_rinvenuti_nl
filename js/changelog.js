$(document).ready(function() {

    // Per una nuova versione: aggiungi un oggetto IN CIMA all'elenco.
    // tipo: 'novita' | 'miglioria' | 'correzione'
    var versioni = [
        {
            versione: '2.2.0',
            data: '15/10/2026',
            voci: [
                {tipo: 'novita',     testo: 'Lista rinvenimenti: clic su una riga per aprirla (Ctrl+clic in una nuova scheda).'},
                {tipo: 'novita',     testo: 'Ricerca automatica mentre si digita; Invio avvia subito la ricerca e i campi non vengono più svuotati.'},
                {tipo: 'novita',     testo: 'Pallini colorati di stato con legenda, al posto del pulsante "Vedi".'},
                {tipo: 'novita',     testo: 'Pulsante "Inserisci nuovo" verde, ben visibile.'},
                {tipo: 'novita',     testo: 'Dettaglio rinvenimento: frecce ← → della tastiera per scorrere i rinvenimenti.'},
                {tipo: 'miglioria',  testo: 'Nuova barra di ricerca con filtri per data raggruppati; "Mostra Tutti" azzera i filtri senza ricaricare la pagina.'},
                {tipo: 'miglioria',  testo: 'Intestazione della tabella fissa e allineata alle colonne.'},
                {tipo: 'miglioria',  testo: 'Grafica rinnovata: pulsanti, contrasto dei testi, righe alternate.'},
                {tipo: 'miglioria',  testo: 'Dettaglio rinvenimento riorganizzato, con barra delle azioni sempre visibile.'},
                {tipo: 'miglioria',  testo: 'Versione e novità in una finestra dedicata.'},
                {tipo: 'correzione', testo: 'Apostrofi e caratteri speciali nelle descrizioni non vengono più mostrati come codici.'},
                {tipo: 'correzione', testo: 'Cambio di stato "Trattato": si torna alla lista solo a salvataggio avvenuto.'},
                {tipo: 'correzione', testo: 'Richiesta di conferma prima di eliminare un rinvenimento.'},
                {tipo: 'correzione', testo: 'Dettaglio rinvenimento: corretto l\'ufficio di restituzione e la visualizzazione del consegnante.'},
                {tipo: 'correzione', testo: 'Maggiore protezione dei parametri nella pagina di dettaglio.'}
            ]
        },
        {
            versione: '2.1.4',
            data: '24/02/2024',
            voci: [
                {tipo: 'miglioria', testo: 'Velocizzata la ricerca dei rinvenimenti.'}
            ]
        },
        {
            versione: '2.0',
            data: '17/09/2013',
            voci: [
                {tipo: 'novita',    testo: 'Aggiunta la possibilità di modificare un record.'},
                {tipo: 'miglioria', testo: 'Ottimizzata la grafica.'},
                {tipo: 'miglioria', testo: 'Ottimizzata la ricerca dei rinvenimenti.'}
            ]
        }
    ];

    var etichette = {novita: 'Novità', miglioria: 'Miglioria', correzione: 'Correzione'};

    // Costruisco il DOM con .text(): nessun rischio di HTML iniettato dai testi
    function costruisci() {
        var $cl = $('<div class="cl"></div>');
        $.each(versioni, function(i, v) {
            var $blocco = $('<section class="cl-ver"></section>');
            var $head = $('<div class="cl-head"></div>');
            $head.append($('<span class="cl-num"></span>').text('Versione ' + v.versione));
            if (i === 0) {
                $head.append($('<span class="cl-badge"></span>').text('Attuale'));
            }
            $head.append($('<span class="cl-data"></span>').text(v.data));
            $blocco.append($head);

            var $ul = $('<ul class="cl-voci"></ul>');
            $.each(v.voci, function(j, voce) {
                var $li = $('<li></li>');
                $li.append($('<span class="cl-tag"></span>')
                    .addClass('cl-' + voce.tipo)
                    .text(etichette[voce.tipo] || ''));
                $li.append($('<span class="cl-testo"></span>').text(voce.testo));
                $ul.append($li);
            });
            $blocco.append($ul);
            $cl.append($blocco);
        });
        return $cl;
    }

    $('#version').click(function() {
        var $d = $('<div></div>').attr('title', 'Novità e modifiche').append(costruisci());
        $d.dialog({
            modal: true,
            width: 640,
            resizable: false,
            dialogClass: 'cl-dialog',
            buttons: {
                'Chiudi': function() { $(this).dialog('close'); }
            },
            close: function() { $(this).dialog('destroy').remove(); }
        });
        // Un clic fuori dalla finestra la chiude
        $('.ui-widget-overlay').one('click', function() { $d.dialog('close'); });
    });
});