<?php $cp = basename($_SERVER['PHP_SELF'], '.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? sanitize($page_title).' — ' : '' ?>Soviecap International</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ROOT_PATH ?>css/style.css">
</head>
<body>
<nav class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12" stroke="#fff" stroke-width="2"/><ellipse cx="16" cy="16" rx="6" ry="12" stroke="#fff" stroke-width="1.5"/><line x1="4" y1="16" x2="28" y2="16" stroke="#fff" stroke-width="1.5"/><line x1="6" y1="11" x2="26" y2="11" stroke="#fff" stroke-width="1"/><line x1="6" y1="21" x2="26" y2="21" stroke="#fff" stroke-width="1"/><circle cx="16" cy="16" r="2.5" fill="#fff"/></svg>
    </div>
    <div><span class="brand-name">SOVIECAP</span><span class="brand-sub">International</span></div>
  </div>

  <div class="nav-section">
    <div class="nav-label">Principal</div>
    <a href="<?= ROOT_PATH ?>pages/dashboard.php" class="nav-item <?= $cp=='dashboard'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Tableau de bord
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-label">Campeurs</div>
    <a href="<?= ROOT_PATH ?>pages/campeurs.php" class="nav-item <?= $cp=='campeurs'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Campeurs
    </a>
    <a href="<?= ROOT_PATH ?>pages/inscriptions.php" class="nav-item <?= $cp=='inscriptions'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Inscriptions
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-label">Finances</div>
    <a href="<?= ROOT_PATH ?>pages/paiements.php" class="nav-item <?= $cp=='paiements'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Finances & Paiements
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-label">Organisation</div>
    <a href="<?= ROOT_PATH ?>pages/sessions.php" class="nav-item <?= $cp=='sessions'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Sessions de camp
    </a>
    <a href="<?= ROOT_PATH ?>pages/activites.php" class="nav-item <?= $cp=='activites'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Activités & Sorties
    </a>
    <a href="<?= ROOT_PATH ?>pages/personnel.php" class="nav-item <?= $cp=='personnel'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Personnel
    </a>
    <a href="<?= ROOT_PATH ?>pages/hebergements.php" class="nav-item <?= $cp=='hebergements'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Hébergements
    </a>
    <a href="<?= ROOT_PATH ?>pages/medical.php" class="nav-item <?= $cp=='medical'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      Suivi médical
    </a>
  </div>

  <div class="sidebar-footer">
    <?php $u = currentUser(); if ($u): ?>
    <div class="user-box">
      <div class="user-av"><?= strtoupper(substr($u['prenom'],0,1).substr($u['nom'],0,1)) ?></div>
      <div>
        <div class="user-name"><?= sanitize($u['prenom'].' '.$u['nom']) ?></div>
        <div class="user-role"><?= sanitize($u['role']) ?></div>
      </div>
    </div>
    <div style="display:flex;gap:6px;">
      <a href="<?= ROOT_PATH ?>pages/profil.php" class="btn-sidebar">⚙ Profil</a>
      <a href="<?= ROOT_PATH ?>logout.php" class="btn-sidebar btn-sidebar-red">Déconnexion</a>
    </div>
    <?php endif; ?>
  </div>
</nav>

<main class="main-content">
  <div class="topbar">
    <h1 class="page-title"><?= isset($page_title) ? sanitize($page_title) : '' ?></h1>
    <div style="display:flex;align-items:center;gap:12px;">
      <span class="site-badge">🌍 Lomé · Cotonou · Accra</span>
      <span class="topbar-date"><?= date('d/m/Y') ?></span>
    </div>
  </div>
  <div class="page-body">
