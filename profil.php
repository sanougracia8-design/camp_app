<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Mon profil & Sécurité';
$db = getDB();
$user = currentUser();
$msg  = '';
$error = '';

// Lire le statut login depuis un fichier de config simple
$login_file = __DIR__.'/../includes/login_status.php';
if (!file_exists($login_file)) {
    file_put_contents($login_file, '<?php define("LOGIN_ACTIF", false); ?>');
}
include $login_file;
$login_actif = defined('LOGIN_ACTIF') ? LOGIN_ACTIF : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Changer infos
    if (isset($_POST['form']) && $_POST['form'] === 'infos') {
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        if ($nom && $prenom && $email) {
            $db->prepare("UPDATE utilisateurs SET nom=?,prenom=?,email=? WHERE id=1")->execute([$nom,$prenom,$email]);
            $st = $db->prepare("SELECT * FROM utilisateurs WHERE id=1");
            $st->execute();
            $_SESSION['user'] = $st->fetch();
            $msg = 'Profil mis à jour !';
            $user = $_SESSION['user'];
        }
    }

    // Changer mot de passe
    if (isset($_POST['form']) && $_POST['form'] === 'mdp') {
        $nouveau = $_POST['nouveau'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (strlen($nouveau) < 4) {
            $error = 'Le mot de passe doit avoir au moins 4 caractères.';
        } elseif ($nouveau !== $confirm) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $db->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=1")->execute([$hash]);
            $msg = 'Mot de passe enregistré avec succès !';
        }
    }

    // Activer / désactiver le login
    if (isset($_POST['form']) && $_POST['form'] === 'login_toggle') {
        $activer = isset($_POST['login_actif']) ? true : false;
        file_put_contents($login_file, '<?php define("LOGIN_ACTIF", '.($activer?'true':'false').'); ?>');
        $login_actif = $activer;

        // Mettre à jour index.php selon le choix
        if ($activer) {
            // Activer le vrai login
            $index_content = '<?php
require_once \'includes/config.php\';
if (isLoggedIn()) redirect(\'pages/dashboard.php\');
$error = \'\';
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $email = trim($_POST[\'email\'] ?? \'\');
    $mdp   = $_POST[\'mdp\'] ?? \'\';
    if ($email && $mdp) {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM utilisateurs WHERE email=? AND actif=1");
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($mdp, $user[\'mot_de_passe\'])) {
            $_SESSION[\'user_id\'] = $user[\'id\'];
            $_SESSION[\'user\']    = $user;
            $db->prepare("UPDATE utilisateurs SET derniere_connexion=NOW() WHERE id=?")->execute([$user[\'id\']]);
            redirect(\'pages/dashboard.php\');
        } else { $error = \'Email ou mot de passe incorrect.\'; }
    } else { $error = \'Veuillez remplir tous les champs.\'; }
}
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group form-group-login"><label>Email</label><input type="email" name="email" placeholder="admin@soviecap.com" required></div>
      <div class="form-group form-group-login"><label>Mot de passe</label><input type="password" name="mdp" placeholder="••••••••" required></div>
      <button type="submit" class="btn btn-primary login-btn">Se connecter</button>
    </form>
  </div>
</div>
</body></html>';
            file_put_contents(__DIR__.'/../index.php', $index_content);
        } else {
            // Désactiver le login — accès direct
            $index_bypass = '<?php
require_once \'includes/config.php\';
$_SESSION[\'user_id\'] = 1;
$_SESSION[\'user\']    = [\'id\'=>1,\'nom\'=>\'Admin\',\'prenom\'=>\'Soviecap\',\'email\'=>\'admin@soviecap.com\',\'role\'=>\'super_admin\'];
redirect(\'pages/dashboard.php\');';
            file_put_contents(__DIR__.'/../index.php', $index_bypass);
        }
        $msg = $activer ? 'Login activé ! Vous devrez vous connecter à la prochaine visite.' : 'Login désactivé. Accès direct sans mot de passe.';
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <div><h2>Mon profil & Sécurité</h2></div>
</div>

<?php if ($msg): ?>
<div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= sanitize($msg) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
    <?= sanitize($error) ?>
</div>
<?php endif; ?>

<div class="grid-2">

    <!-- Infos personnelles -->
    <div class="card">
        <div class="card-header"><span class="card-title">👤 Informations personnelles</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form" value="infos">
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= sanitize($user['prenom'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= sanitize($user['nom'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:18px;">
                    <label>Email de connexion</label>
                    <input type="email" name="email" value="<?= sanitize($user['email'] ?? '') ?>" required>
                </div>
                <button type="submit" style="width:100%;justify-content:center;" class="btn btn-primary">Mettre à jour</button>
            </form>
        </div>
    </div>

    <!-- Mot de passe -->
    <div class="card">
        <div class="card-header"><span class="card-title">🔒 Définir le mot de passe</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form" value="mdp">
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="nouveau" placeholder="Minimum 4 caractères" required>
                </div>
                <div class="form-group" style="margin-bottom:18px;">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm" placeholder="Répéter le mot de passe" required>
                </div>
                <button type="submit" style="width:100%;justify-content:center;" class="btn btn-red">Enregistrer le mot de passe</button>
            </form>
        </div>
    </div>

</div>

<!-- Activation login -->
<div class="card mt-20">
    <div class="card-header"><span class="card-title">🔐 Activer / Désactiver la page de connexion</span></div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;">
                <p style="font-size:.9rem;color:var(--txt-mid);margin-bottom:6px;">
                    <?php if ($login_actif): ?>
                    <span style="color:var(--vert);font-weight:700;">✅ Login ACTIVÉ</span> — Une page de connexion s'affiche quand on ouvre l'application.
                    <?php else: ?>
                    <span style="color:var(--rouge);font-weight:700;">🔓 Login DÉSACTIVÉ</span> — L'application s'ouvre directement sans demander de mot de passe.
                    <?php endif; ?>
                </p>
                <p class="text-sm text-muted">Activez le login quand vous voulez protéger l'accès. Désactivez-le pour un accès rapide.</p>
            </div>
            <form method="POST" style="flex-shrink:0;">
                <input type="hidden" name="form" value="login_toggle">
                <?php if ($login_actif): ?>
                    <button type="submit" class="btn btn-outline">🔓 Désactiver le login</button>
                <?php else: ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:.85rem;">
                        <input type="checkbox" name="login_actif" checked>
                        Activer avec mot de passe
                    </label>
                    <button type="submit" class="btn btn-primary">🔐 Activer le login</button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($login_actif): ?>
        <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/></svg>
            Vos identifiants actuels : <strong><?= sanitize($user['email'] ?? 'admin@soviecap.com') ?></strong> + le mot de passe que vous avez défini ci-dessus.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
