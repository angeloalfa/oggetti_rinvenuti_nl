<?php
//oggetti_rinvenuti_list.php

require_once 'config/header.php';
require_once 'functions/function.php';
require 'config/connect.php';           //connessione al database
require_once 'config/autorizza.php';    // Imposta la variabile di sessione livello di autorizzazione che ha l'utente loggato

$luogo = (isset($_REQUEST['luogo']))?$_REQUEST['luogo']:'';

if ($luogo=='') {
    $luogo = $_SESSION['citta'];
}

//print_r($_SESSION);
?>


<!--Consente di visualizzare gli elementi in base alle autorizzazioni di livello-->
<script type="text/javascript">
    $(document).ready(function() {
        // Ricava la variabile di sessione che indica il livello di autorizzazione dell'utente loggato
        var livello =<?php echo $_SESSION['livello']; ?>;
        // Autorizzazioni per i posti polfer
        if (livello == 3) {
            $('.livello3').show();
        }
        // Autorizzazioni per gli operatori Bologna Cle
        if (livello == 2) {
            $('.livello2').show();
        }
        // Autorizzazioni per Brandi 
        if (livello == 1) {
            $('.livello1').show();
            $('.livello2').show();
        }
        // Autorizzazioni per amministratori
        if (livello == 0) {
            $('.livello0').show();
            $('.livello1').show();
            $('.livello2').show();
            $('.livello3').show();
        }
    });
</script>

<!--Script ajax che consente di effettuare ricerca in real time-->
<script type="text/javascript">
    $(function() {
        $(document).tooltip();
    });

    var xhr = null;              // richiesta ajax in corso
    var modalitaInvio = false;   // true quando sono visibili i checkbox di trasmissione

    function change_view() {
            $('.check').hide();
            $('#pulsanti').hide();
            $('#sel_tutti').hide();
        }

    // Mostra all'utente quale ricerca testuale è attiva
    function aggiornaMessaggio() {
    var prot  = $.trim($('#prot').val());
    var cerca = $.trim($('#cerca').val());
    var msg = '';
    if (prot !== '') {
                msg = 'Hai cercato il protocollo nr. ' + prot;
            } else if (cerca !== '') {
                msg = 'Hai cercato il valore: ' + cerca;
            }
            $('#search_field').text(msg);
        }

    function search_func() {
            change_view();
            aggiornaMessaggio();

    // Se c'è una ricerca ancora in corso la annulla: evita risposte fuori ordine
    if (xhr) {
                xhr.abort();
            }

    // Niente hide/show dell'area (causava sfarfallio a ogni battuta): la attenuo soltanto
            $('.scrollingArea').css('opacity', 0.5);

    var req = xhr = $.ajax({
                type: 'POST',
                url: 'oggetti_rinvenuti_list_submit.php',
                data: $("#form").serialize(),
                success: function(html) {
                    if (req !== xhr) return;
                    $('tbody#ajax_result').html(html);
                    $('tbody#ajax_result tr').on('mouseleave', function() {
                        $('#popup').html('').hide();
                    });
                    $('tbody#ajax_result tr').bind('contextmenu', function(e) {
                    var idrinv = $(this).attr('id');
                        $.ajax({
                            type: 'POST',
                            url: 'get_listaoggetti.php',
                            data: 'idrinvenimento=' + idrinv,
                            success: function(html) {
                                $('#popup').html(html);
                                $('#popup').show();
                            }
                        });
                        e.preventDefault();
                    });
                },
                error: function(jqXHR, textStatus) {
    if (textStatus === 'abort') return;   // annullata da noi: non è un errore
                    $('#statusbar').text("Errore nella select del Database");
                },
                complete: function() {
    if (req !== xhr) return;              // una richiesta più recente ha preso il posto
                    xhr = null;
                    $('.scrollingArea').css('opacity', 1);
    // I campi di ricerca NON vengono più svuotati
                }
            });
        }

    // Filtro rapido sulle date: il protocollo annulla tutti gli altri filtri, quindi lo svuoto
    function filtroRapido(value) {
            $('#prot').val('');
            $('#rapid').val(value == 0 ? '' : value);
            search_func();
        }

    // "Mostra Tutti": azzera i filtri senza ricaricare la pagina (l'ufficio scelto resta)
    function mostraTutti() {
            $('#prot').val('');
            $('#cerca').val('');
            $('#rapid').val('');
            search_func();
        }

    // Consente di inserire soltanto valori numerici
    function validate(evt) {
    var theEvent = evt || window.event;
    var key = theEvent.keyCode || theEvent.which;
            key = String.fromCharCode(key);
    var regex = /[0-9]|\./;
    if (!regex.test(key)) {
                theEvent.returnValue = false;
    if (theEvent.preventDefault)
                    theEvent.preventDefault();
            }
        }
</script>

