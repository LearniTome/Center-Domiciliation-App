<?php
declare(strict_types=1);

if (is_post() && $step === 0) {
    verify_csrf();
    $mode = $_POST['mode'] ?? '';
    if ($mode === 'existante') {
        $wizard['mode'] = 'existante';
        $wizard['societe_id'] = 0;
        $wizard['societe'] = [];
        redirect_to('pv_ago', ['step' => 1]);
    }
    if ($mode === 'nouvelle') {
        $wizard['mode'] = 'nouvelle';
        $wizard['societe_id'] = 0;
        redirect_to('pv_ago', ['step' => 1]);
    }
    set_flash('error', 'Veuillez choisir un mode.');
    redirect_to('pv_ago', ['step' => 0]);
}

if ($step === 0):
?>
<div class="stack">
    <div class="section-header">
        <h2>PV d'Assemblee Generale Ordinaire Annuelle</h2>
        <a class="btn btn-back" href="<?= e(app_url('pvag')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour aux PV AGO</a>
    </div>
    <p class="help-text" style="margin-bottom:0">Selectionnez la societe concernee par le PV AGO.</p>

    <form method="post" class="stack">
        <?= csrf_input() ?>
        <div id="mode-choice-grid">
            <label class="card choice-card" data-mode="existante">
                <input type="radio" name="mode" value="existante" hidden>
                <span class="material-symbols-outlined" style="color:var(--primary)">business</span>
                <h3>Societe existante</h3>
                <p>Selectionnez une societe deja enregistree dans la base</p>
            </label>
            <label class="card choice-card" data-mode="nouvelle">
                <input type="radio" name="mode" value="nouvelle" hidden>
                <span class="material-symbols-outlined" style="color:var(--success)">add_business</span>
                <h3>Nouvelle societe</h3>
                <p>Creer une nouvelle societe pour ce PV AGO</p>
            </label>
        </div>
    </form>
</div>

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
