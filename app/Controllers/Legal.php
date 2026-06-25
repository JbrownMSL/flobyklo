<?php

namespace App\Controllers;

use App\Models\ContractModel;

class Legal extends BaseController
{
    public function terms(): string
    {
        return view('legal/terms', ['title' => 'Rental Terms', 'contract' => (new ContractModel())->current()]);
    }

    public function privacy(): string
    {
        return view('legal/privacy', ['title' => 'Privacy Policy']);
    }

    public function faq(): string
    {
        return view('legal/faq', ['title' => 'FAQ']);
    }

    public function contact(): string
    {
        return view('legal/contact', ['title' => 'Contact Us']);
    }

    public function delivery(): string
    {
        return view('legal/delivery', ['title' => 'Request Delivery']);
    }

    public function deliverySubmit()
    {
        $f = fn ($k) => trim((string) $this->request->getPost($k));
        $name = $f('name'); $email = $f('email');
        if ($name === '' || $email === '') {
            return redirect()->back()->withInput()->with('error', 'Name and email are required.');
        }
        $lines = [
            'Name: ' . $name,
            'Email: ' . $email,
            'Phone: ' . $f('phone'),
            'Equipment wanted: ' . $f('equipment'),
            'Delivery location: ' . $f('location'),
            'Pickup/delivery date+time: ' . $f('pickup'),
            'Return/dropoff date+time: ' . $f('dropoff'),
            'Notes: ' . $f('notes'),
        ];
        $body = '<h3>Delivery quote request — Weekend Tool Rentals</h3><p>' . nl2br(esc(implode("\n", $lines))) . '</p>';
        db_connect()->table('contact_messages')->insert([
            'name' => $name, 'email' => $email,
            'message' => "DELIVERY REQUEST\n" . implode("\n", $lines),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        helper('wtr');
        wtr_send_email(config('Wtr')->businessEmail, 'Delivery quote request: ' . $name, $body, 'delivery');
        return redirect()->to('/delivery')->with('msg', 'Thanks — we\'ll review your delivery request and reply with a quote.');
    }

    public function contactSubmit()
    {
        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $message = trim((string) $this->request->getPost('message'));
        if ($name === '' || $email === '' || $message === '') {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }
        db_connect()->table('contact_messages')->insert([
            'name' => $name, 'email' => $email, 'message' => $message, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        helper('wtr');
        wtr_send_email(config('Wtr')->businessEmail, 'WTR contact form: ' . $name,
            '<p><b>From:</b> ' . esc($name) . ' &lt;' . esc($email) . '&gt;</p><p>' . nl2br(esc($message)) . '</p>', 'contact');
        return redirect()->to('/contact')->with('msg', 'Thanks — we\'ll get back to you soon.');
    }
}
