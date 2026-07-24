<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Inscriptions';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'campeur_id'    => (int)$_POST['campeur_id'],
        'session_id'    => (int)$_POST['session_id'],
        'statut'        => $_POST['statut'] ?? 'en_attente',
        'montant_total' => (float)str_replace(' ','',$_POST['montant_total']??0),
        'notes'         => trim($_POST['notes']??''),
    ];
    if ($action === 'new') {
        $db->prepare("INSERT INTO inscriptions (campeur_id,session_id,statut,montant_total,notes) VALUES (?,?,?,?,?)")->execute(array_values($d));
        redirect('inscriptions.php?msg=added');
    } elseif ($action === 'edit' && $id) {
        $db->prepare("UPDATE inscriptions SET campeur_id=?,session_id=?,statut=?,montant_total=?,notes=? WHERE id=?")->execute(array_merge(array_values($d),[$id]));
        redirect('inscriptions.php?msg=updated');
    }
}
if ($action === 'delete' && $id) {
    $db->prepare("DELETE FROM inscriptions WHERE id=?")->execute([$id]);
    redirect('inscriptions.php?msg=deleted');
}

$insc = null;
if (in_array($action,['edit']) && $id) {
    $st = $db->prepare("SELECT * FROM inscriptions WHERE id=?"); $st->execute([$id]); $insc = $st->fetch();
}

$prefill_campeur = (int)($_GET['campeur_id']??0);
$prefill_session = (int)($_GET['session_id']??0);
$campeurs_list   = $db->query("SELECT id, CONCAT(prenom,' ',nom) AS n FROM campeurs ORDER BY nom")->fetchAll();
$sessions_list   = $db->query("SELECT id, nom, site, prix FROM sessions WHERE statut='ouvert' ORDER BY date_debut")->fetchAll();

$liste = $db->query("
    SELECT i.*, c.nom, c.prenom, s.nom AS snom, s.site,
        COALESCE(SUM(CASE WHEN p.statut='recu' THEN p.montant ELSE 0 END),0) AS paye
    FROM inscriptions i
    JOIN campeurs c ON c.id=i.campeur_id
    JOIN sessions s ON s.id=i.session_id
    LEFT JOIN paiements p ON p.inscription_id=i.id
    GROUP BY i.id ORDER BY i.date_inscription DESC LIMIT 200
")->fetchAll();

// Construire la map prix pour JS
$prix_map = [];
foreach ($sessions_list as $s) $prix_map[$s['id']] = $s['prix'];

require_once '../includes/header.php';
?>

<?php if (in_array($action,['new','edit'])): ?>
<div class="page-header">
  <div><h2><?= $action==='new'?'Nouvelle inscription':'Modifier l\'inscription' ?></h2></div>
  <a href="inscriptions.php" class="btn btn-outline">← Retour</a>
</div>
<form method="POST" action="inscriptions.php?action=<?= $action ?><?= $id?'&id='.$id:'' ?>">
<div class="card mb-20">
  <div class="card-header"><span class="card-title">Détails de l'inscription</span></div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Campeur *</label>
        <select name="campeur_id" required>
          <option value="">-- Sélectionner --</option>
          <?php foreach ($campeurs_list as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($insc['campeur_id']??$prefill_campeur)==$c['id']?'selected':'' ?>><?= sanitize($c['n']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Session *</label>
        <select name="session_id" id="sel_session" required>
          <option value="">-- Sélectionner --</option>
          <?php foreach ($sessions_list as $s): ?>
          <option value="<?= $s['id'] ?>" data-prix="<?= $s['prix'] ?>" <?= ($insc['session_id']??$prefill_session)==$s['id']?'selected':'' ?>>
            <?= sanitize($s['nom']) ?> (<?= formatMontant($s['prix']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Statut</label>
        <select name="statut">
          <option value="en_attente" <?= ($insc['statut']??'en_attente')==='en_attente'?'selected':'' ?>>En attente</option>
          <option value="confirme"   <?= ($insc['statut']??'')==='confirme'?'selected':'' ?>>Confirmé</option>
          <option value="annule"     <?= ($insc['statut']??'')==='annule'?'selected':'' ?>>Annulé</option>
        </select>
      </div>
      <div class="form-group">
        <label>Montant total dû (FCFA)</label>
        <input type="number" name="montant_total" id="inp_montant" step="1" value="<?= $insc['montant_total']??'' ?>" placeholder="Rempli automatiquement">
        <span class="hint">Se remplit automatiquement selon la session</span>
      </div>
      <div class="form-group full">
        <label>Notes</label>
        <textarea name="notes"><?= sanitize($insc['notes']??'') ?></textarea>
      </div>
    </div>
  </div>
</div>
<div style="display:flex;gap:10px;justify-content:flex-end;">
  <a href="inscriptions.php" class="btn btn-outline">Annuler</a>
  <button type="submit" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
    Enregistrer l'inscription
  </button>
</div>
</form>
<script>
const prixMap = <?= json_encode($prix_map) ?>;
document.getElementById('sel_session').addEventListener('change', function() {
    const p = prixMap[this.value];
    if (p) document.getElementById('inp_montant').value = p;
});
if (document.getElementById('sel_session').value) {
    document.getElementById('sel_session').dispatchEvent(new Event('change'));
}
</script>

<?php else: ?>
<div class="page-header">
  <div><h2>Inscriptions</h2><p><?= count($liste) ?> inscription(s)</p></div>
  <a href="inscriptions.php?action=new" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nouvelle inscription
  </a>
</div>

<?php if ($_GET['msg']??''): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
  <?= ['added'=>'Inscription ajoutée !','updated'=>'Inscription mise à jour !','deleted'=>'Inscription supprimée.'][$_GET['msg']]??'' ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <?php if ($liste): ?>
    <table>
      <thead><tr><th>Campeur</th><th>Session</th><th>Site</th><th>Total dû</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($liste as $r):
        $reste = max(0, $r['montant_total'] - $r['paye']);
      ?>
      <tr>
        <td>
          <a href="campeurs.php?action=view&id=<?= $r['campeur_id'] ?>" style="font-weight:600;color:var(--bleu);">
            <?= sanitize($r['prenom'].' '.$r['nom']) ?>
          </a>
        </td>
        <td class="text-sm"><?= sanitize(substr($r['snom'],0,32)) ?></td>
        <td><?= siteBadge($r['site']) ?></td>
        <td class="fw-600"><?= formatMontant($r['montant_total']) ?></td>
        <td style="color:var(--vert);font-weight:600;"><?= formatMontant($r['paye']) ?></td>
        <td>
          <?php if ($reste<=0): ?><span class="badge badge-success">✓ Soldé</span>
          <?php else: ?><span style="color:var(--rouge);font-weight:600;"><?= formatMontant($reste) ?></span><?php endif; ?>
        </td>
        <td><?= statutBadge($r['statut']) ?></td>
        <td>
          <div style="display:flex;gap:4px;">
            <a href="inscriptions.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-outline btn-sm btn-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <a href="paiements.php?action=new&inscription_id=<?= $r['id'] ?>" class="btn btn-gold btn-sm btn-icon" title="Ajouter paiement">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </a>
            <a href="inscriptions.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-red btn-sm btn-icon" onclick="return confirm('Supprimer ?')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty" style="padding:60px;"><h3>Aucune inscription</h3></div><?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
