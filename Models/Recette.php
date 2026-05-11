<?php
declare(strict_types=1);

class Recette
{
    private $conn;
    private $table_name = 'recette';

    public $id;
    public $nom;
    public $instructions;
    public $calories_totales;
    public $status;
    public $temps;
    public $video_url;
    /** @var int|null id_utilisateur (projet Esprit) */
    public $user_id;
    public $user_name;
    /** @var string|null email du formulaire « proposer recette » (colonne optionnelle) */
    public $proposer_email;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /** @return array<int, array<string, mixed>> */
    public function listerPourApi()
    {
        $query = 'SELECT id, nom, instructions, calories_totales FROM ' . $this->table_name . ' ORDER BY id';
        if (!$this->conn) {
            return [];
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = isset($row['id']) ? (string) $row['id'] : '';
            $row['nom'] = isset($row['nom']) ? (string) $row['nom'] : '';
            $row['instructions'] = isset($row['instructions']) ? (string) $row['instructions'] : '';
            $row['calories_totales'] = isset($row['calories_totales']) && $row['calories_totales'] !== null
                ? (string) $row['calories_totales'] : '';
            $row['caloriesTotales'] = $row['calories_totales'];
        }
        unset($row);
        return $rows;
    }

    /** @param array<string, mixed> $data */
    public function creerPourApi(array $data): array
    {
        if (!$this->conn) {
            throw new RuntimeException('Connexion base indisponible.');
        }
        $nom = isset($data['nom']) ? trim((string) $data['nom']) : '';
        if ($nom === '') {
            throw new InvalidArgumentException('Nom recette obligatoire.');
        }
        $instructions = isset($data['instructions']) ? trim((string) $data['instructions']) : '';
        $calories = isset($data['calories_totales']) ? trim((string) $data['calories_totales']) : '';
        $caloriesInt = $calories !== '' && ctype_digit($calories) ? (int) $calories : null;
        $status = isset($data['status']) ? trim((string) $data['status']) : 'active';
        if ($status === '') {
            $status = 'active';
        }
        if ($status === 'active') {
            $status = 'approved';
        }

        try {
            $st = $this->conn->prepare(
                'INSERT INTO recette (nom, instructions, calories_totales, status) VALUES (?, ?, ?, ?)'
            );
            $st->execute([$nom, $instructions, $caloriesInt, $status]);
        } catch (Throwable $e) {
            $st = $this->conn->prepare(
                'INSERT INTO recette (nom, instructions, calories_totales) VALUES (?, ?, ?)'
            );
            $st->execute([$nom, $instructions, $caloriesInt]);
        }

        $id = (int) $this->conn->lastInsertId();
        $st2 = $this->conn->prepare(
            'SELECT id, nom, instructions, calories_totales FROM recette WHERE id = ? LIMIT 1'
        );
        $st2->execute([$id]);
        $row = $st2->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Recette créée mais introuvable.');
        }
        $row['id'] = isset($row['id']) ? (string) $row['id'] : '';
        $row['nom'] = isset($row['nom']) ? (string) $row['nom'] : '';
        $row['instructions'] = isset($row['instructions']) ? (string) $row['instructions'] : '';
        $row['calories_totales'] = isset($row['calories_totales']) && $row['calories_totales'] !== null
            ? (string) $row['calories_totales'] : '';
        $row['caloriesTotales'] = $row['calories_totales'];
        return $row;
    }

    public function readAll()
    {
        $query = 'SELECT * FROM ' . $this->table_name . ' ORDER BY id DESC';
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create()
    {
        if (!$this->conn) {
            return false;
        }

        $video = $this->video_url ?? null;
        $temps = $this->temps ?? null;
        $uid = $this->user_id !== null ? (int) $this->user_id : null;
        $uname = $this->user_name ?? null;
        $pe = isset($this->proposer_email) ? trim((string) $this->proposer_email) : '';

        if ($pe !== '') {
            try {
                $stmt = $this->conn->prepare(
                    'INSERT INTO ' . $this->table_name . '
                    (nom, instructions, video_url, calories_totales, temps, status, user_id, user_name, proposer_email)
                    VALUES (:nom, :instructions, :video_url, :calories_totales, :temps, :status, :user_id, :user_name, :proposer_email)'
                );
                $stmt->bindValue(':nom', $this->nom);
                $stmt->bindValue(':instructions', $this->instructions);
                $stmt->bindValue(':video_url', $video);
                $stmt->bindValue(':calories_totales', $this->calories_totales);
                $stmt->bindValue(':temps', $temps, $temps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':status', $this->status);
                $stmt->bindValue(':user_id', $uid, $uid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':user_name', $uname);
                $stmt->bindValue(':proposer_email', $pe);
                if ($stmt->execute()) {
                    return true;
                }
            } catch (Throwable $e) {
                // colonne proposer_email absente ou schéma différent : poursuivre
            }
            try {
                $stmt = $this->conn->prepare(
                    'INSERT INTO ' . $this->table_name . '
                    (nom, instructions, calories_totales, temps, status, user_id, user_name, proposer_email)
                    VALUES (:nom, :instructions, :calories_totales, :temps, :status, :user_id, :user_name, :proposer_email)'
                );
                $stmt->bindValue(':nom', $this->nom);
                $stmt->bindValue(':instructions', $this->instructions);
                $stmt->bindValue(':calories_totales', $this->calories_totales);
                $stmt->bindValue(':temps', $temps, $temps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':status', $this->status);
                $stmt->bindValue(':user_id', $uid, $uid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':user_name', $uname);
                $stmt->bindValue(':proposer_email', $pe);
                if ($stmt->execute()) {
                    return true;
                }
            } catch (Throwable $e) {
            }
        }

        /** @var list<array{sql:string,has:array<string,bool>}> $tiers */
        $tiers = [
            [
                'sql' => 'INSERT INTO ' . $this->table_name . '
                (nom, instructions, video_url, calories_totales, temps, status, user_id, user_name)
                VALUES (:nom, :instructions, :video_url, :calories_totales, :temps, :status, :user_id, :user_name)',
                'has' => ['video' => true, 'temps' => true, 'status' => true, 'user' => true],
            ],
            [
                'sql' => 'INSERT INTO ' . $this->table_name . '
                (nom, instructions, calories_totales, temps, status, user_id, user_name)
                VALUES (:nom, :instructions, :calories_totales, :temps, :status, :user_id, :user_name)',
                'has' => ['video' => false, 'temps' => true, 'status' => true, 'user' => true],
            ],
            [
                'sql' => 'INSERT INTO ' . $this->table_name . '
                (nom, instructions, calories_totales, status, user_id, user_name)
                VALUES (:nom, :instructions, :calories_totales, :status, :user_id, :user_name)',
                'has' => ['video' => false, 'temps' => false, 'status' => true, 'user' => true],
            ],
            [
                'sql' => 'INSERT INTO ' . $this->table_name . '
                (nom, instructions, calories_totales, status)
                VALUES (:nom, :instructions, :calories_totales, :status)',
                'has' => ['video' => false, 'temps' => false, 'status' => true, 'user' => false],
            ],
            [
                'sql' => 'INSERT INTO ' . $this->table_name . '
                (nom, instructions, calories_totales)
                VALUES (:nom, :instructions, :calories_totales)',
                'has' => ['video' => false, 'temps' => false, 'status' => false, 'user' => false],
            ],
        ];

        foreach ($tiers as $tier) {
            if ($uid !== null && $tier['has']['user'] === false) {
                continue;
            }

            try {
                $stmt = $this->conn->prepare($tier['sql']);
                $stmt->bindValue(':nom', $this->nom);
                $stmt->bindValue(':instructions', $this->instructions);
                $stmt->bindValue(':calories_totales', $this->calories_totales);

                if ($tier['has']['video']) {
                    $stmt->bindValue(':video_url', $video);
                }
                if ($tier['has']['temps']) {
                    $stmt->bindValue(':temps', $temps, $temps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                }
                if ($tier['has']['status']) {
                    $stmt->bindValue(':status', $this->status);
                }
                if ($tier['has']['user']) {
                    $stmt->bindValue(':user_id', $uid, $uid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->bindValue(':user_name', $uname);
                }

                if ($stmt->execute()) {
                    return true;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return false;
    }

    public function readByStatus($status)
    {
        $query = 'SELECT * FROM ' . $this->table_name . ' WHERE status = :status ORDER BY id DESC';
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatus($id, $status)
    {
        $query = 'UPDATE ' . $this->table_name . ' SET status = :status WHERE id = :id';
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function readOne()
    {
        $query = 'SELECT * FROM ' . $this->table_name . ' WHERE id = :id LIMIT 1';
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->nom = $row['nom'];
            $this->instructions = $row['instructions'];
            $this->calories_totales = $row['calories_totales'];
            $this->status = $row['status'] ?? null;
            $this->video_url = $row['video_url'] ?? null;
            $this->temps = $row['temps'] ?? null;
            $this->user_id = isset($row['user_id']) ? (int) $row['user_id'] : null;
            $this->user_name = $row['user_name'] ?? null;
            $this->proposer_email = isset($row['proposer_email']) ? (string) $row['proposer_email'] : null;
            return true;
        }
        return false;
    }

    public function update()
    {
        if (!$this->conn) {
            return false;
        }

        $video = $this->video_url ?? null;
        $temps = $this->temps ?? null;
        $uid = $this->user_id !== null ? (int) $this->user_id : null;
        $uname = $this->user_name ?? null;
        $status = $this->status ?? 'pending';

        /** @var list<array{sql:string,mode:string}> $tiers */
        $tiers = [
            [
                'mode' => 'full',
                'sql' => 'UPDATE ' . $this->table_name . '
                SET nom = :nom,
                    instructions = :instructions,
                    video_url = :video_url,
                    calories_totales = :calories_totales,
                    temps = :temps,
                    status = :status,
                    user_id = :user_id,
                    user_name = :user_name
                WHERE id = :id',
            ],
            [
                'mode' => 'no_video',
                'sql' => 'UPDATE ' . $this->table_name . '
                SET nom = :nom,
                    instructions = :instructions,
                    calories_totales = :calories_totales,
                    temps = :temps,
                    status = :status,
                    user_id = :user_id,
                    user_name = :user_name
                WHERE id = :id',
            ],
            [
                'mode' => 'no_video_temps',
                'sql' => 'UPDATE ' . $this->table_name . '
                SET nom = :nom,
                    instructions = :instructions,
                    calories_totales = :calories_totales,
                    status = :status,
                    user_id = :user_id,
                    user_name = :user_name
                WHERE id = :id',
            ],
            [
                'mode' => 'status_only',
                'sql' => 'UPDATE ' . $this->table_name . ' SET status = :status WHERE id = :id',
            ],
        ];

        foreach ($tiers as $tier) {
            try {
                $stmt = $this->conn->prepare($tier['sql']);
                if ($tier['mode'] === 'status_only') {
                    $stmt->bindValue(':status', $status);
                    $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
                    if ($stmt->execute()) {
                        return true;
                    }

                    continue;
                }

                $stmt->bindValue(':nom', $this->nom);
                $stmt->bindValue(':instructions', $this->instructions);
                $stmt->bindValue(':calories_totales', $this->calories_totales);
                $stmt->bindValue(':status', $status);
                $stmt->bindValue(':user_id', $uid, $uid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':user_name', $uname);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

                if ($tier['mode'] === 'full') {
                    $stmt->bindValue(':video_url', $video);
                    $stmt->bindValue(':temps', $temps, $temps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                } elseif ($tier['mode'] === 'no_video') {
                    $stmt->bindValue(':temps', $temps, $temps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                }

                if ($stmt->execute()) {
                    return true;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return false;
    }

    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id = :id';
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
