<?php
/**
 * Partial: Bouton de suppression / action destructive sécurisée (POST + CSRF)
 *
 * Remplace les anciens `<a href="...delete/X">` qui étaient vulnérables au CSRF
 * (exécution via simple lien GET). Génère un `<form method="POST">` avec token,
 * et un bouton stylé identique à un lien Bootstrap.
 *
 * Le controller cible doit appeler `$this->requirePostCsrf();` en début de méthode.
 *
 * Usage minimal :
 *   $deleteForm = ['action' => BASE_URL . '/jardinage/espaces/delete/' . $id];
 *   include ROOT_PATH . '/app/views/partials/delete_form.php';
 *
 * Options complètes :
 *   $deleteForm = [
 *       'action'  => BASE_URL . '/.../delete/X',           // requis
 *       'confirm' => 'Supprimer cet espace ?',             // message JS (défaut: générique)
 *       'label'   => 'Supprimer',                          // texte bouton (défaut: vide → icône seule)
 *       'icon'    => 'fas fa-trash',                       // classe FontAwesome
 *       'class'   => 'btn btn-sm btn-outline-danger',      // classes du bouton
 *       'title'   => 'Supprimer',                          // tooltip
 *       'inline'  => true,                                 // form en display:inline (défaut: true)
 *   ];
 *
 * NB : ne PAS placer cet include à l'intérieur d'un autre <form> (HTML interdit le nesting).
 */

if (!isset($deleteForm) || empty($deleteForm['action'])) {
    return;
}

$_dfAction  = $deleteForm['action'];
$_dfConfirm = $deleteForm['confirm'] ?? 'Confirmer cette action ?';
$_dfLabel   = $deleteForm['label']   ?? '';
$_dfIcon    = $deleteForm['icon']    ?? 'fas fa-trash';
$_dfClass   = $deleteForm['class']   ?? 'btn btn-sm btn-outline-danger';
$_dfTitle   = $deleteForm['title']   ?? 'Supprimer';
$_dfInline  = $deleteForm['inline']  ?? true;
$_dfToken   = $_SESSION['csrf_token'] ?? '';

$_dfOnsubmit = $_dfConfirm !== ''
    ? 'return confirm(' . json_encode($_dfConfirm, JSON_UNESCAPED_UNICODE) . ')'
    : '';
$_dfStyle = $_dfInline ? 'display:inline;margin:0;' : '';
?>
<form method="POST" action="<?= htmlspecialchars($_dfAction) ?>" style="<?= $_dfStyle ?>" onsubmit="<?= htmlspecialchars($_dfOnsubmit, ENT_QUOTES) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_dfToken) ?>">
    <button type="submit" class="<?= htmlspecialchars($_dfClass) ?>" title="<?= htmlspecialchars($_dfTitle) ?>">
        <i class="<?= htmlspecialchars($_dfIcon) ?>"></i><?php if ($_dfLabel !== ''): ?> <?= htmlspecialchars($_dfLabel) ?><?php endif; ?>
    </button>
</form>
