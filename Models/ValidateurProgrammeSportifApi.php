<?php
declare(strict_types=1);

final class ValidateurProgrammeSportifApi
{
    /** @param array<string, mixed> $data */
    public static function valider(array $data, PDO $pdo, ?int $idExistant = null): ResultatValidation
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

        $type = isset($data['typeSport']) ? trim((string) $data['typeSport']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'typeSport', $type, "Le type d'activité");
        ValidateurChampsCommuns::longueurMax($r, 'typeSport', $type, 128, "Le type d'activité");

        $niveau = isset($data['niveau']) ? trim((string) $data['niveau']) : '';
        ValidateurChampsCommuns::longueurMax($r, 'niveau', $niveau, 64, 'Le niveau');

        $intensite = isset($data['intensite']) ? trim((string) $data['intensite']) : '';
        ValidateurChampsCommuns::longueurMax($r, 'intensite', $intensite, 64, "L'intensité");

        $date = isset($data['dateSeance']) ? trim((string) $data['dateSeance']) : '';
        ValidateurChampsCommuns::dateIso($r, 'dateSeance', $date, 'La date de séance');

        $duree = isset($data['dureeMin']) ? trim((string) $data['dureeMin']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'dureeMin', $duree, 'La durée');
        if ($r->ok() && (!ctype_digit($duree) || (int) $duree < 1)) {
            $r->ajouter('dureeMin', 'La durée doit être un nombre entier de minutes (≥ 1).');
        }
        if ($r->ok()) {
            $n = (int) $duree;
            // SMALLINT UNSIGNED MySQL : au-delà, troncature silencieuse → souvent 65535 ; on refuse avant INSERT.
            if ($n > 65535) {
                $r->ajouter('dureeMin', 'La durée ne peut pas dépasser 65535 minutes (limite technique).');
            } elseif ($n > 1440) {
                $r->ajouter('dureeMin', 'La durée ne peut pas dépasser 1440 minutes (24 h) pour une séance.');
            }
        }

        $statut = isset($data['statut']) ? trim((string) $data['statut']) : 'prevue';
        ValidateurChampsCommuns::longueurMax($r, 'statut', $statut, 64, 'Le statut');

        return $r;
    }
}
