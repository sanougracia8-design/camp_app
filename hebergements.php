<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Hébergements';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ['nom'=>trim($_POST['nom']??''),'type'=>$_POST['type']??'dortoir','site'=>$_POST['site']??'lome','capacite'=>(int)($_POST['capacite']??8),'description'=>trim($_POST['description']??''),'actif'=>isset($_POST['actif'])?1:0];
    if ($action==='new') { $db->prepare("INSERT INTO hebergements (nom,type,site,capacite,description,actif) VALUES (?,?,?,?,?,?)")->execute(array_values($d)); redirect('hebergements.php?msg=added'); }
    elseif ($action==='edit'&&$id) { $db->prepare("UPDATE hebergements SET nom=?,type=?,site=?,capacite=?,description=?,actif=? WHERE id=?")->execute(array_merge(array_values($d),[$id])); redirect('hebergements.php?msg=updated'); }
}
if ($action==='delete'&&$id) { $db->prepare("DELETE FROM hebergements WHERE id=?")->execute([$id]); redirect('hebergements.php?msg=deleted'); }

$heb = null;
if (in_array($action,['edit'])&&$id) { $st=$db->prepare("SELECT * FROM hebergements WHERE id=?"); $st->execute([$id]); $heb=$st->fetch(); if(!$heb) redirect('hebergements.php'); }

$hebergements = $db->query("SELECT * FROM hebergements ORDER BY site, nom")->fetchAll();
$types = ['dortoir'=>'Dortoir','chambre'=>'Chambre','cabine'=>'Cabine','chalet'=>'Chalet'];
$sites = ['lome'=>'Lomé','cotonou'=>'Cotonou','accra'=>'Accra'];
$icons = ['dortoir'=>'🏢','chambre'=>'🛏','cabine'=>'🏠','chalet'=>'🏡'];

require_once '../includes/header.php';
?>
<?php if (in_array($action,['new','edit'])): ?>
<div class="page-header"><div><h2><?= $action==='new'?'Ajouter un hébergement':'Modifier l\'hébergement' ?></h2></div><a href="hebergements.php" class="btn btn-outline">← Retour</a></div>
<form method="POST" action="hebergements.php?action=<?= $action ?><?= $id?'&id='.$id:'' ?>">
<div class="card mb-20"><div class="card-header"><span class="card-title">Détails</span></div><div class="card-body">
<div class="form-grid">
  <div class="form-group"><label>Nom *</label><input type="text" name="nom" required value="<?= sanitize($heb['nom']??'') ?>"></div>
  <div class="form-group"><label>Type</label><select name="type"><?php foreach($types as $v=>$l): ?><option value="<?= $v ?>" <?= ($heb['type']??'dortoir')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Site</label><select name="site"><?php foreach($sites as $v=>$l): ?><option value="<?= $v ?>" <?= ($heb['site']??'lome')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Capacité (places)</label><input type="number" name="capacite" min="1" value="<?= $heb['capacite']??8 ?>"></div>
  <div class="form-group full"><label>Description</label><textarea name="description"><?= sanitize($heb['description']??'') ?></textarea></div>
  <div class="form-group"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="actif" <?= ($heb['actif']??1)?'checked':'' ?>> Actif</label></div>
</div></div></div>
<div style="display:flex;gap:10px;justify-content:flex-end;"><a href="hebergements.php" class="btn btn-outline">Annuler</a><button type="submit" class="btn btn-primary">Enregistrer</button></div>
</form>

<?php else: ?>
<div class="page-header"><div><h2>Hébergements</h2><p><?= count($hebergements) ?> logement(s)</p></div><a href="hebergements.php?action=new" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Ajouter</a></div>
<?php if ($_GET['msg']??''): ?><div class="alert alert-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= ['added'=>'Hébergement ajouté !','updated'=>'Mis à jour !','deleted'=>'Supprimé.'][$_GET['msg']]??'' ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
<?php foreach ($hebergements as $h): ?>
<div class="card" style="opacity:<?= $h['actif']?1:.6 ?>;">
  <div style="height:4px;background:<?= $h['site']==='lome'?'var(--bleu)':($h['site']==='cotonou'?'var(--rouge)':'var(--vert)') ?>;"></div>
  <div class="card-body">
    <div style="font-size:2rem;margin-bottom:6px;"><?= $icons[$h['type']]??'🏠' ?></div>
    <div style="font-family:'Montserrat',sans-serif;font-weight:700;color:var(--bleu);margin-bottom:2px;"><?= sanitize($h['nom']) ?></div>
    <div style="display:flex;gap:6px;margin-bottom:10px;"><?= siteBadge($h['site']) ?><span class="badge badge-secondary"><?= $types[$h['type']]??$h['type'] ?></span></div>
    <?php if ($h['description']): ?><p class="text-sm text-muted" style="margin-bottom:10px;"><?= sanitize($h['description']) ?></p><?php endif; ?>
    <div style="font-size:.88rem;font-weight:600;color:var(--bleu);margin-bottom:12px;">🛏 <?= $h['capacite'] ?> places</div>
    <div style="display:flex;gap:6px;justify-content:flex-end;">
      <a href="hebergements.php?action=edit&id=<?= $h['id'] ?>" class="btn btn-outline btn-sm">Modifier</a>
      <a href="hebergements.php?action=delete&id=<?= $h['id'] ?>" class="btn btn-red btn-sm" onclick="return confirm('Supprimer ?')">✕</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$hebergements): ?><div class="empty" style="grid-column:1/-1;padding:60px;"><h3>Aucun hébergement.</h3></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
