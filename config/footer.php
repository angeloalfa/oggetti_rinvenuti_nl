<?php
$cognome = $_SESSION['cognome'] ?? '';
$nome    = $_SESSION['nome'] ?? '';
$ufficio = $_SESSION['ufficio'] ?? '';
?>

        </td>
    </tr>
</table> <!-- Chiusura tabella principale dell'applicazione -->

</body>
<script>
$(document.ready).ready(function() {
    // Inizializzazione Dialog jQuery UI
    var $aboutDialog = $('#about-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 480,
        resizable: false,
        draggable: true,
        dialogClass: 'cl-dialog',
        buttons: {
            "Chiudi": function() {
                $(this).dialog("close");
            }
        }
    });

    // Apertura al click sul Logo
    $('#btn-about').on('click', function() {
        $aboutDialog.dialog('open');
    });

    // Gestione Feedback a Stelle
    var selectedRating = 0;

    $('.star-rating .star').on('mouseover', function() {
        var val = $(this).data('value');
        highlightStars(val);
    }).on('mouseout', function() {
        highlightStars(selectedRating);
    }).on('click', function() {
        selectedRating = $(this).data('value');
        highlightStars(selectedRating);
        
        // Invio AJAX del feedback
        $.ajax({
            type: 'POST',
            url: 'salva_feedback.php',
            data: { rating: selectedRating },
            success: function(response) {
                $('#rating-status').css('color', '#2e9e44').text('Grazie per il tuo feedback (' + selectedRating + '/5)!');
            },
            error: function() {
                $('#rating-status').css('color', '#c62828').text('Errore durante l\'invio del feedback.');
            }
        });
    });

    function highlightStars(count) {
        $('.star-rating .star').each(function() {
            var starVal = $(this).data('value');
            if (starVal <= count) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }
});
</script>
</html>

<?php 
if (isset($conn) && $conn) {
    mysqli_close($conn); 
}
?>