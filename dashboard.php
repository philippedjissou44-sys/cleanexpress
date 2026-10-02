<?php
session_start();
if(!isset($_SESSION['id'])){ header("Location: index.php"); exit; }
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_site = $_SESSION['id_site'] ?? 1;
$q = trim($_GET['q'] ?? '');

// Récupère le vrai nom du site du gérant connecté
$siteInfo = $pdo->prepare("SELECT nom_site FROM site WHERE id_site=?");
$siteInfo->execute([$id_site]);
$siteRow = $siteInfo->fetch();
$nomSiteAffiche = $siteRow['nom_site'] ?? 'Site inconnu';

// STATS filtrées par site du gérant
$caJour = $pdo->prepare("SELECT COALESCE(SUM(p.montant_paiement),0) FROM paiement p JOIN commande c ON p.id_commande=c.id_commande WHERE c.id_site=? AND DATE(p.date_paiement)=CURDATE()");
$caJour->execute([$id_site]); $caJour=$caJour->fetchColumn();

$nbJour = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_site=? AND DATE(date_commande)=CURDATE()");
$nbJour->execute([$id_site]); $nbJour=$nbJour->fetchColumn();

$enCours = $pdo->prepare("SELECT COUNT(*) FROM commande WHERE id_site=? AND Statut_commande='En cours'");
$enCours->execute([$id_site]); $enCours=$enCours->fetchColumn();

$nouveaux = $pdo->prepare("SELECT COUNT(DISTINCT c.id_client) FROM commande c WHERE c.id_site=? AND DATE(c.date_commande)=CURDATE() AND c.id_client NOT IN (SELECT id_client FROM commande WHERE id_site=? AND DATE(date_commande) < CURDATE())");
$nouveaux->execute([$id_site, $id_site]); $nouveaux=$nouveaux->fetchColumn();

$caTotal = $pdo->prepare("SELECT COALESCE(SUM(p.montant_paiement),0) FROM paiement p JOIN commande c ON p.id_commande=c.id_commande WHERE c.id_site=?");
$caTotal->execute([$id_site]); $caTotal=$caTotal->fetchColumn();

// Recherche
if($q!==""){
  $stmt = $pdo->prepare("SELECT c.id_commande, cl.nom, cl.prenom, cl.telephone, c.date_commande, c.Statut_commande, COALESCE((SELECT SUM(montant_paiement) FROM paiement WHERE id_commande=c.id_commande),0) as montant FROM commande c JOIN client cl ON c.id_client=cl.id_client WHERE c.id_site=? AND (c.id_commande LIKE :q OR cl.nom LIKE :q2 OR cl.telephone LIKE :q3) ORDER BY c.id_commande DESC");
  $stmt->execute([$id_site, ':q'=>"%$q%", ':q2'=>"%$q%", ':q3'=>"%$q%"]);
} else {
  $stmt = $pdo->prepare("SELECT c.id_commande, cl.nom, cl.prenom, cl.telephone, c.date_commande, c.Statut_commande, COALESCE((SELECT SUM(montant_paiement) FROM paiement WHERE id_commande=c.id_commande),0) as montant FROM commande c JOIN client cl ON c.id_client=cl.id_client WHERE c.id_site=? ORDER BY c.id_commande DESC LIMIT 20");
  $stmt->execute([$id_site]);
}
$commandes=$stmt->fetchAll();

