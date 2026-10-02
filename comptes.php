<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start(); 
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }

$isAdmin = (($_SESSION['nom'] ?? '') == 'michel.d' || strtoupper($_SESSION['role'] ?? '') == 'ADMINISTRATEUR' || strtoupper($_SESSION['role'] ?? '') == 'ADMIN');
$siteLabel = $isAdmin ? 'Tous les sites - Dashboard Global' : 'Cotonou_Akpakpa';

function validTel10($t){ 
  $t = preg_replace('/\D/','',$t); 
  $t = substr($t,-10);
  return preg_match('/^01[0-9]{8}$/',$t) ? $t : false; 
}
function formatTel10($t){ 
  $t=preg_replace('/\D/','',$t); 
  return $t ? substr($t,-10) : '-'; 
}

$msg = ""; $msgType = "";
if(isset($_POST['creer'])){ 
  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $ident = trim($_POST['ident'] ?? '');
  $passClair = trim($_POST['pass'] ?? '');
  $tel = validTel10($_POST['tel'] ?? '');

  if($nom === '' || $prenom === '' || $ident === ''){
    $msg = "❌ Nom, prénom et identifiant non vides"; $msgType="err";
  } elseif(!$tel){
    $msg = "❌ Téléphone invalide : 10 chiffres commençant par 01"; $msgType="err";
  } elseif(strlen($passClair) < 4){
    $msg = "❌ Mot de passe trop court"; $msgType="err";
  } else {
    $chk = $pdo->prepare("SELECT id_users FROM users WHERE pseudo=? OR nom_user=?");
    $chk->execute([$ident, $ident]);
    if($chk->fetch()){ $msg = "❌ Pseudo '$ident' existe déjà"; $msgType="err"; }
    else {
      $mdp = $passClair; // EN CLAIR
      $pdo->prepare("INSERT INTO users(nom_users,prenom_users,pseudo,nom_user,telephone,role,id_site,mot_de_passe,etat,derniere_conn) VALUES(?,?,?,?,?,?,?,?,?,?)")
          ->execute([$nom,$prenom,$ident,$ident,$tel,$_POST['role'],$_POST['site'],$mdp,isset($_POST['actif'])?1:0,date('d/m/Y H:i')]);
      $msg = "✅ Compte créé ! $ident sur site ID ".$_POST['site']; $msgType="ok";
    }
  }
}
$users=$pdo->query("SELECT * FROM users LEFT JOIN site ON users.id_site=site.id_site ORDER BY id_users DESC")->fetchAll(); 
$sites=$pdo->query("SELECT * FROM site")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Comptes & Sites</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;background:radial-gradient(circle at top,#203e84,#0b1c44);color:#fff;min-height:100vh}
.top{padding:8px 15px;background:#0a1938;font-size:11px;display:flex;justify-content:space-between;border-bottom:1px solid #1e3a6e}
.nav{padding:10px;display:flex;gap:6px}.nav a{padding:7px 14px;border-radius:20px;background:#132c63;color:#9ab4e0;text-decoration:none;font-size:12px;border:1px solid #24407a}.nav a.active{background:#cbe5ff;color:#0a1938;font-weight:bold}
.wrap{max-width:1100px;margin:10px auto;padding:10px}.grid{display:grid;grid-template-columns:400px 1fr;gap:12px}
.box{background:linear-gradient(180deg,#18307a,#143165);border:1px solid #2a4aa8;border-radius:14px;padding:16px}
input,select{width:100%;background:#0a1c3f;border:1px solid #2a4a8a;color:#d6e8ff;padding:10px;border-radius:10px;margin-top:4px;font-size:12px}
label{font-size:10px;color:#8aa6d0;margin-top:10px;display:block;text-transform:uppercase;font-weight:700}
.btn{width:100%;background:#d6e8ff;color:#0a1938;border:none;padding:11px;border-radius:10px;font-weight:bold;margin-top:12px;cursor:pointer}
table{width:100%;border-collapse:collapse;font-size:11px}th{color:#7ea0d0;font-size:9px;text-align:left;padding:8px 4px;border-bottom:1px solid #24407a;text-transform:uppercase}td{padding:8px 4px;border-bottom:1px solid #1e3a6e}
.actif{color:#6be8b0;font-weight:800}.inactif{color:#ffb4b4}
.alert{padding:10px;border-radius:10px;font-size:12px;margin-bottom:10px}
.alert-err{background:#ff000022;border:1px solid #ff4444;color:#ffb4b4}
.alert-ok{background:#00ff0022;border:1px solid #44ff44;color:#b4ffb4}
</style></head><body>
<div class="top"><div>Site: <?= htmlspecialchars($siteLabel) ?></div><div><?= htmlspecialchars($_SESSION['nom'] ?? '')?> ADMIN</div></div>
<div class="nav"><a href="dashboard_admin.php">📊 Dashboard</a><a href="comptes.php" class="active">👤 Comptes & Sites</a><a href="commande.php?new=1">+ Nouvelle commande</a><a href="index.php">🚪 Déconnexion</a></div>
<div class="wrap"><h2 style="margin:0">Comptes & Sites</h2>
<?php if($msg):?><div class="alert <?= $msgType=='ok'?'alert-ok':'alert-err'?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="grid"><div class="box"><b>Nouveau compte</b>
<form method="post"><div style="display:flex;gap:8px"><div style="flex:1"><label>Nom *</label><input name="nom" required></div><div style="flex:1"><label>Prénom *</label><input name="prenom" required></div></div>
<label>Identifiant *</label><input name="ident" required placeholder="ex. Alida11">
<label>Téléphone * (01XXXXXXXX)</label><input name="tel" maxlength="10" required placeholder="0197000000">
<label>Site *</label><select name="site"><?php foreach($sites as $s) echo "<option value='{$s['id_site']}'>".htmlspecialchars($s['nom_site'])."</option>";?></select>
<label>Rôle</label><select name="role"><option value="gerant">Gérant</option><option value="admin">Admin</option></select>
<label>Mot de passe *</label><input type="password" name="pass" required>
<label><input type="checkbox" name="actif" checked style="width:auto"> Compte actif</label>
<button name="creer" class="btn">Créer l'utilisateur</button></form></div>
<div class="box"><b>Utilisateurs par site</b><table><tr><th>Pseudo</th><th>Site</th><th>Tel</th><th>État</th></tr>
<?php foreach($users as $u):?><tr><td><?= htmlspecialchars($u['pseudo']) ?></td><td><?= htmlspecialchars($u['nom_site'] ?? '-') ?></td><td><?= formatTel10($u['telephone'] ?? '') ?></td><td class="<?= $u['etat']?'actif':'inactif'?>"><?= $u['etat']?'Actif':'Inactif'?></td></tr><?php endforeach;?></table></div></div></div></body></html>