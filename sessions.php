<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Sessions';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ['nom'=>trim($_POST['nom']??''),'site'=>$_POST['site']??'lome','date_debut'=>$_POST['date_debut']??'','date_fin'=>$_POST['date_fin']??'','capacite'=>(int)($_POST['capacite']??50),'prix'=>(float)str_replace(' ','',$_POST['prix']??0),'description'=>trim($_POST['description']??''),'statut'=>$_POST['statut']??'ouvert'];
    if ($action==='new') { $db->prepare("INSERT INTO sessions (nom,site,date_debut,date_fin,capacite,prix,description,statut) VALUES (?,?,?,?,?,?,?,?)")->execute(array_values($d)); redirect('sessions.php?msg=added'); }
    elseif ($action==='edit'&&$id) { $db->prepare("UPDATE sessions SET nom=?,site=?,date_debut=?,date_fin=?,capacite=?,prix=?,description=?,statut=? WHERE id=?")->execute(array_merge(array_values($d),[$id])); redirect('sessions.php?msg=updated'); }
}
if ($action==='delete'&&$id) { $db->prepare("DELETE FROM sessions WHERE id=?")->execute([$id]); redirect('sessions.php?msg=deleted'); }

$session = null;
if (in_array($action,['edit','view'])&&$id) { $st=$db->prepare("SELECT * FROM sessions WHERE id=?"); $st->execute([$id]); $session=$st->fetch(); if(!$session) redirect('sessions.php'); }

$sessions = $db->query("SELECT s.*, COUNT(DISTINCT i.id) AS nb FROM sessions s LEFT JOIN inscriptions i ON i.session_id=s.id AND i.statut IN ('confirme','en_attente') GROUP BY s.id ORDER BY s.date_debut DESC")->fetchAll();
$sites = ['lome'=>'Lomé','cotonou'=>'Cotonou','accra'=>'Accra'];

require_once '../includes/header.php';
?>
<?php if (in_array($action,['new','edit'])): ?>
<div class="page-header"><div><h2><?= $action==='new'?'Nouvelle session':'Modifier la session' ?></h2></div><a href="sessions.php" class="btn btn-outline">← Retour</a></div>
<form method="POST" action="sessions.php?action=<?= $action ?><?= $id?'&id='.$id:'' ?>">
<div class="card mb-20"><div class="card-header"><span class="card-title">Détails</span></div><div class="card-body">
<div class="form-grid">
  <div class="form-group full"><label>Nom de la session *</label><input type="text" name="nom" required value="<?= sanitize($session['nom']??'') ?>" placeholder="Camp Lomé — Session 1 (Juillet 2025)"></div>
  <div class="form-group"><label>Site</label><select name="site"><?php foreach($sites as $v=>$l): ?><option value="<?= $v ?>" <?= ($session['site']??'lome')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Date début *</label><input type="date" name="date_debut" required value="<?= $session['date_debut']??'' ?>"></div>
  <div class="form-group"><label>Date fin *</label><input type="date" name="date_fin" required value="<?= $session['date_fin']??'' ?>"></div>
  <div class="form-group"><label>Capacité (places)</label><input type="number" name="capacite" value="<?= $session['capacite']??50 ?>"></div>
  <div class="form-group"><label>Prix (FCFA)</label><input type="number" name="prix" step="1" value="<?= $session['prix']??'' ?>" placeholder="150000"></div>
  <div class="form-group"><label>Statut</label><select name="statut"><?php foreach(['ouvert'=>'Ouvert','complet'=>'Complet','termine'=>'Terminé','annule'=>'Annulé'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($session['statut']??'ouvert')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group full"><label>Description</label><textarea name="description"><?= sanitize($session['description']??'') ?></textarea></div>
</div></div></div>
<div style="display:flex;gap:10px;justify-content:flex-end;"><a href="sessions.php" class="btn btn-outline">Annuler</a><button type="submit" class="btn btn-primary">Enregistrer</button></div>
</form>

<?php else: ?>
<div class="page-header"><div><h2>Sessions de camp</h2><p><?= count($sessions) ?> session(s)</p></div><a href="sessions.php?action=new" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nouvelle session</a></div>
<?php if ($_GET['msg']??''): ?><div class="alert alert-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= ['added'=>'Session créée !','updated'=>'Session mise à jour !','deleted'=>'Session supprimée.'][$_GET['msg']]??'' ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;">
<?php foreach ($sessions as $s):
  $pct = $s['capacite']>0 ? ($s['nb']/$s['capacite'])*100 : 0;
  $bar = $pct>=90?'danger':($pct>=70?'warn':'');
?>
<div class="card">
  <div style="height:4px;background:<?= $s['site']==='lome'?'var(--bleu)':($s['site']==='cotonou'?'var(--rouge)':'var(--vert)') ?>;"></div>
  <div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
      <div><div style="font-family:'Montserrat',sans-serif;font-weight:700;font-size:.95rem;color:var(--bleu);margin-bottom:4px;"><?= sanitize($s['nom']) ?></div><div class="text-sm text-muted"><?= formatDate($s['date_debut']) ?> → <?= formatDate($s['date_fin']) ?></div></div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;"><?= statutBadge($s['statut']) ?><?= siteBadge($s['site']) ?></div>
    </div>
    <?php if ($s['description']): ?><p class="text-sm text-muted" style="margin-bottom:10px;"><?= sanitize(substr($s['description'],0,80)) ?>…</p><?php endif; ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem;margin-bottom:5px;"><span class="text-muted">Places</span><strong><?= $s['nb'] ?>/<?= $s['capacite'] ?></strong></div>
    <div class="progress" style="margin-bottom:12px;"><div class="progress-fill <?= $bar ?>" style="width:<?= min($pct,100) ?>%"></div></div>
    <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-weight:700;color:var(--bleu);font-size:.9rem;"><?= formatMontant($s['prix']) ?></span>
    <div class="btn-group"><a href="sessions.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Modifier</a><a href="sessions.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-red btn-sm" onclick="return confirm('Supprimer ?')">✕</a></div></div>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$sessions): ?><div class="empty" style="padding:60px;grid-column:1/-1;"><h3>Aucune session.</h3></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
