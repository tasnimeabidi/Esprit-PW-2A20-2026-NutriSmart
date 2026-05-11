<?php
declare(strict_types=1);

final class ValidateurPlanRepasApi
{
    /** @return list<string> */
    private static function statutsAutorises(): array
    {
        return ['brouillon', 'actif', 'archivé', 'en pause'];
    }

    /** @param array<string, mixed> $data */
    public static function valider(array $data): ResultatValidation
    {
        $r = new ResultatValidation();
        $idU = isset($data['idUtilisateur']) ? trim((string) $data['idUtilisateur']) : '';
        ValidateurChampsCommuns::entierPositif($r, 'idUtilisateur', $idU, "L'identifiant utilisateur");

        $dd = isset($data['dateDebut']) ? trim((string) $data['dateDebut']) : '';
        $df = isset($data['dateFin']) ? trim((string) $data['dateFin']) : '';
        ValidateurChampsCommuns::dateIso($r, 'dateDebut', $dd, 'La date de début');
        ValidateurChampsCommuns::dateIso($r, 'dateFin', $df, 'La date de fin');
        if ($r->ok() && strcmp($dd, $df) > 0) {
            $r->ajouter('dateFin', 'La date de fin doit être postérieure ou égale à la date de début.');
        }
        if ($r->ok()) {
            $dDeb = new DateTimeImmutable($dd);
            $dFin = new DateTimeImmutable($df);
            $nbJours = (int) $dDeb->diff($dFin)->days + 1;
            if ($nbJours > 365) {
                $r->ajouter('dateFin', 'La durée du plan repas ne peut pas dépasser 365 jours.');
            }
        }

        $obj = isset($data['objectif']) ? trim((string) $data['objectif']) : '';
        ValidateurChampsCommuns::obligatoire($r, 'objectif', $obj, "L'objectif");
        ValidateurChampsCommuns::longueurMax($r, 'objectif', $obj, 255, "L'objectif");

        $st = isset($data['statut']) ? trim((string) $data['statut']) : '';
        ValidateurChampsCommuns::longueurMax($r, 'statut', $st, 64, 'Le statut');
        if ($st !== '') {
            $normalise = mb_strtolower($st);
            if (!in_array($normalise, self::statutsAutorises(), true)) {
                $r->ajouter('statut', 'Le statut du plan repas est invalide.');
            }
        }

        return $r;
    }
}
