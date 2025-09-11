<?php
declare(strict_types=1);

namespace App\models;

use App\core\Model;
use PDO;
use DateTime;
use DateInterval;

final class Slot extends Model
{
    public function add(int $coachId, string $start, string $end): int {
        $sql = "INSERT INTO slots (coach_id, start_time, end_time, status)
                VALUES (:coach_id,:start,:end,'available')";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach_id'=>$coachId,'start'=>$start,'end'=>$end]);
        return (int)$this->db()->lastInsertId();
    }

    public function deleteIfFree(int $coachId, int $slotId): bool {
        // Libre = aucune résa ACTIVE (status <> cancelled)
        $sql = "DELETE s FROM slots s
                LEFT JOIN reservations r
                     ON r.slot_id = s.id AND r.status <> 'cancelled'
                WHERE s.id = :id AND s.coach_id = :coach AND r.id IS NULL";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id'=>$slotId,'coach'=>$coachId]);
        return $stmt->rowCount() > 0;
    }

    public function setStatusIfFree(int $coachId, int $slotId, string $status): bool {
        $status = ($status === 'blocked') ? 'blocked' : 'available';
        $sql = "UPDATE slots s
                LEFT JOIN reservations r
                     ON r.slot_id = s.id AND r.status <> 'cancelled'
                SET s.status = :status
                WHERE s.id = :id AND s.coach_id = :coach AND r.id IS NULL";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['status'=>$status,'id'=>$slotId,'coach'=>$coachId]);
        return $stmt->rowCount() > 0;
    }

