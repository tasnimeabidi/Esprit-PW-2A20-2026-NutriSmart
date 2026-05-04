<?php
/**
 * Modèle : programme_sportif (N—1 avec plan_repas), avec champs de séance intégrés.
 */
declare(strict_types=1);

final class ProgrammeSportif
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
            'SELECT ps.id,
                    ps.id_plan AS idPlan,
                    ps.type_sport AS typeSport,
                    ps.niveau,
                    ps.intensite,
                    DATE_FORMAT(ps.date_seance, \'%Y-%m-%d\') AS dateSeance,
                    ps.duree_min AS dureeMin,
                    ps.statut,
                    p.id_utilisateur AS planIdUtilisateur,
                    p.objectif AS planObjectif,
                    p.statut AS planStatut
             FROM programme_sportif ps
             INNER JOIN plan_repas p ON p.id = ps.id_plan
             ORDER BY ps.id'
        );
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string) $row['id'];
            $row['idPlan'] = (string) $row['idPlan'];
            $row['dureeMin'] = (string) $row['dureeMin'];
            $row['planIdUtilisateur'] = (string) $row['planIdUtilisateur'];
        }
        unset($row);
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @param array<string, mixed> $data */
    public function creer(array $data): array
    {
        $statut = trim((string) ($data['statut'] ?? 'prevue'));
        if ($statut === '') {
            $statut = 'prevue';
        }
        $sql = 'INSERT INTO programme_sportif (id_plan, type_sport, niveau, intensite, date_seance, duree_min, statut) '
            . 'VALUES (?,?,?,?,?,?,?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            (int) $data['idPlan'],
            $data['typeSport'],
            $data['niveau'],
            $data['intensite'],
            $data['dateSeance'],
            (int) $data['dureeMin'],
            $statut,
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
        $typeSport = isset($patch['typeSport']) ? (string) $patch['typeSport'] : (string) $ex['typeSport'];
        $niveau = isset($patch['niveau']) ? (string) $patch['niveau'] : (string) $ex['niveau'];
        $intensite = isset($patch['intensite']) ? (string) $patch['intensite'] : (string) $ex['intensite'];
        $dateSeance = isset($patch['dateSeance']) ? (string) $patch['dateSeance'] : (string) $ex['dateSeance'];
        $dureeMin = isset($patch['dureeMin']) ? (int) $patch['dureeMin'] : (int) $ex['dureeMin'];
        $statut = isset($patch['statut']) ? trim((string) $patch['statut']) : (string) $ex['statut'];
        if ($statut === '') {
            $statut = 'prevue';
        }

        $st = $this->pdo->prepare(
            'UPDATE programme_sportif SET id_plan=?, type_sport=?, niveau=?, intensite=?, date_seance=?, duree_min=?, statut=? WHERE id=?'
        );
        $st->execute([$idPlan, $typeSport, $niveau, $intensite, $dateSeance, $dureeMin, $statut, $id]);
        return $this->getParIdApi($id);
    }

    public function supprimer(int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM programme_sportif WHERE id=?');
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listerParIdPlan(int $idPlan): array
    {
        $st = $this->pdo->prepare(
            'SELECT ps.id,
                    ps.id_plan AS idPlan,
                    ps.type_sport AS typeSport,
                    ps.niveau,
                    ps.intensite,
                    DATE_FORMAT(ps.date_seance, \'%Y-%m-%d\') AS dateSeance,
                    ps.duree_min AS dureeMin,
                    ps.statut
             FROM programme_sportif ps
             WHERE ps.id_plan=?
             ORDER BY ps.date_seance, ps.id'
        );
        $st->execute([$idPlan]);
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (string) $row['id'];
            $row['idPlan'] = (string) $row['idPlan'];
            $row['dureeMin'] = (string) $row['dureeMin'];
        }
        unset($row);
        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function getParIdApi(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT ps.id,
                    ps.id_plan AS idPlan,
                    ps.type_sport AS typeSport,
                    ps.niveau,
                    ps.intensite,
                    DATE_FORMAT(ps.date_seance, \'%Y-%m-%d\') AS dateSeance,
                    ps.duree_min AS dureeMin,
                    ps.statut,
                    p.id_utilisateur AS planIdUtilisateur,
                    p.objectif AS planObjectif,
                    p.statut AS planStatut
             FROM programme_sportif ps
             INNER JOIN plan_repas p ON p.id = ps.id_plan
             WHERE ps.id=?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row['id'] = (string) $row['id'];
        $row['idPlan'] = (string) $row['idPlan'];
        $row['dureeMin'] = (string) $row['dureeMin'];
        $row['planIdUtilisateur'] = (string) $row['planIdUtilisateur'];
        return $row;
    }

}
