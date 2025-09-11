<?php
declare(strict_types=1);

namespace App\models;

use App\core\Model;
use PDO;

final class Reservation extends Model
{
    public function create(int $slotId, int $adherentId): int {
        $sql = "INSERT INTO reservations (slot_id, adherent_id, status, paid)
                VALUES (:slot,:adherent,'confirmed',0)";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['slot'=>$slotId,'adherent'=>$adherentId]);
        return (int)$this->db()->lastInsertId();
    }

    /** Ne compte que les réservations ACTIVES (status <> cancelled) */
    public function existsForSlot(int $slotId): bool {
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM reservations WHERE slot_id=:id AND status <> 'cancelled'");
        $stmt->execute(['id'=>$slotId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function forAdherent(int $adherentId): array {
        $sql = "SELECT r.*, s.start_time, s.end_time, s.coach_id,
                       u.first_name AS coach_first, u.last_name AS coach_last
                FROM reservations r
                JOIN slots s ON s.id = r.slot_id
                JOIN users u ON u.id = s.coach_id
                WHERE r.adherent_id = :id
                ORDER BY s.start_time DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id'=>$adherentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forCoach(int $coachId): array {
        $sql = "SELECT r.*, s.start_time, s.end_time,
                       a.first_name AS adh_first, a.last_name AS adh_last, a.email
                FROM reservations r
                JOIN slots s ON s.id = r.slot_id
                JOIN users a ON a.id = r.adherent_id
                WHERE s.coach_id = :coach
                ORDER BY s.start_time DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Détails d’une réservation + slot pour contrôle annulation */
    public function getWithSlot(int $reservationId): ?array {
        $sql = "SELECT r.*, s.start_time, s.end_time, s.coach_id
                FROM reservations r
                JOIN slots s ON s.id = r.slot_id
                WHERE r.id = :id";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id'=>$reservationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Passe en cancelled (retourne true si modifiée) */
    public function cancel(int $reservationId, int $adherentId): bool {
        $sql = "UPDATE reservations
                SET status = 'cancelled'
                WHERE id = :id AND adherent_id = :adherent AND status <> 'cancelled'";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id'=>$reservationId, 'adherent'=>$adherentId]);
        return $stmt->rowCount() > 0;
    }

    /** Créneaux réservés d’un adhérent (par un coach donné) */
    public function reservedSlotsForAdherent(int $coachId, int $adherentId): array {
        $sql = "SELECT s.id AS slot_id,
                       DATE(s.start_time) AS date,
                       DATE_FORMAT(s.start_time, '%H:%i') AS start_time,
                       DATE_FORMAT(s.end_time,   '%H:%i') AS end_time
                FROM reservations r
                JOIN slots s ON s.id = r.slot_id
                WHERE r.adherent_id = :adherent
                  AND s.coach_id = :coach
                  AND r.status <> 'cancelled'
                ORDER BY s.start_time ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['adherent'=>$adherentId,'coach'=>$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
