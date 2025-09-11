<?php
declare(strict_types=1);

namespace App\models;

use App\core\Model;
use PDO;

final class User extends Model
{
    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO users
                (first_name,last_name,email,phone,address,password,role,gender,age,coach_id)
                VALUES
                (:first_name,:last_name,:email,:phone,:address,:password,:role,:gender,:age,:coach_id)";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'address'    => $data['address'],
            'password'   => $data['password'],
            'role'       => $data['role'],
            'gender'     => $data['gender'] ?? null,
            'age'        => $data['age'] ?? null,
            'coach_id'   => $data['coach_id'] ?? null,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function coaches(): array {
        $sql = "SELECT id, first_name, last_name FROM users WHERE role = 'coach' ORDER BY last_name, first_name";
        return $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adherentsOfCoach(int $coachId): array {
        $stmt = $this->db()->prepare("
            SELECT id, first_name, last_name, email
            FROM users
            WHERE role='adherent' AND coach_id=:coach
            ORDER BY last_name, first_name
        ");
        $stmt->execute(['coach'=>$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
