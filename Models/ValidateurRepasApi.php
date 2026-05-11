<?php
declare(strict_types=1);

final class ValidateurRepasApi
{
    /** @return list<string> */
    private static function typesAutorises(): array
    {
        return ['petit-déjeuner', 'brunch', 'déjeuner', 'collation', 'goûter', 'dîner', 'souper'];
    }

    /** @param array<string, mixed> $data */
    public static function valider(array $data, PDO $pdo): ResultatValidation
    {
        $r = new ResultatValidation();
        $idPlan = isset($data['idPlan']) ? trim((string) $data['idPlan']) : '';
        ValidateurChampsCommuns::entierPositif($r, 'idPlan', $idPlan, 'Le plan repas');
        if ($r->ok()) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM plan_repas WHERE id=?');
            $st->execute([(int) $idPlan]);
            if ((int) $st->fetchColumn() === 0) {
                $r->ajouter('idPlan', 'Le plan repas indiqué n’existe pas.');
            }
        }

        $type = isset($data['type']) ? trim((string) $data['type']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'type', $type, 'Le type de repas');
        ValidateurChampsCommuns::longueurMax($r, 'type', $type, 64, 'Le type de repas');
        if ($type !== '') {
            $typeNorm = mb_strtolower($type);
            if (!in_array($typeNorm, self::typesAutorises(), true)) {
                $r->ajouter('type', 'Le type de repas doit correspondre à une catégorie métier valide.');
            }
        }

        $idRec = isset($data['idRecette']) ? trim((string) $data['idRecette']) : '';
        if ($idRec !== '') {
            if (!ctype_digit($idRec) || (int) $idRec < 1) {
                $r->ajouter('idRecette', 'La recette doit être un identifiant entier positif ou vide.');
            } else {
                $st = $pdo->prepare('SELECT COUNT(*) FROM recette WHERE id=?');
                $st->execute([(int) $idRec]);
                if ((int) $st->fetchColumn() === 0) {
                    $r->ajouter('idRecette', 'La recette indiquée n’existe pas.');
                }
            }
        }

        $cal = isset($data['calories']) ? trim((string) $data['calories']) : '';
        if ($cal !== '' && (!ctype_digit($cal) || (int) $cal < 0)) {
            $r->ajouter('calories', 'Les calories doivent être un entier positif ou zéro, ou vide.');
        } elseif ($cal !== '' && (int) $cal > 5000) {
            $r->ajouter('calories', 'Les calories ne peuvent pas dépasser 5000 kcal pour un repas.');
        }

        return $r;
    }
}
