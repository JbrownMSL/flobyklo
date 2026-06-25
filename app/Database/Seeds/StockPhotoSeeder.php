<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Attaches a STOCK product photo to each piece of equipment that doesn't yet
 * have an image. Files live at public/media/equipment/<sku>.<ext> (sourced
 * from manufacturer / dealer product pages 2026-06-12 as interim placeholders;
 * Andrew to verify and replace with real photos as they're taken).
 *
 * Idempotent: skips any equipment that already has an image media row, so it
 * won't duplicate and won't clobber a real photo added later via the admin /
 * email-intake. Matches by filename glob on sku, so it tolerates jpg/png/webp.
 *
 * Run: php spark db:seed StockPhotoSeeder
 */
class StockPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $dir = FCPATH . 'media/equipment/';
        $added = 0;
        $skipped = 0;
        $missing = [];

        foreach ($this->db->table('equipment')->select('id, sku, name')->get()->getResultArray() as $e) {
            $id  = (int) $e['id'];
            $sku = (string) $e['sku'];

            // already has an image? leave it alone (idempotent / non-destructive)
            $hasImage = $this->db->table('equipment_media')
                ->where('equipment_id', $id)
                ->where('kind', 'image')
                ->countAllResults();
            if ($hasImage) {
                $skipped++;
                continue;
            }

            $files = glob($dir . $sku . '.*');
            if (! $files) {
                $missing[] = $sku . ' (' . $e['name'] . ')';
                continue;
            }

            $this->db->table('equipment_media')->insert([
                'equipment_id' => $id,
                'kind'         => 'image',
                'url'          => '/media/equipment/' . basename($files[0]),
                'caption'      => 'Stock photo — verify / replace with real photo',
                'sort'         => 0,
            ]);
            $added++;
        }

        echo "StockPhotoSeeder: added {$added}, skipped {$skipped} (already had image)\n";
        if ($missing) {
            echo 'No file for SKUs: ' . implode(', ', $missing) . "\n";
        }
    }
}
