<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
echo "Date MySQL du jour: ";
print_r($pdo->query("SELECT CURDATE() as today")->fetch());
echo "<br><br>";

echo "Dernières commandes:<br>";
foreach($pdo->query("SELECT id_commande, date_commande FROM commande ORDER BY id_commande DESC LIMIT 5") as $r){
  echo "CMD-".$r['id_commande']." => ".$r['date_commande']."<br>";
}

echo "<br>CA avec CURDATE() (aujourd'hui): ";
echo $pdo->query("SELECT COALESCE(SUM(p.montant_paiement),0) FROM paiement p JOIN commande c ON c.id_commande=p.id_commande WHERE DATE(c.date_commande)=CURDATE()")->fetchColumn() . " F<br>";

echo "CA avec 2026-10-01 (tes 2 commandes de la photo): ";
echo $pdo->query("SELECT COALESCE(SUM(p.montant_paiement),0) FROM paiement p JOIN commande c ON c.id_commande=p.id_commande WHERE DATE(c.date_commande)='2026-10-01'")->fetchColumn() . " F<br>";
?>