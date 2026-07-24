<?php
define('ROOT_PATH','../');
require_once '../includes/config.php';
requireLogin();
$page_title = 'Finances & Paiements';
$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscription_id = (int)(isset($_POST['inscription_id']) ? $_POST['inscription_id'] : 0);
    $montant        = (float)str_replace(' ', '', isset($_POST['montant']) ? $_POST['montant'] : 0);
    $mode           = isset($_POST['mode']) ? $_POST['mode'] : 'especes';
    $reference      = trim(isset($_POST['reference']) ? $_POST['reference'] : '');
    $statut         = isset($_POST['statut']) ? $_POST['statut'] : 'recu';
    $notes          = trim(isset($_POST['notes']) ? $_POST['notes'] : '');

    if ($action === 'new' && $inscription_id > 0 && $montant > 0) {
        $db->prepare("INSERT INTO paiements (inscription_id, montant, mode, reference, statut, notes) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$inscription_id, $montant, $mode, $reference, $statut, $notes]);
        header('Location: paiements.php?msg=added');
        exit;
    }
}

if ($action === 'delete' && $id > 0) {
    $db->prepare("DELETE FROM paiements WHERE id=?")->execute([$id]);
    header('Location: paiements.php?msg=deleted');
    exit;
}

$prefill = isset($_GET['inscription_id']) ? (int)$_GET['inscription_id'] : 0;

