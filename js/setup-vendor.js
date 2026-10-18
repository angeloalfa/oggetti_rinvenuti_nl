const fs = require('fs');
const path = require('path');

const src = path.join(__dirname, 'node_modules', 'chart.js', 'dist', 'chart.umd.js');
const destDir = path.join(__dirname, 'vendor');
const destFile = path.join(destDir, 'chart.umd.js');

// 1. Assicura che la cartella js/vendor/ esista
if (!fs.existsSync(destDir)) {
    fs.mkdirSync(destDir, { recursive: true });
}

// 2. Copia il file UMD di Chart.js nella destinazione finale
if (fs.existsSync(src)) {
    fs.copyFileSync(src, destFile);
    console.log('✅ Chart.js copiato con successo in: js/vendor/chart.umd.js');
} else {
    console.error('❌ Impossibile trovare il file sorgente di Chart.js');
}

// 3. Rimuove la cartella node_modules per mantenere il progetto pulito
const nodeModulesDir = path.join(__dirname, 'node_modules');
if (fs.existsSync(nodeModulesDir)) {
    fs.rmSync(nodeModulesDir, { recursive: true, force: true });
    console.log('🧹 Cartella node_modules rimossa.');
}