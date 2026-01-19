<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Hello {{ $user->name }},

Thank you for your payment! Please find your invoice attached to this email.

## Invoice Details

- **Invoice Number:** {{ $invoice->invoice_number }}
- **Issue Date:** {{ $invoice->issued_at->format('F j, Y') }}
- **Status:** {{ $invoice->status->label() }}

## Payment Summary

@foreach($invoice->line_items as $item)
- {{ $item['description'] }}: {{ number_format($item['amount'], 2) }} {{ $invoice->currency }}
@endforeach

@if($invoice->tax_amount > 0)
- **Tax:** {{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}
@endif

**Total Paid:** {{ $invoice->formattedTotal() }}

<x-mail::button :url="config('app.url') . '/invoices'">
View All Invoices
</x-mail::button>

If you have any questions about this invoice, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
