<?php
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$q = trim($_GET['q'] ?? '');

// CA AUJOURD'HUI CORRIGÉ - Basé sur date_commande, pas date_paiement
$ca = $pdo->query("
  SELECT COALESCE(SUM(p.montant_paiement),0) 
  FROM paiement p 
  JOIN commande c ON c.id_commande = p.id_commande
  WHERE DATE(c.date_commande) = CURDATE()
")->fetchColumn();

$nbCmd = $pdo->query("SELECT COUNT(*) FROM commande WHERE DATE(date_commande)=CURDATE()")->fetchColumn();
$enCours = $pdo->query("SELECT COUNT(*) FROM commande WHERE Statut_commande='En cours'")->fetchColumn();
$clients = $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();

// Recherche qui marche
if($q !== ""){
  $req = $pdo->prepare("
  SELECT c.id_commande, cl.nom, cl.prenom, cl.telephone, c.date_commande, c.Statut_commande,
  COALESCE((SELECT SUM(montant_paiement) FROM paiement WHERE id_commande=c.id_commande),0) as montant
  FROM commande c JOIN client cl ON c.id_client=cl.id_client 
  WHERE c.id_commande LIKE :q OR cl.nom LIKE :q2 OR cl.prenom LIKE :q3 OR cl.telephone LIKE :q4 OR c.Statut_commande LIKE :q5
  ORDER BY c.id_commande DESC");
  $req->execute([':q'=>"%$q%", ':q2'=>"%$q%", ':q3'=>"%$q%", ':q4'=>"%$q%", ':q5'=>"%$q%"]);
} else {
  $req = $pdo->query("
  SELECT c.id_commande, cl.nom, cl.prenom, cl.telephone, c.date_commande, c.Statut_commande,
  COALESCE((SELECT SUM(montant_paiement) FROM paiement WHERE id_commande=c.id_commande),0) as montant
  FROM commande c JOIN client cl ON c.id_client=cl.id_client 
  ORDER BY c.id_commande DESC LIMIT 10");
}
$commandes = $req->fetchAll();

function badge($s){
  $s=trim($s);
  if($s=='En cours') return '<span style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:800">• En cours</span>';
  if($s=='Prêt') return '<span style="background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:800">• Prêt</span>';
  if($s=='Livré') return '<span style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:800">• Livré</span>';
  return '<span style="background:#f3f4f6;color:#6b7280;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700">'.htmlspecialchars($s).'</span>';
}
function formatTel($tel){
  $t = preg_replace('/\D/','',$tel); return substr($t,-10);
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin - CleanExpress</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;display:flex;background:#eaf2ff;min-height:100vh}
.sidebar{width:250px;background:linear-gradient(180deg,#0a1938,#0f245a);padding:20px 14px}
.sidebar .logo{color:#fff;font-weight:800;font-size:18px;margin-bottom:30px}
.sidebar a{display:block;padding:11px 14px;border-radius:12px;text-decoration:none;color:#8aa6d0;font-size:13px;margin:4px 0}
.sidebar a.active{background:#1e5af0;color:#fff;font-weight:700}
.main{flex:1}.topbar{background:#fffdf2;padding:12px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e6e8ef;font-size:13px}
.content{padding:24px}.kpis{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-top:20px}
.kpi{background:#fffef5;border-radius:16px;padding:18px;border:1px solid #e8e6d9}
.kpi small{color:#8a8fa8;font-size:11px;text-transform:uppercase}.kpi b{font-size:26px;display:block;margin-top:8px;color:#0a1938}
.card{background:#fffef5;border-radius:16px;padding:22px;border:1px solid #e8e6d9;margin-top:20px}
table{width:100%;border-collapse:collapse}th{font-size:12px;color:#0f172a;text-align:left;padding:14px 8px;text-transform:uppercase;font-weight:900;letter-spacing:0.8px;border-bottom:2px solid #cbd5e1}td{padding:16px 8px;border-bottom:1px solid #f2f0e6;font-size:13px;color:#374151}
.search{flex:1;padding:12px 14px;border-radius:12px;border:1px solid #d1d5db} .btnS{padding:12px 18px;border-radius:12px;border:none;background:#0a1938;color:#fff;font-weight:800}
</style></head><body>
<div class="sidebar"><div class="logo">💧 CleanExpress</div>
<a class="active" href="dashboard_admin.php">📊 Dashboard Global</a><a href="sites.php">🏠 Gestion des Sites</a><a href="comptes.php">👤 Utilisateurs & Gérants</a><a href="commande.php">📦 Toutes les Commandes</a><a href="tarifs.php">🏷️ Tarifs & Services</a><a href="stats.php">📈 Statistiques Générales</a><a href="config.php">⚙️ Configuration</a>
<div style="margin-top:80px"><a href="index.php" style="color:#ff8a8a">🚪 Déconnexion</a></div></div>
<div class="main"><div class="topbar"><div>Espace administrateur > <b>Tous les sites - Dashboard Global</b></div><div style="display:flex;gap:10px;align-items:center"><div style="width:36px;height:36px;background:#1d4ed8;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800">MD</div><div><b>michel.d</b><br><small>ADMINISTRATEUR</small></div></div></div>
<div class="content">
<div style="display:flex;justify-content:space-between;align-items:center"><div><h1 style="font-family:Georgia,serif;margin:0;font-size:32px">Bonjour michel.d (Admin) </h1><small style="color:#6b7280">Activité globale de tous les pressings aujourd'hui.</small></div><a href="commande.php?new=1" style="background:#0a1938;color:#fff;padding:10px 18px;border-radius:24px;text-decoration:none;font-weight:700">+ Nouvelle commande</a></div>
<div class="kpis"><div class="kpi"><small>CA AUJOURD'HUI</small><b><?= number_format($ca,0,',',' ') ?> F</b></div><div class="kpi"><small>COMMANDES DU JOUR</small><b><?= $nbCmd ?></b></div><div class="kpi"><small>EN COURS</small><b><?= $enCours ?></b></div><div class="kpi"><small>NOUVEAUX CLIENTS</small><b><?= $clients ?></b></div></div>
<div class="card">
<h3 style="font-family:Georgia,serif;margin:0 0 12px 0">Dernières commandes</h3>
<form method="get" style="display:flex;gap:8px;margin-bottom:16px"><input class="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="🔍 Rechercher : Réf (101), Client (Aminata), Téléphone 01XXXXXXXX, Statut..."><button class="btnS">Rechercher</button><?php if($q):?><a href="dashboard_admin.php" style="padding:12px;background:#fee2e2;color:#dc2626;border-radius:12px;text-decoration:none;font-weight:800">X</a><?php endif;?></form>
<table><tr><th>RÉF.</th><th>CLIENT</th><th>TÉLÉPHONE</th><th>DATE</th><th>STATUT</th><th>MONTANT</th></tr>
<?php foreach($commandes as $c):?><tr><td style="font-weight:700">CMD-2026-<?= $c['id_commande'] ?></td><td><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></td><td><?= formatTel($c['telephone']) ?></td><td><?= date('Y-m-d',strtotime($c['date_commande'])) ?></td><td><?= badge($c['Statut_commande']) ?></td><td style="font-weight:700"><?= number_format($c['montant'],0,',',' ') ?> F</td></tr><?php endforeach;?></table>
</div></div></div></body></html>