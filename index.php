<?php
// ==========================================
// 1. CONNEXION À LA BASE DE DONNÉES
// ==========================================
$host = 'localhost';
$dbname = 'carte';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// ==========================================
// 2. GESTION DES ACTIONS (AJAX)
// ==========================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Lister
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT * FROM stagiaires ORDER BY date_creation DESC");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Ajouter
    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO stagiaires (telephone, formation, nom, prenoms, photo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['telephone'], $data['formation'], $data['nom'], $data['prenoms'], $data['photo'] ?? null]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getCode() == 23000 ? 'Ce numéro de téléphone existe déjà.' : $e->getMessage()]);
        }
        exit;
    }

    // Modifier
    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE stagiaires SET telephone=?, formation=?, nom=?, prenoms=?, photo=? WHERE id=?");
        $stmt->execute([$data['telephone'], $data['formation'], $data['nom'], $data['prenoms'], $data['photo'] ?? null, $data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Supprimer
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM stagiaires WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MANDIGO SARL — Gestion des stagiaires</title>
<style>
:root{
  --navy:#10243a;
  --blue:#00b4d8;
  --blue-dark:#0b3a66;
  --orange:#f39a19;
  --orange-dark:#e17f00;
  --bg:#f2f5f8;
  --text:#182638;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,Segoe UI,Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#edf3f8 0%,#f7f9fb 260px);color:var(--text)}

/* ============================================================
   EN-TÊTE DU SITE
   ============================================================ */
header.mandigo-header {
    position: relative;
    width: 100%;
    height: 110px;
    background: #00b4d8;
    overflow: hidden;
    display: flex;
    align-items: center;
    padding: 0 max(5%, calc((100% - 1160px) / 2));
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.mandigo-header::before, .mandigo-header::after {
    content: ''; position: absolute; top: -50%; bottom: -50%; width: 45%;
    transform: skewX(-25deg); z-index: 1;
}
.mandigo-header::before { left: -10%; background: linear-gradient(to right, #f39a19, #ff8c00); z-index: 1; }
.mandigo-header::after { right: -15%; background: #0b3a66; z-index: 2; }
.mandigo-content { position: relative; z-index: 10; display: flex; align-items: center; gap: 25px; }
.mandigo-logo { position: relative; width: 80px; height: 80px; flex-shrink: 0; }
.mandigo-logo .m-left, .mandigo-logo .m-right {
    position: absolute; bottom: 15px; width: 28px; height: 60px;
    background: #fff; border-radius: 4px;
}
.mandigo-logo .m-left { left: 10px; transform: skewX(18deg); background: linear-gradient(to bottom, #fff 50%, #f39a19 50%); }
.mandigo-logo .m-right { right: 10px; transform: skewX(-18deg); background: linear-gradient(to bottom, #fff 50%, #00b4d8 50%); }
.mandigo-logo .logo-text {
    position: absolute; bottom: -5px; width: 100%; text-align: center;
    font-family: Arial, sans-serif; font-size: 10px; font-weight: 900;
    color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); line-height: 1;
}
.mandigo-logo .logo-text span { display: block; font-size: 8px; color: #f39a19; margin-bottom: 1px; }
.mandigo-titles { display: flex; flex-direction: column; }
.mandigo-titles h1 {
    margin: 0; font-size: clamp(24px, 4vw, 38px); font-weight: 900;
    color: white; letter-spacing: 1px; text-transform: uppercase;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
}
.mandigo-titles p {
    margin: 4px 0 0; font-size: clamp(14px, 2vw, 20px); font-weight: 400;
    color: white; letter-spacing: 5px; text-transform: uppercase;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
}

/* ============================================================
   RESTE DU DESIGN
   ============================================================ */
main{max-width:1180px;margin:30px auto;padding:0 18px;display:grid;grid-template-columns:minmax(300px,.95fr) minmax(340px,1.05fr);gap:24px;align-items:start}
.panel{background:#fff;border-radius:18px;padding:24px;box-shadow:0 12px 34px #10243a0d;border:1px solid #e0e8ef}
h2{margin:0 0 20px;font-size:21px;color:var(--navy);letter-spacing:-.3px}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}
label{display:block;font-weight:700;font-size:13px;margin-bottom:6px}
input,select,textarea{width:100%;padding:12px 13px;border:1px solid #ccd6df;border-radius:9px;font:inherit;background:#fff;transition:border-color .15s,box-shadow .15s}
input:focus,select:focus{outline:2px solid #08b9d455;border-color:var(--blue)}
.full{grid-column:1/-1}
.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:17px}
button{border:0;border-radius:9px;padding:12px 16px;font-weight:700;cursor:pointer;background:var(--navy);color:white;box-shadow:0 3px 8px #10243a12;transition:transform .15s,filter .15s}
button:hover{filter:brightness(1.08);transform:translateY(-1px)}
button.secondary{background:#e8eef3;color:var(--navy)}
button.orange{background:var(--orange);color:#172536}
button.danger{background:#b42318}
.note{font-size:12px;color:#607080;line-height:1.5;margin-top:14px}

/* ============================================================
   APERÇU DE LA CARTE (ÉCRAN)
   ============================================================ */
.card-wrap{display:flex;justify-content:center;padding:8px 0 18px}
.id-card{
  width:min(100%, 320px);
  aspect-ratio: 0.68;
  background:#fff;
  border:1px solid #d7e0e8;
  border-radius:13px;
  overflow:hidden;
  box-shadow:0 12px 28px #10243a24;
  position:relative;
  color:#13273c;
  display:flex;
  flex-direction:column;
}
.card-header-colorful {
  position: relative;
  background: var(--blue);
  padding-bottom: 10px;
  overflow: hidden;
}
.card-header-colorful::before {
  content: ''; position: absolute; top: -50%; bottom: -50%; left: -15%; width: 40%;
  background: linear-gradient(to right, #f39a19, #ff8c00); transform: skewX(-25deg); z-index: 1;
}
.card-header-colorful::after {
  content: ''; position: absolute; top: -50%; bottom: -50%; right: -20%; width: 40%;
  background: var(--blue-dark); transform: skewX(-25deg); z-index: 2;
}
.card-head-content {
  position: relative; z-index: 10; display: flex; align-items: center; padding: 15px; gap: 12px;
}
.card-head-content img {
  width: 55px; height: 55px; object-fit: contain; background: #fff; border-radius: 8px; padding: 2px; flex-shrink: 0;
}
.card-head-text { display: flex; flex-direction: column; color: white; }
.card-head-text .company-name {
  font-size: 18px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.3); line-height: 1.1;
}
.card-head-text .company-sub {
  font-size: 10px; font-weight: 400; letter-spacing: 2px; text-transform: uppercase;
  color: #fff; opacity: 0.9; margin-top: 2px;
}
.card-title-inside {
  position: relative; z-index: 10; text-align: center; font-size: 12px; font-weight: 800;
  letter-spacing: 2px; color: white; text-transform: uppercase; margin-top: 5px;
}
.card-body{
  display:flex;flex-direction:column;align-items:center;
  padding:15px 16px 0;gap:12px;flex:1;
}
.photo{
  width:110px;height:124px; border:1px dashed #a9b8c5;border-radius:8px;
  display:flex;align-items:center;justify-content:center;text-align:center;
  color:#8a9aa9;font-size:11px;background:#f6f8fa;overflow:hidden;
}
.photo img{width:100%;height:100%;object-fit:cover}
.fields{width:100%;min-width:0}
.field{margin-bottom:8px;font-size:10px;border-bottom:1px solid #e4e9ed;padding-bottom:4px;overflow-wrap:anywhere}
.field b{display:block;color:#607184;font-size:8px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:3px}
.field span{font-weight:800;font-size:13px;line-height:1.2}
.card-foot{
  background:#f1f5f8;border-top:1px solid #dce5eb;
  padding:8px 13px;display:flex;justify-content:space-between;align-items:center;
  font-size:9px;color:#4b6071;margin-top:auto;
}
.card-foot strong{color:var(--orange-dark);font-size:9px}

/* Tableau & Boutons */
.list-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:22px}
.list-head h2{margin:0}
.search{margin:12px 0}
table{border-collapse:collapse;width:100%;font-size:13px}
th,td{text-align:left;padding:10px 8px;border-bottom:1px solid #e6ebef}
th{background:#f4f7f9;color:#425568}
td button{padding:6px 8px;font-size:11px;margin:2px}
.empty{text-align:center;color:#7b8a98;padding:18px}
footer{text-align:center;padding:20px;color:#718090;font-size:12px}

/* ============================================================
   CARTE POUR L'IMPRESSION (Format strict)
   ============================================================ */
#printSheet{display:none}

.print-card{
  width: 80mm;
  height: 120mm;
  background:#fff;
  border:1px solid #cbd5df;
  border-radius:2mm;
  overflow:hidden;
  position:relative;
  color:#13273c;
  break-inside:avoid;
  page-break-inside:avoid;
  display:flex;
  flex-direction:column;
}

.print-card .card-header-colorful {
  position: relative;
  background: var(--blue);
  padding-bottom: 2mm;
  overflow: hidden;
  flex-shrink: 0;
}
.print-card .card-header-colorful::before {
  content: ''; position: absolute; top: -50%; bottom: -50%; left: -15%; width: 40%;
  background: linear-gradient(to right, #f39a19, #ff8c00); transform: skewX(-25deg); z-index: 1;
}
.print-card .card-header-colorful::after {
  content: ''; position: absolute; top: -50%; bottom: -50%; right: -20%; width: 40%;
  background: var(--blue-dark); transform: skewX(-25deg); z-index: 2;
}
.print-card .card-head-content {
  position: relative; z-index: 10; display: flex; align-items: center; padding: 3mm; gap: 3mm;
}
.print-card .card-head-content img {
  width: 14mm; height: 14mm; object-fit: contain; background: #fff; border-radius: 1.5mm; padding: 0.5mm; flex-shrink: 0;
}
.print-card .card-head-text .company-name { font-size: 10pt; font-weight: 900; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); line-height: 1.1; }
.print-card .card-head-text .company-sub { font-size: 5.5pt; letter-spacing: 1.5mm; margin-top: 0.5mm; }
.print-card .card-title-inside { position: relative; z-index: 10; text-align: center; font-size: 7pt; font-weight: 800; color: white; letter-spacing: 1mm; margin-top: 1mm; }

.print-card .card-body{
  gap: 3mm; padding: 4mm 5mm 0; display: flex; flex-direction: column; align-items: center; flex: 1; min-height: 0;
}
.print-card .photo{
  width: 28mm; height: 32mm; border-radius: 2mm; font-size: 6pt; flex-shrink: 0;
}
.print-card .fields{width:100%}
.print-card .fields .field{margin-bottom: 2mm; padding-bottom: 1mm; font-size: 7pt; border-bottom: 0.5pt solid #e4e9ed;}
.print-card .fields .field b{font-size: 5pt; margin-bottom: 0.5mm;}
.print-card .fields .field span{font-size: 8.5pt; line-height: 1.2;}
.print-card .card-foot{padding: 2mm 5mm; font-size: 6pt; margin-top: auto; flex-shrink: 0;}
.print-card .card-foot strong{font-size: 6pt;}

/* ============================================================
   CONFIGURATION DE L'IMPRESSION (4 PAR PAGE A4)
   ============================================================ */
@media print{
  *{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;color-adjust:exact!important}
  @page{size:A4 portrait;margin:10mm;}
  
  body.batch-print header, body.batch-print main, body.batch-print footer{display:none!important}
  
  body.batch-print #printSheet{
    display:grid!important;
    grid-template-columns: 85mm 85mm;
    grid-auto-rows: 130mm;
    gap: 10mm 5mm;
    justify-content:center;
    align-content:start;
    padding:0;
  }
  body.batch-print .print-card{box-shadow:none}
  
  body.batch-print .print-card:nth-child(4n) {
    page-break-after: always;
  }
}

@media print{
  /* Impression d'une seule carte */
  body:not(.batch-print) {background:#fff!important}
  body:not(.batch-print) header,
  body:not(.batch-print) .form-panel,
  body:not(.batch-print) .batch-panel,
  body:not(.batch-print) .list-head,
  body:not(.batch-print) .search,
  body:not(.batch-print) .screen-only,
  body:not(.batch-print) footer,
  body:not(.batch-print) main > section > table {display:none!important}
  
  body:not(.batch-print) main{display:block;margin:0;padding:0}
  body:not(.batch-print) .preview-panel{padding:0;box-shadow:none;border:0}
  body:not(.batch-print) .preview-panel h2{display:none}
  body:not(.batch-print) .card-wrap{padding:0}
  
  body:not(.batch-print) .id-card{
    box-shadow:none; width:80mm; height:120mm; aspect-ratio:auto; border-radius:2mm; background:#fff!important; border:1px solid #d7e0e8;
  }
  
  /* Couleurs forcées à l'impression */
  .id-card .photo, .print-card .photo{background:#f6f8fa!important}
  .id-card .card-foot, .print-card .card-foot{background:#f1f5f8!important}
  .id-card .card-foot strong, .print-card .card-foot strong{color:var(--orange-dark)!important}
}

/* === RESPONSIVE === */
@media(max-width:850px){main{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr 1fr}}
@media(max-width:480px){
  .form-grid{grid-template-columns:1fr}
  .full{grid-column:auto}
  .panel{padding:16px}
}
@media(max-width: 600px) {
    header.mandigo-header { height: 90px; }
    .mandigo-logo { width: 60px; height: 60px; }
    .mandigo-logo .m-left, .mandigo-logo .m-right { width: 20px; height: 45px; bottom: 12px; }
    .mandigo-logo .logo-text { font-size: 8px; bottom: -3px; }
    .mandigo-logo .logo-text span { font-size: 6px; }
    .mandigo-titles h1 { font-size: 22px; }
    .mandigo-titles p { font-size: 11px; letter-spacing: 2px; }
}
</style>
</head>
<body>

<!-- EN-TÊTE DU SITE -->
<header class="mandigo-header">
    <div class="mandigo-content">
        <div class="mandigo-logo">
            <div class="m-left"></div>
            <div class="m-right"></div>
            <div class="logo-text"><span>SARL</span>MANDIGO</div>
        </div>
        <div class="mandigo-titles">
            <h1>MANDIGO SARL</h1>
            <p>CARTE STAGIAIRE</p>
        </div>
    </div>
</header>

<main>
<section class="panel form-panel">
  <h2>Enregistrer un stagiaire</h2>
  <form id="internForm">
    <div class="form-grid">
      <div><label for="telephone">Téléphone *</label><input id="telephone" required placeholder="Ex. +225 01 02 03 04 05"></div>
      <div><label for="formation">Formation *</label><input id="formation" required placeholder="Ex. Informatique"></div>
      <div><label for="nom">Nom *</label><input id="nom" required></div>
      <div><label for="prenoms">Prénoms *</label><input id="prenoms" required></div>
      <div class="full"><label for="photo">Photo du stagiaire (facultatif)</label><input id="photo" type="file" accept="image/*"></div>
    </div>
    <div class="actions"><button type="submit" id="submitBtn">Enregistrer le stagiaire</button><button type="button" class="secondary" id="resetBtn">Vider le formulaire</button></div>
    <p class="note">Les informations sont enregistrées sur le serveur de base de données (MySQL).</p>
  </form>
</section>
<section class="panel preview-panel">
  <h2>Aperçu de la carte</h2>
  <div class="card-wrap">
    <div class="id-card" id="idCard">
      
      <!-- En-tête de la carte -->
      <div class="card-header-colorful">
        <div class="card-head-content">
          <img src="logo.jpeg" alt="Mandigo">
          <div class="card-head-text">
            <div class="company-name">MANDIGO SARL</div>
            <div class="company-sub">CARTE STAGIAIRE</div>
          </div>
        </div>
        <div class="card-title-inside">CARTE D’IDENTIFICATION</div>
      </div>

      <div class="card-body">
        <div class="photo" id="photoPreview">PHOTO<br>DU STAGIAIRE</div>
        <div class="fields">
          <div class="field"><b>Nom</b><span id="vNom">NOM DU STAGIAIRE</span></div>
          <div class="field"><b>Prénoms</b><span id="vPrenoms">Prénoms du stagiaire</span></div>
          <div class="field"><b>Téléphone</b><span id="vTelephone">+225 01 02 03 04 05</span></div>
          <div class="field"><b>Formation</b><span id="vFormation">Formation</span></div>
        </div>
      </div>
      <div class="card-foot"><span><strong>MANDIGO SARL</strong><br>Signature / Cachet</span></div>
    </div>
  </div>
  <div class="actions screen-only"><button class="orange" id="printBtn" type="button">Imprimer la carte</button><button type="button" class="secondary" id="downloadBtn">Exporter la liste CSV</button></div>
  <p class="note screen-only">Conseil : pour imprimer la carte, choisis le format portrait et désactive les marges du navigateur.</p>
  <div class="list-head screen-only"><h2>Stagiaires enregistrés</h2><span id="count">0 stagiaire</span></div>
  <input class="search screen-only" id="search" placeholder="Rechercher par nom ou téléphone..." aria-label="Rechercher">
  <div class="screen-only" style="overflow-x:auto"><table><thead><tr><th>Téléphone</th><th>Nom complet</th><th>Formation</th><th>Actions</th></tr></thead><tbody id="internRows"><tr><td colspan="4" class="empty">Chargement des données...</td></tr></tbody></table></div>
</section>

<section class="panel batch-panel screen-only">
  <h2>Impression groupée</h2>
  <p class="note">Imprime toutes les cartes enregistrées sur plusieurs pages A4. Les cartes sont disposées en grille (4 par page) pour être découpées après impression.</p>
  <div class="actions"><button type="button" class="orange" id="printAllBtn">Imprimer toutes les cartes</button></div>
</section>
</main>
<section id="printSheet" aria-hidden="true"></section>
<footer>MANDIGO SARL · Outil de gestion des cartes stagiaires</footer>
<script>
const $ = id => document.getElementById(id);
let interns = [];
let photoData = '';
let editingId = null;
const fields = ['telephone','formation','nom','prenoms'];

// Charge les données depuis le serveur (index.php)
async function fetchInterns() {
  try {
    const res = await fetch('?action=list');
    interns = await res.json();
    render();
  } catch (e) {
    console.error(e);
    alert("Erreur de connexion au serveur.");
  }
}

function values(){
  const obj={};
  fields.forEach(f=>obj[f]=$(f).value.trim());
  obj.photo=photoData;
  return obj;
}

function updatePreview(){
  const v=values();
  $('vNom').textContent=v.nom||'NOM DU STAGIAIRE';
  $('vPrenoms').textContent=v.prenoms||'Prénoms du stagiaire';
  $('vTelephone').textContent=v.telephone||'+225 01 02 03 04 05';
  $('vFormation').textContent=v.formation||'Formation';
  $('photoPreview').innerHTML=v.photo?`<img src="${v.photo}" alt="Photo du stagiaire">`:'PHOTO<br>DU STAGIAIRE';
}

fields.forEach(f=>$(f).addEventListener('input',updatePreview));

$('photo').addEventListener('change',e=>{
  const file=e.target.files[0];
  if(!file){photoData='';updatePreview();return}
  if(file.size>2*1024*1024){alert('La photo doit faire moins de 2 Mo.');e.target.value='';return}
  const reader=new FileReader();
  reader.onload=()=>{photoData=reader.result;updatePreview()};
  reader.readAsDataURL(file);
});

$('internForm').addEventListener('submit', async e => {
  e.preventDefault();
  const v = values();
  const action = editingId ? 'edit' : 'add';
  if(editingId) v.id = editingId;

  const btn = $('submitBtn');
  btn.disabled = true;
  btn.textContent = 'Enregistrement...';

  try {
    const res = await fetch(`?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(v)
    });
    const data = await res.json();

    if(!res.ok) throw new Error(data.error || "Erreur d'enregistrement");

    alert('Stagiaire enregistré avec succès.');
    editingId = null;
    e.target.reset();
    photoData = '';
    updatePreview();
    fetchInterns(); // Recharger la liste
  } catch (error) {
    alert(error.message);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Enregistrer le stagiaire';
  }
});

$('resetBtn').addEventListener('click',()=>{
  editingId=null;
  $('internForm').reset();
  photoData='';
  updatePreview();
});

$('printBtn').addEventListener('click',()=>{
  document.body.classList.remove('batch-print');
  window.print();
});

$('printAllBtn').addEventListener('click',()=>{
  if(!interns.length){
    alert('Enregistre au moins un stagiaire avant l’impression groupée.');
    return;
  }
  const sheet=$('printSheet');
  sheet.innerHTML=interns.map(x=>cardMarkup(x)).join('');
  document.body.classList.add('batch-print');
  window.print();
  setTimeout(()=>document.body.classList.remove('batch-print'),800);
});

function cardMarkup(x){
  const photo=x.photo?`<img src="${x.photo}" alt="Photo du stagiaire">`:'PHOTO<br>DU STAGIAIRE';
  return `<div class="print-card">
    <div class="card-header-colorful">
      <div class="card-head-content">
        <img src="logo.jpeg" alt="Mandigo">
        <div class="card-head-text">
          <div class="company-name">MANDIGO SARL</div>
          <div class="company-sub">CARTE STAGIAIRE</div>
        </div>
      </div>
      <div class="card-title-inside">CARTE D’IDENTIFICATION</div>
    </div>
    <div class="card-body">
      <div class="photo">${photo}</div>
      <div class="fields">
        <div class="field"><b>Nom</b><span>${escapeHtml(x.nom)}</span></div>
        <div class="field"><b>Prénoms</b><span>${escapeHtml(x.prenoms)}</span></div>
        <div class="field"><b>Téléphone</b><span>${escapeHtml(x.telephone)}</span></div>
        <div class="field"><b>Formation</b><span>${escapeHtml(x.formation)}</span></div>
      </div>
    </div>
    <div class="card-foot">
      <span><strong>MANDIGO SARL</strong><br>Signature / Cachet</span>
    </div>
  </div>`;
}

$('search').addEventListener('input',render);

function render(){
  const q=($('search').value||'').toLowerCase();
  const shown=interns.filter(x=>(x.nom+' '+x.prenoms+' '+x.telephone+' '+x.formation).toLowerCase().includes(q));
  $('count').textContent=interns.length+' stagiaire(s)';
  $('internRows').innerHTML=shown.length
    ? shown.map(x=>`<tr>
        <td>${escapeHtml(x.telephone)}</td>
        <td>${escapeHtml(x.nom+' '+x.prenoms)}</td>
        <td>${escapeHtml(x.formation)}</td>
        <td>
          <button type="button" onclick="editIntern('${x.id}')">Modifier</button>
          <button type="button" class="danger" onclick="deleteIntern('${x.id}')">Supprimer</button>
          <button type="button" class="secondary" onclick="showIntern('${x.id}')">Carte</button>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="4" class="empty">Aucun résultat.</td></tr>';
}

function escapeHtml(s){
  return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

window.editIntern=id=>{
  const x=interns.find(a=>a.id==id);
  if(!x)return;
  editingId=id;
  fields.forEach(f=>$(f).value=x[f]||'');
  photoData=x.photo||'';
  updatePreview();
  window.scrollTo({top:0,behavior:'smooth'});
};

window.deleteIntern=async id=>{
  if(!confirm('Supprimer ce stagiaire ?'))return;
  try {
    await fetch('?action=delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    fetchInterns();
  } catch (e) {
    alert("Erreur de suppression.");
  }
};

window.showIntern=id=>{
  const x=interns.find(a=>a.id==id);
  if(!x)return;
  editingId=null;
  fields.forEach(f=>$(f).value=x[f]||'');
  photoData=x.photo||'';
  updatePreview();
  window.scrollTo({top:0,behavior:'smooth'});
};

$('downloadBtn').addEventListener('click',()=>{
  const cols=['telephone','nom','prenoms','formation'];
  const quote=s=>'"'+String(s||'').replace(/"/g,'""')+'"';
  const csv='\ufeff'+[cols.join(';'),...interns.map(x=>cols.map(c=>quote(x[c])).join(';'))].join('\r\n');
  const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});
  const a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download='liste_stagiaires_mandigo.csv';
  a.click();
  URL.revokeObjectURL(a.href);
});

// Initialisation au chargement de la page
updatePreview();
fetchInterns();
</script>
</body>
</html>