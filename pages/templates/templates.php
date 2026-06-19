<?php

declare(strict_types=1);

$action = (string) ($_GET['action'] ?? 'gestionnaire');

$allowedActions = ['gestionnaire', 'inspecteur', 'editeur'];
if (!in_array($action, $allowedActions, true)) {
    $action = 'gestionnaire';
}

require __DIR__ . '/_' . $action . '.php';