    public function coachSlotsWithStatus(int $coachId): array {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM reservations r
                         WHERE r.slot_id=s.id AND r.status <> 'cancelled') AS reserved_count
                FROM slots s
                WHERE s.coach_id = :coach
                ORDER BY s.start_time ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tous les créneaux disponibles (futurs) pour un coach */
    public function availableForCoach(int $coachId): array {
        $sql = "SELECT s.*
                FROM slots s
                LEFT JOIN reservations r
                       ON r.slot_id = s.id AND r.status <> 'cancelled'
                WHERE s.coach_id = :coach
                  AND s.status = 'available'
                  AND r.id IS NULL
                  AND s.start_time >= (NOW() - INTERVAL 5 MINUTE)
                ORDER BY s.start_time ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Dispos d’un coach sur une journée YYYY-MM-DD */
    public function availableByDayForCoach(int $coachId, string $dateYmd): array {
        $sql = "SELECT s.*
                FROM slots s
                LEFT JOIN reservations r
                       ON r.slot_id = s.id AND r.status <> 'cancelled'
                WHERE s.coach_id = :coach
                  AND DATE(s.start_time) = :d
                  AND s.status = 'available'
                  AND r.id IS NULL
                ORDER BY s.start_time ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId,'d'=>$dateYmd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tous les créneaux d’un jour (avec info réservation ACTIVE) */
    public function daySlotsForCoach(int $coachId, string $dateYmd): array {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM reservations r
                         WHERE r.slot_id=s.id AND r.status <> 'cancelled') AS reserved_count
                FROM slots s
                WHERE s.coach_id = :coach
                  AND DATE(s.start_time) = :d
                ORDER BY s.start_time ASC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId,'d'=>$dateYmd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Carte des dispos par jour pour un mois (YYYY-MM) */
    public function monthAvailabilityMap(int $coachId, string $ym): array {
        $start = new DateTime($ym . '-01 00:00:00');
        $end   = (clone $start)->modify('first day of next month 00:00:00');

        $sql = "SELECT DATE(s.start_time) AS d, 
                       SUM(CASE WHEN s.status='available' AND r.id IS NULL THEN 1 ELSE 0 END) AS cnt
                FROM slots s
                LEFT JOIN reservations r
                       ON r.slot_id = s.id AND r.status <> 'cancelled'
                WHERE s.coach_id = :coach
                  AND s.start_time >= :start
                  AND s.start_time < :end
                GROUP BY DATE(s.start_time)";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([
            'coach'=>$coachId,
            'start'=>$start->format('Y-m-d H:i:s'),
            'end'  =>$end->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) $map[$r['d']] = (int)$r['cnt'];
        return $map;
    }

    /** Totaux (tous / réservés ACTIFS) par jour pour un mois (coach) */
    public function monthSlotsSummaryMap(int $coachId, string $ym): array {
        $start = new DateTime($ym . '-01 00:00:00');
        $end   = (clone $start)->modify('first day of next month 00:00:00');

        $sql = "SELECT DATE(s.start_time) AS d,
                       COUNT(*) AS total,
                       SUM(CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) AS reserved
                FROM slots s
                LEFT JOIN reservations r
                       ON r.slot_id = s.id AND r.status <> 'cancelled'
                WHERE s.coach_id = :coach
                  AND s.start_time >= :start
                  AND s.start_time < :end
                GROUP BY DATE(s.start_time)";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute([
            'coach'=>$coachId,
            'start'=>$start->format('Y-m-d H:i:s'),
            'end'  =>$end->format('Y-m-d H:i:s'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = [
                'total'    => (int)$r['total'],
                'reserved' => (int)$r['reserved'],
            ];
        }
        return $map;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db()->prepare("SELECT * FROM slots WHERE id = :id LIMIT 1");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function overlaps(int $coachId, string $start, string $end): bool {
        $sql = "SELECT COUNT(*) FROM slots
                WHERE coach_id=:coach
                  AND status<>'deleted'
                  AND NOT (end_time <= :start OR start_time >= :end)";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['coach'=>$coachId,'start'=>$start,'end'=>$end]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Génération jour/mois (déjà implémentées) */
    public function ensureGridNextDays(int $coachId, int $days = 14, int $startHour = 8, int $endHour = 20): int {
        $created = 0;
        $today = new DateTime('today');
        $oneHour = new DateInterval('PT1H');
        for ($d = 0; $d < $days; $d++) {
            $day = (clone $today)->add(new DateInterval('P' . $d . 'D'));
            for ($h = $startHour; $h < $endHour; $h++) {
                $startDT = (clone $day)->setTime($h, 0, 0);
                $endDT   = (clone $startDT)->add($oneHour);
                $sql = "INSERT IGNORE INTO slots (coach_id, start_time, end_time, status)
                        VALUES (:coach, :start, :end, 'available')";
                $stmt = $this->db()->prepare($sql);
                $stmt->execute([
                    'coach'=>$coachId,
                    'start'=>$startDT->format('Y-m-d H:i:s'),
                    'end'  =>$endDT->format('Y-m-d H:i:s'),
                ]);
                $created += $stmt->rowCount();
            }
        }
        return $created;
    }

    public function ensureMonthGrid(int $coachId, int $year, int $month, int $startHour = 8, int $endHour = 20): int {
        $created = 0;
        $first = new DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $days = (int)$first->format('t');
        $oneHour = new DateInterval('PT1H');
        for ($d = 1; $d <= $days; $d++) {
            $day = (clone $first)->setDate((int)$first->format('Y'), (int)$first->format('m'), $d);
            for ($h = $startHour; $h < $endHour; $h++) {
                $startDT = (clone $day)->setTime($h, 0, 0);
                $endDT   = (clone $startDT)->add($oneHour);
                $sql = "INSERT IGNORE INTO slots (coach_id, start_time, end_time, status)
                        VALUES (:coach, :start, :end, 'available')";
                $stmt = $this->db()->prepare($sql);
                $stmt->execute([
                    'coach'=>$coachId,
                    'start'=>$startDT->format('Y-m-d H:i:s'),
                    'end'  =>$endDT->format('Y-m-d H:i:s'),
                ]);
                $created += $stmt->rowCount();
            }
        }
        return $created;
    }
}
