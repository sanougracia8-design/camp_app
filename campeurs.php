<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Campeurs';
$db = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ===== ENREGISTREMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'nom'                    => trim($_POST['nom'] ?? ''),
        'prenom'                 => trim($_POST['prenom'] ?? ''),
        'date_naissance'         => $_POST['date_naissance'] ?? '',
        'sexe'                   => $_POST['sexe'] ?? 'M',
        'email'                  => trim($_POST['email'] ?? ''),
        'telephone'              => trim($_POST['telephone'] ?? ''),
        'adresse'                => trim($_POST['adresse'] ?? ''),
        'ville'                  => trim($_POST['ville'] ?? ''),
        'pays'                   => trim($_POST['pays'] ?? 'Togo'),
        'tuteur_nom'             => trim($_POST['tuteur_nom'] ?? ''),
        'tuteur_telephone'       => trim($_POST['tuteur_telephone'] ?? ''),
        'tuteur_email'           => trim($_POST['tuteur_email'] ?? ''),
        'lien_parente'           => trim($_POST['lien_parente'] ?? ''),
        'groupe_sanguin'         => trim($_POST['groupe_sanguin'] ?? ''),
        'allergies'              => trim($_POST['allergies'] ?? ''),
        'medicaments'            => trim($_POST['medicaments'] ?? ''),
        'restrictions_alimentaires' => trim($_POST['restrictions_alimentaires'] ?? ''),
        'medecin_nom'            => trim($_POST['medecin_nom'] ?? ''),
        'medecin_telephone'      => trim($_POST['medecin_telephone'] ?? ''),
        'notes'                  => trim($_POST['notes'] ?? ''),
    ];

    if ($action === 'new') {
        $sql = "INSERT INTO campeurs (nom,prenom,date_naissance,sexe,email,telephone,adresse,ville,pays,
                tuteur_nom,tuteur_telephone,tuteur_email,lien_parente,
                groupe_sanguin,allergies,medicaments,restrictions_alimentaires,
                medecin_nom,medecin_telephone,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $db->prepare($sql)->execute(array_values($d));
        $new_id = $db->lastInsertId();
        redirect('campeurs.php?msg=added&id='.$new_id);
    }

    if ($action === 'edit' && $id) {
        $sql = "UPDATE campeurs SET nom=?,prenom=?,date_naissance=?,sexe=?,email=?,telephone=?,adresse=?,ville=?,pays=?,
                tuteur_nom=?,tuteur_telephone=?,tuteur_email=?,lien_parente=?,
                groupe_sanguin=?,allergies=?,medicaments=?,restrictions_alimentaires=?,
                medecin_nom=?,medecin_telephone=?,notes=? WHERE id=?";
        $db->prepare($sql)->execute(array_merge(array_values($d), [$id]));
        redirect('campeurs.php?msg=updated&id='.$id);
    }
}

if ($action === 'delete' && $id) {
    $db->prepare("DELETE FROM campeurs WHERE id=?")->execute([$id]);
    redirect('campeurs.php?msg=deleted');
}

// Chargement fiche
$campeur = null;
if (in_array($action, ['view','edit']) && $id) {
    $st = $db->prepare("SELECT * FROM campeurs WHERE id=?");
    $st->execute([$id]);
    $campeur = $st->fetch();
    if (!$campeur) redirect('campeurs.php');
}

