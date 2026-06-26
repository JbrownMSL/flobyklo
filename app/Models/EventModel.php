<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table         = 'events';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'client_id', 'type', 'event_date', 'venue', 'guest_count',
        'status', 'capacity_weekend', 'notes',
    ];

    /**
     * Count capacity-flagged, non-cancelled events on the Sat+Sun weekend
     * that contains $date. Returns 0 for weekday dates.
     * Pass $excludeId to omit the event being edited.
     */
    public function weekendCount(string $date, ?int $excludeId = null): int
    {
        $ts  = strtotime($date);
        $dow = (int) date('N', $ts); // 1=Mon … 6=Sat, 7=Sun

        if ($dow === 6) {
            $sat = $date;
            $sun = date('Y-m-d', strtotime('+1 day', $ts));
        } elseif ($dow === 7) {
            $sat = date('Y-m-d', strtotime('-1 day', $ts));
            $sun = $date;
        } else {
            return 0;
        }

        $builder = $this->db->table('events')
            ->whereIn('event_date', [$sat, $sun])
            ->where('capacity_weekend', 1)
            ->where('status !=', 'cancelled');

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Returns all capacity-flagged, non-cancelled events as [{id, date}]
     * for client-side JS checks in the add/edit form.
     */
    public function capacityEventDates(): array
    {
        return $this->db->table('events')
            ->select('id, event_date AS date')
            ->where('capacity_weekend', 1)
            ->where('status !=', 'cancelled')
            ->get()->getResultArray();
    }
}
