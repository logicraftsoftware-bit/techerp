<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body { font-family: DejaVu Sans, sans-serif; color: #334155; font-size: 9px; } h1 { font-size: 20px; color: #0f172a; margin-bottom: 4px; } .meta { color: #64748b; margin-bottom: 15px; } table { width: 100%; border-collapse: collapse; } th { background: #eff6ff; color: #1e40af; text-align: left; } td,th { padding: 7px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; overflow-wrap: break-word; } tr { page-break-inside: avoid; } .muted { color: #64748b; font-size: 8px; }
</style></head><body>
<h1>Customer Coverage Report</h1><div class="meta">Generated: {{ now()->format('d M Y H:i') }} | {{ $records->count() }} matching records<br>
@foreach($filters as $key => $value)@if($key !== 'export' && $value){{ str($key)->replace('_', ' ')->title() }}: {{ $value }} &nbsp; @endif @endforeach
<br>Date range applies to tagging creation date. Area refers to customer city.</div>
<table><thead><tr><th>Customer / Area</th><th>Coverage</th><th>Machine / Model</th><th>AMC Plan</th><th>Price (INR)</th><th>Services</th><th>Payment</th><th>Start / End</th></tr></thead><tbody>
@forelse($records as $record)<tr>
<td>{{ $record->customer->customer_name }}<br><span class="muted">{{ $record->customer->customer_code }}<br>{{ $record->customer->city }}<br>{{ $record->customer->mobile }}</span></td>
<td>{{ $record->customer_type === 'amc' ? 'AMC' : ($record->customer_type === 'in_warranty' ? 'In Warranty' : 'Out Of Warranty') }}</td>
<td>{{ $record->machine->machine_name }}<br><span class="muted">{{ $record->machine->model }}<br>{{ $record->machine->machine_code }}</span></td>
<td>{{ $record->amcPlan?->plan_name ?? '-' }}</td><td>{{ $record->amcPlan ? number_format((float) $record->amcPlan->price, 2) : '-' }}</td><td>{{ $record->service_count }}</td>
<td>{{ $record->payment_collected_by ? str($record->payment_collected_by)->title() : '-' }}<br>{{ $record->paid_amount !== null ? number_format((float) $record->paid_amount, 2) : '-' }}<br>{{ $record->payment_method }}</td>
<td>{{ $record->start_date?->format('d M Y') ?? '-' }}<br>{{ $record->end_date?->format('d M Y') ?? '-' }}</td>
</tr>@empty<tr><td colspan="8">No matching records.</td></tr>@endforelse
</tbody></table></body></html>
