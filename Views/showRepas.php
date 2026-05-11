<?php
require_once "../Controller/repasC.php";

$repasC = new RepasC();
$repas  = $repasC->afficherTousRepas();

include 'header.php';
?>

<div class="container">
    <h1>Liste de tous les Repas</h1>

    <?php if (empty($repas)) { ?>
        <p>Aucun repas disponible.</p>
    <?php } else { ?>
        <table>
            <thead>
                <tr>
                    <th>ID Repas</th>
                    <th>PlanRepas (Objectif)</th>
                    <th>ID Recette</th>
                    <th>Type</th>
                    <th>Calories</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($repas as $r) { ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['objectifPlan']) ?></td>
                        <td><?= $r['id_recette'] ?></td>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td><?= $r['catories'] ?> cal</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>

<?php include 'footer.php'; ?>
