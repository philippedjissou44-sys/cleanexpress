<?php
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }

$isAdmin = (($_SESSION['nom'] ?? '') == 'michel.d' || strtoupper($_SESSION['role'] ?? $_SESSION['type_utilisateur'] ?? '') == 'ADMINISTRATEUR' || strtoupper($_SESSION['role'] ?? '') == 'ADMIN');
$dashboardLink = $isAdmin ? 'dashboard_admin.php' : 'dashboard.php';

$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id'] ?? 1);
$id_site_session = $_SESSION['id_site'] ?? 0;

$stmt = $pdo->prepare("SELECT c.*, cl.nom,cl.prenom,cl.telephone, u.prenom_users,u.nom_users, p.montant_paiement,p.mode_paiement, s.nom_site 
FROM commande c 
JOIN client cl ON cl.id_client=c.id_client 
JOIN users u ON u.id_users=c.id_users 
JOIN paiement p ON p.id_commande=c.id_commande 
JOIN site s ON s.id_site=c.id_site 
WHERE c.id_commande=?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if(!$c){ die("Facture introuvable"); }
if(!$isAdmin && $c['id_site'] != $id_site_session){ die("Accès refusé"); }
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Facture CMD-<?= $id ?></title>
<style>
*{box-sizing:border-box;font-family:Arial, sans-serif}
body{margin:0;background:#0b1c44;color:#fff;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:30px 20px}
.fact{background:#132d67;border:1px solid #2a5aa8;border-radius:16px;padding:22px;width:100%;max-width:800px}

table{width:100%;border-collapse:collapse;margin-top:14px;border:1px solid #2a5aa8}
th{color:#8ab4ff;font-size:10px;text-align:left;padding:10px 8px;text-transform:uppercase;background:#0f2450;border:1px solid #2a5aa8}
td{padding:10px 8px;border:1px solid #2a5aa8;font-size:12px}
.total-bar{display:flex;justify-content:space-between;font-weight:900;font-size:16px;padding:14px 8px;border:1px solid #2a5aa8;border-top:none;background:#0f2450;color:#fff}
.btn{background:#d6e8ff;color:#0a1938;padding:10px 18px;border-radius:10px;border:none;font-weight:800;cursor:pointer}

@media print{
  body{background:#fff!important;color:#000!important;padding:0}
  .fact{background:#fff!important;color:#000!important;border:1px solid #000!important;box-shadow:none;border-radius:0}
  table{border:1px solid #000!important}
  th{background:#dbeafe!important;color:#000!important;border:1px solid #000!important}
  td{background:#fff!important;color:#000!important;border:1px solid #000!important}
  .total-bar{background:#fff!important;color:#000!important;border:1px solid #000!important;border-top:none!important}
  .no-print{display:none!important}
}
</style>
</head><body>
<div class="fact">
<div style="display:flex;justify-content:space-between">
<div><b style="font-size:18px">Facture</b><div style="font-size:11px;color:#8aa6d0">N° FAC-2026-0<?= $id?> - <?= date('d/m/Y', strtotime($c['date_commande'] ?? 'now'))?></div></div>
<div style="text-align:right"><b>CleanExpress</b><div style="font-size:11px;color:#8aa6d0">Site <?= htmlspecialchars($c['nom_site']) ?></div></div>
</div>

<div style="margin:16px 0;font-size:12px"><b>Client : <?= htmlspecialchars($c['nom'].' '.$c['prenom'])?> (<?= htmlspecialchars($c['telephone'])?>)</b><div style="color:#8aa6d0">Commande n° CMD-<?= $id?> - Par <?= htmlspecialchars(($c['prenom_users']??'').' '.($c['nom_users']??''))?></div></div>

<table>
<tr><th>ARTICLE</th><th>ÉTAT</th><th>SERVICE / TARIF</th><th>QTÉ</th><th>TOTAL</th></tr>
<tr><td>Chemise Homme</td><td>Taché (Col)</td><td>Lavage + repassage (Standard)</td><td>3</td><td>1 500 F</td></tr>
<tr><td>Robe de soirée</td><td>Bon état</td><td>Nettoyage à sec (Express)</td><td>1</td><td>3 500 F</td></tr>
<tr><td>Pantalon Jean</td><td>Bon état</td><td>Repassage seul (Eco)</td><td>2</td><td>1 000 F</td></tr>
</table>

<div class="total-bar"><span>Montant Total</span><span><?= number_format((float)($c['montant_paiement'] ?? 6000),0,',',' ') ?> F</span></div>

<div class="no-print" style="display:flex;justify-content:space-between;margin-top:18px">
<button class="btn" onclick="window.print()">Imprimer la facture</button>
<a href="<?= $dashboardLink ?>" style="color:#8ec8ff;font-size:12px;text-decoration:none;font-weight:bold">← Retour tableau de bord</a>
</div>
</div>
</body></html>