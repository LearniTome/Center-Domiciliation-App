<?php

declare(strict_types=1);

/**
 * Design system — chargement de partial et rendu de composants.
 *
 * Source de verite unique du rendu. Les composants de `assets/css/ds-core.css`
 * et `assets/css/ds-components.css` n'ont aucun equivalent dans `app.css` : une
 * nouvelle page qui n'utilise que ces partials n'herite d'aucune mise en forme
 * historique, donc rien ne peut diverger de la page de reference.
 *
 * @param string $name  Nom du partial (avec ou sans `.php`).
 * @param array  $vars  Variables exposees au partial.
 */
function ds(string $name, array $vars = []): void
{
    $fichier = __DIR__ . '/ds/' . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';
    if (!is_file($fichier)) {
        throw new RuntimeException('Design system : partial introuvable « ' . $name . ' ».');
    }
    extract($vars, EXTR_SKIP);
    require $fichier;
}

/** Icone Material Symbols du design system. */
function ds_icon(string $name, string $class = ''): string
{
    return '<span class="material-symbols-outlined' . ($class !== '' ? ' ' . e($class) : '') . '">'
        . e($name) . '</span>';
}

/**
 * Bouton du design system.
 *
 * Le gabarit historique impose deja `<span class="material-symbols-outlined">`
 * avant le libelle ; on le genere ici pour qu'une nouvelle page n'ait rien a
 * recopier. L'icone reste obligatoire : `$icon` n'a pas de valeur par defaut.
 *
 * @param 'primary'|'next'|'back'|'cancel'|'info'|'danger' $variant
 */
function ds_btn(
    string $label,
    string $variant = 'primary',
    ?string $icon = null,
    array $attrs = []
): string {
    $classes = ['btn'];
    if ($variant !== 'primary') {
        $classes[] = 'btn-' . $variant;
    }
    foreach (['class', 'type', 'href', 'name', 'value', 'data-confirm', 'disabled', 'download', 'target'] as $k) {
        if (isset($attrs[$k]) && $attrs[$k] !== '') {
            $attrs[$k] = (string) $attrs[$k];
        }
    }
    if (isset($attrs['class']) && $attrs['class'] !== '') {
        $classes[] = $attrs['class'];
        unset($attrs['class']);
    }
    $tag = 'button';
    if (isset($attrs['href'])) {
        $tag = 'a';
        unset($attrs['type'], $attrs['name'], $attrs['value']);
    }

    $html = '<' . $tag . ' class="' . e(implode(' ', $classes)) . '"';
    foreach ($attrs as $k => $v) {
        $html .= ' ' . e((string) $k) . '="' . e((string) $v) . '"';
    }
    $html .= '>';
    if ($icon !== null) {
        $html .= ds_icon($icon);
    }
    $html .= ' ' . e($label);
    $html .= '</' . $tag . '>';

    return $html;
}

/**
 * Champ du design system : label, controle, aide, message d'etat.
 *
 * @param string $type      text|email|tel|number|date|select|textarea|...
 * @param array  $options   Options du `select` (valeur => libelle), ou [] .
 * @param string $state     ''|error|warning|ok — pilote la bordure du controle.
 * @param string $message   Message d'etat (erreur, avertissement, succes).
 * @param string $icon      Material Symbol du message ; deduit de `$state` si vide.
 */
