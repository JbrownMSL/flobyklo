<?php

namespace App\Controllers;

use App\Services\Availability;
use App\Services\Cart as CartService;

class Cart extends BaseController
{
    public function index(): string
    {
        $cart = new CartService();
        $waiver = (bool) session()->get('wtr_waiver');
        return view('cart/index', [
            'title'   => 'Your Cart',
            'summary' => $cart->summary($waiver),
            'waiver'  => $waiver,
        ]);
    }

    public function add()
    {
        $cart = new CartService();
        $start = $this->request->getPost('start');
        $end   = $this->request->getPost('end');
        $eqId  = (int) $this->request->getPost('equipment_id');
        $qty   = max(1, (int) $this->request->getPost('qty'));

        if ($start && $end) {
            if (strtotime($end) < strtotime($start)) {
                return redirect()->back()->with('error', 'End date must be on or after the start date.');
            }
            $cart->setDates($start, $end);
        }
        [$cStart, $cEnd] = $cart->dates();
        if (! $cStart || ! $cEnd) {
            return redirect()->back()->with('error', 'Pick your rental dates first.');
        }
        if (! (new Availability())->isAvailable($eqId, $cStart, $cEnd, $qty)) {
            return redirect()->back()->with('error', 'Sorry — that item is not available for the selected dates.');
        }
        $cart->add($eqId, $qty);
        return redirect()->to('/cart')->with('msg', 'Added to cart.');
    }

    public function remove()
    {
        (new CartService())->remove((int) $this->request->getPost('equipment_id'));
        return redirect()->to('/cart');
    }

    public function toggleTrailer()
    {
        (new CartService())->toggleTrailer(
            (int) $this->request->getPost('parent_id'),
            (bool) $this->request->getPost('remove')
        );
        return redirect()->to('/cart');
    }

    public function toggleWaiver()
    {
        session()->set('wtr_waiver', (bool) $this->request->getPost('waiver'));
        return redirect()->to('/cart');
    }

    /** AJAX availability probe for the listing page datepicker. */
    public function checkAvailability()
    {
        $eqId  = (int) $this->request->getPost('equipment_id');
        $start = (string) $this->request->getPost('start');
        $end   = (string) $this->request->getPost('end');
        $units = ($start && $end) ? (new Availability())->unitsAvailable($eqId, $start, $end) : 0;
        return $this->response->setJSON(['available' => $units, 'ok' => $units > 0]);
    }
}
