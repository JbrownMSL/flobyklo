<?php

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use App\Models\EquipmentModel;

class Equipment extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        return view('admin/equipment_list', [
            'title'     => 'Inventory',
            'equipment' => (new EquipmentModel())->orderBy('name')->findAll(),
        ]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $em = new EquipmentModel();
        $eq = $id ? $em->find((int) $id) : null;
        $db = db_connect();
        return view('admin/equipment_form', [
            'title'      => $eq ? 'Edit ' . $eq['name'] : 'New Equipment',
            'eq'         => $eq,
            'categories' => (new CategoryModel())->active(),
            'trailers'   => $em->where('is_trailer', 1)->findAll(),
            'media'      => $eq ? $db->table('equipment_media')->where('equipment_id', $id)->orderBy('sort')->get()->getResultArray() : [],
            'safety'     => $eq ? $db->table('equipment_safety')->where('equipment_id', $id)->get()->getRowArray() : null,
            'ownership'  => $eq ? $db->table('equipment_ownership')->where('equipment_id', $id)->get()->getResultArray() : [],
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $em = new EquipmentModel();
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $data = [
            'category_id'         => (int) $this->request->getPost('category_id') ?: null,
            'sku'                 => trim((string) $this->request->getPost('sku')) ?: null,
            'name'                => $name,
            'slug'                => url_title($name, '-', true) . ($id ? '' : '-' . substr(md5($name . microtime()), 0, 4)),
            'description'         => (string) $this->request->getPost('description'),
            'cost'                => $this->request->getPost('cost') !== '' ? (float) $this->request->getPost('cost') : null,
            'daily_rate'          => (float) $this->request->getPost('daily_rate'),
            'four_hour_rate'      => $this->request->getPost('four_hour_rate') !== '' ? (float) $this->request->getPost('four_hour_rate') : null,
            'weekly_rate'         => $this->request->getPost('weekly_rate') !== '' ? (float) $this->request->getPost('weekly_rate') : null,
            'min_days'            => max(1, (int) $this->request->getPost('min_days')),
            'quantity'            => max(1, (int) $this->request->getPost('quantity')),
            'damage_deposit'      => $this->request->getPost('damage_deposit') !== '' ? (float) $this->request->getPost('damage_deposit') : null,
            'tax_class'           => in_array($this->request->getPost('tax_class'), ['equipment_rental', 'motor_vehicle_rental'], true) ? $this->request->getPost('tax_class') : 'equipment_rental',
            'requires_trailer_id' => (int) $this->request->getPost('requires_trailer_id') ?: null,
            'is_trailer'          => $this->request->getPost('is_trailer') ? 1 : 0,
            'is_dangerous'        => $this->request->getPost('is_dangerous') ? 1 : 0,
            'active'              => $this->request->getPost('active') ? 1 : 0,
        ];
        if ($id) {
            unset($data['slug']);
            $em->update($id, $data);
        } else {
            $id = $em->insert($data, true);
        }

        // safety (upsert)
        $db = db_connect();
        $safety = [
            'equipment_id'        => $id,
            'ppe_required'        => (string) $this->request->getPost('ppe_required'),
            'safety_instructions' => (string) $this->request->getPost('safety_instructions'),
            'safety_video_url'    => (string) $this->request->getPost('safety_video_url'),
        ];
        $exists = $db->table('equipment_safety')->where('equipment_id', $id)->countAllResults();
        $exists ? $db->table('equipment_safety')->where('equipment_id', $id)->update($safety)
                : $db->table('equipment_safety')->insert($safety);

        return redirect()->to('/admin/equipment/' . $id)->with('msg', 'Saved.');
    }

    public function saveMedia($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $url = trim((string) $this->request->getPost('url'));
        if ($url !== '') {
            $db->table('equipment_media')->insert([
                'equipment_id' => (int) $id,
                'kind'         => in_array($this->request->getPost('kind'), ['image', 'video', 'safety_doc'], true) ? $this->request->getPost('kind') : 'image',
                'url'          => $url,
                'caption'      => (string) $this->request->getPost('caption'),
                'sort'         => (int) $this->request->getPost('sort'),
            ]);
        }
        return redirect()->to('/admin/equipment/' . $id)->with('msg', 'Media added.');
    }

    public function saveOwnership($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $db->table('equipment_ownership')->where('equipment_id', (int) $id)->delete();
        $owners = (array) $this->request->getPost('owner_entity');
        $pcts   = (array) $this->request->getPost('owned_pct');
        foreach ($owners as $i => $owner) {
            $owner = trim((string) $owner);
            if ($owner === '') {
                continue;
            }
            $db->table('equipment_ownership')->insert([
                'equipment_id' => (int) $id,
                'owner_entity' => $owner,
                'owned_pct'    => (float) ($pcts[$i] ?? 0),
            ]);
        }
        return redirect()->to('/admin/equipment/' . $id)->with('msg', 'Ownership saved.');
    }

    /** One-time CSV inventory import (decision: CSV over Google Sheets OAuth). */
    public function importCsv()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $file = $this->request->getFile('csv');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('/admin/equipment')->with('error', 'Upload a valid CSV.');
        }
        $rows = array_map('str_getcsv', file($file->getTempName()));
        $header = array_map(static fn ($h) => strtolower(trim($h)), array_shift($rows));
        $em = new EquipmentModel();
        $imported = 0;
        foreach ($rows as $r2) {
            if (count(array_filter($r2)) === 0) {
                continue;
            }
            $row = array_combine($header, array_pad($r2, count($header), ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $em->insert([
                'name'        => $name,
                'slug'        => url_title($name, '-', true) . '-' . substr(md5($name . $imported), 0, 4),
                'sku'         => trim((string) ($row['sku'] ?? '')) ?: null,
                'description' => (string) ($row['description'] ?? ''),
                'daily_rate'  => (float) ($row['daily_rate'] ?? 0),
                'quantity'    => max(1, (int) ($row['quantity'] ?? 1)),
                'tax_class'   => (($row['tax_class'] ?? '') === 'motor_vehicle_rental') ? 'motor_vehicle_rental' : 'equipment_rental',
                'is_trailer'  => ! empty($row['is_trailer']) ? 1 : 0,
                'active'      => 1,
            ]);
            $imported++;
        }
        return redirect()->to('/admin/equipment')->with('msg', "Imported {$imported} item(s) from CSV.");
    }
}