<!--Consente di adattare l'altezza dello scroll con l'altezza della finestra-->
<script type="text/javascript">
    // Trova questo blocco nel tuo file oggetti_rinvenuti_list.php
    $(document).ready(function() {
        var h = $(window).height() - 430;
        $('.scrollingArea').css('height', h);
        $(window).resize(function() {
            location.reload(); // <--- ELIMINA O COMMENTA QUESTA RIGA!
        });
    });
</script>

<!--Consente di passare dalla modalità normale a quella con i checkbox per inviare i rinvenimenti-->
<script type="text/javascript">
    $(document).ready(function() {
        $('span#text_sel_all').hide();
        search_func();

        // Evidenzia il filtro data attivo con una classe (niente style inline)
        $('button.searchdate').click(function() {
            $('button.searchdate').removeClass('active');
            $(this).addClass('active');
        });

        // Invio = cerca subito (e blocca il submit nativo del form, che ti porterebbe via dalla pagina)
        // Mentre si digita = cerca dopo 400 ms di pausa
        var timer;
                $('#prot, #cerca').on('keydown', function(e) {
        if (e.which === 13) {
                        e.preventDefault();
                        clearTimeout(timer);
                        search_func();
                    }
                });
                $('#prot, #cerca').on('input', function() {
                    clearTimeout(timer);
                    timer = setTimeout(search_func, 400);
                });

        // Riga cliccabile (delegato: funziona anche dopo ogni refresh ajax della tabella)
                $('#ajax_result').on('click', 'tr', function(e) {
        // Non interferire con checkbox, link e bottoni
        if ($(e.target).closest('a, button, input, label').length) return;
        // Non navigare se l'utente stava selezionando del testo
        if (window.getSelection && window.getSelection().toString() !== '') return;

        if (modalitaInvio) {
        // In modalità trasmissione il click sulla riga spunta/deseleziona il checkbox
        var cb = $(this).find('input[type=checkbox]');
                        cb.prop('checked', !cb.prop('checked'));
        return;
                    }

        var url = 'rinvenimento_view.php?idrinvenimento=' + encodeURIComponent(this.id);
        if (e.ctrlKey || e.metaKey) {
                        window.open(url, '_blank');   // Ctrl/Cmd+click = nuova scheda
                    } else {
                        window.location.href = url;
                    }
                });
        // Consente di fare un redirect quando si vuole inserire un nuovo rinvenimento
        $("#new").click(function() {
            window.location.href = "rinvenimento_add.php";
        });
        $("#invia").click(function() {
            modalitaInvio = true;
            $('#da_trasmettere').val('1');
            search_func();
            $('span#text_sel_all').show();
            $('tbody#ajax_result span.check').show();
            $('#pulsanti').show();
            $('#sel_tutti').show()            
            $('#search_p').hide();
            $('#form').attr('action', 'invio_manager_BO.php');
            setTimeout(function() {
//                $('span.vedi').hide();
                $('span.check').show();
            }, 200);

        });
        $('#stampa').click(function() {
            var newWindow = window.open('', 'Anteprima', 'location=no,menubar=no,resizable=yes,toolbar=no,scrollbars=yes');
            var button = "<button id='stampa' type='button' onClick='window.print();return false'>Stampa</button>"
            //var css = "<link href='css/stampa.css' rel='stylesheet' type='text/css' media='print' />";
            var css = "<style>@page { size: A4 landscape }table,th,td {border: 1px solid black;font-size: 10pt;} .vedi{display:none;}</style>"
            var contenuto = $('div.scrollingArea').html();
            newWindow.document.open();
            newWindow.document.write(css + button + contenuto);
            newWindow.document.close();
        });
        // ricarica la pagina in modo da tornare alla modalità di visualizzazione normale
        $("#reset").click(function() {
            location.reload();
        });
        // Rimette in carico gli oggetti che sono stati trasmessi con l'ultima trasmissione
        $("#elimina").click(function() {
            window.location.href = "elimina_trasmissione.php";
        });
        // A seconda della scelta della città fa vedere gli oggetti relativi a quella città
        $("select").change(function() {
            search_func();
        });

        // consente di selezionare tutti i checkbox della pagina
        $('#sel_tutti').click(function() {
            $('.check :checkbox').prop('checked', $(this).is(':checked') ? true : false);
        });

    });
</script>

<style>
    button#test {
        background-color: greenyellow;
    }
</style>

