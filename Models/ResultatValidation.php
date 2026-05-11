<?php
/**
 * Résultat de contrôle de saisie (orienté objet, messages par champ).
 */
declare(strict_types=1);

final class ResultatValidation
{
    /** @var array<string, string> */
    private array $erreurs = [];

    public function ajouter(string $cle, string $message): void
    {
        $this->erreurs[$cle] = $message;
    }

    public function ok(): bool
    {
        return $this->erreurs === [];
    }

    /** Première erreur pour affichage simple ou JSON { "error": "..." } */
    public function premierMessage(): string
    {
        foreach ($this->erreurs as $msg) {
            return $msg;
        }
        return 'Données invalides.';
    }

    /** @return array<string, string> */
    public function toutes(): array
    {
        return $this->erreurs;
    }
}
