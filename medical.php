<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Suivi médical';
$db = getDB();

$campeurs = $db->query("
    SELECT c.*, s.nom AS session_nom, s.site
    FROM campeurs c
    LEFT JOIN inscriptions i ON i.campeur_id=c.id AND i.statut IN ('confirme','en_attente')
    LEFT JOIN sessions s ON s.id=i.session_id
    ORDER BY c.nom
")->fetchAll();

require_once '../includes/header.php';
?>
<div class="page-header">
  <div><h2>Suivi médical</h2><p>Fiches médicales de tous les campeurs</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <?php if ($campeurs): ?>
    <table>
      <thead><tr><th>Campeur</th><th>Âge</th><th>Session</th><th>Groupe sanguin</th><th>Allergies</th><th>Médicaments</th><th>Régime</th><th>Médecin</th></tr></thead>
      <tbody>
      <?php foreach ($campeurs as $c): ?>
      <tr>
        <td>
          <a href="campeurs.php?action=view&id=<?= $c['id'] ?>" style="font-weight:600;color:var(--bleu);"><?= sanitize($c['prenom'].' '.$c['nom']) ?></a>
          <div class="text-sm text-muted">Tuteur : <?= sanitize($c['tuteur_nom']?:'—') ?> — <?= sanitize($c['tuteur_telephone']?:'—') ?></div>
        </td>
        <td class="text-sm"><?= age($c['date_naissance']) ?> ans</td>
        <td><?php if ($c['session_nom']): ?><?= siteBadge($c['site']) ?><div class="text-sm text-muted" style="margin-top:3px;"><?= sanitize(substr($c['session_nom'],0,20)) ?></div><?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?></td>
        <td><?php if ($c['groupe_sanguin']): ?><span class="badge badge-danger"><?= sanitize($c['groupe_sanguin']) ?></span><?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?></td>
        <td class="text-sm"><?= $c['allergies'] ? '<span style="color:var(--rouge);">⚠ '.sanitize(substr($c['allergies'],0,40)).'</span>' : '<span class="text-muted">Aucune</span>' ?></td>
        <td class="text-sm"><?= sanitize($c['medicaments']?substr($c['medicaments'],0,40):'—') ?></td>
        <td class="text-sm"><?= sanitize($c['restrictions_alimentaires']?:'—') ?></td>
        <td class="text-sm"><?= sanitize($c['medecin_nom']?:'—') ?><?php if ($c['medecin_telephone']): ?><div class="text-muted"><?= sanitize($c['medecin_telephone']) ?></div><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty" style="padding:60px;"><h3>Aucun campeur enregistré.</h3></div><?php endif; ?>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