<tr>
    <td>
        <a class='livello0' href='manager_auth.php' style='display: none'><button type='button' style='position: relative; top: -20px;'>Manager Auth</button></a>
        <a class='livello0' href='config/delete.php' style='display: none'><button type='button' style='position: relative; top: -20px;'>Delete Record</button></a>
        <form id='form' name='datapost' method='post' action='oggetti_rinvenuti_list_submit.php'>
            <input type='hidden' name='stato' value=1 />
            <div id='search_p'>

                <!-- Riga 1: ricerca -->
                <div class="toolbar-search">
                    <label>Ufficio Polfer
                <?php
                    $result = mysqli_query($conn, "SELECT * FROM home.luogo");
                    echo "<select name='luogo'>";
                    echo "<option value='Tutti'>Tutti</option>";
                    while ($row = mysqli_fetch_array($result)) {
                        $l   = htmlspecialchars($row['luogo'], ENT_QUOTES);
                        $sel = ($row['luogo'] === $luogo) ? " selected='selected'" : "";
                        echo "<option value='$l'$sel>$l</option>\n";
                    }
                    echo "</select>";
                ?>
                    </label>

                    <label>Protocollo
                    <input type='search' id='prot' name='search_prot' class='w-prot'
                            onkeypress='validate(event)' placeholder="N°" />
                    </label>

                    <label class="grow">Trova
                    <input type='search' id='cerca' name='searchfield'
                            placeholder="Cognome, nome, oggetto…" />
                    </label>

                    <button type='button' onclick='search_func()'>Cerca</button>
                    <span class="spacer"></span>
                    <button type='button' id='new' title='Registra un nuovo rinvenimento'><span class='plus'>+</span> Inserisci nuovo</button>
                </div>

                <!-- Riga 2: filtri data e azioni -->
                <div class="toolbar-filters">
                    <div class="seg">
                    <button type='button' class='searchdate active' onclick='mostraTutti()'>Tutti</button>
                    <button type='button' class='searchdate' onclick='filtroRapido(1)'>Oggi</button>
                    <button type='button' class='searchdate' onclick='filtroRapido(7)'>7 giorni</button>
                    <button type='button' class='searchdate' onclick='filtroRapido(15)'>15 giorni</button>
                    <button type='button' class='searchdate' onclick='filtroRapido(30)'>1 mese</button>
                    </div>

                    <span class="spacer"></span>

                    <span class='livello2 livello3' style='display:none'>
                    <button type='button' id='stampa'>Anteprima di stampa</button>
                    </span>
                    <span class='livello2' style='display:none'>
                    <button type='button' id='invia'>Trasmissione all'O.R. di Bologna</button>
                    </span>
                </div>

            </div>

            <!--selezione rapida delle date da visualizzare-->
            <input type='hidden' id='rapid' name='rapid' value='' />

            <!--visualizza solo i rinvenimenti da trasmette-->
            <input type='hidden' id='da_trasmettere' name='da_trasmettere' value='0' />

            <div id="loading" style="display:none"><img src="images/loader2.gif" id="Image1" border="0" style="width:81px;height:92px;"></div>
            <div class='tableheader' id='search_field'> </div>
            <div class="legenda">
    <span class="stato-container">
        <span class="badge-stato badge-visto-3">
            <svg class="badge-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </span>
        <span class="stato-testo">Da trasmettere</span>
    </span>

    <span class="stato-container">
        <span class="badge-stato badge-visto-2">
            <svg class="badge-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        </span>
        <span class="stato-testo">Accertamenti in corso</span>
    </span>

    <span class="stato-container">
        <span class="badge-stato badge-visto-1">
            <svg class="badge-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14h-2v-2h2zm0-4h-2V7h2z"/></svg>
        </span>
        <span class="stato-testo">In attesa di restituzione</span>
    </span>

    <span class="stato-container">
        <span class="badge-stato badge-visto-0">
            <svg class="badge-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm1-13h-2v6h6v-2h-4z"/></svg>
        </span>
        <span class="stato-testo">Da trattare</span>
    </span>
</div>
            <div class='scrollingArea'>
                <table id='risultati' width=100% border='0' align='center'>
                    <colgroup>
                        <col style="width: 4%;">
                        <col style="width: 9%;">
                        <col style="width: 31%;">
                        <col style="width: 22%;">
                        <col style="width: 20%;">
                        <col style="width: 10%;">
                        <col style="width: 6%;">
                    </colgroup>
                    <thead>
                        <tr class='tableheader'>
                            <th>Nr</th>
                            <th>Data<br>Rinvenimento</th>
                            <th>Breve descrizione oggetti</th>
                            <th>Luogo Rinvenimento</th>
                            <th>Proprietario</th>
                            <th>Posizione</th>
                            <th>
                                <span id="text_sel_all">Seleziona Tutti</span>
                                <input id='sel_tutti' type='checkbox'/>
                            </th>
                        </tr>
                        </thead>
                    <tbody id='ajax_result'></tbody>
                </table>
            </div>
            <div id='pulsanti'>
                <br/><br/>
                <table align='center'>
                    <tr>
                        <td><button type='submit'>Invia a<br>O.R. Bologna</button></td>
                        <td><button type='button' id='reset'>Annulla</button></td>
                        <td>
                            <span class='livello1'  style='display:none'>
                                <button type='button' id='elimina'>Elimina ultima trasmissione</button>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
    </td>
</tr>
</table>
<div id='popup'>test</div>
<div id='statusbar'></div>

<?php require_once 'config/footer.php'; ?>