// Inscriptions du campeur
$inscriptions = [];
if ($action === 'view' && $id) {
    $st = $db->prepare("SELECT i.*, s.nom AS session_nom, s.site, s.date_debut, s.date_fin,
        COALESCE(SUM(CASE WHEN p.statut='recu' THEN p.montant ELSE 0 END),0) AS paye
        FROM inscriptions i JOIN sessions s ON s.id=i.session_id
        LEFT JOIN paiements p ON p.inscription_id=i.id
        WHERE i.campeur_id=? GROUP BY i.id ORDER BY i.date_inscription DESC");
    $st->execute([$id]);
    $inscriptions = $st->fetchAll();
}

// Liste avec recherche
$q = trim($_GET['q'] ?? '');
$params = [];
$where  = '';
if ($q) { $where = "WHERE CONCAT(c.nom,' ',c.prenom,' ',COALESCE(c.email,'')) LIKE ?"; $params = ["%$q%"]; }
$campeurs = $db->prepare("SELECT c.*, COUNT(DISTINCT i.id) AS nb FROM campeurs c LEFT JOIN inscriptions i ON i.campeur_id=c.id $where GROUP BY c.id ORDER BY c.created_at DESC");
$campeurs->execute($params);
$campeurs = $campeurs->fetchAll();

require_once '../includes/header.php';
?>

<?php if (in_array($action, ['new','edit'])): ?>
<!-- ===== FORMULAIRE ===== -->
<div class="page-header">
  <div><h2><?= $action==='new' ? 'Ajouter un campeur' : 'Modifier : '.sanitize($campeur['prenom'].' '.$campeur['nom']) ?></h2></div>
  <a href="campeurs.php" class="btn btn-outline">← Retour</a>
</div>

<form method="POST" action="campeurs.php?action=<?= $action ?><?= $id?'&id='.$id:'' ?>">
  <!-- Infos personnelles -->
  <div class="card mb-20">
    <div class="card-header"><span class="card-title">👤 Informations personnelles</span></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Prénom *</label>
          <input type="text" name="prenom" required value="<?= sanitize($campeur['prenom']??'') ?>" placeholder="Prénom">
        </div>
        <div class="form-group">
          <label>Nom *</label>
          <input type="text" name="nom" required value="<?= sanitize($campeur['nom']??'') ?>" placeholder="Nom de famille">
        </div>
        <div class="form-group">
          <label>Date de naissance *</label>
          <input type="date" name="date_naissance" required value="<?= $campeur['date_naissance']??'' ?>">
        </div>
        <div class="form-group">
          <label>Sexe</label>
          <select name="sexe">
            <option value="M" <?= ($campeur['sexe']??'M')==='M'?'selected':'' ?>>Masculin</option>
            <option value="F" <?= ($campeur['sexe']??'')==='F'?'selected':'' ?>>Féminin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="tel" name="telephone" value="<?= sanitize($campeur['telephone']??'') ?>" placeholder="+228 XX XX XX">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= sanitize($campeur['email']??'') ?>">
        </div>
        <div class="form-group">
          <label>Ville</label>
          <input type="text" name="ville" value="<?= sanitize($campeur['ville']??'') ?>" placeholder="Lomé, Cotonou...">
        </div>
        <div class="form-group">
          <label>Pays</label>
          <select name="pays">
            <?php foreach (['Burkina Faso','Togo','Bénin','Ghana','Mali','Côte d\'Ivoire','Sénégal','Niger','Nigeria','Cameroun','France','Autre'] as $p): ?>
            <option value="<?= $p ?>" <?= ($campeur['pays']??'Burkina Faso')===$p?'selected':'' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group full">
          <label>Adresse</label>
          <input type="text" name="adresse" value="<?= sanitize($campeur['adresse']??'') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Tuteur -->
  <div class="card mb-20">
    <div class="card-header"><span class="card-title">👨‍👩‍👧 Parent / Tuteur</span></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Nom complet du tuteur *</label>
          <input type="text" name="tuteur_nom" required value="<?= sanitize($campeur['tuteur_nom']??'') ?>" placeholder="Nom et prénom du parent">
        </div>
        <div class="form-group">
          <label>Téléphone tuteur *</label>
          <input type="tel" name="tuteur_telephone" required value="<?= sanitize($campeur['tuteur_telephone']??'') ?>" placeholder="+228 XX XX XX">
        </div>
        <div class="form-group">
          <label>Email tuteur</label>
          <input type="email" name="tuteur_email" value="<?= sanitize($campeur['tuteur_email']??'') ?>">
        </div>
        <div class="form-group">
          <label>Lien de parenté</label>
          <select name="lien_parente">
            <?php foreach (['Père','Mère','Tuteur légal','Grand-parent','Oncle/Tante','Autre'] as $l): ?>
            <option value="<?= $l ?>" <?= ($campeur['lien_parente']??'')===$l?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Médical -->
  <div class="card mb-20">
    <div class="card-header"><span class="card-title">🏥 Informations médicales</span></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Groupe sanguin</label>
          <select name="groupe_sanguin">
            <option value="">-- Non renseigné --</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
            <option value="<?= $g ?>" <?= ($campeur['groupe_sanguin']??'')===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Médecin traitant</label>
          <input type="text" name="medecin_nom" value="<?= sanitize($campeur['medecin_nom']??'') ?>">
        </div>
        <div class="form-group">
          <label>Téléphone médecin</label>
          <input type="tel" name="medecin_telephone" value="<?= sanitize($campeur['medecin_telephone']??'') ?>">
        </div>
        <div class="form-group">
          <label>Restrictions alimentaires</label>
          <input type="text" name="restrictions_alimentaires" value="<?= sanitize($campeur['restrictions_alimentaires']??'') ?>" placeholder="Halal, végétarien...">
        </div>
        <div class="form-group full">
          <label>Allergies</label>
          <textarea name="allergies" placeholder="Allergies connues..."><?= sanitize($campeur['allergies']??'') ?></textarea>
        </div>
        <div class="form-group full">
          <label>Médicaments en cours</label>
          <textarea name="medicaments" placeholder="Médicaments, posologie..."><?= sanitize($campeur['medicaments']??'') ?></textarea>
        </div>
        <div class="form-group full">
          <label>Notes / Observations</label>
          <textarea name="notes"><?= sanitize($campeur['notes']??'') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;justify-content:flex-end;">
    <a href="campeurs.php" class="btn btn-outline">Annuler</a>
    <button type="submit" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
      <?= $action==='new' ? 'Enregistrer le campeur' : 'Mettre à jour' ?>
    </button>
  </div>
</form>

<?php elseif ($action==='view' && $campeur): ?>
<!-- ===== FICHE CAMPEUR ===== -->
<div class="page-header">
  <div><h2>Fiche campeur</h2></div>
  <div class="btn-group">
    <a href="campeurs.php?action=edit&id=<?= $campeur['id'] ?>" class="btn btn-outline">✏ Modifier</a>
    <a href="inscriptions.php?action=new&campeur_id=<?= $campeur['id'] ?>" class="btn btn-primary">+ Inscrire à une session</a>
    <a href="campeurs.php" class="btn btn-outline">← Retour</a>
  </div>
</div>

<div class="detail-banner">
  <div style="display:flex;align-items:center;gap:16px;">
    <div style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-size:1.3rem;color:#fff;font-weight:800;flex-shrink:0;">
      <?= strtoupper(substr($campeur['prenom'],0,1).substr($campeur['nom'],0,1)) ?>
    </div>
    <div>
      <h2><?= sanitize($campeur['prenom'].' '.$campeur['nom']) ?></h2>
      <p><?= age($campeur['date_naissance']) ?> ans — <?= $campeur['sexe']==='M'?'Masculin':'Féminin' ?> — <?= sanitize($campeur['pays']) ?></p>
    </div>
  </div>
  <div class="banner-meta">
    <span><strong>Né(e) le :</strong> <?= formatDate($campeur['date_naissance']) ?></span>
    <?php if ($campeur['telephone']): ?><span><strong>Tél :</strong> <?= sanitize($campeur['telephone']) ?></span><?php endif; ?>
    <?php if ($campeur['ville']): ?><span><strong>Ville :</strong> <?= sanitize($campeur['ville']) ?></span><?php endif; ?>
  </div>
</div>

<div class="grid-2 mb-20">
  <div class="card">
    <div class="card-header"><span class="card-title">👨‍👩‍👧 Parent / Tuteur</span></div>
    <div class="card-body">
      <?php if ($campeur['tuteur_nom']): ?>
      <div style="display:grid;gap:8px;font-size:.87rem;">
        <div style="display:flex;justify-content:space-between;"><span class="text-muted">Nom</span><strong><?= sanitize($campeur['tuteur_nom']) ?></strong></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-muted">Lien</span><span><?= sanitize($campeur['lien_parente']??'—') ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-muted">Téléphone</span><strong><?= sanitize($campeur['tuteur_telephone']) ?></strong></div>
        <?php if ($campeur['tuteur_email']): ?><div style="display:flex;justify-content:space-between;"><span class="text-muted">Email</span><span><?= sanitize($campeur['tuteur_email']) ?></span></div><?php endif; ?>
      </div>
      <?php else: ?><p class="text-muted">Non renseigné.</p><?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">🏥 Médical</span></div>
    <div class="card-body">
      <div style="display:grid;gap:8px;font-size:.87rem;">
        <?php if ($campeur['groupe_sanguin']): ?><div><span class="text-muted">Groupe sanguin : </span><strong><?= sanitize($campeur['groupe_sanguin']) ?></strong></div><?php endif; ?>
        <?php if ($campeur['allergies']): ?><div><span class="text-muted">Allergies : </span><?= sanitize($campeur['allergies']) ?></div><?php endif; ?>
        <?php if ($campeur['medicaments']): ?><div><span class="text-muted">Médicaments : </span><?= sanitize($campeur['medicaments']) ?></div><?php endif; ?>
        <?php if ($campeur['restrictions_alimentaires']): ?><div><span class="text-muted">Régime : </span><?= sanitize($campeur['restrictions_alimentaires']) ?></div><?php endif; ?>
        <?php if (!$campeur['groupe_sanguin'] && !$campeur['allergies']): ?><p class="text-muted">Aucune info médicale.</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Inscriptions (<?= count($inscriptions) ?>)</span>
    <a href="inscriptions.php?action=new&campeur_id=<?= $campeur['id'] ?>" class="btn btn-primary btn-sm">+ Nouvelle inscription</a>
  </div>
  <div class="table-wrap">
    <?php if ($inscriptions): ?>
    <table>
      <thead><tr><th>Session</th><th>Site</th><th>Dates</th><th>Total dû</th><th>Payé</th><th>Reste</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($inscriptions as $ins):
        $reste = max(0, $ins['montant_total'] - $ins['paye']);
      ?>
      <tr>
        <td style="font-weight:600;"><?= sanitize($ins['session_nom']) ?></td>
        <td><?= siteBadge($ins['site']) ?></td>
        <td class="text-sm text-muted"><?= formatDate($ins['date_debut']) ?> → <?= formatDate($ins['date_fin']) ?></td>
        <td><?= formatMontant($ins['montant_total']) ?></td>
        <td style="color:var(--vert);font-weight:600;"><?= formatMontant($ins['paye']) ?></td>
        <td><?php if ($reste<=0): ?><span class="badge badge-success">✓ Soldé</span><?php else: ?><span style="color:var(--rouge);font-weight:600;"><?= formatMontant($reste) ?></span><?php endif; ?></td>
        <td><?= statutBadge($ins['statut']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty" style="padding:40px;"><p>Aucune inscription.</p></div><?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ===== LISTE ===== -->
<div class="page-header">
  <div><h2>Campeurs</h2><p><?= count($campeurs) ?> campeur(s) enregistré(s)</p></div>
  <a href="campeurs.php?action=new" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nouveau campeur
  </a>
</div>

<?php if ($_GET['msg']??''): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
  <?= ['added'=>'Campeur ajouté avec succès !','updated'=>'Campeur mis à jour !','deleted'=>'Campeur supprimé.'][$_GET['msg']]??'' ?>
</div>
<?php endif; ?>

<form method="GET" style="margin-bottom:16px;">
  <div style="position:relative;max-width:360px;">
    <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--txt-light);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" placeholder="Rechercher un campeur…" value="<?= sanitize($q) ?>" style="padding-left:36px;width:100%;" oninput="this.form.submit()">
  </div>
</form>

<div class="card">
  <div class="table-wrap">
    <?php if ($campeurs): ?>
    <table>
      <thead><tr><th>Campeur</th><th>Âge</th><th>Tuteur</th><th>Téléphone tuteur</th><th>Ville</th><th>Inscriptions</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($campeurs as $c): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:9px;">
            <div class="av av-blue"><?= strtoupper(substr($c['prenom'],0,1).substr($c['nom'],0,1)) ?></div>
            <div>
              <div style="font-weight:600;"><?= sanitize($c['prenom'].' '.$c['nom']) ?></div>
              <div class="text-sm text-muted"><?= sanitize($c['email']?:'—') ?></div>
            </div>
          </div>
        </td>
        <td class="text-sm"><?= age($c['date_naissance']) ?> ans</td>
        <td class="text-sm"><?= sanitize($c['tuteur_nom']?:'—') ?></td>
        <td class="text-sm"><?= sanitize($c['tuteur_telephone']?:'—') ?></td>
        <td class="text-sm text-muted"><?= sanitize($c['ville']?:'—') ?></td>
        <td><span class="badge badge-info"><?= $c['nb'] ?></span></td>
        <td>
          <div style="display:flex;gap:4px;">
            <a href="campeurs.php?action=view&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Voir la fiche">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <a href="campeurs.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Modifier">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <a href="campeurs.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-red btn-sm btn-icon" title="Supprimer" onclick="return confirm('Supprimer ce campeur ?')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty" style="padding:60px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <h3>Aucun campeur</h3>
      <p><?= $q ? 'Aucun résultat pour "'.$q.'".' : 'Commencez par ajouter un campeur.' ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
