<?php
session_start(); 
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- CORRECTION ADMIN ROBUSTE ---
$roleUpper = strtoupper(trim($_SESSION['role'] ?? $_SESSION['type_utilisateur'] ?? ''));
$isAdmin = in_array($roleUpper, ['ADMIN','ADMINISTRATEUR']) || (($_SESSION['pseudo'] ?? $_SESSION['nom'] ?? '') == 'michel.d');
$dashboardLink = $isAdmin ? 'dashboard_admin.php' : 'dashboard.php';

// --- CORRECTION SITE DYNAMIQUE ---
$id_site_session = $_SESSION['id_site'] ?? 1;
$sites = $pdo->query("SELECT * FROM site ORDER BY nom_site")->fetchAll();
$nomSiteActuel = 'Site inconnu';
foreach($sites as $s){ if($s['id_site']==$id_site_session) $nomSiteActuel=$s['nom_site']; }
$siteLabel = $isAdmin ? 'Tous les sites - Dashboard Global' : $nomSiteActuel;
$roleLabel = $isAdmin ? 'ADMINISTRATEUR - '.($_SESSION['pseudo']??'michel.d') : 'Gérant - '.$nomSiteActuel;

if(!isset($_SESSION['panier'])) $_SESSION['panier']=[];

$error = "";
function validTelBenin($tel){
  $tel = preg_replace('/\D/', '', trim($tel));
  return preg_match('/^01[0-9]{8}$/', $tel) ? $tel : false;
}
function validText($txt, $min=2){
  $txt = trim($txt ?? '');
  if($txt === '') return false;
  if(mb_strlen($txt) < $min) return false;
  if(!preg_match('/[a-zA-ZÀ-ÿ]{2,}/', $txt)) return false;
  return $txt;
}

if(isset($_GET['new'])){ $_SESSION['panier']=[]; $_SESSION['last_client']=[]; header("Location: commande.php"); exit; }

