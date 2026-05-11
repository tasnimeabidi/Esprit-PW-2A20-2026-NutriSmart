<?php
/**
 * Contrôle serveur du choix d’un plan (page jointure repas / plan).
 */
declare(strict_types=1);

final class ValidateurSelectionPlanRepas
{
    /**
     * @param string $cleErreur Clé du champ (ex. id_plan ou idGenre selon le formulaire).
     */
    public static function valider(string $idPlanBrut, PDO $pdo, string $cleErreur = 'id_plan'): ResultatValidation
    {
        $r = new ResultatValidation();
        ValidateurChampsCommuns::obligatoire($r, $cleErreur, $idPlanBrut, 'Le plan repas');
        if (!$r->ok()) {
            return $r;
        }
        ValidateurChampsCommuns::entierPositif($r, $cleErreur, $idPlanBrut, 'L’identifiant du plan');
        if (!$r->ok()) {
            return $r;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM plan_repas WHERE id=?');
        $st->execute([(int) $idPlanBrut]);
        if ((int) $st->fetchColumn() === 0) {
            $r->ajouter($cleErreur, 'Le plan repas sélectionné n’existe pas.');
        }
        return $r;
    }
}
