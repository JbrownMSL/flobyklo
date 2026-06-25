<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Real WTR equipment inventory, generated from Andrew's Business Tracker.xlsx
 * ('2025WTR Equipment' sheet, 2026-06-09). Sold items excluded; the 3 identical
 * Dayton 400k heaters collapsed to one row (qty 3). Trailers tagged
 * motor_vehicle_rental. Deposit auto-set from cost via Config\Wtr::depositFor().
 *
 * OWNERSHIP NOTE: items tagged 'JAB Brothers' in the sheet are seeded as a
 * PLACEHOLDER 50/50 WTR/JAB split — Andrew must confirm the real percentages.
 *
 * Run AFTER WtrSeeder (needs categories): php spark db:seed InventorySeeder
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $wtr = config('Wtr');
        $catId = [];
        foreach ($this->db->table('categories')->get()->getResultArray() as $c) {
            $catId[$c['name']] = (int) $c['id'];
        }
        foreach ($this->items() as $i) {
            if ($this->db->table('equipment')->where('sku', $i['sku'])->countAllResults()) {
                continue;
            }
            $deposit = $wtr->depositFor($i['cost']);
            $id = $this->db->table('equipment')->insert([
                'category_id'    => $catId[$i['cat']] ?? null,
                'sku'            => $i['sku'],
                'name'           => $i['name'],
                'slug'           => url_title($i['name'], '-', true) . '-' . $i['sku'],
                'description'    => $i['desc'],
                'cost'           => $i['cost'],
                'daily_rate'     => $i['daily'],
                'four_hour_rate' => $i['four'],
                'weekly_rate'    => $i['weekly'],
                'min_days'       => 1,
                'quantity'       => $i['qty'],
                'damage_deposit' => $deposit,
                'tax_class'      => $i['tax'],
                'is_trailer'     => $i['is_trailer'],
                'is_dangerous'   => $i['dangerous'],
                'active'         => 1,
                'created_at'     => date('Y-m-d H:i:s'),
            ], true) ? $this->db->insertID() : null;
            if (! $id) { continue; }
            $owners = $i['owner'] === 'JAB Brothers'
                ? [['WTR', 50.0], ['JAB Brothers', 50.0]]   // PLACEHOLDER split — confirm with Andrew
                : [['WTR', 100.0]];
            foreach ($owners as $o) {
                $this->db->table('equipment_ownership')->insert([
                    'equipment_id' => $id, 'owner_entity' => $o[0], 'owned_pct' => $o[1],
                ]);
            }
            $this->db->table('equipment_costs')->insert([
                'equipment_id' => $id, 'kind' => 'purchase', 'amount' => $i['cost'] ?? 0,
                'cost_date' => '2025-06-01', 'notes' => 'Opening inventory (from Business Tracker)', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function items(): array
    {
        return json_decode(<<<'JSON'
[{"sku": "251", "name": "Kubota Tractor MX Series with Cab", "desc": "Kubota MX5400HSTC", "cost": 43000.0, "daily": 325.0, "four": 211.25, "weekly": 1300.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heavy Equipment & Trailers", "owner": "WTR Owned", "dangerous": 1}, {"sku": "252", "name": "Bobcat Mini Skid Steer", "desc": "Bobcat MT85", "cost": 14300.0, "daily": 250.0, "four": 162.5, "weekly": 1000.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heavy Equipment & Trailers", "owner": "WTR Owned", "dangerous": 1}, {"sku": "254", "name": "2019 Concrete Buggy", "desc": "Wacker Neusen DT08", "cost": 2600.0, "daily": 75.0, "four": 48.75, "weekly": 300.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heavy Equipment & Trailers", "owner": "WTR Owned", "dangerous": 1}, {"sku": "255", "name": "2021 Best Tilt Deck Trailer S/A", "desc": "Best Trailers S/ATilt", "cost": 3425.0, "daily": 50.0, "four": 32.5, "weekly": 200.0, "qty": 1, "is_trailer": 1, "tax": "motor_vehicle_rental", "cat": "Heavy Equipment & Trailers", "owner": "WTR Owned", "dangerous": 0}, {"sku": "256", "name": "PJ Trailers Car Hauler", "desc": "PJ Trailers C521832CS", "cost": 1000.0, "daily": 75.0, "four": 50.0, "weekly": 400.0, "qty": 1, "is_trailer": 1, "tax": "motor_vehicle_rental", "cat": "Heavy Equipment & Trailers", "owner": "WTR Owned", "dangerous": 0}, {"sku": "100", "name": "3 Point Auger", "desc": "", "cost": 1600.0, "daily": 50.0, "four": 32.5, "weekly": 200.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 1}, {"sku": "101", "name": "3 Point Box Scraper", "desc": "72\"", "cost": 1000.0, "daily": 40.0, "four": 26.0, "weekly": 160.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 0}, {"sku": "102", "name": "3 Point Ballast Box (Small)", "desc": "Titan", "cost": 459.99, "daily": 25.0, "four": 16.25, "weekly": 100.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 0}, {"sku": "103", "name": "3 Point Ballast Box (Large)", "desc": "Titan", "cost": 699.99, "daily": 30.0, "four": 19.5, "weekly": 120.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 0}, {"sku": "104", "name": "3 Point Snow Blower", "desc": "Nortrac BE-SBS7276G", "cost": 2300.0, "daily": 125.0, "four": 81.25, "weekly": 500.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 1}, {"sku": "105", "name": "3 Point Trailer Hitch", "desc": "", "cost": 130.0, "daily": 15.0, "four": 9.75, "weekly": 60.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 0}, {"sku": "106", "name": "3 Point Boom Sprayer", "desc": "Fimco", "cost": 599.0, "daily": 60.0, "four": 39.0, "weekly": 240.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 1}, {"sku": "107", "name": "3 Point Ditch Bank Flail Mower", "desc": "", "cost": 3999.0, "daily": 185.0, "four": 120.25, "weekly": 740.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Tractor Attachments (3-Point)", "owner": "WTR Owned", "dangerous": 1}, {"sku": "108", "name": "Quicktach Pallet Forks (42\")", "desc": "Titan", "cost": 599.0, "daily": 35.0, "four": 22.75, "weekly": 140.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "109", "name": "Quicktach Pallet Forks (60\")", "desc": "Titan", "cost": 670.0, "daily": 40.0, "four": 26.0, "weekly": 160.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "110", "name": "Hay Spears (Qty: 2)", "desc": "Titan", "cost": 385.0, "daily": 30.0, "four": 19.5, "weekly": 120.0, "qty": 2, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "111", "name": "Lifting Jib", "desc": "", "cost": 250.0, "daily": 20.0, "four": 13.0, "weekly": 80.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "112", "name": "Quicktach Rock Grizzly", "desc": "", "cost": 3500.0, "daily": 300.0, "four": 195.0, "weekly": 1200.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "113", "name": "Front Mount Tractor Snow Plow", "desc": "Meyer", "cost": 2600.0, "daily": 65.0, "four": 42.25, "weekly": 260.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "114", "name": "Evolution Steel Chop Saw", "desc": "Evolution S355MCS", "cost": 849.0, "daily": 65.0, "four": 42.25, "weekly": 260.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "115", "name": "Evolution Blades (Blade Deposit Required)", "desc": "Evolution", "cost": 350.0, "daily": 10.0, "four": 6.5, "weekly": 40.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "116", "name": "Ridgid Portable Table Saw", "desc": "Ridgid", "cost": 250.0, "daily": 30.0, "four": 19.5, "weekly": 120.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "117", "name": "Ridgid Portable Sliding Compount Miter Saw", "desc": "Ridgid", "cost": 450.0, "daily": 40.0, "four": 26.0, "weekly": 160.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "118", "name": "Grizzly Helical Blade Planer", "desc": "Grizzly", "cost": 699.0, "daily": 55.0, "four": 35.75, "weekly": 220.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "119", "name": "Router Table & Router", "desc": "Bosch/Ridgid", "cost": 349.0, "daily": 40.0, "four": 26.0, "weekly": 160.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "120", "name": "Propane/Natural Gas Furnace", "desc": "Flagro 1,000,000 BTU", "cost": 3200.0, "daily": 200.0, "four": 130.0, "weekly": 800.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heaters", "owner": "WTR Owned", "dangerous": 1}, {"sku": "121", "name": "Diesel Tube Heater", "desc": "Mr. Heater 180,000 BTU", "cost": 299.0, "daily": 35.0, "four": 22.75, "weekly": 140.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heaters", "owner": "WTR Owned", "dangerous": 1}, {"sku": "122", "name": "Diesel Tube Heater", "desc": "Dayton 400,000 BTU", "cost": 2100.0, "daily": 120.0, "four": 78.0, "weekly": 480.0, "qty": 3, "is_trailer": 0, "tax": "equipment_rental", "cat": "Heaters", "owner": "WTR Owned", "dangerous": 1}, {"sku": "125", "name": "Laser Level", "desc": "Spectra Precision", "cost": 475.0, "daily": 25.0, "four": 16.25, "weekly": 100.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "126", "name": "Pipe Bender", "desc": "Central", "cost": 299.0, "daily": 25.0, "four": 16.25, "weekly": 100.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Metalworking", "owner": "WTR Owned", "dangerous": 0}, {"sku": "127", "name": "Tubing Roller", "desc": "Central", "cost": 299.0, "daily": 25.0, "four": 16.25, "weekly": 100.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Metalworking", "owner": "WTR Owned", "dangerous": 0}, {"sku": "128", "name": "M18 Bandsaw - 3.25\" Capacity", "desc": "Milwaukee", "cost": 329.0, "daily": 25.0, "four": 16.25, "weekly": 100.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "129", "name": "M18 Bandsaw - 5\" Capacity", "desc": "Milwaukee", "cost": 379.0, "daily": 35.0, "four": 22.75, "weekly": 140.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Saws & Woodworking", "owner": "WTR Owned", "dangerous": 1}, {"sku": "130", "name": "M18 SDS Plus Hammer Drill", "desc": "Milwaukee", "cost": 349.0, "daily": 30.0, "four": 19.5, "weekly": 120.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Other Tools", "owner": "WTR Owned", "dangerous": 0}, {"sku": "131", "name": "MT 85 Fork Attachement", "desc": "Express Steel", "cost": 770.0, "daily": 65.0, "four": 42.25, "weekly": 260.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "132", "name": "MT 85 Auger", "desc": "Express Steel", "cost": 400.0, "daily": 40.0, "four": 26.0, "weekly": 160.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "133", "name": "Multiquip Power Trowel", "desc": "Multiquip", "cost": 350.0, "daily": 75.0, "four": 48.75, "weekly": 300.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Concrete", "owner": "WTR Owned", "dangerous": 1}, {"sku": "134", "name": "Wacker Neuson Jumping Jack", "desc": "Wacker Neuseon", "cost": 300.0, "daily": 65.0, "four": 42.25, "weekly": 260.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Concrete", "owner": "WTR Owned", "dangerous": 1}, {"sku": "135", "name": "Telescopic Skid Steer Boom Attachment", "desc": "Generic", "cost": 1650.0, "daily": 95.0, "four": 61.75, "weekly": 380.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "WTR Owned", "dangerous": 1}, {"sku": "136", "name": "59\" Skid Steer Vibratory Roller", "desc": "Generic", "cost": 1210.0, "daily": 95.0, "four": 61.75, "weekly": 380.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "JAB Brothers", "dangerous": 1}, {"sku": "137", "name": "Skid Steer Dozer attachment", "desc": "Raytree", "cost": 1430.0, "daily": 95.0, "four": 61.75, "weekly": 380.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "JAB Brothers", "dangerous": 1}, {"sku": "138", "name": "Raytree Skid Steer Auger", "desc": "Raytree", "cost": 1870.0, "daily": 95.0, "four": 61.75, "weekly": 380.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "JAB Brothers", "dangerous": 1}, {"sku": "139", "name": "Skid Steer Sweeper", "desc": "Generic", "cost": 3300.0, "daily": 150.0, "four": 97.5, "weekly": 600.0, "qty": 1, "is_trailer": 0, "tax": "equipment_rental", "cat": "Skid Steer Attachments", "owner": "JAB Brothers", "dangerous": 1}]
JSON, true);
    }
}
