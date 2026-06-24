{{-- POS receipt — designed for 80mm thermal printer width.

When the seller clicks "Print receipt" in the POS UI, the browser opens this
view in a new tab and triggers print. The CSS forces a narrow page width so
that on a typical thermal printer driver the receipt comes out cleanly with
no cropping. Auto-print fires once the document is loaded. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
        }

        .receipt {
            width: 80mm;
            padding: 6mm 4mm;
            box-sizing: border-box;
        }

        .center      { text-align: center; }
        .right       { text-align: right; }
        .bold        { font-weight: bold; }
        .small       { font-size: 10px; }
        .uppercase   { text-transform: uppercase; }

        .divider {
            border: 0;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.items th,
        table.items td {
            text-align: left;
            padding: 2px 0;
            vertical-align: top;
        }
        table.items td.qty,
        table.items td.amount,
        table.items th.qty,
        table.items th.amount {
            text-align: right;
            white-space: nowrap;
        }

        .totals .row {
            margin-top: 2px;
        }
        .totals .grand {
            font-size: 13px;
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .footer-note {
            margin-top: 8px;
            text-align: center;
            font-size: 11px;
        }

        @media screen {
            body {
                background: #f0f0f0;
                padding: 24px;
            }
            .receipt {
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 2px 12px rgba(0,0,0,.15);
            }
        }
    </style>
</head>
<body onload="window.print();">
<div class="receipt">

    <div class="center bold uppercase" style="font-size: 14px;">{{ $store->name }}</div>
    @if(!empty($store->address))
        <div class="center small">{{ $store->address }}</div>
    @endif
    <div class="center small">
        @if(!empty($store->city)){{ $store->city }}@endif{{-- --}}
        @if(!empty($store->state)), {{ $store->state }}@endif{{-- --}}
        @if(!empty($store->zipcode)) — {{ $store->zipcode }}@endif
    </div>
    @if(!empty($store->contact_number))
        <div class="center small">Tel: {{ $store->contact_number }}</div>
    @endif
    @if(!empty($store->tax_name) && !empty($store->tax_number))
        <div class="center small">{{ $store->tax_name }}: {{ $store->tax_number }}</div>
    @endif

    <hr class="divider">

    <div class="row small">
        <span>Order #</span><span>{{ $order->id }}</span>
    </div>
    <div class="row small">
        <span>Date</span><span>{{ $order->created_at->format('Y-m-d H:i') }}</span>
    </div>
    <div class="row small">
        <span>Customer</span>
        <span>{{ $customerName }}</span>
    </div>
    @if(!empty($customerMobile))
        <div class="row small">
            <span>Mobile</span><span>{{ $customerMobile }}</span>
        </div>
    @endif
    @php
        $paymentLabel = match ($order->payment_method) {
            'pos_upi'             => 'UPI',
            'cod'                 => 'Cash',
            'razorpayPayment'     => 'Razorpay',
            'stripePayment'       => 'Stripe',
            'paystackPayment'     => 'Paystack',
            'flutterwavePayment'  => 'Flutterwave',
            default               => strtoupper((string) $order->payment_method),
        };
        $splitCash = (float) ($order->pos_split_cash ?? 0);
        // Split-tender sale: cashier collected $splitCash in cash and rest online.
        $isSplit = $splitCash > 0 && $splitCash < (float) $order->final_total;
    @endphp
    <div class="row small">
        <span>Payment</span><span class="uppercase">{{ $isSplit ? 'Split' : $paymentLabel }} ({{ $order->payment_status }})</span>
    </div>
    @if($isSplit)
        <div class="row small">
            <span>&nbsp;&nbsp;Cash</span><span>{{ number_format($splitCash, 2) }}</span>
        </div>
        <div class="row small">
            <span>&nbsp;&nbsp;{{ $paymentLabel }}</span><span>{{ number_format((float) $order->final_total - $splitCash, 2) }}</span>
        </div>
    @endif

    <hr class="divider">

    <table class="items">
        <thead>
        <tr class="small">
            <th>Item</th>
            <th class="qty">Qty</th>
            <th class="amount">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>
                    {{ $item->title }}
                    @if(!empty($item->variant_title) && strcasecmp($item->variant_title, 'Default') !== 0)
                        <div class="small">{{ $item->variant_title }}</div>
                    @endif
                </td>
                <td class="qty">{{ rtrim(rtrim(number_format((float)$item->quantity, 2, '.', ''), '0'), '.') }}</td>
                <td class="amount">{{ number_format((float)$item->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <hr class="divider">

    @php
        // Tax breakdown by rate — items at the same % collapse into one row
        // so the customer sees "Tax @ 5% — ₹X" + "Tax @ 18% — ₹Y" instead
        // of an opaque "incl. tax" total. Skips 0% items.
        $byRate = [];
        foreach ($order->items as $i) {
            $rate = (float) $i->tax_percent;
            if ($rate <= 0) continue;
            $key = number_format($rate, ($rate == (int) $rate) ? 0 : 2, '.', '');
            $byRate[$key] = ($byRate[$key] ?? 0) + (float) $i->tax_amount;
        }
        ksort($byRate, SORT_NUMERIC);
        $taxTotal     = array_sum($byRate);
        $savingsTotal = (float) ($order->pos_savings ?? 0);

        // Subtotal exclusive-of-tax (what the items would have cost without
        // any tax) — clearer than showing the inclusive subtotal + a
        // separate tax row that confuses people.
        $subtotalExTax = max(0.0, (float) $order->subtotal - $taxTotal);
    @endphp
    <div class="totals">
        <div class="row"><span>Subtotal</span><span>{{ number_format($subtotalExTax, 2) }}</span></div>
        @foreach($byRate as $rate => $amount)
            <div class="row small"><span>&nbsp;&nbsp;Tax @ {{ $rate }}%</span><span>{{ number_format($amount, 2) }}</span></div>
        @endforeach
        @if($taxTotal > 0)
            <div class="row small" style="border-top: 1px dotted #999; padding-top:2px"><span>Total tax</span><span>{{ number_format($taxTotal, 2) }}</span></div>
        @endif
        @if($savingsTotal > 0)
            <div class="row small"><span>You saved (sale price)</span><span>-{{ number_format($savingsTotal, 2) }}</span></div>
        @endif
        @if((float)$order->promo_discount > 0)
            <div class="row"><span>Discount</span><span>-{{ number_format((float)$order->promo_discount, 2) }}</span></div>
        @endif
        <div class="row grand"><span>TOTAL</span><span>{{ number_format((float)$order->final_total, 2) }} {{ $order->currency_code }}</span></div>
    </div>

    <hr class="divider">

    <div class="footer-note">
        {{ $footerNote }}
    </div>

</div>
</body>
</html>
