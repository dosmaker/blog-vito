-- ============================================
-- POST DI ESEMPIO PER TESTARE IL BLOG
-- ============================================
-- Esegui questo script DOPO aver importato schema.sql
-- Questi post dimostrano le funzionalità del blog

-- Post 1: Tutorial con codice
INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) VALUES (
'Guida a PHP: I Primi Passi',
'guida-php-primi-passi',
'<h2>Introduzione a PHP</h2>
<p>PHP è un linguaggio di scripting lato server perfetto per lo sviluppo web. In questa guida vedremo i concetti base.</p>

<h3>Il tuo primo script PHP</h3>
<p>Crea un file chiamato <code>hello.php</code> con questo contenuto:</p>

<pre><code>&lt;?php
echo "Ciao Mondo!";
?&gt;</code></pre>

<h3>Variabili e Tipi di Dati</h3>
<p>PHP supporta diversi tipi di dati:</p>
<ul>
    <li><strong>String</strong> - Testo tra virgolette</li>
    <li><strong>Integer</strong> - Numeri interi</li>
    <li><strong>Float</strong> - Numeri decimali</li>
    <li><strong>Boolean</strong> - true o false</li>
    <li><strong>Array</strong> - Liste di valori</li>
</ul>

<blockquote>
<p>💡 <strong>Suggerimento:</strong> PHP è un linguaggio debolmente tipizzato, quindi non devi dichiarare il tipo delle variabili!</p>
</blockquote>

<p>Per maggiori informazioni, visita la <a href="https://www.php.net" target="_blank">documentazione ufficiale di PHP</a>.</p>',
1, 1
);

-- Post 2: Con immagine placeholder
INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) VALUES (
'Bootstrap 5: Il Framework CSS Moderno',
'bootstrap-5-framework-css',
'<h2>Perché Bootstrap 5?</h2>
<p>Bootstrap 5 è la versione più recente del framework CSS più popolare al mondo. Ecco perché dovresti usarlo:</p>

<h3>Vantaggi Principali</h3>
<ol>
    <li><strong>Responsive by Default</strong> - Il tuo sito si adatta automaticamente a tutti i dispositivi</li>
    <li><strong>Componenti Pronti</strong> - Navbar, card, modals, tutto già pronto</li>
    <li><strong>Niente jQuery</strong> - Bootstrap 5 usa solo JavaScript vanilla</li>
    <li><strong>Personalizzabile</strong> - Usa le variabili CSS per cambiare i colori</li>
</ol>

<h3>Sistema a Griglia</h3>
<p>Il sistema a griglia di Bootstrap usa 12 colonne. Esempio:</p>

<pre><code>&lt;div class="row"&gt;
    &lt;div class="col-md-6"&gt;Metà schermo&lt;/div&gt;
    &lt;div class="col-md-6"&gt;Altra metà&lt;/div&gt;
&lt;/div&gt;</code></pre>

<div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #0d6efd; margin: 20px 0;">
    <strong>📘 Nota Bene:</strong> Le classi <code>col-md-*</code> si applicano da tablet in su (medium devices).
</div>

<p>Inizia subito con Bootstrap visitando <a href="https://getbootstrap.com" target="_blank">getbootstrap.com</a>!</p>',
2, 1
);

-- Post 3: News breve
INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) VALUES (
'PHP 8.3 è Stato Rilasciato!',
'php-8-3-rilasciato',
'<h2>🎉 Novità in PHP 8.3</h2>

<p>È finalmente arrivato PHP 8.3 con tantissime novità interessanti per gli sviluppatori!</p>

<h3>Caratteristiche Principali</h3>

<ul>
    <li><strong>Typed Class Constants</strong> - Costanti di classe con tipo</li>
    <li><strong>Readonly Amendments</strong> - Miglioramenti alle proprietà readonly</li>
    <li><strong>New json_validate() Function</strong> - Valida JSON senza decodificarlo</li>
    <li><strong>Randomizer Additions</strong> - Nuovi metodi per la generazione random</li>
</ul>

<h3>Esempio: json_validate()</h3>

<pre><code>$json = \'{"nome": "Mario", "età": 30}\';

if (json_validate($json)) {
    echo "JSON valido!";
}</code></pre>

<blockquote>
<p>⚡ <em>Performance:</em> PHP 8.3 è fino al 10% più veloce di PHP 8.2 in alcuni benchmark!</p>
</blockquote>

<p><a href="https://www.php.net/releases/8.3/en.php" target="_blank" class="btn btn-primary">Leggi il changelog completo →</a></p>',
3, 1
);

-- Post 4: Con embed video (placeholder)
INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) VALUES (
'I Migliori Editor di Codice del 2024',
'migliori-editor-codice-2024',
'<h2>Quale Editor Scegliere?</h2>

<p>La scelta dell\'editor giusto può fare la differenza nella produttività. Ecco i migliori del 2024:</p>

<h3>1. Visual Studio Code ⭐⭐⭐⭐⭐</h3>
<ul>
    <li>✅ Gratuito e open source</li>
    <li>✅ Migliaia di estensioni</li>
    <li>✅ IntelliSense potente</li>
    <li>✅ Git integrato</li>
    <li>❌ Può essere pesante con molte estensioni</li>
</ul>

<h3>2. PHPStorm ⭐⭐⭐⭐</h3>
<ul>
    <li>✅ IDE completo per PHP</li>
    <li>✅ Refactoring avanzato</li>
    <li>✅ Database tools integrati</li>
    <li>❌ A pagamento (€199/anno)</li>
</ul>

<h3>3. Sublime Text ⭐⭐⭐⭐</h3>
<ul>
    <li>✅ Velocissimo</li>
    <li>✅ Interfaccia pulita</li>
    <li>✅ Multi-cursore potente</li>
    <li>❌ Meno estensioni di VSCode</li>
</ul>

<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
    <strong>💡 Il mio consiglio:</strong> Se sei alle prime armi, parti con <strong>Visual Studio Code</strong>. È gratuito, potente e ha una community enorme!
</div>

<h3>Video Tutorial: Setup VS Code per PHP</h3>
<p><em>Qui puoi inserire un video YouTube usando l\'editor. Clicca l\'icona "Media" e incolla l\'URL!</em></p>

<p style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;">
    <strong>Quale editor usi tu?</strong> Fammi sapere nei commenti! 👇
</p>',
4, 1
);

-- Post 5: Bozza (non pubblicato)
INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) VALUES (
'Come Creare un API REST con PHP',
'api-rest-php',
'<h2>Introduzione alle API REST</h2>
<p>Questo è un post in bozza che verrà completato prossimamente...</p>
<p>Le API REST sono fondamentali per la comunicazione tra applicazioni moderne.</p>',
1, 0
);

COMMIT;

-- ============================================
-- Fine script post di esempio
-- Ora puoi visualizzare il blog con contenuti di test!
-- ============================================
