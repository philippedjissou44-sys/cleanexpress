<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }

function cleanTel($t){ 
  $t = preg_replace('/\D/','',$t); 
  return substr($t,-10); 
}
function validTel($t){
  $t = cleanTel($t);
  return preg_match('/^01[0-9]{8}$/',$t) ? $t : false;
}

$error = '';

if(isset($_POST['ajouter'])){
  $code = trim($_POST['code'] ?? '');
  $nom = trim($_POST['nom'] ?? '');
  $adresse = trim($_POST['adresse'] ?? '');
  $tel = validTel($_POST['telephone'] ?? '');

  if($code === '' || $nom === '' || $adresse === ''){
    $error = "❌ Veuillez remplir CODE, NOM et ADRESSE.";
  } elseif(!$tel){
    $error = "❌ Téléphone invalide : doit commencer par 01 et avoir 10 chiffres.";
  } else {
    $pdo->prepare("INSERT INTO site(code_site,nom_site,adresse_site,telephone) VALUES(?,?,?,?)")
        ->execute([$code,$nom,$adresse,$tel]);
    header("Location: sites.php?ok=1"); exit;
  }
}

if(isset($_POST['modifier'])){
  $code = trim($_POST['code'] ?? '');
  $nom = trim($_POST['nom'] ?? '');
  $adresse = trim($_POST['adresse'] ?? '');
  $tel = validTel($_POST['telephone'] ?? '');

  if($code === '' || $nom === '' || $adresse === ''){
    $error = "❌ Veuillez remplir CODE, NOM et ADRESSE.";
  } elseif(!$tel){
    $error = "❌ Téléphone invalide.";
  } else {
    $pdo->prepare("UPDATE site SET code_site=?, nom_site=?, adresse_site=?, telephone=? WHERE id_site=?")
        ->execute([$code,$nom,$adresse,$tel,$_POST['id_site']]);
    header("Location: sites.php?ok=1"); exit;
  }
}

if(isset($_GET['del'])){
  $pdo->prepare("DELETE FROM site WHERE id_site=?")->execute([$_GET['del']]);
  header("Location: sites.php"); exit;
}

