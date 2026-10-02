<?php
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Créer table config si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_entreprise VARCHAR(100) DEFAULT 'CleanExpress',
  email_contact VARCHAR(100) DEFAULT 'contact@cleanexpress.bj',
  delai_livraison INT DEFAULT 2,
  frais_livraison INT DEFAULT 500,
  tva INT DEFAULT 0
)");
$cfg = $pdo->query("SELECT * FROM config LIMIT 1")->fetch();
if(!$cfg){ $pdo->exec("INSERT INTO config(nom_entreprise) VALUES('CleanExpress')"); $cfg=$pdo->query("SELECT * FROM config LIMIT 1")->fetch(); }

if(isset($_POST['save'])){
  $pdo->prepare("UPDATE config SET nom_entreprise=?, email_contact=?, delai_livraison=?, frais_livraison=?, tva=? WHERE id=?")
  ->execute([$_POST['nom'], $_POST['email'], intval($_POST['delai']), intval($_POST['frais']), intval($_POST['tva']), $cfg['id']]);
  header("Location: config.php?ok=1"); exit;
}
$cfg = $pdo->query("SELECT * FROM config LIMIT 1")->fetch();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Config - CleanExpress</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;display:flex;background:#eaf2ff;min-height:100vh}
.sidebar{width:250px;background:linear-gradient(180deg,#0a1938,#0f245a);padding:20px 14px}
.sidebar .logo{color:#fff;font-weight:800;font-size:18px;margin-bottom:30px}
.sidebar a{display:block;padding:11px 14px;border-radius:12px;text-decoration:none;color:#8aa6d0;font-size:13px;margin:4px 0}
.sidebar a.active{background:#1e5af0;color:#fff;font-weight:700}
.main{flex:1}.topbar{background:#fffdf2;padding:12px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e6e8ef}
.content{padding:24px}.card{background:#fffef5;border-radius:16px;padding:24px;border:1px solid #e8e6d9;max-width:700px}
label{font-size:12px;font-weight:800;text-transform:uppercase;color:#64748b;display:block;margin-top:14px}
input{width:100%;padding:12px;border-radius:10px;border:1px solid #d1d5db;margin-top:6px}
button{background:#0a1938;color:#fff;padding:12px 20px;border-radius:12px;border:none;font-weight:800;margin-top:20px;cursor:pointer;width:100%}
.success{background:#dcfce7;color:#15803d;padding:12px;border-radius:10px;margin-bottom:14px;border:1px solid #bbf7d0}
</style></head><body>
<div class="sidebar">
<div class="logo">💧 CleanExpress</div>
<a href="dashboard_admin.php">📊 Dashboard Global</a>
<a href="sites.php">🏠 Gestion des Sites</a>
<a href="comptes.php">👤 Utilisateurs & Gérants</a>
<a href="commande.php">📦 Toutes les Commandes</a>
<a href="tarifs.php">🏷️ Tarifs & Services</a>
<a href="stats.php">📈 Statistiques Générales</a>
<a class="active" href="config.php">⚙️ Configuration</a>
<div style="margin-top:80px"><a href="index.php" style="color:#ff8a8a">🚪 Déconnexion</a></div>
</div>
<div class="main">
<div class="topbar"><div>Configuration</div><div style="display:flex;gap:10px;align-items:center"><div style="width:36px;height:36px;background:#1d4ed8;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800">MD</div><div><b>michel.d</b><br><small>michel.d@cleanexpress.bj</small></div></div></div>
<div class="content">
<h2 style="font-family:Georgia,serif;font-size:28px">Paramètres système</h2>
<p style="color:#64748b">Ici tu pourras modifier le nom de l'entreprise, le logo, les délais de livraison, etc.</p>

<?php if(isset($_GET['ok'])): ?><div class="success">✅ Paramètres enregistrés avec succès</div><?php endif;?>

<div class="card">
<form method="post">
<label>Nom de l'entreprise</label><input name="nom" value="<?= htmlspecialchars($cfg['nom_entreprise'])?>" required>
<label>Email de contact</label><input name="email" value="<?= htmlspecialchars($cfg['email_contact'])?>" required>
<label>Délai de livraison (jours)</label><input name="delai" type="number" value="<?= $cfg['delai_livraison']?>">
<label>Frais de livraison (F CFA)</label><input name="frais" type="number" value="<?= $cfg['frais_livraison']?>">
<label>TVA (%)</label><input name="tva" type="number" value="<?= $cfg['tva']?>">
<button type="submit" name="save">💾 Enregistrer les modifications</button>
</form>
</div>

</div>
</div>
</body></html>