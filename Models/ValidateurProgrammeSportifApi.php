<?php
declare(strict_types=1);

final class ValidateurProgrammeSportifApi
{
    /** @return list<string> */
    private static function niveauxAutorises(): array
    {
        return ['débutant', 'intermédiaire', 'avancé', 'expert'];
    }

    /** @return list<string> */
    private static function intensitesAutorisees(): array
    {
        return ['très légère', 'légère', 'modérée', 'soutenue', 'très soutenue', 'récupération'];
    }

    /** @return list<string> */
    private static function statutsAutorises(): array
    {
        return ['prevue', 'en cours', 'realisee', 'annulee', 'reportee'];
    }

    /** @param array<string, mixed> $data */
    public static function valider(array $data, PDO $pdo, ?int $idExistant = null): ResultatValidation
    {
        $r = new ResultatValidation();
        $idPlan = isset($data['idPlan']) ? trim((string) $data['idPlan']) : '';
        ValidateurChampsCommuns::entierPositif($r, 'idPlan', $idPlan, 'Le plan repas');
        $planDebut = '';
        $planFin = '';
        if ($r->ok()) {
            $st = $pdo->prepare('SELECT date_debut, date_fin FROM plan_repas WHERE id=?');
            $st->execute([(int) $idPlan]);
            $plan = $st->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                $r->ajouter('idPlan', 'Le plan repas indiqué n’existe pas.');
            } else {
                $planDebut = (string) ($plan['date_debut'] ?? '');
                $planFin = (string) ($plan['date_fin'] ?? '');
            }
        }

        $type = isset($data['typeSport']) ? trim((string) $data['typeSport']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'typeSport', $type, "Le type d'activité");
        ValidateurChampsCommuns::longueurMax($r, 'typeSport', $type, 128, "Le type d'activité");

        $niveau = isset($data['niveau']) ? trim((string) $data['niveau']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'niveau', $niveau, 'Le niveau');
        ValidateurChampsCommuns::longueurMax($r, 'niveau', $niveau, 64, 'Le niveau');
        if ($niveau !== '' && !in_array(mb_strtolower($niveau), self::niveauxAutorises(), true)) {
            $r->ajouter('niveau', 'Le niveau est invalide.');
        }

        $intensite = isset($data['intensite']) ? trim((string) $data['intensite']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'intensite', $intensite, "L'intensité");
        ValidateurChampsCommuns::longueurMax($r, 'intensite', $intensite, 64, "L'intensité");
        if ($intensite !== '' && !in_array(mb_strtolower($intensite), self::intensitesAutorisees(), true)) {
            $r->ajouter('intensite', "L'intensité est invalide.");
        }

        $date = isset($data['dateSeance']) ? trim((string) $data['dateSeance']) : '';
        ValidateurChampsCommuns::dateIso($r, 'dateSeance', $date, 'La date de séance');
        // Date libre: on valide seulement le format ISO, sans contrainte de période du plan.

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
        ValidateurChampsCommuns::obligatoire($r, 'statut', $statut, 'Le statut');
        ValidateurChampsCommuns::longueurMax($r, 'statut', $statut, 64, 'Le statut');
        if ($statut !== '' && !in_array(mb_strtolower($statut), self::statutsAutorises(), true)) {
            $r->ajouter('statut', 'Le statut de séance est invalide.');
        }

        if ($r->ok()) {
            $sql = 'SELECT COUNT(*) FROM programme_sportif WHERE id_plan = ? AND date_seance = ? AND type_sport = ?';
            $params = [(int) $idPlan, $date, $type];
            if ($idExistant !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $idExistant;
            }
            $stDup = $pdo->prepare($sql);
            $stDup->execute($params);
            if ((int) $stDup->fetchColumn() > 0) {
                $r->ajouter('typeSport', 'Une séance de ce type existe déjà pour ce plan et cette date.');
            }
        }

        return $r;
    }
}
