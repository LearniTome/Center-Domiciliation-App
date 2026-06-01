<?php

declare(strict_types=1);

$messages = $_SESSION['ai_chat'] ?? [];
$response = null;

if (is_post() && isset($_POST['ask'])) {
    verify_csrf();
    $question = trim($_POST['question'] ?? '');
    if ($question !== '') {
        $messages[] = ['role' => 'user', 'content' => $question];
        $response = ClaudeService::chat($messages);
        if ($response !== null) {
            $messages[] = ['role' => 'assistant', 'content' => $response];
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible. Veuillez configurer la cle API Anthropic dans config/ai.local.php.");
        }
        $_SESSION['ai_chat'] = $messages;
    }
    redirect_to('ai-assistant');
}

if (is_post() && isset($_POST['clear'])) {
    verify_csrf();
    $_SESSION['ai_chat'] = [];
    redirect_to('ai-assistant');
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Posez des questions sur la domiciliation, la creation d'entreprise ou les demarches administratives.</p>
        </div>
        <div class="table-actions">
            <form method="post" style="display:inline">
                <?= csrf_input() ?>
                <button type="submit" name="clear" value="1" class="btn btn-cancel"><span class="mdi mdi-delete-outline"></span> Effacer l'historique</button>
            </form>
        </div>
    </div>

    <div class="chat-box" style="max-height:500px;overflow-y:auto;border:1px solid var(--line);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;display:flex;flex-direction:column;gap:1rem">
        <?php if (empty($messages)): ?>
            <p class="table-empty" style="padding:2rem;text-align:center">
                <span class="mdi mdi-robot" style="font-size:3rem;display:block;margin-bottom:0.5rem;color:var(--text-secondary)"></span>
                Posez votre premiere question.<br>
                <small style="color:var(--text-secondary)">Ex: "Quels sont les documents necessaires pour creer une SARL ?"</small>
            </p>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <div class="chat-msg <?= $m['role'] ?>" style="display:flex;gap:10px;align-items:flex-start">
                    <span class="mdi <?= $m['role'] === 'user' ? 'mdi-account-circle' : 'mdi-robot' ?>" style="font-size:1.5rem;color:<?= $m['role'] === 'user' ? 'var(--primary)' : 'var(--success)' ?>;flex-shrink:0"></span>
                    <div class="chat-bubble" style="background:var(--panel-strong);padding:10px 14px;border-radius:8px;line-height:1.6;font-size:0.9rem;white-space:pre-wrap;word-break:break-word">
                        <?= e($m['content']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="post" style="display:flex;gap:8px">
        <?= csrf_input() ?>
        <input type="text" name="question" placeholder="Posez votre question..." required autofocus
            style="flex:1;padding:10px 14px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--text);font-size:0.9rem">
        <button type="submit" name="ask" value="1" class="btn btn-next"><span class="mdi mdi-send"></span> Envoyer</button>
    </form>
</section>
