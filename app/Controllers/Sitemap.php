<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\EquipmentModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * XML sitemap at /sitemap.xml — home, catalog, every active category + item,
 * and the static info pages. Referenced from public/robots.txt; submit the URL
 * in Google Search Console once GSC is verified.
 */
class Sitemap extends BaseController
{
    public function index(): ResponseInterface
    {
        $urls = [
            ['loc' => base_url('/'),        'pri' => '1.0'],
            ['loc' => base_url('catalog'),  'pri' => '0.9'],
        ];
        foreach ((new CategoryModel())->active() as $c) {
            $urls[] = ['loc' => base_url('catalog/' . $c['slug']), 'pri' => '0.7'];
        }
        foreach ((new EquipmentModel())->listing() as $e) {
            $urls[] = ['loc' => base_url('equipment/' . $e['id']), 'pri' => '0.6'];
        }
        foreach (['terms', 'privacy', 'faq', 'contact', 'delivery'] as $p) {
            $urls[] = ['loc' => base_url($p), 'pri' => '0.3'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                  . '<priority>' . $u['pri'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        return $this->response->setContentType('application/xml')->setBody($xml);
    }
}
