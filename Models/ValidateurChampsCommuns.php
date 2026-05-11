<?php
/**
 * Règles de saisie réutilisables (sans validation HTML5 — logique serveur / JS miroir).
 */
declare(strict_types=1);

final class ValidateurChampsCommuns
{
    public static function obligatoire(ResultatValidation $r, string $cle, $valeur, string $label): void
    {
        if ($valeur === null || trim((string) $valeur) === '') {
            $r->ajouter($cle, $label . ' est obligatoire.');
        }
    }

    public static function entierPositif(ResultatValidation $r, string $cle, string $valeur, string $label): void
    {
        if ($valeur === '' || !ctype_digit($valeur) || (int) $valeur < 1) {
            $r->ajouter($cle, $label . ' doit être un entier strictement positif.');
        }
    }

    public static function dateIso(ResultatValidation $r, string $cle, string $valeur, string $label): void
    {
        if ($valeur === '') {
            $r->ajouter($cle, $label . ' est obligatoire.');
            return;
        }
        $d = DateTime::createFromFormat('Y-m-d', $valeur);
        if ($d === false || $d->format('Y-m-d') !== $valeur) {
            $r->ajouter($cle, $label . ' doit être au format AAAA-MM-JJ.');
        }
    }

    /** Décimal positif ou chaîne vide (optionnel) */
    public static function decimalPositifOuVide(ResultatValidation $r, string $cle, string $valeur, string $label): void
    {
        if ($valeur === '') {
            return;
        }
        if (!is_numeric($valeur) || (float) $valeur < 0) {
            $r->ajouter($cle, $label . ' doit être un nombre positif ou vide.');
        }
    }

    public static function longueurMax(ResultatValidation $r, string $cle, string $valeur, int $max, string $label): void
    {
        if (mb_strlen($valeur) > $max) {
            $r->ajouter($cle, $label . ' ne peut pas dépasser ' . $max . ' caractères.');
        }
    }
}
