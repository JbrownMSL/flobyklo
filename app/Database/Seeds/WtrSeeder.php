<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds tax classes, the real v1 rental packet, and categories.
 * Idempotent: skips rows that already exist.
 * Run: php spark db:seed WtrSeeder   (then InventorySeeder for equipment)
 */
class WtrSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // --- tax rates (PLACEHOLDER rates — Andrew/accountant to confirm) -----
        $taxes = [
            ['tax_class' => 'equipment_rental',     'label' => 'Equipment Rental Tax',                       'rate' => 0.0745],
            ['tax_class' => 'motor_vehicle_rental', 'label' => 'Utah Motor-Vehicle Rental Tax (trailers)',   'rate' => 0.1250],
        ];
        foreach ($taxes as $t) {
            if (! $this->db->table('tax_rates')->where('tax_class', $t['tax_class'])->countAllResults()) {
                $this->db->table('tax_rates')->insert($t + ['active' => 1]);
            }
        }

        // --- categories (aligned to the sheet's two sections + tool types) ----
        $cats = ['Heavy Equipment & Trailers', 'Tractor Attachments (3-Point)', 'Skid Steer Attachments', 'Saws & Woodworking', 'Heaters', 'Concrete', 'Metalworking', 'Other Tools'];
        $sort = 0;
        foreach ($cats as $name) {
            $slug = url_title($name, '-', true);
            if (! $this->db->table('categories')->where('slug', $slug)->countAllResults()) {
                $this->db->table('categories')->insert(['name' => $name, 'slug' => $slug, 'sort' => $sort++, 'active' => 1]);
            }
        }

        // --- v1 rental packet (real text from Andrew's RENTAL PACKET 2026-08) -
        if (! $this->db->table('contracts')->where('version', 'v1')->countAllResults()) {
            $this->db->table('contracts')->insert([
                'version'      => 'v1',
                'title'        => 'Weekend Tool Rentals LLC — Rental Packet',
                'body_html'    => $this->packetHtml(),
                'effective_at' => $now,
                'active'       => 1,
            ]);
        }
    }

    private function packetHtml(): string
    {
        return <<<'HTML'
<p><strong>Pickup/Dropoff Location:</strong> 2313 W Mountainside Circle, Bluffdale, UT 84065<br>
<strong>Contact:</strong> Andrew Brown · 801-979-6027 · ajbfrms@gmail.com</p>

<h3>Section 1: Equipment Rental Agreement</h3>
<p>This section outlines the terms under which equipment is leased to the undersigned Lessee. Minimum rental periods and billing increments vary by item, and weekends and off-hours are billable. Lessee agrees to pay 25% of the daily rate for each hour the equipment is retained beyond the agreed rental term, plus an additional $100 late return fee. Cancellations or no-shows result in forfeiture of the rental deposit. A refundable security deposit of $300 for equipment valued under $10,000 and $500 for equipment valued at $10,000 or more is required, which may be applied to unpaid charges, damages, or fees. Security deposits will be refunded to the original payment method within 5 business days of return, pending inspection (2–7 days to appear depending on bank). Lessee remains liable for all amounts due in excess of the deposit. Lessee is responsible for basic maintenance such as cleaning and lubrication but may not perform repairs unless qualified and authorized by WTR. Lessee accepts full responsibility for all damages, loss, third-party injuries, or property damage resulting from use of the equipment, and must use the correct fuel. This Agreement is governed by Utah law, venue in Salt Lake County. Lessor retains ownership of all equipment at all times. Lessee may not assign, sublet, transfer, pledge, or encumber the equipment. In the event of default, Lessor may repossess without notice, accelerate all remaining charges, and recover damages, attorney's fees, interest, and collection costs. Each rental includes up to eight (8) engine hours per rental day; overages billed at (Daily Rate ÷ 8) × Hours Over. Faxed, scanned, photocopied, or electronic signatures shall be treated as originals and fully enforceable.</p>

<h3>Section 2: Liability Waiver and Release</h3>
<p>Lessee acknowledges that the use and operation of construction, landscaping, and heavy equipment involves inherent risks, including personal injury, death, and property damage, and voluntarily assumes all such risks and releases Weekend Tool Rentals LLC (WTR), its members, agents, successors, and assigns from any and all claims, including those resulting from the negligence of WTR. <strong>Insurance Disclaimer:</strong> WTR does not provide insurance coverage for rental equipment, trailers, or transport. Lessee must provide and maintain primary commercial general liability and property-damage insurance during the rental period and, prior to release, provide a valid Certificate of Insurance naming WTR as Additional Insured and Loss Payee. If Lessee does not provide a COI, Lessee is deemed to self-insure and assumes full financial responsibility for all loss, theft, damage, liability, or injury. WTR may require an increased deposit or card authorization as a condition of self-insurance. <strong>Indemnity:</strong> Lessee agrees to indemnify, defend, and hold harmless WTR from all liability arising from misuse, unauthorized or impaired operation, or damage to third parties. Lessee waives all express and implied warranties and remedies under UCC Article 2A to the maximum extent permitted by law.</p>

<h3>Section 3: Safety Acknowledgment</h3>
<p>Lessee affirms they have received any desired in-person training, that manufacturer safety manuals are available online and should be consulted, and that it is their sole responsibility to operate safely and wear appropriate PPE (gloves, boots, glasses, fall protection where applicable). Lessee will prevent operation by anyone impaired or under 18, will inspect equipment before use and report defects immediately, and is responsible for compliance with all laws and permits. For any digging, Lessee must call 811 before commencing work.</p>

<h3>Section 4: Equipment Inspection &amp; Condition Checklist</h3>
<p>Condition (visible damage, fuel level, engine hours, photos) is documented before and after the rental. Lessee agrees to inspect the equipment prior to use and upon return.</p>

<h3>Section 5: Credit Card Authorization</h3>
<p>Lessee authorizes WTR to charge their card via Square for all rental-related transactions (rental fees, late/overtime, cleaning, refueling, delivery/pickup, damage, and other authorized charges), and to retain card information securely on file through Square for the rental period and up to fourteen (14) days after return, to charge for fees discovered on inspection. WTR does not store or access full card numbers; all payment data is handled through Square's PCI-compliant platform.</p>

<h3>Section 6: Equipment Information &amp; Pricing</h3>
<p>Equipment, rental periods, rates, and deposits are specified at the time of rental. All rates are subject to applicable sales tax and are per calendar day unless otherwise agreed in writing. Additional charges may include a Cleaning Fee ($75 if returned dirty), Refueling Fee ($6/gallon), Fuel Prepay, and Delivery &amp; Pickup Fee (each way). Lessee shall pay all applicable taxes.</p>

<h3>Acknowledgment &amp; Signature</h3>
<p>By signing, Lessee certifies they have read, understood, and agree to all terms in this Rental Packet — the Equipment Rental Agreement, Liability Waiver and Release, Safety Acknowledgment, Equipment Inspection Checklist, and Credit Card Authorization — voluntarily and with full knowledge of its legal effect. Lessee waives consequential, special, and incidental damages, waives the right to a jury trial, and waives participation in any class or representative action. Under Utah Code §78B-6-1001, theft or criminal conversion of rental property may subject Lessee to treble damages, attorney's fees, and costs. Lessee affirms all operators are at least 18 and will present a valid driver's license and second ID at pickup.</p>
HTML;
    }
}
