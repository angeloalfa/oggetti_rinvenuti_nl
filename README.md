# oggetti_rinvenuti_nl
Oggetti Rinvenuti è un'applicazione web gestionale sviluppata in PHP, MySQL/MariaDB e jQuery per la catalogazione, tracciabilità e gestione telematica del materiale e degli oggetti smarriti negli ambiti ferroviari e di stazione (sviluppata per la Polizia Ferroviaria).

# Oggetti Rinvenuti - Gestionale Web

**Oggetti Rinvenuti** è la piattaforma web istituzionale sviluppata dal Settore Informatico del Compartimento Polizia Ferroviaria per l'Emilia Romagna per la tracciabilità, catalogazione e reportistica telematica dei beni e degli oggetti smarriti e rinvenuti negli ambiti ferroviari.

---

## 🚀 Caratteristiche Principali

* **Gestione Operativa Rinvenimenti**:
  * Ricerca rapida in real-time tramite protocollo, testo libero, data e Ufficio Polfer.
  * Tracciamento dello stato tramite badge cromatici: *Da trattare*, *In attesa di restituzione*, *Accertamenti in corso*, *Da trasmettere*.
  * Procedura guidata di trasmissione telematica del materiale verso l'Ufficio O.R. di Bologna.
  * Anteprima di stampa landscape formattata per A4.

* **Statistiche & Analytics (Offline-Ready)**:
  * Grafici interattivi integrati con **Chart.js v4** (senza dipendenze esterne/CDN).
  * Schede KPI sintetiche: *Totale Rinvenimenti*, *Oggetti Riconsegnati*, *Oggetti Trasmessi*.
  * Grafico a ciambella della ripartizione per stato e grafico a barre con le Top 5 tipologie di oggetti.
  * Grafico a linee ad adattamento dinamico:
    * **Modalità Giornaliera**: selezionando Mese + Anno specifici.
    * **Modalità Mensile**: selezionando "Tutti i Mesi" per un singolo Anno.
    * **Modalità Annuale**: selezionando "Tutti gli Anni".

* **Header/Footer Istituzionale & Layout Responsive**:
  * Header unificato a 3 colonne (Logo/Versione, Operatore loggato, Navigazione contestuale).
  * Gestione visibilità dinamica dei pulsanti in base al file corrente e al livello utente (`Manager Auth` riservato al Livello 0).
  * Dialog "Informazioni sull'applicazione" con sistema di rating/feedback a 5 stelle memorizzato su DB.
  * Console riservata agli amministratori (Livello 0) per la lettura dei feedback degli utenti e la gestione delle autorizzazioni.

---

## 🛠️ Architettura Tecnica

* **Backend**: PHP 8.x (MySQLi, Sessions).
* **Database**: MySQL / MariaDB (Schema `oggetti2`).
* **Frontend**: HTML5, CSS3 (Flexbox/Grid), jQuery, jQuery UI.
* **Librerie Grafiche**: Chart.js v4 (versione UMD compilata in locale in `js/vendor/chart.umd.js`).

---

## 📁 Struttura del Progetto

```text
applicazioni/oggetti_rinvenuti/
├── config/
│   ├── header.php                 # Header istituzionale e layout iniziale
│   ├── footer.php                 # Footer fisso
│   ├── connect.php                # Connessione al Database MySQL
│   └── autorizza.php              # Controllo sessioni e ruoli utente
├── css/
│   └── all.css                    # CSS unificato per layout, header e componenti
├── js/
│   └── vendor/
│       └── chart.umd.js           # Libreria locale Chart.js v4
├── functions/
│   └── function.php               # Funzioni di supporto globale
├── oggetti_rinvenuti_list.php     # Home page e gestione della lista rinvenimenti
├── statistiche.php                # Dashboard analitica e grafici
├── salva_feedback.php             # Endpoint AJAX salvataggio feedback
├── get_feedback_list.php          # Endpoint AJAX consultazione feedback (Livello 0)
├── setup-vendor.js                # Script di posizionamento librerie locali
└── package.json                   # Gestione dipendenze NPM per sviluppo locale
```

## 📦 Installazione e Configurazione Asset Locali
Per aggiornare o scaricare le librerie JS in locale tramite NPM mantenendo l'ambiente di produzione privo della cartella node_modules:

Esegui l'installazione delle dipendenze nella cartella di sviluppo:

```Bash

npm install
```

Lo script automatico setup-vendor.js copierà il file chart.umd.js nella directory js/vendor/ e rimuoverà automaticamente la cartella node_modules.

## 🔐 Livelli di Autorizzazione
* Livello 0 (Amministratore): Accesso a tutte le sezioni, compreso il menu Manager Auth, la pulizia record e il pannello di consultazione dei feedback ricevuti.

* Livello 1 (Supervisore/Brandi): Accesso alle opzioni di invio e revoca trasmissioni.

* Livello 2 (Operatori Bologna Cle): Autorizzazione all'invio e stampa delle distinte.

* Livello 3 (Posti Polfer): Consultazione e registrazione base dei rinvenimenti.

## 👥 Copyright
© 2026 - Realizzato dal Settore Informatico del Compartimento Polizia Ferroviaria per l'Emilia Romagna.
