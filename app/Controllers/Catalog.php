<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\EquipmentModel;

class Catalog extends BaseController
{
    public function index(): string
    {
        return view('catalog/index', [
            'title'       => 'Equipment & Tool Rental Catalog',
            'description' => 'Browse the full Weekend Tool Rentals fleet — tools, trailers, tractors, and attachments for rent in Bluffdale, UT. Live availability, day and week rates, online booking.',
            'categories'  => (new CategoryModel())->active(),
            'equipment'   => (new EquipmentModel())->listing(),
            'heading'     => 'All Equipment',
        ]);
    }

    public function category(string $slug): string
    {
        $cat = (new CategoryModel())->bySlug($slug);
        if (! $cat) {
            return $this->index();
        }
        return view('catalog/index', [
            'title'       => $cat['name'] . ' Rental',
            'description' => 'Rent ' . strtolower($cat['name']) . ' in Bluffdale, UT from Weekend Tool Rentals. Live availability, day and week rates, pickup or delivery.',
            'categories'  => (new CategoryModel())->active(),
            'equipment'   => (new EquipmentModel())->listing((int) $cat['id']),
            'heading'     => $cat['name'],
        ]);
    }

    public function search(): string
    {
        $term = trim((string) $this->request->getGet('q'));
        return view('catalog/index', [
            'title'      => $term !== '' ? 'Search: ' . $term : 'Search',
            'metaRobots' => 'noindex,follow',
            'categories' => (new CategoryModel())->active(),
            'equipment'  => $term !== '' ? (new EquipmentModel())->search($term) : [],
            'heading'    => 'Search results for "' . esc($term) . '"',
        ]);
    }

    public function show(int $id): string
    {
        $em = new EquipmentModel();
        $eq = $em->find($id);
        if (! $eq || ! $eq['active']) {
            return view('catalog/index', [
                'title' => 'Not found', 'categories' => (new CategoryModel())->active(),
                'equipment' => [], 'heading' => 'Equipment not found',
            ]);
        }
        $db    = db_connect();
        $media = $db->table('equipment_media')->where('equipment_id', $id)->orderBy('sort')->get()->getResultArray();

        // SEO: meta description from the item description, first image for OG/Product schema.
        $rawDesc  = trim(preg_replace('/\s+/', ' ', (string) ($eq['description'] ?? '')));
        $metaDesc = $rawDesc !== ''
            ? (mb_strlen($rawDesc) > 155 ? mb_substr($rawDesc, 0, 152) . '…' : $rawDesc)
            : ('Rent the ' . $eq['name'] . ' by the day or week in Bluffdale, UT from Weekend Tool Rentals.');
        $firstImg = '';
        foreach ($media as $m) {
            if (($m['kind'] ?? '') === 'image') { $firstImg = (string) $m['url']; break; }
        }
        $ogImg = $firstImg !== '' ? base_url(ltrim($firstImg, '/')) : base_url('logo.png');
        $price = number_format((float) $eq['daily_rate'], 2, '.', '');
        $jsonLd = array_filter([
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $eq['name'],
            'description' => $metaDesc,
            'image'       => $ogImg,
            'sku'         => $eq['sku'] ?? null,
            'offers'      => [
                '@type'              => 'Offer',
                'priceCurrency'      => 'USD',
                'price'              => $price,
                'availability'       => 'https://schema.org/InStock',
                'url'                => base_url('equipment/' . $id),
                'priceSpecification' => [
                    '@type'         => 'UnitPriceSpecification',
                    'price'         => $price,
                    'priceCurrency' => 'USD',
                    'unitCode'      => 'DAY',
                ],
            ],
        ]);

        return view('catalog/show', [
            'title'       => $eq['name'] . ' Rental',
            'description' => $metaDesc,
            'canonical'   => base_url('equipment/' . $id),
            'ogImage'     => $ogImg,
            'jsonLd'      => $jsonLd,
            'eq'          => $eq,
            'weekly'      => $em->weeklyRate($eq),
            'media'       => $media,
            'safety'      => $db->table('equipment_safety')->where('equipment_id', $id)->get()->getRowArray(),
            'trailer'     => ! empty($eq['requires_trailer_id']) ? $em->find((int) $eq['requires_trailer_id']) : null,
        ]);
    }
}
