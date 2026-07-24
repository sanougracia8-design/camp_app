<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Activités & Sorties';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ['nom'=>trim($_POST['nom']??''),'description'=>trim($_POST['description']??''),'categorie'=>$_POST['categorie']??'sport','site'=>$_POST['site']??'campus','duree_minutes'=>(int)($_POST['duree_minutes']??90),'nb_max_participants'=>(int)($_POST['nb_max_participants']??20),'lieu'=>trim($_POST['lieu']??'')];
    if ($action==='new') { $db->prepare("INSERT INTO activites (nom,description,categorie,site,duree_minutes,nb_max_participants,lieu) VALUES (?,?,?,?,?,?,?)")->execute(array_values($d)); redirect('activites.php?msg=added'); }
    elseif ($action==='edit'&&$id) { $db->prepare("UPDATE activites SET nom=?,description=?,categorie=?,site=?,duree_minutes=?,nb_max_participants=?,lieu=? WHERE id=?")->execute(array_merge(array_values($d),[$id])); redirect('activites.php?msg=updated'); }
}
if ($action==='delete'&&$id) { $db->prepare("DELETE FROM activites WHERE id=?")->execute([$id]); redirect('activites.php?msg=deleted'); }

$act = null;
if (in_array($action,['edit'])&&$id) { $st=$db->prepare("SELECT * FROM activites WHERE id=?"); $st->execute([$id]); $act=$st->fetch(); if(!$act) redirect('activites.php'); }

$tab = $_GET['tab'] ?? 'campus';
$activites = $db->prepare("SELECT * FROM activites WHERE site=? ORDER BY categorie, nom");
$activites->execute([$tab]);
$activites = $activites->fetchAll();

$cats = ['sport'=>'🏃 Sport','arts'=>'🎨 Arts','nature'=>'🌿 Nature','jeux'=>'🎮 Jeux','education'=>'📚 Éducation','sortie'=>'🚌 Sortie','restauration'=>'🍔 Restauration'];
$sites_tabs = ['campus'=>'Campus (commun)','lome'=>'Lomé','cotonou'=>'Cotonou','accra'=>'Accra'];

require_once '../includes/header.php';
?>
<?php if (in_array($action,['new','edit'])): ?>
<div class="page-header"><div><h2><?= $action==='new'?'Nouvelle activité':'Modifier l\'activité' ?></h2></div><a href="activites.php" class="btn btn-outline">← Retour</a></div>
<form method="POST" action="activites.php?action=<?= $action ?><?= $id?'&id='.$id:'' ?>">
<div class="card mb-20"><div class="card-body">
<div class="form-grid">
  <div class="form-group full"><label>Nom *</label><input type="text" name="nom" required value="<?= sanitize($act['nom']??'') ?>"></div>
  <div class="form-group"><label>Catégorie</label><select name="categorie"><?php foreach($cats as $v=>$l): ?><option value="<?= $v ?>" <?= ($act['categorie']??'sport')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Site</label><select name="site"><?php foreach($sites_tabs as $v=>$l): ?><option value="<?= $v ?>" <?= ($act['site']??'campus')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Durée (minutes)</label><input type="number" name="duree_minutes" value="<?= $act['duree_minutes']??90 ?>"></div>
  <div class="form-group"><label>Participants max</label><input type="number" name="nb_max_participants" value="<?= $act['nb_max_participants']??20 ?>"></div>
  <div class="form-group"><label>Lieu</label><input type="text" name="lieu" value="<?= sanitize($act['lieu']??'') ?>" placeholder="Plage, Musée, Campus..."></div>
  <div class="form-group full"><label>Description</label><textarea name="description"><?= sanitize($act['description']??'') ?></textarea></div>
</div></div></div>
<div style="display:flex;gap:10px;justify-content:flex-end;"><a href="activites.php" class="btn btn-outline">Annuler</a><button type="submit" class="btn btn-primary">Enregistrer</button></div>
</form>

<?php else: ?>
<div class="page-header"><div><h2>Activités & Sorties</h2><p>Catalogue par site</p></div><a href="activites.php?action=new" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nouvelle activité</a></div>
<?php if ($_GET['msg']??''): ?><div class="alert alert-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= ['added'=>'Activité ajoutée !','updated'=>'Mis à jour !','deleted'=>'Supprimée.'][$_GET['msg']]??'' ?></div><?php endif; ?>

<div class="tabs">
  <?php foreach ($sites_tabs as $v=>$l): ?>
  <a href="activites.php?tab=<?= $v ?>" class="tab <?= $tab===$v?'active':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;">
<?php foreach ($activites as $a):
  $cat_colors=['sport'=>'badge-info','arts'=>'badge-warning','nature'=>'badge-success','jeux'=>'badge-primary','education'=>'badge-secondary','sortie'=>'badge-danger','restauration'=>'badge-warning'];
?>
<div class="card">
  <div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
      <div>
        <div style="font-weight:700;font-size:.9rem;color:var(--bleu);margin-bottom:4px;"><?= sanitize($a['nom']) ?></div>
        <span class="badge <?= $cat_colors[$a['categorie']]??'badge-secondary' ?>"><?= $cats[$a['categorie']]??$a['categorie'] ?></span>
      </div>
      <div style="display:flex;gap:4px;">
        <a href="activites.php?action=edit&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm btn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
        <a href="activites.php?action=delete&id=<?= $a['id'] ?>&tab=<?= $tab ?>" class="btn btn-red btn-sm btn-icon" onclick="return confirm('Supprimer ?')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></a>
      </div>
    </div>
    <?php if ($a['description']): ?><p class="text-sm text-muted" style="margin-bottom:8px;line-height:1.5;"><?= sanitize(substr($a['description'],0,90)) ?>…</p><?php endif; ?>
    <div style="display:flex;gap:10px;font-size:.79rem;color:var(--txt-muted);">
      <span>⏱ <?= $a['duree_minutes'] ?> min</span>
      <span>👥 Max <?= $a['nb_max_participants'] ?></span>
      <?php if ($a['lieu']): ?><span>📍 <?= sanitize($a['lieu']) ?></span><?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$activites): ?><div class="empty" style="grid-column:1/-1;padding:50px;"><h3>Aucune activité pour ce site.</h3><p>Cliquez "+ Nouvelle activité" pour en ajouter.</p></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
