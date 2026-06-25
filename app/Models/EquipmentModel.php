<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipmentModel extends Model
{
    protected $table         = 'equipment';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'sku', 'name', 'slug', 'description', 'cost', 'daily_rate',
        'four_hour_rate', 'weekly_rate', 'min_days', 'quantity', 'damage_deposit',
        'tax_class', 'requires_trailer_id', 'is_trailer', 'is_dangerous', 'active',
    ];

    /** Active, listable equipment (optionally by category), with category name. */
    public function listing(?int $categoryId = null): array
    {
        $b = $this->db->table('equipment e')
            ->select('e.*, c.name AS category_name, c.slug AS category_slug')
            ->select("(SELECT m.url FROM equipment_media m WHERE m.equipment_id = e.id AND m.kind = 'image' ORDER BY m.sort, m.id LIMIT 1) AS thumb", false)
            ->join('categories c', 'c.id = e.category_id', 'left')
            ->where('e.active', 1)
            ->orderBy('e.name', 'ASC');
        if ($categoryId) {
            $b->where('e.category_id', $categoryId);
        }
        return $b->get()->getResultArray();
    }

    public function search(string $term): array
    {
        return $this->db->table('equipment')
            ->select('equipment.*')
            ->select("(SELECT m.url FROM equipment_media m WHERE m.equipment_id = equipment.id AND m.kind = 'image' ORDER BY m.sort, m.id LIMIT 1) AS thumb", false)
            ->where('active', 1)
            ->groupStart()->like('name', $term)->orLike('description', $term)->orLike('sku', $term)->groupEnd()
            ->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function bySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Weekly rate, computed from daily when not explicitly set (Config\Wtr multiplier). */
    public function weeklyRate(array $eq): float
    {
        if (! empty($eq['weekly_rate'])) {
            return (float) $eq['weekly_rate'];
        }
        $mult = config('Wtr')->weeklyDayMultiplier;
        return round((float) $eq['daily_rate'] * $mult, 2);
    }
}