$sites = $pdo->query("SELECT * FROM site ORDER BY id_site ASC")->fetchAll();
$edit = null;
if(isset($_GET['edit'])){
  $stmt = $pdo->prepare("SELECT * FROM site WHERE id_site=?");
  $stmt->execute([$_GET['edit']]);
  $edit = $stmt->fetch();
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Gestion des Sites</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;display:flex;background:#eaf2ff;min-height:100vh}
.sidebar{width:230px;background:linear-gradient(180deg,#0a1938,#0f245a);padding:18px 12px;min-height:100vh;color:#fff}
.sidebar a{display:block;padding:10px 12px;border-radius:10px;color:#8aa6d0;text-decoration:none;font-size:12px;margin:3px 0}
.sidebar a.active{background:#1e5af0;color:#fff;font-weight:700}
.main{flex:1}
.topbar{background:#fffdf2;padding:12px 22px;display:flex;justify-content:space-between;border-bottom:1px solid #e6e8ef;font-size:12px}
.content{padding:18px}
.card{background:#fffef5;border-radius:14px;padding:16px;border:1px solid #e8e6d9;margin-bottom:14px}
input{padding:9px 10px;border-radius:10px;border:1px solid #d1d5db;font-size:12px}
.btn{padding:9px 14px;border-radius:10px;border:none;font-weight:800;cursor:pointer}
.btn-add{background:#0a1938;color:#fff}
.btn-edit{background:#e0edff;color:#0a1938;border:1px solid #b0c8f0}
table{width:100%;border-collapse:collapse;font-size:12px}th{font-size:10px;color:#475569;text-align:left;padding:10px 6px;border-bottom:2px solid #cbd5e1;text-transform:uppercase}td{padding:10px 6px;border-bottom:1px solid #f0efe6}
.action a{margin-right:8px;text-decoration:none;font-weight:700;font-size:11px}
.del{color:#dc2626}.mod{color:#1e5af0}
.alert{padding:12px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:700}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.alert-ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
</style></head><body>
<div class="sidebar">
<div style="font-weight:800;margin-bottom:24px;font-size:18px">💧 CleanExpress</div>
<a href="dashboard_admin.php">📊 Dashboard Global</a>
<a class="active" href="sites.php">🏠 Gestion des Sites</a>
<a href="comptes.php">👤 Utilisateurs & Gérants</a>
<a href="commande.php">📦 Commandes</a>
<a href="tarifs.php">🏷️ Tarifs</a>
<a href="stats.php">📈 Statistiques</a>
<a href="index.php" style="color:#ff8a8a;margin-top:60px;display:block">🚪 Déconnexion</a>
</div>
<div class="main">
<div class="topbar"><div>Gestion des Sites - <?= count($sites) ?> sites</div><div style="font-weight:800"><?= $_SESSION['nom'] ?? 'michel.d' ?> ADMINISTRATEUR</div></div>
<div class="content">

<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if(isset($_GET['ok'])): ?><div class="alert alert-ok">✅ Site enregistré avec succès</div><?php endif; ?>

<div class="card">
<b><?= $edit ? '✏️ Modifier le site #'.$edit['id_site'] : '➕ Ajouter un nouveau site' ?></b>
<form method="post" style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;align-items:center">
<?php if($edit): ?><input type="hidden" name="id_site" value="<?= $edit['id_site'] ?>"><?php endif; ?>
<input name="code" placeholder="CODE ex: SITE-03" value="<?= htmlspecialchars($edit['code_site'] ?? $_POST['code'] ?? '') ?>" style="width:130px" required>
<input name="nom" placeholder="Nom: Cotonou - Gbegamey" value="<?= htmlspecialchars($edit['nom_site'] ?? $_POST['nom'] ?? '') ?>" style="flex:1;min-width:180px" required>
<input name="adresse" placeholder="Adresse: Rue 102" value="<?= htmlspecialchars($edit['adresse_site'] ?? $_POST['adresse'] ?? '') ?>" style="flex:1;min-width:180px" required>
<input name="telephone" id="telSite" placeholder="01XXXXXXXX" value="<?= htmlspecialchars(cleanTel($edit['telephone'] ?? $_POST['telephone'] ?? '')) ?>" maxlength="10" pattern="^01[0-9]{8}$" style="width:140px" required>
<?php if($edit): ?>
<button name="modifier" class="btn btn-edit">💾 Enregistrer</button>
<a href="sites.php" class="btn" style="background:#f1f5f9;text-decoration:none;color:#000">Annuler</a>
<?php else: ?>
<button name="ajouter" class="btn btn-add">+ Ajouter</button>
<?php endif; ?>
</form>
</div>

<div class="card">
<table>
<tr><th>ID</th><th>CODE</th><th>NOM DU SITE</th><th>ADRESSE</th><th>TELEPHONE</th><th>ACTION</th></tr>
<?php foreach($sites as $s): ?>
<tr>
<td><span style="background:#e0f2fe;padding:4px 8px;border-radius:20px;font-weight:700;font-size:10px"><?= $s['id_site'] ?></span></td>
<td><?= htmlspecialchars($s['code_site']) ?></td>
<td><?= htmlspecialchars($s['nom_site']) ?></td>
<td><?= htmlspecialchars($s['adresse_site']) ?></td>
<td><?= htmlspecialchars(cleanTel($s['telephone'])) ?></td>
<td class="action">
<a class="mod" href="sites.php?edit=<?= $s['id_site'] ?>">✏️ Modifier</a>
<a class="del" href="sites.php?del=<?= $s['id_site'] ?>" onclick="return confirm('Supprimer ce site ?')">🗑️ Supprimer</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div></div>

<script>
const t = document.getElementById('telSite');
if(t){
 t.addEventListener('input', ()=>{
  t.value = t.value.replace(/\D/g,'').slice(0,10);
 });
}
</script>
</body></html>