function badgeG($s){
  $s=trim($s);
  if($s=='En cours') return '<span style="background:#fee2e2;color:#991b1b;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700">• En cours</span>';
  if($s=='Prêt') return '<span style="background:#dbeafe;color:#1e40af;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700">• Prêt</span>';
  if($s=='Livré') return '<span style="background:#dcfce7;color:#166534;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700">• Livré</span>';
  return htmlspecialchars($s);
}
function formatTel10($tel){ $t=preg_replace('/\D/','',$tel); return substr($t,-10); }
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Tableau de bord - <?= htmlspecialchars($nomSiteAffiche) ?></title>
<style>
*{box-sizing:border-box;font-family:Arial, sans-serif}
body{margin:0;background:#e8f0ff;min-height:100vh;color:#1e293b;width:100%}
.top{padding:12px 24px;background:#0a1938;color:#fff;display:flex;justify-content:space-between;align-items:center;font-size:12px;border-bottom:1px solid #1e3a6e;width:100%}
.pills{padding:12px 24px;display:flex;gap:8px;background:#0a1938;width:100%}
.pills a{padding:7px 16px;border-radius:20px;text-decoration:none;font-size:12px;background:#11245a;color:#8aa6d0;border:1px solid #24407a}
.pills a.active{background:#cbe5ff;color:#0a1938;font-weight:bold}
.wrap{width:100%;max-width:100%;margin:0;padding:24px;box-sizing:border-box}
.kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;width:100%}
.cardK{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0}
.cardK .k{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;font-weight:700}
.cardK .v{font-size:24px;font-weight:900;margin-top:6px;color:#0f172a}
.cardMain{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e2e8f0;width:100%}
table{width:100%;border-collapse:collapse} th{font-size:10px;color:#64748b;text-align:left;padding:12px 8px;text-transform:uppercase;border-bottom:2px solid #e2e8f0} td{padding:14px 8px;border-bottom:1px solid #f1f5f9;font-size:13px}
input.s{flex:1;background:#f8fafc;border:1px solid #cbd5e1;padding:10px 14px;border-radius:8px;font-size:13px}
.btnS{background:#0a1938;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-weight:bold;cursor:pointer}
</style></head><body>

<div class="top">
  <div style="display:flex;gap:14px;align-items:center">
    <span style="font-weight:900;font-size:22px;letter-spacing:0.8px;color:#ffffff">💧 CleanExpress</span>
    <span style="opacity:0.35;font-size:18px">|</span>
    <span style="font-size:13px;color:#cbd5e1">Site d'activité : <?= htmlspecialchars($nomSiteAffiche) ?></span>
  </div>
  <div style="font-weight:700"><?= htmlspecialchars($_SESSION['pseudo']??$_SESSION['nom'])?> - <?= htmlspecialchars($nomSiteAffiche) ?></div>
</div>

<div class="pills"><a class="active" href="dashboard.php">Tableau de bord</a><a href="commande.php?new=1">Nouvelle commande</a><a href="facture.php">Facture</a><a href="index.php">Déconnexion</a></div>

<div class="wrap">
<h2 style="font-size:20px;margin:0 0 4px 0">Bonjour <?= htmlspecialchars($_SESSION['nom']??'Gérant') ?> </h2>
<p style="font-size:13px;color:#64748b;margin:0 0 18px 0">Site : <?= htmlspecialchars($nomSiteAffiche) ?> - CA total : <?= number_format($caTotal,0,',',' ') ?> F</p>

<div class="kpi">
  <div class="cardK"><div class="k">CA AUJOURD'HUI - <?= htmlspecialchars($nomSiteAffiche) ?></div><div class="v" style="color:#16a34a"><?= number_format($caJour,0,',',' ') ?> F</div></div>
  <div class="cardK"><div class="k">COMMANDES DU JOUR</div><div class="v"><?= $nbJour ?></div></div>
  <div class="cardK"><div class="k">EN COURS</div><div class="v" style="color:#dc2626"><?= $enCours ?></div></div>
  <div class="cardK"><div class="k">NOUVEAUX CLIENTS</div><div class="v"><?= $nouveaux ?></div></div>
</div>

<div class="cardMain">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
<h3 style="margin:0;font-size:16px">Dernières commandes - <?= htmlspecialchars($nomSiteAffiche) ?></h3>
<a href="commande.php?new=1" style="background:#0a1938;color:#fff;padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:700">+ Nouvelle commande</a>
</div>

<form method="get" style="display:flex;gap:8px;margin-bottom:14px">
<input class="s" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Rechercher : Réf (101), Client (Aminata), Téléphone 01XXXXXXXX, Statut...">
<button class="btnS">Rechercher</button>
<?php if($q):?><a href="dashboard.php" style="padding:10px 14px;background:#fee2e2;color:#dc2626;border-radius:8px;text-decoration:none;font-size:12px">X</a><?php endif;?>
</form>

<table>
<tr><th>RÉF.</th><th>CLIENT</th><th>TÉLÉPHONE</th><th>DATE</th><th>STATUT</th><th>MONTANT</th><th>ACTION</th></tr>
<?php foreach($commandes as $c):?>
<tr>
<td>CMD-<?= $c['id_commande'] ?></td>
<td><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></td>
<td><?= formatTel10($c['telephone']) ?></td>
<td><?= date('Y-m-d H:i',strtotime($c['date_commande'])) ?></td>
<td><?= badgeG($c['Statut_commande']) ?></td>
<td style="font-weight:700"><?= $c['montant'] ?> F</td>
<td><a href="facture.php?id=<?= $c['id_commande'] ?>" style="background:#e0e7ff;color:#1e40af;padding:6px 14px;border-radius:20px;text-decoration:none;font-size:11px;font-weight:700">Voir</a></td>
</tr>
<?php endforeach;?>
</table>
</div>
</div>
</body></html>