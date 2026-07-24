<?php
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'camp_vacances');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME',  'Soviecap International');
define('CURRENCY',   'FCFA');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;border-left:4px solid #dc2626;margin:20px;border-radius:8px;">
                <h3 style="color:#dc2626">❌ Erreur base de données</h3>
                <p>'.$e->getMessage().'</p>
                <p>Vérifiez <code>includes/config.php</code> et que la base <strong>camp_vacances</strong> existe dans phpMyAdmin.</p>
            </div>');
        }
    }
    return $pdo;
}

function formatDate($d)    { return $d ? date('d/m/Y', strtotime($d)) : '—'; }
function formatMontant($m) { return number_format((float)$m, 0, ',', ' ').' FCFA'; }
function age($dn)          { return (int)date_diff(date_create($dn), date_create('today'))->y; }
function sanitize($s)      { return htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'); }
function redirect($url)    { header("Location: $url"); exit; }

function statutBadge($s) {
    $map = [
        'ouvert'     => ['Ouvert',     'badge-success'],
        'complet'    => ['Complet',    'badge-warning'],
        'termine'    => ['Terminé',    'badge-secondary'],
        'annule'     => ['Annulé',     'badge-danger'],
        'confirme'   => ['Confirmé',   'badge-success'],
        'en_attente' => ['En attente', 'badge-warning'],
        'recu'       => ['Reçu',       'badge-success'],
        'echec'      => ['Échec',      'badge-danger'],
        'actif'      => ['Actif',      'badge-success'],
        'inactif'    => ['Inactif',    'badge-secondary'],
        'planifie'   => ['Planifié',   'badge-info'],
    ];
    $b = $map[$s] ?? [ucfirst($s), 'badge-secondary'];
    return '<span class="badge '.$b[1].'">'.$b[0].'</span>';
}

function siteBadge($s) {
    $map = ['lome'=>['Lomé','#0a2d6e'],'cotonou'=>['Cotonou','#c0141e'],'accra'=>['Accra','#1a7a3a'],'campus'=>['Campus','#7a5000'],'tous'=>['Tous sites','#555']];
    $b = $map[$s] ?? [ucfirst($s),'#555'];
    return '<span style="background:'.htmlspecialchars($b[1]).'22;color:'.htmlspecialchars($b[1]).';padding:2px 9px;border-radius:20px;font-size:.74rem;font-weight:700;">'.htmlspecialchars($b[0]).'</span>';
}

session_start();
function isLoggedIn()  { return isset($_SESSION['user_id']); }
function requireLogin(){
    // Login temporairement désactivé
    if (!isLoggedIn()) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user']    = ['id'=>1,'nom'=>'Admin','prenom'=>'Soviecap','email'=>'admin@soviecap.com','role'=>'super_admin'];
    }
}
function currentUser() { return $_SESSION['user'] ?? null; }
