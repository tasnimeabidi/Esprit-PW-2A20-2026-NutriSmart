<?php
/**
 * Partie 3–10 du PDF : appel au contrôleur, formulaire, résultats.
 * Point 10 : fichier équivalent searchAlbums.php (ici searchRepas.php + Views).
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/controllers/genreC.php';
require_once __DIR__ . '/controllers/programmeSportifC.php';

$genreC = new GenreC();
$programmeSportifC = new ProgrammeSportifC();

$list = null;
$listProgrammesSportifs = null;
$plans = $genreC->afficherGenres();
if (!is_array($plans)) {
    $plans = [];
}

$validation = new ResultatValidation();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id_plan']) && isset($_POST['search'])) {
        $idPlan = trim((string) $_POST['id_plan']);
        $validation = ValidateurSelectionPlanRepas::valider($idPlan, Database::getConnection(), 'id_plan');
        if ($validation->ok()) {
            $id = (int) $idPlan;
            $ret = $genreC->afficherAlbums($id);
            $list = is_array($ret) ? $ret : [];
            $retPs = $programmeSportifC->afficherProgrammesSportifs($id);
            $listProgrammesSportifs = is_array($retPs) ? $retPs : [];
        }
    }
}

$idPlanSelection = isset($_POST['id_plan']) ? trim((string) $_POST['id_plan']) : '';

$planRepasChoisi = null;
if ($list !== null && $validation->ok() && $idPlanSelection !== '') {
    foreach ($plans as $p) {
        if ((string) $p['id'] === $idPlanSelection) {
            $planRepasChoisi = $p;
            break;
        }
    }
}

$pageTitle = 'Repas et sport par plan repas';
require __DIR__ . '/Views/header.php';
require __DIR__ . '/Views/searchRepas.php';
require __DIR__ . '/Views/footer.php';
