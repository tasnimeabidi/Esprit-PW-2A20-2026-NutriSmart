<?php
require_once "../Controller/programmeSportifC.php";

$progC      = new ProgrammeSportifC();
$programmes = $progC->afficherTousProgrammes();

include 'header.php';
?>

<div class="container">
    <h1>Liste des Programmes Sportifs</h1>

    <?php if (empty($programmes)) { ?>
        <p>Aucun programme disponible.</p>
    <?php } else { ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PlanRepas (Objectif)</th>
                    <th>Type Sport</th>
                    <th>Niveau</th>
                    <th>Intensité</th>
                    <th>Date Séance</th>
                    <th>Durée (min)</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programmes as $p) { ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['objectifPlan']) ?></td>
                        <td><?= htmlspecialchars($p['type_sport']) ?></td>
                        <td><?= htmlspecialchars($p['niveau']) ?></td>
                        <td><?= htmlspecialchars($p['intensite']) ?></td>
                        <td><?= $p['date_seance'] ?></td>
                        <td><?= $p['duree_min'] ?></td>
                        <td><?= htmlspecialchars($p['statut']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>

<?php include 'footer.php'; ?>
