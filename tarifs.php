<?php
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$nom = $_SESSION['nom']?? 'michel.d';
$email = 'michel.d@cleanexpress.bj';

// AUTO DETECTION
$cols = $pdo->query("SHOW COLUMNS FROM service")->fetchAll(PDO::FETCH_ASSOC);
$fields = array_column($cols, 'Field');
function findCol($fields, $cands){ foreach($cands as $c){ if(in_array($c,$fields)) return $c; } return null; }
$idCol = findCol($fields, ['id_service','id','id_tarif'])?? $fields[0];
$nameCol = findCol($fields, ['libelle_service','nom_service','nom','libelle'])?? $fields[1];
$priceCol = findCol($fields, ['prix_service','prix','tarif'])?? $fields[2];

$error = '';

if(isset($_POST['update'])){
  $n = trim($_POST['nom']?? '');
  $p = trim($_POST['prix']?? '');
  if($n === ''){
    $error = "❌ Le nom du service est obligatoire";
  } elseif($p === '' ||!is_numeric($p) || intval($p) <= 0){
    $error = "❌ Prix invalide, doit être > 0";
  } else {
    $pdo->prepare("UPDATE service SET `$nameCol`=?, `$priceCol`=? WHERE `$idCol`=?")
        ->execute([$n, intval($p), intval($_POST['id'])]);
    header("Location: tarifs.php?ok=1"); exit;
  }
}
if(isset($_POST['add'])){
  $n = trim($_POST['nom']?? '');
  $p = trim($_POST['prix']?? '');
  if($n === ''){
    $error = "❌ Le nom du service est obligatoire";
  } elseif($p === '' ||!is_numeric($p) || intval($p) <= 0){
    $error = "❌ Prix invalide, doit être > 0";
  } else {
    $pdo->prepare("INSERT INTO service(`$nameCol`,`$priceCol`) VALUES(?,?)")
        ->execute([$n, intval($p)]);
    header("Location: tarifs.php?ok=1"); exit;
  }
}
if(isset($_GET['del'])){
  $pdo->prepare("DELETE FROM service WHERE `$idCol`=?")->execute([intval($_GET['del'])]);
  header("Location: tarifs.php"); exit;
}

$tarifs = $pdo->query("SELECT * FROM service ORDER BY `$idCol` DESC")->fetchAll(PDO::FETCH_ASSOC);

$edit = null;
if(isset($_GET['edit'])){
  $stmt=$pdo->prepare("SELECT * FROM service WHERE `$idCol`=?");
  $stmt->execute([intval($_GET['edit'])]); $edit=$stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Tarifs</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;display:flex;background:#eef3ff;min-height:100vh}
.sidebar{width:240px;background:linear-gradient(180deg,#0a1938,#0f245a);padding:18px 12px}
.sidebar a{display:flex;gap:8px;padding:11px 12px;border-radius:12px;text-decoration:none;color:#8aa6d0;font-size:13px;margin:3px 0}
.sidebar a.active{background:#1e5af0;color:#fff;font-weight:700}
.main{flex:1}.topbar{background:#fffdf2;padding:12px 20px;display:flex;justify-content:space-between;border-bottom:1px solid #e6e8ef;align-items:center}
.content{padding:22px}.card{background:#fffef5;border-radius:14px;padding:18px;border:1px solid #e8e6d9;margin-bottom:14px}
input{padding:12px;border-radius:12px;border:1px solid #d1d5db;flex:1}button{padding:12px 18px;border-radius:12px;border:none;background:#0a1938;color:#fff;font-weight:800;cursor:pointer}
th{color:#0f172a;font-size:12px;text-align:left;padding:14px 8px;font-weight:900;text-transform:uppercase;border-bottom:2px solid #cbd5e1}
td{padding:14px 8px;border-bottom:1px solid #f2f0e6;font-size:13px}
.btn{padding:6px 10px;border-radius:8px;text-decoration:none;font-size:11px;font-weight:800;display:inline-block;margin-right:4px}
.mod{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe}
.del{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.alert{padding:12px;border-radius:10px;margin-bottom:12px;font-weight:700;font-size:13px}
.alert-err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.alert-ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
</style></head><body>
<div class="sidebar"><div style="color:#fff;font-weight:800;margin-bottom:22px;font-size:18px">💧 CleanExpress</div>
<a href="dashboard_admin.php">📊 Dashboard Global</a><a href="sites.php">🏠 Gestion des Sites</a><a href="comptes.php">👤 Utilisateurs & Gérants</a><a href="commande.php">📦 Toutes les Commandes</a><a class="active" href="tarifs.php">🏷️ Tarifs & Services</a><a href="stats.php">📈 Statistiques Générales</a><a href="config.php">⚙️ Configuration</a>
<div style="margin-top:60px"><a href="index.php" style="color:#ff8a8a;text-decoration:none;padding:11px 12px;display:block">🚪 Déconnexion</a></div></div>
<div class="main">
<div class="topbar"><div style="font-weight:600">Tarifs & Services</div><div style="display:flex;gap:10px;align-items:center"><div style="width:36px;height:36px;background:#2a5bd7;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700">MD</div><div><div style="font-weight:800"><?= htmlspecialchars($nom)?></div><div style="font-size:11px;color:#1d4ed8;font-weight:700"><?= $email?></div></div></div></div>
<div class="content">
<h2 style="font-family:Georgia,serif;font-size:28px;margin:0 0 12px 0">Tarifs & Services</h2>

<?php if($error):?><div class="alert alert-err"><?= $error?></div><?php endif;?>
<?php if(isset($_GET['ok'])):?><div class="alert alert-ok">✅ Enregistré avec succès</div><?php endif;?>

<?php if($edit):?>
<div class="card" style="border:2px solid #1e5af0;background:#eff6ff">
<form method="post" style="display:flex;gap:8px">
<input type="hidden" name="id" value="<?= $edit[$idCol]?>">
<input name="nom" value="<?= htmlspecialchars($edit[$nameCol])?>" required>
<input name="prix" type="number" min="1" value="<?= $edit[$priceCol]?>" required style="max-width:180px">
<button name="update">Enregistrer</button>
<a href="tarifs.php" class="btn del" style="padding:12px 18px">Annuler</a>
</form>
</div>
<?php else:?>
<div class="card"><form method="post" style="display:flex;gap:8px"><input name="nom" placeholder="Nom service (ex: Lavage + Repassage)" required><input name="prix" type="number" min="1" placeholder="Prix F" required style="max-width:180px"><button name="add">+ Ajouter</button></form></div>
<?php endif;?>

<div class="card">
<table style="width:100%;border-collapse:collapse">
<tr><th>SERVICE</th><th>PRIX</th><th>ACTION</th></tr>
<?php foreach($tarifs as $t):?>
<tr>
<td style="font-weight:700"><?= htmlspecialchars($t[$nameCol])?: '<span style="color:#dc2626;font-style:italic">(sans nom)</span>'?></td>
<td><?= $t[$priceCol]?> F</td>
<td>
<a class="btn mod" href="?edit=<?= $t[$idCol]?>">Modifier</a>
<a class="btn del" href="?del=<?= $t[$idCol]?>" onclick="return confirm('Supprimer?')">Supprimer</a>
</td>
</tr>
<?php endforeach;?>
</table>
</div>
</div></div></body></html>