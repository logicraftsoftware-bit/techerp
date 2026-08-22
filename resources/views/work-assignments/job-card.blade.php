<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; }
        .outer { border: 0.75pt solid #000; }
        .header-table td { vertical-align: middle; padding: 6pt 8pt; }
        .company-name { font-size: 13pt; font-weight: bold; }
        .company-line { font-size: 8pt; margin-top: 1pt; }
        .logo { width: 34mm; height: auto; }
        .title-bar { border-top: 0.75pt solid #000; border-bottom: 0.75pt solid #000; padding: 4pt 8pt; font-size: 12pt; font-weight: bold; text-align: center; letter-spacing: 1pt; }
        .ref-bar td { padding: 3pt 8pt; font-size: 8.5pt; border-bottom: 0.75pt solid #000; }
        .section-title { background: #e5e5e5; font-weight: bold; padding: 3pt 8pt; font-size: 8.5pt; text-transform: uppercase; border-top: 0.75pt solid #000; border-bottom: 0.5pt solid #000; }
        .detail-table td { padding: 3pt 8pt; font-size: 8.5pt; vertical-align: top; border-bottom: 0.25pt solid #ccc; }
        .detail-table .label { width: 32%; color: #444; }
        .detail-table .value { font-weight: bold; }
        .half { width: 50%; vertical-align: top; }
        .half-divider { border-right: 0.75pt solid #000; }
        .blank-line { display: inline-block; border-bottom: 0.5pt solid #000; min-width: 60pt; }
        .items-table th, .items-table td { border: 0.5pt solid #000; padding: 4pt 5pt; font-size: 8pt; text-align: left; }
        .items-table th { background: #e5e5e5; }
        .totals-table td { padding: 3pt 8pt; font-size: 8.5pt; border-top: 0.5pt solid #000; }
        .totals-table .amount { text-align: right; font-weight: bold; }
        .remarks-box { border: 0.5pt solid #000; min-height: 45pt; padding: 4pt 6pt; font-size: 8.5pt; }
        .sign-table td { padding-top: 28pt; font-size: 8.5pt; }
        .sign-line { border-top: 0.75pt solid #000; padding-top: 3pt; width: 60%; }
    </style>
</head>
<body>
<div class="outer">
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="company-name">INDIAN HEALTHY & HYGIENIC HOME (IHHH)</div>
                <div class="company-line">GB 45 NARAYANTALA, BAGUIATI, 700059</div>
                <div class="company-line">Ph: 9330087940 / 9062571995 / 8670032150</div>
            </td>
            <td style="width: 30%; text-align: right;"><img class="logo" src="{{ $logo }}" alt="IHHH logo"></td>
        </tr>
    </table>

    <div class="title-bar">JOB CARD</div>

    <table class="ref-bar">
        <tr>
            <td style="width: 34%;"><b>Ref. No.:</b> {{ $assignment->assignment_code }}</td>
            <td style="width: 33%;"><b>Docket No.:</b> {{ $assignment->serviceRequest->request_code }}</td>
            <td style="width: 33%;"><b>Date:</b> {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="half half-divider" style="width: 50%;">
                <table>
                    <tr><td class="section-title" colspan="2">Customer Details</td></tr>
                </table>
                <table class="detail-table">
                    <tr><td class="label">Name</td><td class="value">{{ $assignment->serviceRequest->customer?->customer_name ?? 'Deleted customer' }}</td></tr>
                    <tr><td class="label">Address</td><td class="value">{{ $assignment->service_address }}</td></tr>
                    <tr><td class="label">City / State</td><td class="value">{{ $assignment->serviceRequest->city }}, {{ $assignment->serviceRequest->state }}</td></tr>
                    <tr><td class="label">Pin Code</td><td class="value">{{ $assignment->serviceRequest->pin_code }}</td></tr>
                    <tr><td class="label">Contact No.</td><td class="value">{{ $assignment->serviceRequest->contact_phone }}</td></tr>
                    <tr><td class="label">Model</td><td class="value">{{ $assignment->serviceRequest->machine?->machine_name ?? $assignment->serviceRequest->product_name ?: '—' }}</td></tr>
                    <tr><td class="label">Serial No.</td><td class="value">{{ $assignment->serviceRequest->serial_number ?: '—' }}</td></tr>
                    <tr><td class="label">Asset No.</td><td class="value">{{ $assignment->serviceRequest->asset_number ?: '—' }}</td></tr>
                </table>
            </td>
            <td class="half" style="width: 50%;">
                <table>
                    <tr><td class="section-title" colspan="2">Call Details</td></tr>
                </table>
                <table class="detail-table">
                    <tr><td class="label">Technician</td><td class="value">{{ $assignment->technician->name }}</td></tr>
                    <tr><td class="label">Call Type</td><td class="value">{{ str($assignment->serviceRequest->service_type)->replace('_', ' ')->title() }}</td></tr>
                    <tr><td class="label">Description</td><td class="value">{{ $assignment->serviceRequest->subject }}</td></tr>
                    <tr><td class="label">Coverage</td><td class="value">{{ $assignment->serviceRequest->amcPlans->pluck('plan_name')->join(', ') ?: '—' }}</td></tr>
                    <tr><td class="label">Priority</td><td class="value">{{ ucfirst($assignment->priority) }}</td></tr>
                    <tr><td class="label">Status</td><td class="value">{{ str($assignment->status)->replace('_', ' ')->title() }}</td></tr>
                    <tr><td class="label">Appointment</td><td class="value">{{ $assignment->scheduled_date->format('d/m/Y') }}, {{ date('g:i A', strtotime($assignment->start_time)) }} - {{ date('g:i A', strtotime($assignment->end_time)) }}</td></tr>
                    <tr><td class="label">Remarks</td><td class="value">{{ $assignment->work_instructions ?: '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="half half-divider" style="width: 50%;">
                <table>
                    <tr><td class="section-title" colspan="2">Visit Details</td></tr>
                </table>
                <table class="detail-table">
                    <tr><td class="label">Attend Date</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Time In</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Time Out</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Defects</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Task Done</td><td class="value">&nbsp;</td></tr>
                </table>
            </td>
            <td class="half" style="width: 50%;">
                <table>
                    <tr><td class="section-title" colspan="2">Job Details</td></tr>
                </table>
                <table class="detail-table">
                    <tr><td class="label">Call Status</td><td class="value">Completed / Cancelled / Pending</td></tr>
                    <tr><td class="label">Next Date</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Preferred Time</td><td class="value">&nbsp;</td></tr>
                    <tr><td class="label">Remarks</td><td class="value">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Inspection Details / Spares Installed</div>
    <table class="items-table">
        <tr>
            <th style="width: 12%;">Spare Code</th>
            <th style="width: 38%;">Spare Name</th>
            <th style="width: 10%;">Qty</th>
            <th style="width: 12%;">Return Qty</th>
            <th style="width: 13%;">Rate</th>
            <th style="width: 15%;">Amount</th>
        </tr>
        @forelse($jobParts as $jobPart)
            <tr>
                <td>{{ $jobPart->part?->part_code }}</td>
                <td>{{ $jobPart->part?->part_name }}</td>
                <td>{{ $jobPart->quantity }}</td>
                <td>&nbsp;</td>
                <td>{{ number_format((float) $jobPart->rate, 2) }}</td>
                <td>{{ number_format($jobPart->quantity * (float) $jobPart->rate, 2) }}</td>
            </tr>
        @empty
            @for($i = 0; $i < 3; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        @endforelse
    </table>

    <div class="section-title">Previous Visits / Status History</div>
    <table class="items-table">
        <tr>
            <th style="width: 20%;">Docket No.</th>
            <th style="width: 20%;">Docket Date</th>
            <th style="width: 35%;">Status Change</th>
            <th style="width: 25%;">Employee Name</th>
        </tr>
        @forelse($assignment->statusHistories as $history)
            <tr>
                <td>WSH-{{ $history->id }}</td>
                <td>{{ $history->created_at->format('d/m/Y') }}</td>
                <td>{{ $history->from_status ? str($history->from_status)->replace('_', ' ')->title().' -> ' : '' }}{{ str($history->to_status)->replace('_', ' ')->title() }}</td>
                <td>{{ $history->changedBy?->name ?? 'System' }}</td>
            </tr>
        @empty
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        @endforelse
    </table>

    <table>
        <tr>
            <td class="half half-divider" style="width: 58%; vertical-align: top;">
                <div class="section-title">Customer Remarks</div>
                <div class="remarks-box">&nbsp;</div>
            </td>
            <td class="half" style="width: 42%; vertical-align: top;">
                <table class="totals-table">
                    <tr><td>Total Spare Amount</td><td class="amount">{{ number_format($jobParts->sum(fn ($jp) => $jp->quantity * (float) $jp->rate), 2) }}</td></tr>
                    <tr><td>Call Charges</td><td class="amount">&nbsp;</td></tr>
                    <tr><td><b>Total Amount Payable</b></td><td class="amount">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td style="width: 50%;"><div class="sign-line">Customer's Signature</div></td>
            <td style="width: 50%;"><div class="sign-line">Technician's Signature</div></td>
        </tr>
    </table>
</div>
</body>
</html>
