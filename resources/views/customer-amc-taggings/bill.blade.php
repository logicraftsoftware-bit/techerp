<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bill AMC-{{ $record->id }} | IHHH Sales &amp; Service</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f5; color: #111; font: 10pt/1.35 Arial, sans-serif; }
        .toolbar, .bill { width: min(190mm, calc(100% - 24px)); margin: 12px auto; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; }
        .toolbar a, .toolbar button { font: inherit; color: #153d80; }
        .toolbar button { padding: 6px 16px; cursor: pointer; }
        .bill { background: #fff; border: 2px solid; overflow-wrap: anywhere; }
        .block { padding: 3mm; border-bottom: 1px solid; }
        header, h2, .notice, footer { text-align: center; }
        header img { width: 36mm; height: 16mm; object-fit: contain; }
        h1 { margin: 1mm 0; font-size: 19pt; }
        h2 { margin: 0 0 2mm; font-size: 11pt; text-transform: uppercase; }
        h3, p { margin: 0; }
        h3 { font-size: 10pt; }
        .notice { margin-bottom: 2mm; font-weight: bold; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1mm 5mm; }
        .meta { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .full { grid-column: 1 / -1; }
        dl { margin: 0; }
        dl > div { display: flex; gap: 2mm; align-items: baseline; }
        dt { flex: 0 0 auto; text-transform: uppercase; font-size: 9pt; }
        dd { margin: 0; flex: 1; min-width: 0; border-bottom: 1px dotted #777; font-weight: bold; }
        ul { margin: 1mm 0 2mm; padding-left: 5mm; }
        li + li { margin-top: 1mm; }
        .checks { padding-left: 5mm; margin: 0; font-weight: bold; }
        .checks li { margin: 0; }
        .signoff { margin-top: 2mm; text-align: right; font-weight: bold; }
        .payment { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 0; }
        .payment > div { padding: 3mm; }
        .payment > div + div { border-left: 1px solid; text-align: center; }
        .payment img { display: block; width: 31mm; height: 31mm; object-fit: contain; margin: 1mm auto 0; }
        footer { padding: 2mm; font-size: 9pt; }
        @media screen and (max-width: 580px) {
            .meta, dl.grid, .payment { grid-template-columns: minmax(0, 1fr); }
            .payment > div + div { border-left: 0; border-top: 1px solid; }
        }
        @media print {
            body { background: #fff; font-size: 9pt; line-height: 1.25; }
            .toolbar { display: none; }
            .bill { width: 100%; margin: 0; }
            .block, footer { break-inside: avoid; }
            .block, .payment > div { padding: 2mm 3mm; }
            .payment { padding: 0; }
            .grid { row-gap: .6mm; }
            .payment img { width: 29mm; height: 29mm; }
        }
    </style>
</head>
<body>
@php
    $customer = $record->customer;
    $machine = $record->machine;
    $plan = $record->amcPlan;
    $money = fn ($amount) => $amount === null ? '—' : '₹'.number_format((float) $amount, 2);
    $bank = array_filter(config('amc_billing.bank'));
    $qr = config('amc_billing.payment_qr_url');
@endphp
<nav class="toolbar" aria-label="Bill actions">
    <a href="{{ route('customer-amc-taggings.index') }}">Back to Customer-AMC Tagging</a>
    <button type="button" onclick="window.print()">Print</button>
</nav>
<main class="bill">
    <header class="block">
        <img src="{{ asset('images/fieldservice-logo.png') }}" alt="IHHH">
        <h1>IHHH SALES &amp; SERVICE</h1>
        <p>ADD - GB 45 NARAYANTALA (WEST) BAGUIATI,KOL - 700059</p>
        <p>PH - 8670032150/9330087940/9062571995</p>
    </header>

    <dl class="block grid meta">
        <div><dt>Doc No.</dt><dd>AMC-{{ $record->id }}</dd></div>
        <div><dt>Doc Date</dt><dd>{{ $record->created_at?->format('d-m-Y') ?? '—' }}</dd></div>
        <div><dt>Coverage</dt><dd>{{ str($record->customer_type)->replace('_', ' ')->title() }}</dd></div>
    </dl>

    <section class="block">
        <h2>AMC Billing &amp; Service Details</h2>
        @if($plan)
            <p class="notice">For free service, call our service center within the validity of your service period.</p>
        @endif
        <dl class="grid">
            <div><dt>Customer Name</dt><dd>{{ $customer?->customer_name ?? '—' }}</dd></div>
            <div><dt>Customer Code</dt><dd>{{ $customer?->customer_code ?? '—' }}</dd></div>
            <div class="full"><dt>Address</dt><dd>{{ implode(', ', array_filter([$customer?->address, $customer?->city, $customer?->state])) ?: '—' }}</dd></div>
            <div><dt>PIN Code</dt><dd>{{ $customer?->pin_code ?? '—' }}</dd></div>
            <div><dt>Contact No.</dt><dd>{{ implode(' / ', array_filter([$customer?->mobile, $customer?->alternate_mobile])) ?: '—' }}</dd></div>
            <div class="full"><dt>Product</dt><dd>{{ $machine?->machine_name ?? '—' }}</dd></div>
            <div><dt>Model</dt><dd>{{ $machine?->model ?: '—' }}</dd></div>
            <div><dt>Machine Code</dt><dd>{{ $machine?->machine_code ?? '—' }}</dd></div>
            <div><dt>Product Price</dt><dd>{{ $money($machine?->selling_price) }}</dd></div>
            <div><dt>AMC Plan</dt><dd>{{ $plan?->plan_name ?? '—' }}</dd></div>
            <div><dt>Start Date</dt><dd>{{ $record->start_date?->format('d-m-Y') ?? '—' }}</dd></div>
            <div><dt>End Date</dt><dd>{{ $record->end_date?->format('d-m-Y') ?? '—' }}</dd></div>
            <div><dt>Services Included</dt><dd>{{ $record->service_count ?? '—' }}</dd></div>
            <div><dt>AMC Plan Price</dt><dd>{{ $money($plan?->price) }}</dd></div>
            <div><dt>Amount Paid</dt><dd>{{ $money($record->paid_amount) }}</dd></div>
            <div><dt>Payment Method</dt><dd>{{ $record->payment_method ? str($record->payment_method)->replace('_', ' ')->title() : '—' }}</dd></div>
            @if($record->payment_collected_by)
                <div class="full"><dt>Collected By</dt><dd>{{ str($record->payment_collected_by)->title() }}</dd></div>
            @endif
            @if($record->payment_remarks)
                <div class="full"><dt>Payment Remarks</dt><dd>{{ $record->payment_remarks }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="block">
        <h2>Terms &amp; Conditions</h2>
        <h3>What is Covered</h3>
        <ul>
            <li><strong>Functional Spares (if under a parts-inclusive AMC):</strong> Covers functional spare parts and repair labor via Kutchina or authorized service centers.</li>
            <li><strong>Diagnosis &amp; Repairs:</strong> Complete diagnosis of mechanical or electrical components like fan motors and wiring issues.</li>
        </ul>
        <h3>What is Excluded</h3>
        <ul>
            <li><strong>Consumables &amp; Exterior Parts:</strong> Lamps, knobs, filters, glass panels, oil collectors, plastic/rubber parts, cords, and installation fittings are generally excluded unless explicitly stated.</li>
            <li><strong>External Damages:</strong> Does not cover damage caused by normal wear and tear, physical abuse, tampering, voltage fluctuations, power short circuits, or acts of God.</li>
            <li><strong>Third-Party Service:</strong> Repairs, modifications, or servicing carried out by unauthorized technicians or external personnel void the contract and warranty.</li>
        </ul>
    </section>

    <section class="block">
        <h2>Mandatory Checks During Agreement Period</h2>
        <ol class="grid checks">
            <li>Clean body (outside/inside)</li>
            <li>Clean oil channel</li>
            <li>Clean oil collectors</li>
            <li>Check all the functions of the machine</li>
        </ol>
        <p class="signoff">Thanks &amp; Regards<br>IHHH SALES &amp; SERVICE</p>
    </section>

    @if($bank || $qr)
        <section class="block payment">
            <div>
                @if($bank)
                    <h3>Bank Details</h3>
                    @foreach($bank as $label => $value)
                        <p><strong>{{ $label }}</strong> {{ $value }}</p>
                    @endforeach
                @endif
            </div>
            <div>
                @if($qr)
                    <h3>Scan This QR Code for Payment</h3>
                    <img src="{{ asset($qr) }}" alt="IHHH payment QR code">
                @endif
            </div>
        </section>
    @endif

    <footer><strong>Tuesday Closed</strong><p>This is a system-generated document. No signature is required.</p></footer>
</main>
@if($autoPrint)
    <script>
        window.addEventListener('load', () => window.print(), { once: true });
    </script>
@endif
</body>
</html>