function ds_field(string $name, array $opt = []): string
{
    $label    = (string) ($opt['label'] ?? $name);
    $type     = (string) ($opt['type'] ?? 'text');
    $value    = (string) ($opt['value'] ?? '');
    $state    = (string) ($opt['state'] ?? '');
    $message  = (string) ($opt['message'] ?? '');
    $hint     = (string) ($opt['hint'] ?? '');
    $options  = (array) ($opt['options'] ?? []);
    $required = !empty($opt['required']);
    $wide     = !empty($opt['wide']);
    $mono     = !empty($opt['mono']);
    $badge    = (string) ($opt['badge'] ?? '');
    $sub      = (string) ($opt['sub'] ?? '');
    $icon     = (string) ($opt['icon'] ?? '');
    $extra    = (array) ($opt['attrs'] ?? []);

    $classes = ['ds-field'];
    if ($wide) { $classes[] = 'ds-field--wide'; }
    if ($mono) { $classes[] = 'ds-field--mono'; }
    if ($state !== '') { $classes[] = 'is-' . $state; }

    $id = 'f-' . preg_replace('/[^a-zA-Z0-9]+/', '-', $name);
    $h = '<label class="' . e(implode(' ', $classes)) . '" for="' . e($id) . '">';
    $h .= '<span class="ds-field__label">' . e($label);
    if ($sub !== '') { $h .= ' <small>' . e($sub) . '</small>'; }
    if ($required) { $h .= ' <em class="req-mark">*</em>'; }
    if ($badge !== '') { $h .= ' <span class="ds-field__req">' . e($badge) . '</span>'; }
    $h .= '</span>';

    if (isset($extra['id'])) {
        $id = (string) $extra['id'];
        unset($extra['id']);
    }

    $common = ' name="' . e($name) . '" id="' . e($id) . '"';
    if ($required) { $common .= ' required'; }
    if (isset($extra['readonly'])) {
        $common .= ' readonly';
        unset($extra['readonly']);
    }
    if (isset($extra['placeholder'])) {
        $common .= ' placeholder="' . e((string) $extra['placeholder']) . '"';
        unset($extra['placeholder']);
    }
    if (isset($extra['minlength'])) {
        $common .= ' minlength="' . e((string) $extra['minlength']) . '"';
        unset($extra['minlength']);
    }
    if (isset($extra['maxlength'])) {
        $common .= ' maxlength="' . e((string) $extra['maxlength']) . '"';
        unset($extra['maxlength']);
    }
    if (isset($extra['step'])) {
        $common .= ' step="' . e((string) $extra['step']) . '"';
        unset($extra['step']);
    }
    if ($hint !== '') {
        $common .= ' aria-describedby="' . e($id) . '-hint"';
    }
    foreach ($extra as $k => $v) {
        $common .= ' ' . e((string) $k) . '="' . e((string) $v) . '"';
    }

    if ($type === 'select') {
        $h .= '<select' . $common . '>';
        if (isset($opt['empty_label'])) {
            $h .= '<option value=""' . ($value === '' ? ' selected' : '') . '>'
                . e((string) $opt['empty_label']) . '</option>';
        }
        foreach ($options as $val => $lbl) {
            $h .= '<option value="' . e((string) $val) . '"'
                . ((string) $val === $value ? ' selected' : '') . '>' . e((string) $lbl) . '</option>';
        }
        $h .= '</select>';
    } elseif ($type === 'textarea') {
        $h .= '<textarea' . $common . ' rows="' . (int) ($opt['rows'] ?? 3) . '">' . e($value) . '</textarea>';
    } else {
        $h .= '<input type="' . e($type) . '"' . $common
            . ' value="' . e($value) . '"' . ($mono ? ' inputmode="numeric"' : '') . '>';
    }

    if ($hint !== '') {
        $h .= '<small class="ds-field__hint" id="' . e($id) . '-hint">' . e($hint) . '</small>';
    }
    if ($message !== '') {
        if ($icon === '') {
            $icon = match ($state) {
                'error'   => 'error',
                'warning' => 'warning',
                'ok'      => 'check_circle',
                default   => 'info',
            };
        }
        $h .= '<small class="ds-msg ds-msg--' . e($state !== '' ? $state : 'info') . '">'
            . ds_icon($icon) . e($message) . '</small>';
    }

    return $h . '</label>';
}

/**
 * Carte du design system. Repliable sur mobile : le corps porte un `id` et le
 * bouton `aria-expanded`, ce que le CSS et le JS d'accordéon exploitent.
 *
 * @param string $body  Contenu du corps, deja rendu.
 * @param string $note  Note de section posee au-dessus des champs.
 */
function ds_card(array $opt = []): string
{
    $title = (string) ($opt['title'] ?? '');
    $icon  = (string) ($opt['icon'] ?? '');
    $tag   = (string) ($opt['tag'] ?? '');
    $note  = (string) ($opt['note'] ?? '');
    $body  = (string) ($opt['body'] ?? '');
    $uid   = (string) ($opt['id'] ?? 'ds-c-' . substr(md5($title . $icon), 0, 6));

    $classes = ['ds-card'];
    if (isset($opt['accent']) && $opt['accent']) {
        $classes[] = 'ds-card--accent';
    }

    $h = '<section class="' . e(implode(' ', $classes)) . '" data-ds-card>';
    $h .= '<header class="ds-card__head">';
    if ($icon !== '') {
        $h .= '<span class="ds-card__icon material-symbols-outlined">' . e($icon) . '</span>';
    }
    $h .= '<h3>' . e($title) . '</h3>';
    if ($tag !== '') {
        $h .= '<span class="ds-card__tag">' . e($tag) . '</span>';
    }
    $h .= '<button type="button" class="ds-card__toggle" data-ds-toggle aria-expanded="true"'
        . ' aria-controls="' . e($uid) . '">'
        . '<span class="material-symbols-outlined">expand_more</span>'
        . '<span class="ds-sr-only">Replier la section ' . e($title) . '</span>'
        . '</button>';
    $h .= '</header>';

    $h .= '<div class="ds-card__body" id="' . e($uid) . '">';
    if ($note !== '') {
        $h .= '<p class="ds-card__note">' . e($note) . '</p>';
    }
    $h .= $body;
    $h .= '</div></section>';

    return $h;
}

/**
 * Alerte du design system.
 *
 * @param 'error'|'warning'|'success'|'info' $tone
 */
function ds_alert(string $tone, string $message, ?string $icon = null): string
{
    if ($icon === null) {
        $icon = match ($tone) {
            'error'   => 'error',
            'warning' => 'warning',
            'success' => 'check_circle',
            default   => 'info',
        };
    }
    return '<div class="ds-alert is-' . e($tone) . '" role="'
        . (($tone === 'error' || $tone === 'warning') ? 'alert' : 'status')
        . '">' . ds_icon($icon) . '<div>' . $message . '</div></div>';
}
