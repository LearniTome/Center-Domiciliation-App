<?php
declare(strict_types=1);

// POST handler
if (is_post() && $step === 0) {
    verify_csrf();
    $mode = $_POST['mode'] ?? '';
    if ($mode === 'existante') {
        $wizard['mode'] = 'existante';
        $wizard['mode_nouvelle_sous'] = '';
        $wizard['societe'] = [];
        $wizard['associes'] = [];
        $wizard['societe_id'] = 0;
        redirect_to('cession', ['step' => 1]);
    } elseif ($mode === 'nouvelle') {
        $wizard['mode'] = 'nouvelle';
        $wizard['mode_nouvelle_sous'] = '';
        $wizard['societe'] = [];
        $wizard['associes'] = [];
        $wizard['societe_id'] = 0;
        $wizard['parts'] = [];
        redirect_to('cession', ['step' => 1]);
    }
    set_flash('error', 'Veuillez choisir un mode.');
    redirect_to('cession', ['step' => 0]);
}

// HTML view
if ($step === 0):
?>
<form method="post" class="stack">
    <?= csrf_input() ?>
    <p class="help-text">Comment souhaitez-vous proceder ?</p>
    <div class="grid two" id="mode-choice-grid">
        <label class="card choice-card" data-mode="existante">
            <input type="radio" name="mode" value="existante" id="mode-existante" style="display:none">
            <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--primary)">business</span>
            <h3 style="margin:8px 0 4px">Societe existante</h3>
            <p class="help-text">Selectionnez une societe deja enregistree</p>
        </label>
        <label class="card choice-card" data-mode="nouvelle">
            <input type="radio" name="mode" value="nouvelle" id="mode-nouvelle" style="display:none">
            <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--success)">add_business</span>
            <h3 style="margin:8px 0 4px">Nouvelle societe</h3>
            <p class="help-text">Creer une nouvelle societe pour cette cession</p>
        </label>
    </div>
    <div class="table-actions" style="margin-top:20px">
        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>

<script>
(function(){
    var cards = document.querySelectorAll('.choice-card');
    cards.forEach(function(c){
        c.addEventListener('click', function(){
            var radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            var group = this.closest('#mode-choice-grid') ? document.querySelectorAll('#mode-choice-grid .choice-card') : [];
            group.forEach(function(x){ x.style.borderColor = 'var(--line)'; });
            this.style.borderColor = this.dataset.mode === 'nouvelle' ? 'var(--success)' : 'var(--primary)';
        });
    });
})();
</script>
<?php endif; ?>
