<?php
require_once "../Controller/repasC.php";
require_once "../Controller/planRepasC.php";

$repasC     = new RepasC();
$planRepasC = new PlanRepasC();
$plans      = $planRepasC->afficherPlans();
$message    = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ajouter'])) {
        $id_plan    = $_POST['id_plan'];
        $id_recette = $_POST['id_recette'];
        $type       = $_POST['type'];
        $catories   = $_POST['catories'];

        $repasC->ajouterRepas($id_plan, $id_recette, $type, $catories);
        $message = "Repas ajouté avec succès !";
    }
}

include 'header.php';
?>

<div class="container">
    <h1>Ajouter un Repas</h1>

    <?php if ($message) { ?>
        <p class="success"><?= $message ?></p>
    <?php } ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="id_plan">PlanRepas :</label>
            <select name="id_plan" id="id_plan">
                <?php foreach ($plans as $plan) { ?>
                    <option value="<?= $plan['id'] ?>">
                        <?= htmlspecialchars($plan['objectif']) ?> (id: <?= $plan['id'] ?>)
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_recette">ID Recette :</label>
            <input type="number" name="id_recette" id="id_recette" required>
        </div>

        <div class="form-group">
            <label for="type">Type :</label>
            <select name="type" id="type">
                <option value="Petit-déjeuner">Petit-déjeuner</option>
                <option value="Déjeuner">Déjeuner</option>
                <option value="Dîner">Dîner</option>
                <option value="Collation">Collation</option>
            </select>
        </div>

        <div class="form-group">
            <label for="catories">Calories :</label>
            <input type="number" name="catories" id="catories" step="0.1" required>
        </div>

        <input type="submit" name="ajouter" value="Ajouter le Repas">
    </form>
</div>

<?php include 'footer.php'; ?>