if(isset($_POST['add'])){ 
  $nomClean = validText($_POST['nom'] ?? '', 2);
  $prenomClean = validText($_POST['prenom'] ?? '', 2);
  $adresseClean = trim($_POST['adresse'] ?? '');
  $telOk = validTelBenin($_POST['tel'] ?? '');

  if(!$nomClean){
    $error = "❌ Nom invalide : ne peut pas être vide ou juste un espace. Minimum 2 lettres.";
  } elseif(!$prenomClean){
    $error = "❌ Prénom invalide : ne peut pas être vide ou juste un espace. Minimum 2 lettres.";
  } elseif(!$telOk){
    $error = "❌ Téléphone invalide. Format Bénin : 10 chiffres commençant par 01 (ex: 0197123456)";
  } else {
    $_SESSION['last_client']=['nom'=>$nomClean,'prenom'=>$prenomClean,'tel'=>$telOk,'adresse'=>$adresseClean, 'id_site_choisi'=>$_POST['id_site']??$id_site_session];
    $_SESSION['panier'][]=[
      'nom_article'=>trim($_POST['nom_article']),
      'etat_article'=>trim($_POST['etat_article']),
      'service'=>trim($_POST['service']),
      'prix'=>(int)$_POST['prix'],
      'qte'=>(int)$_POST['qte']
    ]; 
    header("Location: commande.php"); exit; 
  }
}
if(isset($_POST['valider'])){
 $nomClean = validText($_POST['nom'] ?? '', 2);
 $prenomClean = validText($_POST['prenom'] ?? '', 2);
 $adresseClean = trim($_POST['adresse'] ?? '');
 $telOk = validTelBenin($_POST['tel'] ?? '');

 if(!$nomClean){
   $error = "❌ Impossible de valider : Nom vide ou invalide.";
 } elseif(!$prenomClean){
   $error = "❌ Impossible de valider : Prénom vide ou invalide.";
 } elseif(!$telOk){
   $error = "❌ Téléphone invalide : 10 chiffres commençant par 01 requis.";
 } elseif(empty($_SESSION['panier'])){
   $error = "❌ Panier vide : ajoutez au moins un article.";
 } else {
   $pdo->prepare("INSERT INTO client(nom,prenom,telephone,adresse) VALUES(?,?,?,?)")->execute([$nomClean,$prenomClean,$telOk,$adresseClean]);
   $idc=$pdo->lastInsertId();
   // --- CORRECTION MULTI-SITE POUR ADMIN ---
   $id_site_final = $isAdmin ? (int)($_POST['id_site_final'] ?? $id_site_session) : $id_site_session;
   $pdo->prepare("INSERT INTO commande(id_client,id_users,id_site,Statut_commande) VALUES(?,?,?,?)")->execute([$idc,$_SESSION['id'],$id_site_final,'En cours']);
   $idcmd=$pdo->lastInsertId();
   $tot=0; foreach($_SESSION['panier'] as $a) $tot+=$a['prix']*$a['qte'];
   $pdo->prepare("INSERT INTO paiement(montant_paiement,mode_paiement,id_commande) VALUES(?,?,?)")->execute([$tot,$_POST['mode'],$idcmd]);
   $_SESSION['panier']=[]; $_SESSION['last_client']=[]; 
   header("Location: facture.php?id=$idcmd"); exit;
 }
}
$total=0; foreach($_SESSION['panier'] as $a) $total+=$a['prix']*$a['qte'];
$isEmpty = empty($_SESSION['panier']);
$last = $_SESSION['last_client'] ?? [];
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Nouvelle commande</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;background:radial-gradient(circle at top,#1c2f7a,#0a1938 70%);min-height:100vh;color:#fff}
.top{padding:8px 15px;background:#08132e;border-bottom:1px solid #1e3a6e;display:flex;justify-content:space-between;font-size:11px}
.pills{padding:10px 15px;display:flex;gap:6px}.pills a{padding:6px 14px;border-radius:20px;text-decoration:none;font-size:11px;background:#11245a;color:#8aa6d0;border:1px solid #24407a}.pills a.active{background:#cbe5ff;color:#0a1938;font-weight:bold}
.wrap{max-width:1050px;margin:15px auto;padding:0 15px}.grid{display:flex;gap:14px;justify-content:center}
.card{background:linear-gradient(180deg,#18307a,#143165);border:1px solid #2a4aa8;border-radius:12px;padding:14px;width:50%}
label{font-size:10px;color:#8aa6d0;display:block;margin:8px 0 3px 2px}
input,select{width:100%;background:#0a1a4a;border:1px solid #2a4aa8;color:#fff;padding:9px 10px;border-radius:8px;font-size:12px}
.row2{display:flex;gap:8px}.row2>div{flex:1}.btn{width:100%;background:#d6e8ff;color:#0a1938;border:none;padding:10px;border-radius:8px;font-weight:bold;margin-top:10px;cursor:pointer}
.infoBox{background:#0f235a;border:1px solid #2a4aa8;border-radius:8px;padding:8px 10px;margin-top:8px;font-size:11px}
.err{background:#fee2e2;color:#dc2626;padding:10px;border-radius:8px;margin-bottom:10px;font-size:12px;font-weight:700}
.ok{border-color:#22c55e!important}.bad{border-color:#ef4444!important}
</style></head><body>
<div class="top"><div>Site d'activité : <?= htmlspecialchars($siteLabel) ?> (<?= htmlspecialchars($roleLabel) ?>)</div><div><?= htmlspecialchars($_SESSION['pseudo']??$_SESSION['nom']??'')?></div></div>
<div class="pills"><a href="<?= $dashboardLink ?>">📊 <?= $isAdmin ? 'Dashboard Global' : 'Tableau de bord' ?></a><a class="active" href="commande.php?new=1">Nouvelle commande</a><a href="facture.php">Facture</a><a href="index.php">Déconnexion</a></div>
<div class="wrap">
<div style="font-size:18px;font-weight:bold">Nouvelle commande - <?= htmlspecialchars($isAdmin ? 'ADMIN' : $nomSiteActuel) ?></div><div style="font-size:11px;color:#8aa6d0">Paiement intégral obligatoire à la commande</div>
<?php if($error): ?><div class="err" style="margin-top:10px"><?= $error ?></div><?php endif; ?>
<div class="grid" style="margin-top:12px">
<div class="card">
<h3 style="margin:0">Client (Passer)</h3>
<form method="post" id="fClient" autocomplete="off">
<?php if($isAdmin): ?>
<label>Site de la commande (Admin) *</label>
<select name="id_site" required>
<?php foreach($sites as $s): ?>
<option value="<?= $s['id_site'] ?>" <?= ($last['id_site_choisi']??$id_site_session)==$s['id_site']?'selected':'' ?>><?= htmlspecialchars($s['nom_site']) ?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>
<div class="row2"><div><label>Nom *</label><input name="nom" id="nom" value="<?= htmlspecialchars($last['nom'] ?? '')?>" placeholder="Nom client" required minlength="2"></div><div><label>Prénom *</label><input name="prenom" id="prenom" value="<?= htmlspecialchars($last['prenom'] ?? '')?>" placeholder="Prénom" required minlength="2"></div></div>
<label>Téléphone (Bénin : 10 chiffres, commence par 01) *</label>
<input name="tel" id="tel" type="tel" value="<?= htmlspecialchars($last['tel'] ?? '')?>" placeholder="Ex: 0197123456" maxlength="10" pattern="^01[0-9]{8}$" required>
<small id="tel-help" style="font-size:10px;color:#8aa6d0">10 chiffres - commence par 01</small><small id="tel-error" style="color:#ff6b6b;font-size:11px;display:none;font-weight:800"></small>
<label>Adresse</label><input name="adresse" id="adresse" value="<?= htmlspecialchars($last['adresse'] ?? '')?>" placeholder="Cotonou...">
<h3 style="margin-top:14px">Ajouter un article (Contenir)</h3>
<label>Article</label><select name="nom_article"><option>Chemise Homme</option><option>Robe de soirée</option><option>Pantalon Jean</option></select>
<label>État de l'article</label><select name="etat_article"><option>Bon état</option><option>Taché (Col)</option></select>
<label>Service & Tarif</label><select name="service" id="srv"><option value="500">Lavage + Repassage (Standard : 500 F)</option><option value="3500">Nettoyage à sec : 3500 F</option></select>
<div class="infoBox"><div style="display:flex;justify-content:space-between"><span>Prix unitaire :</span><span id="pA">500 F</span></div></div>
<div class="row2"><div><label>Quantité</label><input type="number" name="qte" id="qte" value="1" min="1"></div><div><label>Prix</label><input type="number" name="prix" id="prix" value="500"></div></div>
<button type="submit" name="add" class="btn">Ajouter à la commande</button>
</form>
</div>
<div class="card"><h3 style="margin:0">Récapitulatif de la commande</h3>
<div style="margin-top:8px;font-size:11px">
<?php foreach($_SESSION['panier'] as $a):?><div><?= htmlspecialchars($a['nom_article'])?> - <?= $a['qte']?> x <?= $a['prix']?>F</div><?php endforeach;?>
<?php if($isEmpty) echo "<div style='color:#7ea0d0'>Aucun article</div>";?>
</div><div style="display:flex;justify-content:space-between;font-weight:bold;margin:10px 0">Montant Total à Payer <span><?= $total?> F</span></div>
<form method="post" id="fValid">
<label>Mode de paiement</label><select name="mode"><option>Espèces</option><option>Mobile Money</option></select>
<input type="hidden" name="nom" id="vn"><input type="hidden" name="prenom" id="vp"><input type="hidden" name="tel" id="vt"><input type="hidden" name="adresse" id="va">
<?php if($isAdmin): ?><input type="hidden" name="id_site_final" id="vs" value="<?= htmlspecialchars($last['id_site_choisi']??$id_site_session) ?>"><?php endif; ?>
<button type="submit" name="valider" class="btn" style="background:#eaf0ff">Valider et encaisser <?= $total?> F</button></form>
</div></div></div>
<script>
const tel=document.getElementById('tel');const err=document.getElementById('tel-error');
const nom=document.getElementById('nom');const prenom=document.getElementById('prenom');
const srv=document.getElementById('srv');const prix=document.getElementById('prix');const pA=document.getElementById('pA');
srv.onchange=function(){prix.value=this.value;pA.innerText=this.value+' F';}
function isBlank(s){ return !s || s.trim().length < 2; }
function checkTel(){
  let v=tel.value.replace(/\D/g,'').slice(0,10); tel.value=v;
  if(v.length==0){err.style.display='none';tel.classList.remove('ok','bad');return false;}
  if(!v.startsWith('01')){err.style.display='block';err.textContent='❌ Doit commencer par 01';tel.classList.add('bad');return false;}
  if(v.length<10){err.style.display='block';err.textContent='❌ Incomplet : '+v.length+'/10';tel.classList.add('bad');return false;}
  if(!/^01[0-9]{8}$/.test(v)){err.style.display='block';err.textContent='❌ Format invalide';tel.classList.add('bad');return false;}
  err.style.display='none';tel.classList.add('ok');tel.classList.remove('bad');return true;
}
tel.addEventListener('input',checkTel);
document.getElementById('fClient').addEventListener('submit',function(e){
  if(isBlank(nom.value)){ e.preventDefault(); alert('Nom invalide : pas d\'espace vide'); nom.focus(); return; }
  if(isBlank(prenom.value)){ e.preventDefault(); alert('Prénom invalide : pas d\'espace vide'); prenom.focus(); return; }
  if(!checkTel()){ e.preventDefault(); }
});
document.getElementById('fValid').onsubmit=function(e){
  vn.value=fClient.nom.value;vp.value=fClient.prenom.value;vt.value=fClient.tel.value;va.value=fClient.adresse.value;
  const selSite = document.querySelector('select[name="id_site"]');
  if(selSite){ document.getElementById('vs').value = selSite.value; }
  if(isBlank(vn.value) || isBlank(vp.value)){ e.preventDefault(); alert('Nom/Prénom ne peuvent pas être vides'); return; }
  if(!checkTel()){e.preventDefault();alert('Téléphone invalide : 10 chiffres commençant par 01');}
}
</script>
</body></html>