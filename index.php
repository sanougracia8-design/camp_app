<?php
require_once 'includes/config.php';
// Login temporairement désactivé — accès direct
$_SESSION['user_id'] = 1;
$_SESSION['user']    = [
    'id'     => 1,
    'nom'    => 'Admin',
    'prenom' => 'Soviecap',
    'email'  => 'admin@soviecap.com',
    'role'   => 'super_admin',
];
redirect('pages/dashboard.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — Soviecap International</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-icon">
        <svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="15" stroke="white" stroke-width="2.2"/><ellipse cx="20" cy="20" rx="7" ry="15" stroke="white" stroke-width="1.8"/><line x1="5" y1="20" x2="35" y2="20" stroke="white" stroke-width="1.8"/><line x1="7" y1="13" x2="33" y2="13" stroke="white" stroke-width="1.2"/><line x1="7" y1="27" x2="33" y2="27" stroke="white" stroke-width="1.2"/><circle cx="20" cy="20" r="2.8" fill="white"/></svg>
      </div>
      <span class="login-h1">SOVIECAP</span>
      <span class="login-sub">International</span>
      <span class="login-desc">Gestion des camps de vacances<br>🌍 Lomé · Cotonou · Accra</span>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom:16px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= sanitize($error) ?>
    </div>
    <?php endif; ?>
    <?php if (($_GET['msg']??'')==='login'): ?>
    <div class="alert alert-warning" style="margin-bottom:16px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
      Veuillez vous connecter.
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group form-group-login">
        <label>Email</label>
        <input type="email" name="email" placeholder="admin@soviecap.com" value="<?= sanitize($_POST['email']??'') ?>" required>
      </div>
      <div class="form-group form-group-login">
        <label>Mot de passe</label>
        <input type="password" name="mdp" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary login-btn">Se connecter</button>
    </form>
    <p style="text-align:center;margin-top:18px;font-size:.76rem;color:#aab;">Démo : admin@soviecap.com / password</p>
  </div>
</div>
</body></html>
