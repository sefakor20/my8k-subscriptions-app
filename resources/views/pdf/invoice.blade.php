<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        .company-details {
            color: #666;
            font-size: 11px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        .invoice-meta {
            font-size: 11px;
            color: #666;
        }
        .invoice-meta strong {
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-issued {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-void {
            background-color: #f8d7da;
            color: #721c24;
        }
        .billing-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .billing-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .billing-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .billing-name {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .billing-details {
            font-size: 11px;
            color: #666;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
        }
        table.items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        table.items .item-description {
            font-weight: 500;
            color: #1a1a1a;
        }
        table.items .item-details {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        table.items .text-right {
            text-align: right;
        }
        table.items .text-center {
            text-align: center;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .totals-label {
            display: table-cell;
            width: 50%;
            text-align: left;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-weight: 500;
        }
        .totals-row.total {
            border-top: 2px solid #1a1a1a;
            margin-top: 8px;
            padding-top: 12px;
        }
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .footer p {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $invoice->company_details['name'] ?? config('invoice.company.name') }}</div>
            <div class="company-details">
                @if(!empty($invoice->company_details['address']))
                    {{ $invoice->company_details['address'] }}<br>
                @endif
                @if(!empty($invoice->company_details['city']))
                    {{ $invoice->company_details['city'] }}
                    @if(!empty($invoice->company_details['country']))
                        , {{ $invoice->company_details['country'] }}
                    @endif
                    <br>
                @endif
                @if(!empty($invoice->company_details['phone']))
                    Phone: {{ $invoice->company_details['phone'] }}<br>
                @endif
                @if(!empty($invoice->company_details['email']))
                    Email: {{ $invoice->company_details['email'] }}<br>
                @endif
                @if(!empty($invoice->company_details['website']))
                    {{ $invoice->company_details['website'] }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-meta">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Issue Date:</strong> {{ $invoice->issued_at?->format('F j, Y') ?? now()->format('F j, Y') }}<br>
                @if($invoice->paid_at)
                    <strong>Payment Date:</strong> {{ $invoice->paid_at->format('F j, Y') }}
                @endif
            </div>
            <div class="status-badge status-{{ $invoice->status->value }}">
                {{ $invoice->status->label() }}
            </div>
        </div>
    </div>

    {{-- Billing Section --}}
    <div class="billing-section">
        <div class="billing-box">
            <div class="billing-label">Bill To</div>
            <div class="billing-name">{{ $invoice->customer_details['name'] ?? 'Customer' }}</div>
            <div class="billing-details">
                {{ $invoice->customer_details['email'] ?? '' }}
                @if(!empty($invoice->customer_details['address']))
                    <br>{{ $invoice->customer_details['address'] }}
                @endif
            </div>
        </div>
        <div class="billing-box">
            {{-- Can add shipping or other info here --}}
        </div>
    </div>

    {{-- Line Items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-center" style="width: 15%;">Qty</th>
                <th class="text-right" style="width: 17.5%;">Unit Price</th>
                <th class="text-right" style="width: 17.5%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->line_items as $item)
                <tr>
                    <td>
                        <div class="item-description">{{ $item['description'] }}</div>
                        @if(!empty($item['details']))
                            <div class="item-details">{{ $item['details'] }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2) }} {{ $invoice->currency }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-row">
            <div class="totals-label">Subtotal</div>
            <div class="totals-value">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</div>
        </div>
        @if($invoice->tax_amount > 0)
            <div class="totals-row">
                <div class="totals-label">Tax</div>
                <div class="totals-value">{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</div>
            </div>
        @endif
        <div class="totals-row total">
            <div class="totals-label">Total</div>
            <div class="totals-value">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>If you have any questions about this invoice, please contact us at {{ $invoice->company_details['email'] ?? config('invoice.company.email') }}</p>
        @if(!empty($invoice->company_details['vat_number']))
            <p>VAT Number: {{ $invoice->company_details['vat_number'] }}</p>
        @endif
    </div>
</body>
</html>
