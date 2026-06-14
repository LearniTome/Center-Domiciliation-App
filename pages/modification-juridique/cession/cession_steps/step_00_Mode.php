<?php
declare(strict_types=1);

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

if ($step === 0):
?>
<form method="post" class="stack">
    <?= csrf_input() ?>
    <div id="mode-choice-grid">
        <label class="card choice-card" data-mode="existante">
            <input type="radio" name="mode" value="existante" hidden>
            <span class="material-symbols-outlined" style="color:var(--primary)">business</span>
            <h3>Société existante</h3>
            <p>Sélectionnez une société déjà enregistrée</p>
        </label>
        <label class="card choice-card" data-mode="nouvelle">
            <input type="radio" name="mode" value="nouvelle" hidden>
            <span class="material-symbols-outlined" style="color:var(--success)">add_business</span>
            <h3>Nouvelle société</h3>
            <p>Créer une nouvelle société pour cette cession</p>
        </label>
    </div>
</form>

<style>
#mode-choice-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
#mode-choice-grid .choice-card.selected[data-mode="existante"] { border-color: var(--primary); }
#mode-choice-grid .choice-card.selected[data-mode="nouvelle"]  { border-color: var(--success); }
#mode-choice-grid .choice-card { cursor:pointer; }
</style>

<script>
(function(){
    var cards = document.querySelectorAll('.choice-card');
    cards.forEach(function(c){
        c.addEventListener('click', function(){
            var radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                cards.forEach(function(x){ x.classList.remove('selected'); });
                this.classList.add('selected');
                this.closest('form').submit();
            }
        });
    });
})();
</script>
<?php endif; ?>
