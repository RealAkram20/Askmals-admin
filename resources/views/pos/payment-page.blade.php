<!DOCTYPE html>
{{-- Public POS payment page reached via the QR scan from a customer's
     phone. No auth — the {token} in the URL is the secret. Shows a
     gateway picker scoped to whichever gateways the platform admin has
     configured. --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pay {{ $store->name ?? '' }}</title>
    <link rel="icon" href="{{ $systemSettings['favicon'] ?? asset('logos/hyper-local-favicon.png') }}">
    <style>
        :root { --bg:#f5f7fb; --card:#fff; --muted:#6b7280; --line:#e5e7eb; --primary:#2563eb; --success:#16a34a; --danger:#dc2626; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: var(--bg); color: #111827; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width: 480px; margin: 0 auto; padding: 24px 16px 48px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.04); padding: 18px; margin-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .brand-logo { width: 44px; height: 44px; border-radius: 10px; background: linear-gradient(135deg, #eef4ff, #fff); display: inline-flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700; }
        .brand-name { font-weight: 600; font-size: 1.05rem; }
        .brand-sub  { color: var(--muted); font-size: .85rem; }
        .label { color: var(--muted); font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
        .amount { font-size: 2.2rem; font-weight: 700; line-height: 1.1; margin: 4px 0 8px; }
        .row { display: flex; align-items: baseline; gap: 8px; margin: 6px 0; }
        .row .name { flex: 1; }
        .row .meta { color: var(--muted); font-size: .8rem; }
        .row .qty { color: var(--muted); }
        .row .price { font-weight: 600; }

        .gateways { display: flex; flex-direction: column; gap: 10px; }
        .gateway-btn {
            display: flex; align-items: center; gap: 12px;
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            font-size: 1rem; font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: border-color .12s ease, transform .08s ease, box-shadow .12s ease;
            color: #111827;
        }
        .gateway-btn:hover:not(:disabled) { border-color: var(--primary); transform: translateY(-1px); box-shadow: 0 6px 14px -8px rgba(0,0,0,.15); }
        .gateway-btn:disabled { opacity: .55; cursor: wait; }
        .gateway-btn .gw-mark { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; color: #fff; flex: 0 0 auto; }
        .gateway-btn[data-gateway="razorpay"]    .gw-mark { background: #3395ff; }
        .gateway-btn[data-gateway="stripe"]      .gw-mark { background: #635bff; }
        .gateway-btn[data-gateway="paystack"]    .gw-mark { background: #00C3F7; }
        .gateway-btn[data-gateway="flutterwave"] .gw-mark { background: #f5a623; }
        .gateway-btn .gw-arrow { margin-left: auto; color: var(--muted); }

        .status-banner { padding: 14px; border-radius: 12px; font-weight: 500; margin-bottom: 12px; }
        .status-banner.is-success { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        .status-banner.is-error   { background: #fef2f2; color: var(--danger);  border: 1px solid #fecaca; }
        .status-banner.is-info    { background: #eff6ff; color: var(--primary); border: 1px solid #bfdbfe; }

        .footer-hint { text-align: center; color: var(--muted); font-size: .8rem; margin-top: 14px; }
        .icon-tick { width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: var(--success); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 8px; font-size: 2rem; }
        .center { text-align: center; }
    </style>
</head>
<body>
<div class="wrap">

    @php
        $isPaid    = $session->status === \App\Enums\Pos\PosPaymentSessionStatusEnum::PAID;
        $isExpired = $session->status === \App\Enums\Pos\PosPaymentSessionStatusEnum::EXPIRED;
        $isCancel  = $session->status === \App\Enums\Pos\PosPaymentSessionStatusEnum::CANCELLED;
        $isFailed  = $session->status === \App\Enums\Pos\PosPaymentSessionStatusEnum::FAILED;
        $isPending = $session->status === \App\Enums\Pos\PosPaymentSessionStatusEnum::PENDING;

        $sym = match(strtoupper($session->currency_code ?: 'INR')) {
            'INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦', 'GHS' => '₵', default => $session->currency_code . ' ',
        };
    @endphp

    @if($isPaid)
        <div class="card center">
            <div class="icon-tick">✓</div>
            <h2 style="margin:6px 0">Payment received</h2>
            <p style="color:var(--muted)">Order has been confirmed at the counter.</p>
        </div>
    @elseif($isExpired || $isCancel || $isFailed)
        <div class="card center">
            <h2 style="margin:6px 0">Payment {{ $session->status }}</h2>
            <p style="color:var(--muted)">
                @if($isExpired) This payment link has expired. Please ask the cashier for a new QR.
                @elseif($isCancel) The cashier cancelled this payment. Please ask for a new QR.
                @else
                    {{ $session->failure_reason ?: 'This payment could not be completed.' }}
                    Please ask the cashier to retry or use cash.
                @endif
            </p>
        </div>
    @else
        {{-- ── Pending: show order summary + gateway picker ── --}}
        <div class="card">
            <div class="brand">
                <div class="brand-logo">{{ strtoupper(substr($store->name ?? 'S', 0, 2)) }}</div>
                <div>
                    <div class="brand-name">{{ $store->name }}</div>
                    <div class="brand-sub">Secure payment</div>
                </div>
            </div>

            <div class="label">You are paying</div>
            <div class="amount">{{ $sym }}{{ number_format((float) $session->amount, 2) }}</div>

            @if(!empty($lineItems))
                <div style="margin-top:10px; border-top: 1px dashed var(--line); padding-top: 10px">
                    @foreach($lineItems as $li)
                        <div class="row">
                            <div class="name">
                                <div>{{ $li['title'] }} <span class="qty">× {{ $li['qty'] }}</span></div>
                                @if(!empty($li['variant']) || !empty($li['addons']))
                                    <div class="meta">
                                        @if(!empty($li['variant'])){{ $li['variant'] }}@endif{{-- --}}
                                        @if(!empty($li['variant']) && !empty($li['addons'])) · @endif{{-- --}}
                                        @if(!empty($li['addons'])){{ implode(', ', $li['addons']) }}@endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(($breakdown['tax'] ?? 0) > 0 || ($breakdown['savings'] ?? 0) > 0 || ($breakdown['discount'] ?? 0) > 0)
                <div style="margin-top:10px; border-top: 1px dashed var(--line); padding-top: 10px; font-size: .9rem">
                    {{-- Subtotal shown EX-TAX so "Tax @ 18% = ₹X" reads as a true 18% of
                         the subtotal line. Matches the receipt + cart sidebar + CFD. --}}
                    <div class="row"><div class="name" style="color:var(--muted)">Subtotal</div><div>{{ $sym }}{{ number_format($breakdown['subtotalExTax'] ?? max(0, ($breakdown['subtotal'] ?? 0) - ($breakdown['tax'] ?? 0)), 2) }}</div></div>
                    @if(!empty($breakdown['taxByRate']))
                        @foreach($breakdown['taxByRate'] as $rate => $amount)
                            <div class="row"><div class="name" style="color:var(--muted); font-size:.85rem">Tax @ {{ $rate }}%</div><div style="color:var(--muted); font-size:.85rem">{{ $sym }}{{ number_format($amount, 2) }}</div></div>
                        @endforeach
                    @elseif(($breakdown['tax'] ?? 0) > 0)
                        <div class="row"><div class="name" style="color:var(--muted); font-size:.85rem">Tax</div><div style="color:var(--muted); font-size:.85rem">{{ $sym }}{{ number_format($breakdown['tax'], 2) }}</div></div>
                    @endif
                    @if(($breakdown['savings'] ?? 0) > 0)
                        <div class="row"><div class="name" style="color:var(--success); font-size:.85rem">You saved</div><div style="color:var(--success); font-size:.85rem">−{{ $sym }}{{ number_format($breakdown['savings'], 2) }}</div></div>
                    @endif
                    @if(($breakdown['discount'] ?? 0) > 0)
                        <div class="row"><div class="name" style="color:var(--muted)">Discount</div><div style="color:var(--danger)">−{{ $sym }}{{ number_format($breakdown['discount'], 2) }}</div></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="card">
            <div id="payment-banner" class="status-banner is-info" style="display:none"></div>

            @if(empty($gateways))
                <div class="status-banner is-error">
                    No online payment methods are configured. Please ask the cashier to use cash.
                </div>
            @else
                <div class="label" style="margin-bottom:8px">Choose a payment method</div>
                <div class="gateways">
                    @foreach($gateways as $gw)
                        <button type="button" class="gateway-btn"
                                data-gateway="{{ $gw['name'] }}"
                                data-mode="{{ $gw['mode'] }}">
                            <span class="gw-mark">{{ strtoupper(substr($gw['label'], 0, 2)) }}</span>
                            <span>
                                <div>{{ $gw['label'] }}</div>
                                <div class="brand-sub">
                                    @if($gw['name'] === 'razorpay') UPI · Cards · Netbanking
                                    @elseif($gw['name'] === 'stripe') Cards · Wallets
                                    @elseif($gw['name'] === 'paystack') Cards · Bank · USSD
                                    @elseif($gw['name'] === 'flutterwave') Cards · Bank · Mobile money
                                    @endif
                                </div>
                            </span>
                            <span class="gw-arrow">→</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <p class="footer-hint">
                After payment, this page will confirm automatically.
                <br>You may close it once you see the success message.
            </p>
        </div>
    @endif

</div>

@if($isPending && !empty($gateways))
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const banner    = document.getElementById('payment-banner');
        const buttons   = document.querySelectorAll('.gateway-btn');
        const verifyUrl = @json($verifyUrl);
        const statusUrl = @json($statusUrl);
        const initUrlT  = @json($initUrlT);
        const merchant  = @json($store->name ?? 'POS');
        const symbol    = @json($sym);
        const amount    = @json((float) $session->amount);

        function setBanner(kind, html) {
            banner.className = 'status-banner is-' + kind;
            banner.style.display = 'block';
            banner.innerHTML = html;
        }
        function clearBanner() { banner.style.display = 'none'; banner.innerHTML = ''; }

        function disableAll(except) {
            buttons.forEach(b => { if (b !== except) b.disabled = true; });
        }
        function enableAll() {
            buttons.forEach(b => { b.disabled = false; });
        }

        async function initGateway(name) {
            const url = initUrlT.replace('__GW__', encodeURIComponent(name));
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: '{}',
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err?.message || 'Could not initialise payment.');
            }
            return (await res.json()).data;
        }

        // Razorpay (inline) — same pattern as before, just dispatched from
        // the gateway picker now.
        function openRazorpay(initData) {
            const opts = {
                key: initData.key,
                amount: initData.amount,
                currency: initData.currency,
                name: merchant,
                description: 'In-store purchase',
                order_id: initData.order_id,
                handler: function (response) { verifyAndComplete(response); },
                modal: { ondismiss: function () { enableAll(); clearBanner(); } },
                theme: { color: '#2563eb' },
            };
            const rzp = new Razorpay(opts);
            rzp.on('payment.failed', function (resp) {
                setBanner('error', 'Payment failed: ' + (resp.error?.description || 'Unknown error'));
                enableAll();
            });
            rzp.open();
        }

        async function verifyAndComplete(rzpResponse) {
            setBanner('info', 'Confirming your payment…');
            try {
                const res = await fetch(verifyUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        razorpay_payment_id: rzpResponse.razorpay_payment_id,
                        razorpay_order_id:   rzpResponse.razorpay_order_id,
                        razorpay_signature:  rzpResponse.razorpay_signature,
                    }),
                });
                const data = await res.json();
                if (data?.success) {
                    showSuccessAndStop();
                } else {
                    setBanner('error', data?.message || 'Could not confirm payment. Please show the cashier this screen.');
                    enableAll();
                }
            } catch (e) {
                setBanner('error', 'Network error. Please show the cashier this screen.');
                enableAll();
            }
        }

        function showSuccessAndStop() {
            document.body.innerHTML = `
                <div class="wrap">
                    <div class="card center">
                        <div class="icon-tick">✓</div>
                        <h2 style="margin:6px 0">Payment received</h2>
                        <p style="color:var(--muted)">Thank you. Your order has been confirmed at the counter.</p>
                    </div>
                </div>`;
        }

        // Click handler — dispatches per gateway.
        buttons.forEach(b => b.addEventListener('click', async function () {
            const name = b.dataset.gateway;
            const mode = b.dataset.mode;
            disableAll(b);
            setBanner('info', `Connecting to ${b.querySelector('div')?.textContent || name}…`);

            try {
                const initData = await initGateway(name);
                if (mode === 'inline' && name === 'razorpay') {
                    clearBanner();
                    openRazorpay(initData);
                } else if (mode === 'redirect' && initData?.url) {
                    // Stripe Checkout / Paystack auth_url / Flutterwave hosted page
                    window.location.href = initData.url;
                } else {
                    setBanner('error', 'Could not start payment for this method.');
                    enableAll();
                }
            } catch (err) {
                setBanner('error', err.message || 'Could not start payment.');
                enableAll();
            }
        }));

        // Background poll: catches webhook backstops in case the customer
        // closed the page after paying.
        setInterval(async () => {
            try {
                const r = await fetch(statusUrl, { credentials: 'same-origin' });
                const j = await r.json();
                const st = j.data?.status;
                if (st && st !== 'pending') {
                    if (st === 'paid') showSuccessAndStop();
                    else location.reload();
                }
            } catch (_) {}
        }, 6000);
    })();
    </script>
@endif

</body>
</html>