// ===== STATS — seulement inscriptions actives =====
$stats = $db->query("
    SELECT
        COALESCE(SUM(i.montant_total), 0) AS attendu,
        COALESCE((SELECT SUM(p2.montant) FROM paiements p2 
                  JOIN inscriptions i2 ON i2.id = p2.inscription_id 
                  WHERE i2.statut IN ('confirme','en_attente') AND p2.statut = 'recu'), 0) AS recu,
        COALESCE((SELECT SUM(p3.montant) FROM paiements p3 
                  JOIN inscriptions i3 ON i3.id = p3.inscription_id 
                  WHERE i3.statut IN ('confirme','en_attente') AND p3.statut = 'en_attente'), 0) AS attente
    FROM inscriptions i
    WHERE i.statut IN ('confirme', 'en_attente')
")->fetch();

$restant = $stats['attendu'] - $stats['recu'];
$pct = $stats['attendu'] > 0 ? ($stats['recu'] / $stats['attendu']) * 100 : 0;

// ===== SOLDE PAR CAMPEUR — recalculé proprement =====
$soldes = $db->query("
    SELECT
        c.id AS cid,
        CONCAT(c.prenom, ' ', c.nom) AS cnom,
        s.nom AS snom,
        s.site,
        i.id AS iid,
        i.statut AS istatut,
        i.montant_total AS total,
        COALESCE(
            (SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id = i.id AND p.statut = 'recu'),
        0) AS paye,
        i.montant_total - COALESCE(
            (SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id = i.id AND p.statut = 'recu'),
        0) AS reste
    FROM inscriptions i
    JOIN campeurs c ON c.id = i.campeur_id
    JOIN sessions s ON s.id = i.session_id
    WHERE i.statut IN ('confirme', 'en_attente')
    ORDER BY reste DESC, c.nom ASC
")->fetchAll();

// ===== HISTORIQUE =====
$historique = $db->query("
    SELECT p.*, CONCAT(c.prenom, ' ', c.nom) AS cnom, s.nom AS snom
    FROM paiements p
    JOIN inscriptions i ON i.id = p.inscription_id
    JOIN campeurs c ON c.id = i.campeur_id
    JOIN sessions s ON s.id = i.session_id
    ORDER BY p.date_paiement DESC
    LIMIT 200
")->fetchAll();

// ===== LISTE INSCRIPTIONS POUR FORMULAIRE =====
$inscriptions_list = $db->query("
    SELECT i.id,
        CONCAT(c.prenom, ' ', c.nom, ' — ', s.nom) AS label,
        i.montant_total - COALESCE(
            (SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id = i.id AND p.statut = 'recu'),
        0) AS reste
    FROM inscriptions i
    JOIN campeurs c ON c.id = i.campeur_id
    JOIN sessions s ON s.id = i.session_id
    WHERE i.statut IN ('confirme', 'en_attente')
    ORDER BY c.nom ASC
")->fetchAll();

$reste_map = [];
foreach ($inscriptions_list as $r) {
    $reste_map[$r['id']] = max(0, $r['reste']);
}

require_once '../includes/header.php';
?>

<?php if ($action === 'new'): ?>
<div class="page-header">
    <div><h2>Enregistrer un paiement</h2></div>
    <a href="paiements.php" class="btn btn-outline">← Retour</a>
</div>
<form method="POST" action="paiements.php?action=new">
<div class="card mb-20">
    <div class="card-header"><span class="card-title">Nouveau paiement reçu</span></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group full">
                <label>Campeur / Inscription *</label>
                <select name="inscription_id" id="sel_insc" required>
                    <option value="">-- Sélectionner le campeur --</option>
                    <?php foreach ($inscriptions_list as $ins): ?>
                    <option value="<?= $ins['id'] ?>" <?= $prefill == $ins['id'] ? 'selected' : '' ?>>
                        <?= sanitize($ins['label']) ?> (Reste : <?= formatMontant(max(0,$ins['reste'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Montant reçu (FCFA) *</label>
                <input type="number" name="montant" id="inp_montant" step="1" required placeholder="0">
                <span class="hint" id="hint_reste"></span>
            </div>
            <div class="form-group">
                <label>Mode de paiement</label>
                <select name="mode">
                    <option value="especes">💵 Espèces</option>
                    <option value="mobile_money">📱 Mobile Money</option>
                    <option value="virement">🏦 Virement bancaire</option>
                    <option value="cheque">📄 Chèque</option>
                    <option value="carte">💳 Carte bancaire</option>
                </select>
            </div>
            <div class="form-group">
                <label>Référence / N° reçu</label>
                <input type="text" name="reference" placeholder="Optionnel">
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select name="statut">
                    <option value="recu">✅ Reçu / Encaissé</option>
                    <option value="en_attente">⏳ En attente</option>
                    <option value="echec">❌ Échec</option>
                </select>
            </div>
            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes" placeholder="Remarques éventuelles..."></textarea>
            </div>
        </div>
    </div>
</div>
<div style="display:flex;gap:10px;justify-content:flex-end;">
    <a href="paiements.php" class="btn btn-outline">Annuler</a>
    <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer le paiement
    </button>
</div>
</form>
<script>
var resteMap = <?= json_encode($reste_map) ?>;
var sel = document.getElementById('sel_insc');
var inp = document.getElementById('inp_montant');
var hint = document.getElementById('hint_reste');
sel.addEventListener('change', function() {
    var r = resteMap[this.value];
    if (r !== undefined) {
        inp.value = r;
        hint.textContent = 'Reste à payer : ' + r.toLocaleString('fr-FR') + ' FCFA';
        hint.style.color = r > 0 ? 'var(--rouge)' : 'var(--vert)';
    } else {
        inp.value = '';
        hint.textContent = '';
    }
});
if (sel.value) sel.dispatchEvent(new Event('change'));
</script>

<?php else: ?>

<div class="page-header">
    <div><h2>Finances & Paiements</h2><p>Suivi complet des encaissements</p></div>
    <a href="paiements.php?action=new" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Enregistrer un paiement
    </a>
</div>

<?php $msg = isset($_GET['msg']) ? $_GET['msg'] : ''; ?>
<?php if ($msg): ?>
<div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= $msg === 'added' ? 'Paiement enregistré !' : 'Paiement supprimé.' ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="stat-card green">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="stat-value"><?= number_format($stats['recu'], 0, ',', ' ') ?></div>
        <div class="stat-label">FCFA encaissés</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg></div>
        <div class="stat-value"><?= number_format(max(0,$restant), 0, ',', ' ') ?></div>
        <div class="stat-label">FCFA restants</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-value"><?= number_format($stats['attente'], 0, ',', ' ') ?></div>
        <div class="stat-label">FCFA en attente</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
        <div class="stat-value"><?= number_format($stats['attendu'], 0, ',', ' ') ?></div>
        <div class="stat-label">FCFA total attendu</div>
    </div>
</div>

<!-- Barre progression -->
<div class="card mb-20">
    <div class="card-body-sm">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
            <span style="font-weight:600;color:var(--bleu);">Progression globale des encaissements</span>
            <span style="font-weight:700;color:var(--bleu);"><?= number_format($pct, 1) ?>%</span>
        </div>
        <div class="progress" style="height:10px;">
            <div class="progress-fill <?= $pct < 50 ? 'danger' : ($pct < 80 ? 'warn' : '') ?>" style="width:<?= min($pct, 100) ?>%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:5px;font-size:.77rem;color:var(--txt-muted);">
            <span><?= formatMontant($stats['recu']) ?> encaissé</span>
            <span><?= formatMontant($stats['attendu']) ?> attendu</span>
        </div>
    </div>
</div>

<!-- Tabs -->
<?php $tab = isset($_GET['tab']) ? $_GET['tab'] : 'soldes'; ?>
<div class="tabs">
    <a href="paiements.php?tab=soldes" class="tab <?= $tab === 'soldes' ? 'active' : '' ?>">💰 Solde par campeur</a>
    <a href="paiements.php?tab=historique" class="tab <?= $tab === 'historique' ? 'active' : '' ?>">📋 Historique des paiements</a>
</div>

<?php if ($tab === 'soldes'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Solde par campeur</span>
        <span class="text-sm text-muted"><?= count($soldes) ?> inscription(s) active(s)</span>
    </div>
    <div class="table-wrap">
        <?php if ($soldes): ?>
        <table>
            <thead>
                <tr>
                    <th>Campeur</th>
                    <th>Session</th>
                    <th>Site</th>
                    <th>Total dû</th>
                    <th>✅ Payé</th>
                    <th>⏳ Reste</th>
                    <th>Progression</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($soldes as $s):
                $pct2  = $s['total'] > 0 ? ($s['paye'] / $s['total']) * 100 : 0;
                $reste = max(0, $s['reste']);
            ?>
            <tr>
                <td>
                    <a href="campeurs.php?action=view&id=<?= $s['cid'] ?>" style="font-weight:600;color:var(--bleu);">
                        <?= sanitize($s['cnom']) ?>
                    </a>
                </td>
                <td class="text-sm"><?= sanitize(substr($s['snom'], 0, 28)) ?></td>
                <td><?= siteBadge($s['site']) ?></td>
                <td class="fw-600"><?= formatMontant($s['total']) ?></td>
                <td style="color:var(--vert);font-weight:700;"><?= formatMontant($s['paye']) ?></td>
                <td>
                    <?php if ($reste <= 0): ?>
                        <span class="badge badge-success">✓ Soldé</span>
                    <?php else: ?>
                        <span style="color:var(--rouge);font-weight:700;"><?= formatMontant($reste) ?></span>
                    <?php endif; ?>
                </td>
                <td style="min-width:120px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div class="progress" style="flex:1;margin:0;">
                            <div class="progress-fill <?= $pct2 < 50 ? 'danger' : ($pct2 < 100 ? 'warn' : '') ?>" style="width:<?= min($pct2, 100) ?>%"></div>
                        </div>
                        <span class="text-sm text-muted"><?= number_format($pct2, 0) ?>%</span>
                    </div>
                </td>
                <td>
                    <?php if ($reste > 0): ?>
                    <a href="paiements.php?action=new&inscription_id=<?= $s['iid'] ?>" class="btn btn-gold btn-sm nowrap">
                        + Paiement
                    </a>
                    <?php else: ?>
                    <span class="text-sm text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty" style="padding:50px;">
            <h3>Aucune inscription active.</h3>
            <p>Les inscriptions confirmées ou en attente apparaissent ici.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Historique des paiements</span>
        <span class="text-sm text-muted"><?= count($historique) ?> transaction(s)</span>
    </div>
    <div class="table-wrap">
        <?php if ($historique): ?>
        <table>
            <thead><tr><th>Campeur</th><th>Session</th><th>Montant</th><th>Mode</th><th>Référence</th><th>Statut</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php
            $modes = ['especes'=>'💵 Espèces','mobile_money'=>'📱 Mobile Money','virement'=>'🏦 Virement','cheque'=>'📄 Chèque','carte'=>'💳 Carte'];
            foreach ($historique as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= sanitize($p['cnom']) ?></td>
                <td class="text-sm text-muted"><?= sanitize(substr($p['snom'], 0, 26)) ?></td>
                <td style="font-weight:700;color:var(--bleu);"><?= formatMontant($p['montant']) ?></td>
                <td class="text-sm"><?= isset($modes[$p['mode']]) ? $modes[$p['mode']] : $p['mode'] ?></td>
                <td class="text-sm text-muted"><?= sanitize($p['reference'] ? $p['reference'] : '—') ?></td>
                <td><?= statutBadge($p['statut']) ?></td>
                <td class="text-sm text-muted"><?= formatDate($p['date_paiement']) ?></td>
                <td>
                    <a href="paiements.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-red btn-sm btn-icon"
                       onclick="return confirm('Supprimer ce paiement ?')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty" style="padding:50px;"><h3>Aucun paiement enregistré.</h3></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
