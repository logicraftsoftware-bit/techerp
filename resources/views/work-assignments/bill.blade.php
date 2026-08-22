<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px; border: 1px solid #cbd5e1; text-align: left; }
        th { background: #f1f5f9; }
        .header td { border: 0; padding: 0 0 14px; }
        .logo { width: 120px; }
        .title { font-size: 20pt; font-weight: bold; text-align: right; }
        .muted { color: #64748b; font-size: 9pt; }
        .details { margin: 16px 0; }
        .details td { width: 50%; vertical-align: top; }
        .total td { font-size: 12pt; font-weight: bold; }
        .right { text-align: right; }
    </style>
</head>
<body>
<table class="header"><tr><td><img class="logo" src="{{ $logo }}" alt="IHHH"></td><td class="title">SERVICE BILL<div class="muted">{{ $assignment->assignment_code }}</div></td></tr></table>

<table class="details">
    <tr><td><b>Customer</b><br>{{ $assignment->serviceRequest->customer?->customer_name ?? 'Deleted customer' }}<br>{{ $assignment->serviceRequest->service_address }}<br>{{ $assignment->serviceRequest->contact_phone }}</td><td><b>Service Request</b><br>{{ $assignment->serviceRequest->request_code }}<br><b>Machine:</b> {{ $assignment->serviceRequest->machine?->machine_name ?? $assignment->serviceRequest->product_name }}<br><b>Technician:</b> {{ $assignment->technician->name }}<br><b>Completed:</b> {{ $assignment->completed_at?->format('d M Y') }}</td></tr>
</table>

<table>
    <thead><tr><th>Part</th><th class="right">Qty</th><th class="right">Rate</th><th class="right">Tax</th><th class="right">Amount</th></tr></thead>
    <tbody>
    @forelse($assignment->jobParts as $jobPart)
        @php($lineTotal = $jobPart->quantity * (float) $jobPart->rate * (1 + ((float) $jobPart->tax_percent / 100)))
        <tr><td>{{ $jobPart->part?->part_name ?? 'Part' }}</td><td class="right">{{ $jobPart->quantity }}</td><td class="right">₹{{ number_format((float) $jobPart->rate, 2) }}</td><td class="right">{{ number_format((float) $jobPart->tax_percent, 2) }}%</td><td class="right">₹{{ number_format($lineTotal, 2) }}</td></tr>
    @empty
        <tr><td colspan="5" style="text-align:center;color:#64748b">No chargeable parts recorded for this AMC service.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr class="total"><td colspan="4" class="right">Total Bill</td><td class="right">₹{{ number_format($assignment->bill_total, 2) }}</td></tr></tfoot>
</table>

<p class="muted" style="margin-top:18px">AMC plan: {{ $assignment->serviceRequest->amcPlans->pluck('plan_name')->join(', ') ?: '—' }}. Generated on {{ now()->format('d M Y, g:i A') }}.</p>
</body>
</html>
