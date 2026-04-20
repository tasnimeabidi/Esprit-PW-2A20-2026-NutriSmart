<?php
/**
 * Modèle : repas.
 */
declare(strict_types=1);

final class Repas
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** @return list<array<string, mixed>> */
    public function listerPourApi(): array
    {
        $st = $this->pdo->query(
            'SELECT id, id_plan AS idPlan, id_recette AS idRecette, type, calories FROM repas ORDER BY id'
        );
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string) $row['id'];
            $row['idPlan'] = (string) $row['idPlan'];
            $row['idRecette'] = $row['idRecette'] !== null ? (string) $row['idRecette'] : '';
            $row['calories'] = $row['calories'] !== null ? (string) $row['calories'] : '';
        }
        unset($row);
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @param array<string, mixed> $data */
    public function creer(array $data): array
    {
        $idRecette = isset($data['idRecette']) && $data['idRecette'] !== '' && $data['idRecette'] !== null
            ? (int) $data['idRecette'] : null;
        $calories = isset($data['calories']) && $data['calories'] !== '' ? (int) $data['calories'] : null;

        $sql = 'INSERT INTO repas (id_plan, id_recette, type, calories) VALUES (?,?,?,?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            (int) $data['idPlan'],
            $idRecette,
            $data['type'],
            $calories,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->getParIdApi($id);
    }

    /** @param array<string, mixed> $patch */
    public function mettreAJour(int $id, array $patch): ?array
    {
        $ex = $this->getParIdApi($id);
        if ($ex === null) {
            return null;
        }
        $idPlan = isset($patch['idPlan']) ? (int) $patch['idPlan'] : (int) $ex['idPlan'];
        $idRecette = array_key_exists('idRecette', $patch)
            ? ($patch['idRecette'] === '' || $patch['idRecette'] === null ? null : (int) $patch['idRecette'])
            : ($ex['idRecette'] === '' ? null : (int) $ex['idRecette']);
        $type = isset($patch['type']) ? (string) $patch['type'] : (string) $ex['type'];
        $calories = array_key_exists('calories', $patch)
            ? ($patch['calories'] === '' || $patch['calories'] === null ? null : (int) $patch['calories'])
            : ($ex['calories'] === '' ? null : (int) $ex['calories']);

        $st = $this->pdo->prepare('UPDATE repas SET id_plan=?, id_recette=?, type=?, calories=? WHERE id=?');
        $st->execute([$idPlan, $idRecette, $type, $calories, $id]);
        return $this->getParIdApi($id);
    }

    public function supprimer(int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM repas WHERE id=?');
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    /**
     * Jointure repas ⟷ plan_repas (workshop : liste des repas pour un plan donné).
     *
     * @return list<array<string, mixed>>
     */
    public function listerParIdPlanEnJointure(int $idPlan): array
    {
        $sql = 'SELECT r.id, r.id_plan AS idPlan, r.id_recette AS idRecette, r.type, r.calories,
                p.id_utilisateur AS planIdUtilisateur, p.date_debut AS planDateDebut,
                p.date_fin AS planDateFin, p.objectif AS planObjectif, p.statut AS planStatut
            FROM repas r
            INNER JOIN plan_repas p ON p.id = r.id_plan
            WHERE r.id_plan = ?
            ORDER BY r.id';
        $st = $this->pdo->prepare($sql);
        $st->execute([$idPlan]);
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string) $row['id'];
            $row['idPlan'] = (string) $row['idPlan'];
            $row['planIdUtilisateur'] = (string) $row['planIdUtilisateur'];
            $row['idRecette'] = $row['idRecette'] !== null ? (string) $row['idRecette'] : '';
            $row['calories'] = $row['calories'] !== null ? (string) $row['calories'] : '';
        }
        unset($row);
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function getParIdApi(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id, id_plan AS idPlan, id_recette AS idRecette, type, calories FROM repas WHERE id=?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row['id'] = (string) $row['id'];
        $row['idPlan'] = (string) $row['idPlan'];
        $row['idRecette'] = $row['idRecette'] !== null ? (string) $row['idRecette'] : '';
        $row['calories'] = $row['calories'] !== null ? (string) $row['calories'] : '';
        return $row;
    }
}
