<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Personnel';
$db = getDB();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// SAUVEGARDE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim(isset($_POST['nom']) ? $_POST['nom'] : '');
    $prenom        = trim(isset($_POST['prenom']) ? $_POST['prenom'] : '');
    $poste         = isset($_POST['poste']) ? $_POST['poste'] : 'animateur';
    $site          = isset($_POST['site']) ? $_POST['site'] : 'tous';
    $email         = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $telephone     = trim(isset($_POST['telephone']) ? $_POST['telephone'] : '');
    $date_embauche = !empty($_POST['date_embauche']) ? $_POST['date_embauche'] : null;
    $salaire       = !empty($_POST['salaire']) ? (float)$_POST['salaire'] : null;
    $actif         = isset($_POST['actif']) ? 1 : 0;

    if ($action === 'new') {
        $sql = "INSERT INTO personnel (nom, prenom, poste, site, email, telephone, date_embauche, salaire, actif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->prepare($sql)->execute([$nom, $prenom, $poste, $site, $email, $telephone, $date_embauche, $salaire, $actif]);
        header('Location: personnel.php?msg=added');
        exit;
    }

    if ($action === 'edit' && $id > 0) {
        $sql = "UPDATE personnel SET nom=?, prenom=?, poste=?, site=?, email=?, telephone=?, date_embauche=?, salaire=?, actif=? WHERE id=?";
        $db->prepare($sql)->execute([$nom, $prenom, $poste, $site, $email, $telephone, $date_embauche, $salaire, $actif, $id]);
        header('Location: personnel.php?msg=updated');
        exit;
    }
}

// SUPPRESSION
if ($action === 'delete' && $id > 0) {
    $db->prepare("DELETE FROM personnel WHERE id=?")->execute([$id]);
    header('Location: personnel.php?msg=deleted');
    exit;
}

// CHARGEMENT POUR EDIT
$membre = null;
if ($action === 'edit' && $id > 0) {
    $st = $db->prepare("SELECT * FROM personnel WHERE id=?");
    $st->execute([$id]);
    $membre = $st->fetch();
    if (!$membre) { header('Location: personnel.php'); exit; }
}

$personnel = $db->query("SELECT * FROM personnel ORDER BY actif DESC, nom ASC")->fetchAll();

$postes = [
    'directeur' => 'Directeur',
    'animateur' => 'Animateur',
    'moniteur'  => 'Moniteur',
    'infirmier' => 'Infirmier(e)',
    'cuisinier' => 'Cuisinier',
    'chauffeur' => 'Chauffeur',
    'autre'     => 'Autre'
];
$sites = [
    'lome'    => 'Lomé',
    'cotonou' => 'Cotonou',
    'accra'   => 'Accra',
    'tous'    => 'Tous les sites'
];

require_once '../includes/header.php';
?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="page-header">
    <div><h2><?= $action === 'new' ? 'Ajouter un membre' : 'Modifier le profil' ?></h2></div>
    <a href="personnel.php" class="btn btn-outline">← Retour</a>
</div>

<form method="POST" action="personnel.php?action=<?= $action ?><?= $id > 0 ? '&id='.$id : '' ?>">
    <div class="card mb-20">
        <div class="card-header"><span class="card-title">Informations du membre</span></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" required value="<?= sanitize(isset($membre['prenom']) ? $membre['prenom'] : '') ?>" placeholder="Prénom">
                </div>
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" required value="<?= sanitize(isset($membre['nom']) ? $membre['nom'] : '') ?>" placeholder="Nom">
                </div>
                <div class="form-group">
                    <label>Poste</label>
                    <select name="poste">
                        <?php foreach ($postes as $v => $l): ?>
                        <option value="<?= $v ?>" <?= (isset($membre['poste']) ? $membre['poste'] : 'animateur') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Site affecté</label>
                    <select name="site">
                        <?php foreach ($sites as $v => $l): ?>
                        <option value="<?= $v ?>" <?= (isset($membre['site']) ? $membre['site'] : 'tous') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= sanitize(isset($membre['email']) ? $membre['email'] : '') ?>" placeholder="email@soviecap.com">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" value="<?= sanitize(isset($membre['telephone']) ? $membre['telephone'] : '') ?>" placeholder="+226 XX XX XX XX">
                </div>
                <div class="form-group">
                    <label>Date d'embauche</label>
                    <input type="date" name="date_embauche" value="<?= isset($membre['date_embauche']) ? $membre['date_embauche'] : '' ?>">
                </div>
                <div class="form-group">
                    <label>Salaire mensuel (FCFA)</label>
                    <input type="number" name="salaire" value="<?= isset($membre['salaire']) ? $membre['salaire'] : '' ?>" placeholder="150000">
                </div>
                <div class="form-group" style="padding-top:20px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="actif" <?= (isset($membre['actif']) ? $membre['actif'] : 1) ? 'checked' : '' ?>>
                        Membre actif
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="personnel.php" class="btn btn-outline">Annuler</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
            <?= $action === 'new' ? 'Enregistrer le membre' : 'Mettre à jour' ?>
        </button>
    </div>
</form>

<?php else: ?>

<div class="page-header">
    <div>
        <h2>Personnel</h2>
        <p><?= count(array_filter($personnel, function($p){ return $p['actif']; })) ?> membre(s) actif(s) sur <?= count($personnel) ?></p>
    </div>
    <a href="personnel.php?action=new" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouveau membre
    </a>
</div>

<?php $msg = isset($_GET['msg']) ? $_GET['msg'] : ''; ?>
<?php if ($msg): ?>
<div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?php
    if ($msg === 'added')   echo 'Membre ajouté avec succès !';
    if ($msg === 'updated') echo 'Profil mis à jour !';
    if ($msg === 'deleted') echo 'Membre supprimé.';
    ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <?php if ($personnel): ?>
        <table>
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Poste</th>
                    <th>Site</th>
                    <th>Téléphone</th>
                    <th>Salaire</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($personnel as $p): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:9px;">
                        <div class="av av-blue"><?= strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)) ?></div>
                        <div>
                            <div style="font-weight:600;"><?= sanitize($p['prenom'].' '.$p['nom']) ?></div>
                            <div class="text-sm text-muted"><?= sanitize($p['email'] ? $p['email'] : '—') ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-info"><?= isset($postes[$p['poste']]) ? $postes[$p['poste']] : $p['poste'] ?></span></td>
                <td><?= siteBadge($p['site']) ?></td>
                <td class="text-sm"><?= sanitize($p['telephone'] ? $p['telephone'] : '—') ?></td>
                <td class="text-sm"><?= $p['salaire'] ? formatMontant($p['salaire']) : '—' ?></td>
                <td><?= statutBadge($p['actif'] ? 'actif' : 'inactif') ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="personnel.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="personnel.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-red btn-sm btn-icon" onclick="return confirm('Supprimer ce membre ?')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty" style="padding:60px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h3>Aucun membre du personnel.</h3>
            <p>Cliquez "Nouveau membre" pour commencer.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
