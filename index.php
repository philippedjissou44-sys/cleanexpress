<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=clean_express;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start();
$erreur="";

try{ 
  $sites=$pdo->query("SELECT * FROM site ORDER BY nom_site")->fetchAll(); 
}catch(Exception $e){ 
  $sites=[]; 
}

if(isset($_POST['login'])){
  $u=trim($_POST['username']); 
  $p=trim($_POST['password']); 
  $id_site_choisi = $_POST['id_site'] ?? null;
  
  $stmt=$pdo->prepare("SELECT * FROM users WHERE pseudo=? OR nom_user=? LIMIT 1");
  $stmt->execute([$u,$u]); 
  $user=$stmt->fetch();

  if(!$user){
    $erreur="Pseudo '$u' introuvable.";
  } elseif($user['etat'] == 0){
    $erreur="Compte désactivé. Contacte l'admin.";
  } elseif($user['mot_de_passe'] !== $p){
    $erreur="Mot de passe incorrect pour '$u'";
  } elseif(strtolower($user['role'])!='admin' && strtolower($user['role'])!='administrateur' && $user['id_site'] != $id_site_choisi){
    $erreur="Ce compte n'est pas affecté à ce site. Choisis le bon site.";
  } else {
    $_SESSION['id']=$user['id_users']; 
    $_SESSION['nom']=$user['nom_users']; 
    $_SESSION['prenom']=$user['prenom_users'];
    $_SESSION['pseudo']=$user['pseudo'];
    $_SESSION['role']=$user['role']; 
    $_SESSION['id_site']=$user['id_site'];
    
    $pdo->prepare("UPDATE users SET derniere_conn=? WHERE id_users=?")->execute([date('d/m/Y H:i'), $user['id_users']]);

    if(strtolower($user['role'])=='admin' || strtolower($user['role'])=='administrateur'){
      header("Location: dashboard_admin.php"); exit;
    }else{
      header("Location: dashboard.php"); exit;
    }
  }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>CleanExpress - Connexion</title>
<style>
*{box-sizing:border-box;font-family:Arial}body{margin:0;background:#f2f4f8;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px}
.main{width:980px;background:#fff;border-radius:20px;display:flex;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.1)}
.left{width:48%;background:linear-gradient(180deg,#eaf2ff 0%,#d9e6ff 100%);padding:24px;display:flex;flex-direction:column;justify-content:space-between}
.logo{display:flex;gap:10px;align-items:center}.icoLogo{width:36px;height:36px;background:#0b1c3d;border-radius:8px;display:flex;align-items:center;justify-content:center}
.brand{font-weight:800;color:#0b1c3d;font-size:17px}.brand span{display:block;font-weight:400;font-size:12px;color:#5a6d8e}
.card{background:#0a1938;border-radius:18px;padding:20px;text-align:center;color:#fff;margin-top:24px}
.card h2{margin:0;color:#3b82f6;font-size:24px;font-weight:900}.card .subTitle{font-size:10px;letter-spacing:2px;color:#6ea8ff;margin-top:2px}
.badgeBlue{display:inline-block;background:#2563eb;color:#fff;padding:6px 14px;border-radius:20px;font-size:11px;margin:14px 0;font-weight:700}
.card p{font-size:13px;color:#dbe6ff;margin:8px 0}.pill{display:inline-flex;background:#132a5a;border:1px solid #1e3c7a;padding:5px 10px;border-radius:20px;font-size:11px;margin:4px}
.footCard{margin-top:12px;background:#07122e;border-radius:10px;padding:8px 10px;display:flex;justify-content:space-between;font-size:11px;color:#8aa6d0}
.slogan{margin-top:auto;padding-top:30px}.slogan h3{font-family:Georgia,serif;margin:0;font-size:20px;color:#111}.slogan p{font-size:12px;color:#6b7280}
.right{width:52%;background:#fffdf5;padding:34px 32px}.right h1{margin:0;font-size:24px}.right .desc{font-size:13px;color:#6b7280;margin:6px 0 22px 0}
label{font-size:12px;color:#374151;display:block;margin:16px 0 6px 0}.input{background:#eef3ff;border:1px solid #d7e2f3;border-radius:12px;display:flex;align-items:center;padding:0 12px}
.input input,.input select{border:none;background:transparent;width:100%;padding:12px 4px;outline:none;font-size:13px}.btn{width:100%;background:#2d72ea;color:#fff;border:none;padding:13px;border-radius:12px;font-weight:700;margin-top:18px;cursor:pointer}
.smallNote{margin-top:20px;text-align:center;font-size:11px;color:#9ca3af}.error{background:#ffe5e5;color:#b91c1c;padding:10px;border-radius:8px;font-size:12px;margin-bottom:12px;border:1px solid #fecaca}
</style></head><body>
<div class="main">
<div class="left">
<div>
<div class="logo"><div class="icoLogo">💧</div><div class="brand">CleanExpress<span>Pressing et blanchisserie</span></div></div>
<div class="card"><h2>CleanExpress</h2><div class="subTitle">PRESSING & BLANCHISSERIE</div><div class="badgeBlue">Vos vêtements, notre priorité !</div><p>Un service de pressing complet pour tous vos vêtements</p><div><span class="pill">🧼 Nettoyage à sec</span> <span class="pill">🫧 Lavage de qualité</span><br><span class="pill">➡️ Repassage soigné</span></div><div class="footCard"><span>📞 +229 0147331039</span><span>📍 Cotonou, Benin</span></div></div>
</div>
<div class="slogan"><h3>Vos vêtements, comme neufs.</h3><p>Suivez les commandes, les paiements et les livraisons par site.</p></div>
</div>
<div class="right">
<h1>Bon retour</h1><div class="desc">Connectez-vous à votre espace utilisateur</div>
<?php if($erreur) echo "<div class='error'>$erreur</div>";?>
<form method="post">
<label>Site d'affectation</label><div class="input"><select name="id_site" required>
<?php foreach($sites as $s):?><option value="<?= $s['id_site']?>" <?= stripos($s['nom_site'],'Calavi')!==false?'selected':''?>><?= htmlspecialchars($s['nom_site'])?></option><?php endforeach;?>
<?php if(empty($sites)):?><option value="1" selected>Calavi - Zogbadjè</option><?php endif;?>
</select></div>
<label>Nom d'utilisateur (Pseudo)</label><div class="input"><input type="text" name="username" placeholder="Votre nom d'utilisateur" required></div>
<label>Mot de passe</label><div class="input"><input type="password" name="password" placeholder="Votre mot de passe" required></div>
<div style="margin-top:14px;display:flex;align-items:center;gap:8px;font-size:12px"><input type="checkbox" checked> Rester connecté</div>
<button class="btn" type="submit" name="login">Se connecter</button>
<div class="smallNote">Accès sécurisé gérant et administrateur</div>
</form>
</div>
</div>
</body></html>