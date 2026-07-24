<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Tableau de bord';
$db = getDB();

$nb_campeurs     = $db->query("SELECT COUNT(*) FROM campeurs")->fetchColumn();
$nb_inscriptions = $db->query("SELECT COUNT(*) FROM inscriptions WHERE statut IN ('confirme','en_attente')")->fetchColumn();
$nb_sessions     = $db->query("SELECT COUNT(*) FROM sessions WHERE statut='ouvert'")->fetchColumn();
$total_recu      = $db->query("SELECT COALESCE(SUM(montant),0) FROM paiements WHERE statut='recu'")->fetchColumn();
$total_attendu   = $db->query("SELECT COALESCE(SUM(montant_total),0) FROM inscriptions WHERE statut IN ('confirme','en_attente')")->fetchColumn();
$total_restant   = $total_attendu - $total_recu;

$sessions = $db->query("
    SELECT s.*, COUNT(DISTINCT i.id) AS nb_inscrits
    FROM sessions s
    LEFT JOIN inscriptions i ON i.session_id=s.id AND i.statut IN ('confirme','en_attente')
    WHERE s.statut IN ('ouvert','complet')
    GROUP BY s.id ORDER BY s.date_debut LIMIT 5
")->fetchAll();

$derniers = $db->query("
    SELECT i.*, c.nom, c.prenom, c.date_naissance, s.nom AS session_nom, s.site
    FROM inscriptions i
    JOIN campeurs c ON c.id=i.campeur_id
    JOIN sessions s ON s.id=i.session_id
    ORDER BY i.date_inscription DESC LIMIT 8
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h2>Bonjour, <?= sanitize(currentUser()['prenom']) ?> 👋</h2>
    <p>Aperçu général de Soviecap International</p>
  </div>
  <a href="inscriptions.php?action=new" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nouvelle inscription
  </a>
</div>

<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
    <div class="stat-value"><?= $nb_campeurs ?></div>
    <div class="stat-label">Campeurs enregistrés</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
    <div class="stat-value"><?= $nb_inscriptions ?></div>
    <div class="stat-label">Inscriptions actives</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
    <div class="stat-value"><?= number_format($total_recu,0,',',' ') ?></div>
    <div class="stat-label">FCFA encaissés</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
    <div class="stat-value"><?= number_format($total_restant,0,',',' ') ?></div>
    <div class="stat-label">FCFA restants à encaisser</div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Sessions ouvertes</span>
      <a href="sessions.php" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <div>
      <?php foreach ($sessions as $s):
        $pct = $s['capacite']>0 ? ($s['nb_inscrits']/$s['capacite'])*100 : 0;
        $bar = $pct>=90?'danger':($pct>=70?'warn':'');
      ?>
      <div style="padding:14px 20px;border-bottom:1px solid var(--border-2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <div>
            <div style="font-weight:600;font-size:.88rem;color:var(--bleu);"><?= sanitize($s['nom']) ?></div>
            <div class="text-sm text-muted"><?= formatDate($s['date_debut']) ?> → <?= formatDate($s['date_fin']) ?></div>
          </div>
          <div style="text-align:right;">
            <?= siteBadge($s['site']) ?>
            <div class="text-sm text-muted" style="margin-top:3px;"><?= $s['nb_inscrits'] ?>/<?= $s['capacite'] ?></div>
          </div>
        </div>
        <div class="progress"><div class="progress-fill <?= $bar ?>" style="width:<?= min($pct,100) ?>%"></div></div>
      </div>
      <?php endforeach; ?>
      <?php if (!$sessions): ?><div class="empty"><p>Aucune session ouverte.</p></div><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Dernières inscriptions</span>
      <a href="inscriptions.php" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Campeur</th><th>Site</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($derniers as $d): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="av av-blue"><?= strtoupper(substr($d['prenom'],0,1).substr($d['nom'],0,1)) ?></div>
              <div>
                <div style="font-weight:600;"><?= sanitize($d['prenom'].' '.$d['nom']) ?></div>
                <div class="text-sm text-muted"><?= age($d['date_naissance']) ?> ans</div>
              </div>
            </div>
          </td>
          <td><?= siteBadge($d['site']) ?></td>
          <td><?= statutBadge($d['statut']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$derniers): ?><tr><td colspan="3" style="text-align:center;padding:30px;color:var(--txt-muted);">Aucune inscription.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
