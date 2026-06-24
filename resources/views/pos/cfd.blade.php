<!DOCTYPE html>
{{-- Customer-Facing Display.

     The cashier opens this in a fresh tab and drags it to the second screen
     facing the customer. It polls the cashier's live cart and renders it
     in big, customer-friendly type. No auth — the {token} is the secret.
     Token is rotated per cashier session, ephemeral via cache. --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Customer Display</title>
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --line:#334155; --muted:#94a3b8; --primary:#3b82f6; --success:#10b981; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: var(--bg); color: #fff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; }
        .wrap { display: grid; grid-template-rows: auto 1fr auto; min-height: 100vh; }
        header { padding: 24px 32px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-mark { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; }
        .brand-name { font-size: 1.5rem; font-weight: 700; }
        .brand-sub  { color: var(--muted); font-size: .95rem; }
        .conn-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--muted); display: inline-block; margin-right: 8px; }
        .conn-dot.live { background: var(--success); box-shadow: 0 0 8px rgba(16,185,129,.6); }
        .conn-label { color: var(--muted); font-size: .85rem; }

        main { padding: 24px 32px; overflow: auto; display: flex; flex-direction: column; gap: 14px; }
        .empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; gap: 16px; color: var(--muted); }
        .empty .icon-large { width: 96px; height: 96px; opacity: .35; }
        .empty h2 { color: #fff; font-weight: 600; font-size: 2rem; margin: 0; }
        .empty p { font-size: 1.05rem; margin: 0; }

        .row { display: grid; grid-template-columns: 1fr auto auto; align-items: baseline; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--line); }
        .row .name { font-size: 1.4rem; font-weight: 600; }
        .row .meta { color: var(--muted); font-size: .9rem; margin-top: 2px; }
        .row .qty { color: var(--muted); font-size: 1.1rem; min-width: 64px; text-align: right; }
        .row .amt { font-size: 1.4rem; font-weight: 600; min-width: 130px; text-align: right; }

        footer { padding: 24px 32px; border-top: 1px solid var(--line); background: rgba(255,255,255,.02); }
        .breakdown { display: flex; flex-direction: column; gap: 4px; max-width: 420px; margin-left: auto; color: var(--muted); font-size: 1rem; }
        .breakdown .line { display: flex; justify-content: space-between; }
        .breakdown .line.savings { color: var(--success); }
        .total-line { display: flex; justify-content: space-between; align-items: baseline; margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--line); }
        .total-line .label { font-size: 1.05rem; color: var(--muted); }
        .total-line .amt { font-size: 3rem; font-weight: 800; color: #fff; }

        @media (min-width: 768px) {
            .empty h2 { font-size: 2.6rem; }
            .row .name { font-size: 1.6rem; }
            .row .amt { font-size: 1.6rem; }
            .total-line .amt { font-size: 3.6rem; }
        }
    </style>
</head>
<body>
<div class="wrap">

    <header>
        <div class="brand">
            <div class="brand-mark" id="cfd-brand-mark">·</div>
            <div>
                <div class="brand-name" id="cfd-store-name">Welcome</div>
                <div class="brand-sub" id="cfd-store-sub">Please wait at the counter</div>
            </div>
        </div>
        <div>
            <span class="conn-dot" id="cfd-conn-dot"></span><span class="conn-label" id="cfd-conn-label">connecting…</span>
        </div>
    </header>

    <main id="cfd-main">
        <div class="empty" id="cfd-empty">
            <svg class="icon-large" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                <path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>
            </svg>
            <h2>Ready when you are</h2>
            <p>Your bill will appear here as the cashier rings it up.</p>
        </div>
        <div id="cfd-rows" style="display:none"></div>
    </main>

    <footer style="display:none" id="cfd-footer">
        <div class="breakdown" id="cfd-breakdown"></div>
        <div class="total-line">
            <span class="label">Total</span>
            <span class="amt" id="cfd-total">—</span>
        </div>
    </footer>

</div>

<script>
(function () {
    const pullUrl = @json($pullUrl);
    const $main      = document.getElementById('cfd-main');
    const $empty     = document.getElementById('cfd-empty');
    const $rows      = document.getElementById('cfd-rows');
    const $footer    = document.getElementById('cfd-footer');
    const $breakdown = document.getElementById('cfd-breakdown');
    const $total     = document.getElementById('cfd-total');
    const $brandMark = document.getElementById('cfd-brand-mark');
    const $storeName = document.getElementById('cfd-store-name');
    const $storeSub  = document.getElementById('cfd-store-sub');
    const $connDot   = document.getElementById('cfd-conn-dot');
    const $connLabel = document.getElementById('cfd-conn-label');

    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function money(n, sym) {
        const s = (sym || '').toString();
        return s + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtInitials(t) {
        const w = String(t || '').trim().split(/\s+/).slice(0, 2);
        return (w.map(x => x.charAt(0).toUpperCase()).join('') || '·').slice(0, 2);
    }

    function showEmpty() {
        $empty.style.display = '';
        $rows.style.display  = 'none';
        $footer.style.display = 'none';
    }
    function showCart(state) {
        const sym = state.currency_symbol || '';
        const items = Array.isArray(state.items) ? state.items : [];
        if (!items.length) { showEmpty(); return; }

        $empty.style.display = 'none';
        $rows.style.display  = '';
        $footer.style.display = '';

        $storeName.textContent = state.store_name || $storeName.textContent;
        $storeSub.textContent  = 'Order in progress';
        $brandMark.textContent = fmtInitials(state.store_name);

        $rows.innerHTML = items.map(it => {
            const meta = [it.variant, (it.addons || []).join(', ')].filter(Boolean).join(' · ');
            return `
                <div class="row">
                    <div>
                        <div class="name">${escapeHtml(it.title)}</div>
                        ${meta ? `<div class="meta">${escapeHtml(meta)}</div>` : ''}
                    </div>
                    <div class="qty">× ${Number(it.qty || 1)}</div>
                    <div class="amt">${money(it.line_total, sym)}</div>
                </div>`;
        }).join('');

        const b = state.breakdown || {};
        const lines = [];
        // Match receipt + cart: ex-tax subtotal so Tax @ X% reads as a true
        // percentage of the subtotal line. Falls back to b.subtotal for
        // older POS pushes that don't include subtotalExTax.
        const sub = (typeof b.subtotalExTax === 'number') ? b.subtotalExTax : b.subtotal;
        lines.push(`<div class="line"><span>Subtotal</span><span>${money(sub, sym)}</span></div>`);
        // Per-rate tax rows so the customer sees exactly what tax is baked
        // into the (inclusive-priced) subtotal — "Tax @ 5%: ₹X" instead of an
        // opaque "incl. tax" line. Falls back to a combined line if the
        // cashier's POS hasn't yet pushed taxByRate (older snapshots).
        if (Array.isArray(b.taxByRate) && b.taxByRate.length) {
            for (const t of b.taxByRate) {
                lines.push(`<div class="line"><span>Tax @ ${escapeHtml(t.rate)}%</span><span>${money(t.amount, sym)}</span></div>`);
            }
        } else if (b.tax > 0) {
            lines.push(`<div class="line"><span>incl. tax</span><span>${money(b.tax, sym)}</span></div>`);
        }
        if (b.savings > 0)        lines.push(`<div class="line savings"><span>You saved</span><span>−${money(b.savings, sym)}</span></div>`);
        if (b.discountAmount > 0) lines.push(`<div class="line"><span>Discount</span><span>−${money(b.discountAmount, sym)}</span></div>`);
        if (b.promoAmount > 0)    lines.push(`<div class="line"><span>Promo${b.promoCode ? ' · ' + escapeHtml(b.promoCode) : ''}</span><span>−${money(b.promoAmount, sym)}</span></div>`);
        if (b.walletApplied > 0)  lines.push(`<div class="line"><span>Wallet</span><span>−${money(b.walletApplied, sym)}</span></div>`);
        $breakdown.innerHTML = lines.join('');
        $total.textContent = money(b.total ?? 0, sym);
    }

    function setLive(on) {
        $connDot.classList.toggle('live', on);
        $connLabel.textContent = on ? 'live' : 'reconnecting…';
    }

    let lastPushedAt = null;
    let stalenessTicker = null;

    function startStalenessTicker() {
        // If we don't get a fresh push within 30s, fade the connection dot
        // — helps the cashier notice if their POS tab crashed.
        clearInterval(stalenessTicker);
        stalenessTicker = setInterval(() => {
            if (!lastPushedAt) return;
            const ageSec = (Date.now() - lastPushedAt.getTime()) / 1000;
            if (ageSec > 30) setLive(false);
        }, 5000);
    }

    async function poll() {
        try {
            const res  = await fetch(pullUrl, { cache: 'no-store' });
            const data = await res.json();
            if (data.active && data.state) {
                lastPushedAt = data.state.__pushed_at ? new Date(data.state.__pushed_at) : new Date();
                setLive(true);
                showCart(data.state);
            } else {
                setLive(false);
                showEmpty();
            }
        } catch {
            setLive(false);
        }
    }

    poll();
    setInterval(poll, 1500);
    startStalenessTicker();
})();
</script>
</body>
</html>
