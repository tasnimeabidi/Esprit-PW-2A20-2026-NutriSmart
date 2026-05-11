<?php
declare(strict_types=1);

if (!isset($plans) || !isset($validation)) {
    header('Location: ../searchRepas.php');
    exit;
}
?>
<main class="container">
    <h1><?= htmlspecialchars($pageTitle ?? 'Repas et sport par plan repas', ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && !$validation->ok()) { ?>
        <div class="alert alert-error" role="alert">
            <?php foreach ($validation->toutes() as $msg) { ?>
                <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <form action="" method="post">
        <label for="id_plan">Sélectionnez un PlanRepas :</label>
        <select name="id_plan" id="id_plan" required>
            <?php foreach ($plans as $plan) {
                $id = (string) $plan['id'];
                $sel = $id === $idPlanSelection ? ' selected' : '';
                $label = htmlspecialchars((string) ($plan['objectif'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                <option value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"<?= $sel ?>><?= $label ?> (id&nbsp;: <?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>)</option>
            <?php } ?>
        </select>
        <button type="submit" name="search" value="1">Rechercher</button>
    </form>

    <?php
    if ($list !== null && $validation->ok() && $planRepasChoisi !== null) {
        $kcal = static function (array $r): string {
            if (array_key_exists('calories', $r) && $r['calories'] !== null && $r['calories'] !== '') {
                return (string) $r['calories'];
            }
            if (array_key_exists('catories', $r) && $r['catories'] !== null && $r['catories'] !== '') {
                return (string) $r['catories'];
            }

            return '—';
        };
        ?>
        <section aria-labelledby="h-repas">
            <h2 id="h-repas">Repas pour ce plan</h2>
            <?php if ($list === []) { ?>
                <p>Aucun repas pour ce plan.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ($list as $repas) { ?>
                        <li>
                            <?= htmlspecialchars((string) ($repas['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($kcal($repas), ENT_QUOTES, 'UTF-8') ?> kcal
                            <?php if (isset($repas['id_recette']) && $repas['id_recette'] !== null && $repas['id_recette'] !== '') { ?>
                                (recette #<?= htmlspecialchars((string) $repas['id_recette'], ENT_QUOTES, 'UTF-8') ?>)
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>

        <section aria-labelledby="h-sport">
            <h2 id="h-sport">Programmes sportifs pour ce plan</h2>
            <?php
            $ps = $listProgrammesSportifs ?? [];
            if ($ps === []) { ?>
                <p>Aucun programme sportif pour ce plan.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ($ps as $row) { ?>
                        <li>
                            <?= htmlspecialchars((string) ($row['type_sport'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars((string) ($row['niveau'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars((string) ($row['date_seance'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            (<?= htmlspecialchars((string) ($row['duree_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?> min)
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>
    <?php } ?>
</main>
