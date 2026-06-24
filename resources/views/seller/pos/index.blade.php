@extends('layouts.seller.app', [
    'page' => $menuSeller['pos']['active'] ?? '',
    'sub_page' => $menuSeller['pos']['route']['pos_overview']['sub_active'] ?? '',
])

@section('title', __('labels.pos'))

@section('header_data')
    @php
        $page_title = __('labels.pos');
        $page_pretitle = __('labels.seller') . ' ' . __('labels.pos');
    @endphp
@endsection

@section('seller-content')
    <div class="container-xl pos-shell">

        @if($stores->isEmpty())
            <div class="empty">
                <div class="empty-img">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/>
                        <path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/>
                    </svg>
                </div>
                <p class="empty-title">No approved store yet</p>
                <p class="empty-subtitle text-secondary">POS will be available once at least one of your stores is approved and visible.</p>
            </div>
        @else
            @php $defaultStore = $stores->first(); @endphp

            {{-- ── Sticky toolbar ── --}}
            <div class="pos-toolbar mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3 col-lg-3">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/></svg>
                            </span>
                            <select id="pos-store-select" class="form-select ps-5" aria-label="Store">
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" data-currency="{{ $store->currency_code }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M21 21l-6 -6"/><path d="M10 17a7 7 0 1 0 0 -14a7 7 0 0 0 0 14"/></svg>
                            </span>
                            <input type="search" id="pos-search-input" class="form-control ps-5 pe-5"
                                   placeholder="Search products by title…" autocomplete="off" aria-label="Search products">
                            <button type="button" class="pos-input-clear d-none" data-target="pos-search-input" title="Clear search" aria-label="Clear search">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M4 7v-2a1 1 0 0 1 1 -1h2"/><path d="M4 17v2a1 1 0 0 0 1 1h2"/><path d="M16 4h2a1 1 0 0 1 1 1v2"/><path d="M16 20h2a1 1 0 0 0 1 -1v-2"/><path d="M5 11h1v2h-1z" fill="currentColor"/><path d="M10 11l0 2"/><path d="M14 11h1v2h-1z" fill="currentColor"/><path d="M19 11l0 2"/></svg>
                            </span>
                            <input type="text" id="pos-barcode-input" class="form-control ps-5 pe-5"
                                   placeholder="Scan barcode…" autocomplete="off" aria-label="Scan barcode"
                                   inputmode="numeric">
                            <button type="button" class="pos-input-clear d-none" data-target="pos-barcode-input" title="Clear" aria-label="Clear">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-2 d-flex justify-content-end gap-1 pos-action-bar">
                        {{-- Secondary actions (Held / Recent / Display) live in a single
                             dropdown so 4 buttons don't crowd the toolbar on 13–14" laptops.
                             IDs are preserved so existing JS click handlers keep working. --}}
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="More actions" aria-label="More actions">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
                                <span class="badge bg-warning text-dark ms-1 d-none" id="pos-held-count">0</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" id="pos-held-btn" class="dropdown-item d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                                        Held sales
                                    </button>
                                </li>
                                <li>
                                    <button type="button" id="pos-recent-btn" class="dropdown-item d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M3 12a9 9 0 1 0 3 -6.7l-3 2.7"/><path d="M3 4v4h4"/><path d="M12 8v4l3 2"/></svg>
                                        Recent sales
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <button type="button" id="pos-fullscreen-btn" class="btn btn-outline-secondary" title="Toggle distraction-free mode" aria-label="Focus mode">
                            <svg class="pos-fullscreen-on icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8v-2a2 2 0 0 1 2 -2h2"/><path d="M4 16v2a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v2"/><path d="M16 20h2a2 2 0 0 0 2 -2v-2"/></svg>
                            <svg class="pos-fullscreen-off icon d-none" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M5 9l4 0l0 -4"/><path d="M3 3l6 6"/><path d="M5 15l4 0l0 4"/><path d="M3 21l6 -6"/><path d="M19 9l-4 0l0 -4"/><path d="M15 9l6 -6"/><path d="M19 15l-4 0l0 4"/><path d="M15 15l6 6"/></svg>
                            <span class="pos-fullscreen-on">Focus</span>
                            <span class="pos-fullscreen-off d-none">Exit</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Category chips ── --}}
            <div id="pos-category-row" class="pos-chip-row mb-3">
                <button type="button" class="pos-chip active" data-category-id="">All</button>
                @foreach($categories as $cat)
                    <button type="button" class="pos-chip" data-category-id="{{ $cat->id }}">{{ $cat->title }}</button>
                @endforeach
            </div>

            <div class="row g-3">
                {{-- ── Product grid ── --}}
                <div class="col-12 col-lg-8">
                    <div id="pos-product-grid" class="row g-2"></div>

                    <div id="pos-product-empty" class="empty py-5 d-none">
                        <div class="empty-icon">
                            {{-- Magnifier-with-dots: universal "searched, nothing found".
                                 Reads cleaner than the prior building-store-off icon
                                 which looked like sunglasses on small renders. --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10" cy="10" r="7"/>
                                <path d="M21 21l-6 -6"/>
                                <circle cx="7"  cy="10" r=".5" fill="currentColor"/>
                                <circle cx="10" cy="10" r=".5" fill="currentColor"/>
                                <circle cx="13" cy="10" r=".5" fill="currentColor"/>
                            </svg>
                        </div>
                        <p class="empty-title">No products to show</p>
                        <p class="empty-subtitle text-secondary" id="pos-product-empty-hint">Try a different search or category.</p>
                    </div>

                    <div id="pos-loadmore-wrap" class="text-center mt-3 d-none">
                        <span class="text-secondary small me-2" id="pos-loadmore-info"></span>
                        <button type="button" id="pos-loadmore-btn" class="btn btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path d="M6 9l6 6l6 -6"/></svg>
                            Load more
                        </button>
                    </div>
                </div>

                {{-- ── Cart ── --}}
                <div class="col-12 col-lg-4">
                    <div class="card pos-cart sticky-lg-top" style="top: 70px;">
                        <div class="card-header py-2 align-items-center">
                            <span class="pos-cart-icon me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                            </span>
                            <h3 class="card-title mb-0">Cart</h3>
                            <span class="ms-auto text-secondary small" id="pos-cart-meta">empty</span>
                            <button type="button" id="pos-cart-clear-btn" class="btn btn-link btn-sm text-secondary p-0 ms-2 d-none" title="Clear cart">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                            </button>
                        </div>

                        {{-- Customer chip — visible at all times so the cashier can attach a
                             customer BEFORE ringing up items. Drives the same customerState
                             that the checkout modal's Customer pane reads on open, so the
                             two surfaces stay consistent. --}}
                        <div class="pos-cart-customer">
                            <button type="button" id="pos-cart-customer-btn" class="pos-cart-customer-chip" title="Attach a customer to this sale">
                                <span class="pos-cart-customer-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                </span>
                                <span class="pos-cart-customer-label">
                                    <span id="pos-cart-customer-name">Walk-in customer</span>
                                    <span id="pos-cart-customer-meta" class="text-secondary small d-none"></span>
                                </span>
                                <span id="pos-cart-customer-detach" class="pos-cart-customer-detach d-none" title="Detach customer" aria-label="Detach customer">×</span>
                            </button>
                        </div>

                        <div id="pos-cart-empty" class="card-body text-center py-4">
                            <div class="text-secondary mb-1">Your cart is empty</div>
                            <div class="text-secondary small">Tap a product to add it.</div>
                        </div>

                        <div id="pos-cart-list" class="list-group list-group-flush"></div>

                        {{-- Subtotal / tax / savings / discount summary block.
                             Hidden when cart is empty. Discount input is the
                             cashier's bill-level adjustment — % or fixed. --}}
                        <div id="pos-cart-summary" class="card-body py-2 d-none">
                            <div class="pos-summary-line">
                                <span class="text-secondary">Subtotal</span>
                                <span id="pos-cart-subtotal">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                            </div>
                            {{-- Per-rate tax rows (populated by JS). Tax-inclusive prices
                                 are the project default — these rows show what's already
                                 baked into the subtotal so the cashier and customer can see
                                 "Tax @ 5%: ₹X" instead of an opaque combined number. --}}
                            <div id="pos-cart-tax-rows"></div>
                            <div class="pos-summary-line d-none" id="pos-cart-savings-row">
                                <span class="text-success small">You saved</span>
                                <span class="text-success small" id="pos-cart-savings">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                            </div>

                            <div class="pos-summary-line d-none" id="pos-cart-discount-row">
                                <span class="text-secondary">
                                    Discount
                                    <span class="text-secondary small ms-1" id="pos-cart-discount-label"></span>
                                </span>
                                <span>
                                    <span class="text-danger" id="pos-cart-discount-amt">−{{ $systemSettings['currencySymbol'] }} 0.00</span>
                                    <button type="button" class="btn btn-link btn-sm text-secondary p-0 ms-1" id="pos-cart-discount-clear" title="Remove discount">×</button>
                                </span>
                            </div>

                            <div class="pos-summary-line d-none" id="pos-cart-wallet-row">
                                <span class="text-secondary">Wallet</span>
                                <span class="text-primary" id="pos-cart-wallet-amt">−{{ $systemSettings['currencySymbol'] }} 0.00</span>
                            </div>

                            {{-- Promo applied row (separate from cashier discount). --}}
                            <div class="pos-summary-line d-none" id="pos-cart-promo-row">
                                <span class="text-secondary">
                                    Promo
                                    <span class="text-secondary small ms-1" id="pos-cart-promo-code"></span>
                                </span>
                                <span>
                                    <span class="text-danger" id="pos-cart-promo-amt">−{{ $systemSettings['currencySymbol'] }} 0.00</span>
                                    <button type="button" class="btn btn-link btn-sm text-secondary p-0 ms-1" id="pos-cart-promo-clear" title="Remove promo">×</button>
                                </span>
                            </div>

                            <div class="pos-discount-toggle mt-1 d-flex gap-3">
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="pos-discount-toggle-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M9 14l6 -6"/><circle cx="9.5" cy="8.5" r=".5"/><circle cx="14.5" cy="13.5" r=".5"/></svg>
                                    Apply discount
                                </button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="pos-promo-toggle-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M5 7h-2a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"/><path d="M19 7h2a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-2"/><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
                                    Promo code
                                </button>
                            </div>

                            <div class="pos-discount-form d-none mt-2" id="pos-promo-form">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="pos-promo-input" placeholder="Enter code" autocomplete="off" maxlength="64">
                                    <button type="button" class="btn btn-primary" id="pos-promo-apply-btn">Apply</button>
                                    <button type="button" class="btn btn-outline-secondary" id="pos-promo-cancel-btn" title="Hide">×</button>
                                </div>
                                <small class="form-hint d-block mt-1" id="pos-promo-hint">Re-validated server-side at checkout.</small>
                            </div>

                            <div class="pos-discount-form d-none mt-2" id="pos-discount-form">
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary pos-discount-type" data-type="percent" id="pos-discount-type-pct">%</button>
                                    <button type="button" class="btn btn-outline-secondary pos-discount-type active" data-type="fixed" id="pos-discount-type-fixed">{{ $systemSettings['currencySymbol'] }} </button>
                                    <input type="number" class="form-control" id="pos-discount-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
                                    <button type="button" class="btn btn-primary" id="pos-discount-apply-btn">Apply</button>
                                    <button type="button" class="btn btn-outline-secondary" id="pos-discount-cancel-btn" title="Hide" aria-label="Hide">×</button>
                                </div>
                                <small class="form-hint d-block mt-1" id="pos-discount-hint">Cashier discount, capped at the subtotal.</small>
                            </div>
                        </div>

                        <div class="card-footer py-2 d-flex align-items-baseline">
                            <span class="text-secondary">Total</span>
                            <span class="ms-auto h2 mb-0" id="pos-cart-total">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                        </div>
                        <div class="card-footer py-2 d-flex gap-2">
                            <button type="button" id="pos-hold-btn" class="btn btn-outline-secondary flex-shrink-0" disabled title="Hold this cart and start a new sale">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                                Hold
                            </button>
                            <button type="button" id="pos-checkout-btn" class="btn btn-primary flex-fill" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path d="M5 12l5 5l10 -10"/></svg>
                                Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Variant + Addon picker modal ── --}}
            <div class="modal modal-blur fade" id="pos-customize-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header pos-customize-header">
                            <span class="pos-customize-thumb" id="pos-customize-thumb"></span>
                            <h5 class="modal-title flex-fill" id="pos-customize-title">Customize</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="pos-customize-body">
                            {{-- Populated by JS — variant picker + addon groups --}}
                        </div>
                        <div class="modal-footer">
                            <span class="me-auto">
                                <span class="text-secondary small d-block">Line total</span>
                                <span class="h3 mb-0" id="pos-customize-line-total">0.00</span>
                            </span>
                            <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="pos-customize-add-btn" class="btn btn-primary">Add to cart</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Checkout modal ── --}}
            <div class="modal modal-blur fade" id="pos-checkout-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Complete sale</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pos-checkout-body">

                            {{-- ① Order summary --}}
                            <section class="pos-section">
                                <div class="pos-section-label">Order</div>
                                <div id="pos-modal-summary" class="pos-summary"></div>
                                <div class="pos-summary-total">
                                    <span class="text-secondary">Total</span>
                                    <span class="h2 mb-0" id="pos-modal-total-money">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                                </div>
                            </section>

                            {{-- ② Customer --}}
                            <section class="pos-section">
                                <div class="pos-section-label">Customer</div>
                                <ul class="nav nav-pills mb-2" id="pos-customer-tabs" role="tablist">
                                    <li class="nav-item"><button class="nav-link active" data-mode="walkin"   type="button">Walk-in</button></li>
                                    <li class="nav-item"><button class="nav-link"        data-mode="existing" type="button">Customer</button></li>
                                </ul>

                                <div class="pos-customer-pane" data-pane="walkin">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="pos-walkin-name" maxlength="255" placeholder="Name (optional)">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="pos-walkin-mobile" maxlength="32" placeholder="Mobile (optional)">
                                        </div>
                                    </div>
                                </div>

                                <div class="pos-customer-pane d-none" data-pane="existing">
                                    <select id="pos-customer-search" class="form-select" placeholder="Search by name, mobile, or email…" autocomplete="off"></select>
                                    <div id="pos-customer-selected" class="alert alert-success mt-2 mb-0 d-none" role="alert"></div>

                                    {{-- Wallet snapshot --}}
                                    <div id="pos-wallet-block" class="d-none mt-3 p-3" style="background: var(--tblr-bg-surface-secondary, #f6f8fb); border-radius: .5rem;">
                                        <div class="d-flex align-items-center mb-2">
                                            <strong>Wallet balance</strong>
                                            <span class="ms-auto h4 mb-0 text-primary" id="pos-wallet-balance">—</span>
                                        </div>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-7">
                                                <label class="form-label small text-secondary mb-1">Apply to this sale</label>
                                                <input type="number" class="form-control" id="pos-wallet-input" inputmode="decimal" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <div class="col-md-5 d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="pos-wallet-max-btn">Use max</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="pos-wallet-clear-btn">Clear</button>
                                            </div>
                                        </div>
                                        <div class="form-hint small mt-1" id="pos-wallet-hint">Capped at customer's balance and the remaining bill total.</div>
                                    </div>

                                    {{-- Inline register (collapsed by default) --}}
                                    <div class="mt-3">
                                        <button type="button" id="pos-new-toggle-btn" class="btn btn-sm btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-plus" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M16 19h6"/><path d="M19 16v6"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/></svg>
                                            Register new customer
                                        </button>
                                        <div id="pos-new-form" class="d-none mt-2">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" id="pos-new-name" maxlength="255" placeholder="Name *">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" id="pos-new-cc" placeholder="+91 *" maxlength="10">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" id="pos-new-mobile" maxlength="32" placeholder="Mobile *">
                                                </div>
                                                <div class="col-md-12">
                                                    <input type="email" class="form-control" id="pos-new-email" maxlength="255" placeholder="Email (optional)">
                                                </div>
                                            </div>
                                            <button type="button" id="pos-new-register-btn" class="btn btn-sm btn-primary mt-2">Register & select</button>
                                            <div id="pos-new-result" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            {{-- ③ Payment --}}
                            <section class="pos-section">
                                <div class="pos-section-label">Payment</div>

                                <ul class="nav nav-pills mb-3 flex-wrap" id="pos-payment-tabs" role="tablist">
                                    <li class="nav-item" data-pm="cash"><button class="nav-link active" data-method="cash" type="button">Cash</button></li>
                                    <li class="nav-item" data-pm="upi"><button class="nav-link" data-method="upi" type="button" id="pos-payment-tab-upi">UPI <small class="text-muted">(India)</small></button></li>
                                    <li class="nav-item" data-pm="online"><button class="nav-link" data-method="online" type="button" id="pos-payment-tab-online">Online QR</button></li>
                                    <li class="nav-item" data-pm="split"><button class="nav-link" data-method="split" type="button" id="pos-payment-tab-split">Split</button></li>
                                </ul>

                                {{-- Cash pane --}}
                                <div class="pos-payment-pane" data-pane="cash">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small text-secondary mb-1">Cash received</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="pos-cash-symbol">{{ $systemSettings['currencySymbol'] }} </span>
                                                <input type="number" class="form-control" id="pos-cash-received"
                                                       inputmode="decimal" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="d-flex flex-wrap gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary pos-quick-tender" data-tender="exact">Exact</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary pos-quick-tender" data-tender="100">+100</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary pos-quick-tender" data-tender="500">+500</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary pos-quick-tender" data-tender="1000">+1000</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary pos-quick-tender" data-tender="round-up">Round up</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-baseline mt-2">
                                        <span class="text-secondary">Change due</span>
                                        <span class="ms-auto h3 mb-0" id="pos-change-due">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                                    </div>
                                    <div class="small text-danger mt-1 d-none" id="pos-cash-short">Short by <span id="pos-cash-short-amt"></span></div>
                                </div>

                                {{-- Online (Razorpay) pane: cashier creates a session on first
                                     visit to this tab; QR encodes a public URL the customer scans
                                     from any phone. Razorpay's webhook + customer-side verify call
                                     are both wired — whichever fires first promotes the session
                                     into a real Order. --}}
                                <div class="pos-payment-pane d-none" data-pane="online">
                                    <div id="pos-online-idle" class="">
                                        <p class="text-secondary small mb-2">
                                            Customer scans a QR from any phone, pays with Razorpay (UPI / cards / netbanking).
                                            Payment is auto-confirmed — no manual verification.
                                        </p>
                                        <button type="button" id="pos-online-generate-btn" class="btn btn-outline-primary">
                                            Generate payment QR
                                        </button>
                                    </div>

                                    <div id="pos-online-active" class="d-none">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-sm-auto text-center">
                                                <div class="pos-upi-qr" id="pos-online-qr">
                                                    <div class="text-secondary small">QR…</div>
                                                </div>
                                                <div class="text-secondary small mt-1">Expires in <span id="pos-online-countdown">15:00</span></div>
                                            </div>
                                            <div class="col">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="status-dot pulse me-2"></span>
                                                    <strong id="pos-online-status-text">Waiting for customer to scan…</strong>
                                                </div>
                                                <div class="text-secondary small mb-2">
                                                    Tab-close is blocked while this session is active.
                                                </div>
                                                <div class="text-secondary small">Amount</div>
                                                <div class="h3 mb-2" id="pos-online-amount">—</div>
                                                <div class="text-secondary small">Or share this link</div>
                                                <input type="text" id="pos-online-url" class="form-control form-control-sm mb-2" readonly>
                                                <button type="button" id="pos-online-cancel-btn" class="btn btn-sm btn-outline-danger">
                                                    Cancel & switch to cash
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="pos-online-error" class="alert alert-danger d-none mb-0" role="alert"></div>
                                </div>

                                {{-- Split tender (cash + online QR) --}}
                                <div class="pos-payment-pane d-none" data-pane="split">
                                    <div id="pos-split-idle">
                                        <p class="text-secondary small mb-2">
                                            Enter how much the customer is paying in cash. The remainder will be charged via Online QR (Razorpay).
                                        </p>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label small text-secondary mb-1">Cash portion</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ $systemSettings['currencySymbol'] }} </span>
                                                    <input type="number" class="form-control" id="pos-split-cash-input"
                                                           inputmode="decimal" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-secondary mb-1">Online portion</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ $systemSettings['currencySymbol'] }} </span>
                                                    <input type="number" class="form-control" id="pos-split-online-input" readonly placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-baseline mt-2">
                                            <span class="text-secondary small">Total bill</span>
                                            <span class="ms-auto fw-medium" id="pos-split-total">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                                        </div>
                                        <button type="button" id="pos-split-generate-btn" class="btn btn-outline-primary mt-2" disabled>
                                            Collect cash &amp; generate QR for online
                                        </button>
                                        <div id="pos-split-error" class="alert alert-danger mt-2 d-none mb-0" role="alert"></div>
                                    </div>

                                    <div id="pos-split-active" class="d-none">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-sm-auto text-center">
                                                <div class="pos-upi-qr" id="pos-split-qr">
                                                    <div class="text-secondary small">QR…</div>
                                                </div>
                                                <div class="text-secondary small mt-1">Expires in <span id="pos-split-countdown">15:00</span></div>
                                            </div>
                                            <div class="col">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="status-dot pulse me-2"></span>
                                                    <strong id="pos-split-status-text">Waiting for online payment…</strong>
                                                </div>
                                                <div class="text-secondary small mb-2">
                                                    Make sure cash is collected before customer pays online.
                                                </div>
                                                <div class="text-secondary small">Cash collected</div>
                                                <div class="h4 mb-2" id="pos-split-cash-shown">—</div>
                                                <div class="text-secondary small">Pay online</div>
                                                <div class="h3 mb-2" id="pos-split-online-shown">—</div>
                                                <button type="button" id="pos-split-cancel-btn" class="btn btn-sm btn-outline-danger">
                                                    Cancel split &amp; switch to cash
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- UPI pane --}}
                                <div class="pos-payment-pane d-none" data-pane="upi">
                                    <div id="pos-upi-not-configured" class="alert alert-warning d-none mb-0" role="alert">
                                        Online QR is not configured for this store. Set the UPI VPA in store settings to enable it.
                                    </div>
                                    <div id="pos-upi-active" class="d-none">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-sm-auto text-center">
                                                <div class="pos-upi-qr" id="pos-upi-qr">
                                                    <div class="text-secondary small">QR…</div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="text-secondary small">Pay to</div>
                                                <div class="fw-medium" id="pos-upi-payee">—</div>
                                                <div class="text-secondary small mt-1" id="pos-upi-vpa">—</div>
                                                <div class="text-secondary small mt-2">Amount</div>
                                                <div class="h3 mb-0" id="pos-upi-amount">—</div>
                                                <div class="form-hint mt-2">
                                                    Customer scans this QR from any UPI app (GPay / PhonePe / Paytm / BHIM).
                                                    After it lands in your UPI app, tap <strong>Mark as paid</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Custom payment method pane (dynamically populated by JS) --}}
                                <div class="pos-payment-pane d-none" data-pane="custom" id="pos-custom-method-pane">
                                    <div class="text-center py-2">
                                        <div class="mb-2">
                                            <span id="pos-custom-icon" style="font-size:2rem"></span>
                                        </div>
                                        <div class="fw-medium mb-1" id="pos-custom-label"></div>
                                        <div class="text-secondary small mb-2" id="pos-custom-instructions"></div>
                                        <div class="text-secondary small mt-2">Amount</div>
                                        <div class="h3 mb-0" id="pos-custom-amount">—</div>
                                    </div>
                                </div>
                            </section>

                            {{-- ④ Note --}}
                            <section class="pos-section pos-section-last">
                                <div class="pos-section-label">Note <span class="text-secondary fw-normal">(optional)</span></div>
                                <textarea class="form-control" id="pos-order-note" rows="2" maxlength="500" placeholder="Any internal note…"></textarea>
                            </section>

                            <div class="alert alert-danger mt-2 d-none" id="pos-checkout-error" role="alert"></div>
                        </div>
                        <div class="modal-footer">
                            <span class="me-auto">
                                <span class="text-secondary small d-block">Total</span>
                                <span class="h3 mb-0" id="pos-modal-total">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                            </span>
                            <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="pos-complete-sale-btn">Complete sale</button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ── Held sales modal ── --}}
            <div class="modal modal-blur fade" id="pos-held-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Held sales</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary small mb-2">
                                Carts you set aside. Resume a sale to load it back; stock is re-checked at checkout.
                            </p>
                            <div id="pos-held-loading" class="text-secondary small py-3 text-center">Loading…</div>
                            <div id="pos-held-empty" class="empty py-4 d-none">
                                <p class="empty-title">Nothing on hold</p>
                                <p class="empty-subtitle text-secondary">Click <strong>Hold</strong> on a cart to set it aside.</p>
                            </div>
                            <div class="list-group d-none" id="pos-held-list"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- POS confirmation modal --}}
            <div class="modal modal-blur fade" id="pos-confirm-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pos-confirm-title">Confirm action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0 text-secondary" id="pos-confirm-message">Are you sure?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal" id="pos-confirm-cancel-btn">Cancel</button>
                            <button type="button" class="btn btn-primary" id="pos-confirm-ok-btn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- POS hold label modal --}}
            <div class="modal modal-blur fade" id="pos-hold-label-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Hold sale</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label for="pos-hold-label-input" class="form-label">Label</label>
                            <input type="text" class="form-control" id="pos-hold-label-input" maxlength="120" placeholder="Optional short label">
                            <div class="form-hint mt-2">This helps you recognize the held cart later.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="pos-hold-label-save-btn">Hold cart</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Recent orders modal ── --}}
            <div class="modal modal-blur fade" id="pos-recent-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Recent POS sales</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                                <p class="text-secondary small mb-0 me-auto">
                                    Most recent in-store sales for your stores. Use Reprint if a receipt didn't make it to the printer.
                                </p>
                                <a href="{{ route('seller.orders.index', ['type' => 'pos']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-link p-0 ms-2 text-decoration-none">
                                    All orders
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="ms-1"><path d="M11 7h-5a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-5"/><path d="M10 14l10 -10"/><path d="M15 4h5v5"/></svg>
                                </a>
                            </div>

                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-7">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M21 21l-6 -6"/><path d="M10 17a7 7 0 1 0 0 -14a7 7 0 0 0 0 14"/></svg>
                                        </span>
                                        <input type="search" id="pos-recent-search" class="form-control ps-5" placeholder="Search by order #, customer, payment, or total…" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-5 d-flex justify-content-md-end">
                                    <select id="pos-recent-limit" class="form-select form-select-sm" style="max-width: 150px;">
                                        <option value="25">25 per page</option>
                                        <option value="50" selected>50 per page</option>
                                        <option value="100">100 per page</option>
                                        <option value="200">200 per page</option>
                                    </select>
                                </div>
                            </div>

                            <div id="pos-recent-loading" class="text-secondary small py-3 text-center">Loading…</div>
                            <div id="pos-recent-empty" class="empty py-4 d-none">
                                <p class="empty-title">No POS sales yet</p>
                                <p class="empty-subtitle text-secondary">Once you complete sales, they'll show up here for quick reprinting.</p>
                            </div>
                            <div class="table-responsive d-none" id="pos-recent-table-wrap">
                                <table class="table table-vcenter card-table pos-recent-table">
                                    <thead>
                                        <tr>
                                            <th class="pos-sortable" data-sort="id">Order <span class="pos-sort-icon"></span></th>
                                            <th class="pos-sortable" data-sort="created_at">When <span class="pos-sort-icon"></span></th>
                                            <th>Customer</th>
                                            <th>Payment</th>
                                            <th class="text-end pos-sortable" data-sort="final_total">Total <span class="pos-sort-icon"></span></th>
                                            <th class="text-end" style="min-width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pos-recent-rows"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <span class="text-secondary small me-auto" id="pos-recent-count"></span>
                            <button type="button" id="pos-recent-refresh-btn" class="btn">Refresh</button>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Quick-attach customer modal ── --}}
            <div class="modal modal-blur fade" id="pos-attach-customer-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Attach customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Phone-first lookup: cashier types phone and matching customers
                                 appear instantly. Falls back to name/email match too — the
                                 backend search already covers all three. --}}
                            <label for="pos-attach-search" class="form-label">Phone number</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
                                </span>
                                <input type="search" id="pos-attach-search" class="form-control ps-5" inputmode="tel" placeholder="Type phone number, name or email…" autocomplete="off" autofocus>
                            </div>
                            <div id="pos-attach-results" class="list-group mt-3" style="max-height: 280px; overflow:auto;"></div>
                            <div class="text-center mt-3">
                                <button type="button" id="pos-attach-new-toggle" class="btn btn-link btn-sm text-decoration-none">
                                    + Register new customer
                                </button>
                            </div>
                            <div id="pos-attach-new-form" class="mt-2 d-none">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="pos-attach-new-name" maxlength="255" placeholder="Name *">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="pos-attach-new-cc" placeholder="+91 *" maxlength="10">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="pos-attach-new-mobile" maxlength="32" placeholder="Mobile *">
                                    </div>
                                    <div class="col-md-12">
                                        <input type="email" class="form-control" id="pos-attach-new-email" maxlength="255" placeholder="Email (optional)">
                                    </div>
                                </div>
                                <button type="button" id="pos-attach-new-submit" class="btn btn-primary mt-2">Register &amp; attach</button>
                                <div id="pos-attach-new-result" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Refund modal ── --}}
            <div class="modal modal-blur fade" id="pos-refund-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Refund sale <span class="text-secondary fw-normal" id="pos-refund-order-label"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="pos-refund-loading" class="text-secondary py-3">Loading…</div>
                            <div id="pos-refund-empty" class="empty py-4 d-none">
                                <p class="empty-title">Nothing to refund</p>
                                <p class="empty-subtitle text-secondary">Every item from this sale has already been refunded.</p>
                            </div>
                            <div id="pos-refund-already" class="alert alert-info d-none mb-3"></div>
                            <div id="pos-refund-form-wrap" class="d-none">
                                <p class="text-secondary small mb-2">
                                    Enter the quantity you want to refund per item. This restores stock and records an audit row, but does not call the payment gateway — refund the customer manually with the configured method.
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-center" style="width: 110px">Sold</th>
                                                <th class="text-center" style="width: 130px">Refund qty</th>
                                                <th class="text-end" style="width: 110px">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pos-refund-rows"></tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">{{ __('labels.pos_refund_method_label') }}</label>
                                    <div class="btn-group w-100" role="group" aria-label="Refund method">
                                        <input type="radio" class="btn-check" name="pos-refund-method" id="pos-refund-method-cash" value="cash" checked>
                                        <label class="btn btn-outline-secondary" for="pos-refund-method-cash">{{ __('labels.pos_refund_cash') }}</label>
                                        <input type="radio" class="btn-check" name="pos-refund-method" id="pos-refund-method-other" value="other">
                                        <label class="btn btn-outline-secondary" for="pos-refund-method-other">{{ __('labels.pos_refund_other') }}</label>
                                    </div>
                                    <input type="text" id="pos-refund-method-note" class="form-control form-control-sm mt-2" maxlength="200" placeholder="{{ __('labels.pos_refund_note_placeholder') }}">
                                </div>
                                <div class="mt-3">
                                    <label for="pos-refund-reason" class="form-label">{{ __('labels.pos_refund_reason_label') }} <span class="text-secondary">({{ __('labels.pos_refund_reason_hint') }})</span></label>
                                    <textarea id="pos-refund-reason" class="form-control" rows="2" maxlength="500" placeholder="{{ __('labels.pos_refund_reason_placeholder') }}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="me-auto">
                                <span class="text-secondary small">Refund total</span>
                                <span class="h4 mb-0 ms-2" id="pos-refund-total">{{ $systemSettings['currencySymbol'] }} 0.00</span>
                            </div>
                            <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="pos-refund-submit" class="btn btn-danger" disabled>
                                Refund
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Refund history modal (read-only audit trail per order) ── --}}
            <div class="modal modal-blur fade" id="pos-refund-history-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Refund history <span class="text-secondary fw-normal" id="pos-refund-history-order-label"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="pos-refund-history-loading" class="text-secondary py-3">Loading…</div>
                            <div id="pos-refund-history-empty" class="empty py-4 d-none">
                                <p class="empty-title">No refunds</p>
                                <p class="empty-subtitle text-secondary">Nothing has been refunded against this order yet.</p>
                            </div>
                            <div id="pos-refund-history-summary" class="alert alert-info d-none mb-3"></div>
                            <div id="pos-refund-history-list" class="d-flex flex-column gap-3"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @if(!$stores->isEmpty())
        <style>
            /* ── Toolbar ── */
            .pos-toolbar { background: var(--tblr-card-bg); padding: .75rem; border-radius: var(--tblr-border-radius, .5rem); border: 1px solid var(--tblr-border-color); }
            .pos-toolbar .form-select, .pos-toolbar .form-control { border-radius: var(--tblr-border-radius, .375rem); height: 2.5rem; }

            /* Inline clear (×) on search + barcode inputs. Visible only when
               the input has a value (JS toggles .d-none). The ps-5/pe-5 on
               the inputs leaves room for the leading icon and this trailing
               button without overlapping the typed text. */
            .input-icon .pos-input-clear {
                position: absolute;
                right: .35rem;
                top: 50%;
                transform: translateY(-50%);
                width: 26px; height: 26px;
                padding: 0;
                border: 0;
                background: transparent;
                color: var(--tblr-secondary);
                border-radius: 999px;
                display: inline-flex; align-items: center; justify-content: center;
                line-height: 0;
                cursor: pointer;
                transition: color .12s ease, background-color .12s ease;
            }
            .input-icon .pos-input-clear svg { display: block; }
            .input-icon .pos-input-clear:hover {
                color: var(--tblr-danger);
                background: rgba(214, 51, 70, .08);
            }
            /* Suppress the browser-native clear on type="search" so we don't
               render two clear buttons stacked. */
            input[type="search"]::-webkit-search-cancel-button,
            input[type="search"]::-webkit-search-decoration { -webkit-appearance: none; appearance: none; }

            /* Action bar — Focus stays standalone, secondary actions
               (Held / Recent / Display) collapse into a single dropdown so
               the toolbar never crowds the barcode input on 13-14" laptops. */
            .pos-action-bar .btn {
                padding-left: .65rem; padding-right: .65rem;
                display: inline-flex; align-items: center; justify-content: center;
                gap: .35rem;
                min-height: 2.5rem;     /* match toolbar input height */
                min-width: 2.5rem;      /* keep kebab from squishing next to Focus */
            }
            /* drop SVG baseline gap *and* kill Tabler's default
               margin-inline-end on .icon — without text following the icon,
               that margin pushes the dots/icon left of true horizontal centre. */
            .pos-action-bar .btn > .icon { display: block; margin: 0 !important; }
            .pos-action-bar .dropdown-menu .icon { color: var(--tblr-secondary); }
            /* The cart uses sticky positioning which creates its own stacking
               context — bump the dropdown above it so menu items aren't clipped. */
            .pos-action-bar .dropdown-menu { z-index: 1080; }
            /* Held-count sits as a small floating pill on the kebab's
               top-right corner. Box-shadow rings it so when it overlaps the
               neighbouring Focus button (on tight toolbars) it still reads
               as part of the kebab and stays legible. */
            .pos-action-bar .dropdown { overflow: visible; }
            .pos-action-bar { gap: .4rem !important; }   /* small buffer for the floating badge */
            .pos-action-bar #pos-held-count {
                position: absolute;
                top: -.4rem;
                right: -.4rem;
                margin: 0;
                min-width: 1.1rem;
                height: 1.1rem;
                padding: 0 .3rem;
                border-radius: 999px;
                font-size: .7rem;
                line-height: 1.1rem;
                box-shadow: 0 0 0 2px var(--tblr-card-bg, #fff);
            }

            /* ── Category chips ── */
            .pos-chip-row {
                display: flex; gap: .35rem; flex-wrap: nowrap;
                overflow-x: auto; padding-bottom: .25rem;
                scrollbar-width: thin;
            }
            .pos-chip-row::-webkit-scrollbar { height: 4px; }
            .pos-chip-row::-webkit-scrollbar-thumb { background: var(--tblr-border-color); border-radius: 4px; }
            .pos-chip {
                flex: 0 0 auto;
                padding: .35rem .85rem;
                background: var(--tblr-card-bg);
                border: 1px solid var(--tblr-border-color);
                color: var(--tblr-secondary);
                border-radius: 999px;
                font-size: .85rem; font-weight: 500;
                white-space: nowrap;
                transition: all .12s ease;
                cursor: pointer;
            }
            .pos-chip:hover { color: var(--tblr-body-color); border-color: var(--tblr-secondary); }
            .pos-chip.active { background: var(--tblr-primary); color: #fff; border-color: var(--tblr-primary); }

            /* ── Product cards ── */
            .pos-product-card {
                position: relative;
                width: 100%;
                padding: 0;
                text-align: left;
                background: var(--tblr-card-bg);
                border: 1px solid var(--tblr-border-color);
                border-radius: var(--tblr-border-radius, .5rem);
                overflow: hidden;
                cursor: pointer;
                transition: transform .1s ease, box-shadow .12s ease, border-color .12s ease;
            }
            .pos-product-card:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px -8px rgba(0, 0, 0, .15);
                border-color: var(--tblr-primary);
            }
            .pos-product-card:disabled { opacity: .55; cursor: not-allowed; }
            .pos-product-card:focus-visible { outline: 2px solid var(--tblr-primary); outline-offset: 2px; }

            .pos-product-thumb {
                aspect-ratio: 4 / 3;
                width: 100%;
                background: linear-gradient(135deg, var(--tblr-bg-surface-secondary, #f6f8fb) 0%, var(--tblr-card-bg) 100%);
                display: flex; align-items: center; justify-content: center;
                color: var(--tblr-primary); font-weight: 700; font-size: 1.4rem;
                position: relative;
                overflow: hidden;
            }
            .pos-product-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
            .pos-product-no-image {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: .75rem;
                font-size: .78rem;
                font-weight: 600;
                color: var(--tblr-secondary);
                background: linear-gradient(135deg, rgba(148, 163, 184, .14), rgba(148, 163, 184, .06));
            }

            .pos-product-flag {
                position: absolute; top: .4rem; left: .4rem;
                display: inline-flex; gap: .25rem;
            }
            .pos-product-flag .badge {
                font-weight: 600;
                font-size: .65rem;
                padding: .2rem .5rem;
                /* Force a near-opaque plate so the badge stays legible on top
                   of arbitrary product photos. The Tabler *-lt variants are
                   ~10% alpha which collides with photographic backgrounds. */
                background: rgba(255, 255, 255, .95) !important;
                color: var(--tblr-body-color);
                backdrop-filter: blur(6px);
                box-shadow: 0 1px 2px rgba(15, 23, 42, .1);
            }
            .pos-product-flag .badge.bg-azure-lt  { color: var(--tblr-azure); }
            .pos-product-flag .badge.bg-purple-lt { color: var(--tblr-purple); }

            .pos-product-stock {
                position: absolute; top: .4rem; right: .4rem;
                font-size: .65rem; font-weight: 500;
                padding: .15rem .4rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, .9);
                backdrop-filter: blur(6px);
                color: var(--tblr-secondary);
            }
            .pos-product-stock.is-out      { color: var(--tblr-danger);  background: rgba(255, 230, 232, .95); }
            .pos-product-stock.is-critical { color: var(--tblr-danger);  background: rgba(255, 235, 220, .95); animation: pos-stock-pulse 1.6s ease-in-out infinite; font-weight: 600; }
            .pos-product-stock.is-low      { color: var(--tblr-warning); background: rgba(255, 248, 230, .95); }
            .pos-product-stock.is-good     { color: var(--tblr-success); background: rgba(230, 255, 240, .95); }
            @keyframes pos-stock-pulse {
                0%,100% { box-shadow: 0 0 0 0 rgba(214, 51, 70, .35); }
                50%     { box-shadow: 0 0 0 5px rgba(214, 51, 70, 0); }
            }
            /* Optional left-edge stripe on the card for very-low items so it
               draws attention even before the cashier reads the badge. */
            .pos-product-card:has(.pos-product-stock.is-critical) { box-shadow: inset 3px 0 0 0 var(--tblr-danger); }
            .pos-product-card:has(.pos-product-stock.is-low)      { box-shadow: inset 3px 0 0 0 var(--tblr-warning); }

            .pos-product-body { padding: .65rem .75rem .75rem; }
            .pos-product-title {
                font-weight: 600; font-size: .92rem; line-height: 1.25;
                color: var(--tblr-body-color);
                display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
                overflow: hidden; min-height: 2.3em; margin-bottom: .35rem;
            }
            .pos-product-priceline { display: flex; align-items: baseline; gap: .4rem; flex-wrap: wrap; }
            .pos-product-price { font-weight: 700; font-size: 1.05rem; color: var(--tblr-body-color); }
            .pos-product-price-from { font-weight: 500; font-size: .7rem; color: var(--tblr-secondary); margin-right: .15rem; }
            .pos-product-price-strike { color: var(--tblr-secondary); text-decoration: line-through; font-size: .8rem; }

            /* ── Cart ── */
            .pos-cart .card-header { background: var(--tblr-card-bg); }
            .pos-cart .pos-cart-icon { color: var(--tblr-primary); display: inline-flex; }
            .pos-cart .list-group-item { padding: .65rem .8rem; }
            .pos-cart .list-group-item .fw-medium { font-size: .92rem; }

            /* ── Customer chip in cart header ── */
            .pos-cart-customer { padding: .5rem .8rem; border-bottom: 1px solid var(--tblr-border-color); }
            .pos-cart-customer-chip {
                appearance: none;
                width: 100%;
                display: inline-flex; align-items: center; gap: .55rem;
                padding: .45rem .65rem;
                border: 1px dashed var(--tblr-border-color);
                background: transparent;
                border-radius: .45rem;
                color: var(--tblr-secondary);
                font-size: .9rem;
                cursor: pointer;
                transition: all .12s ease;
                text-align: left;
            }
            .pos-cart-customer-chip:hover {
                color: var(--tblr-primary);
                border-color: var(--tblr-primary);
                background: rgba(37, 99, 235, .04);
            }
            .pos-cart-customer-chip.is-attached {
                border-style: solid;
                border-color: var(--tblr-success);
                background: rgba(16, 185, 129, .06);
                color: var(--tblr-body-color);
            }
            .pos-cart-customer-icon { display: inline-flex; }
            .pos-cart-customer-label { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; line-height: 1.15; }
            .pos-cart-customer-label > span:first-child { font-weight: 500; color: inherit; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .pos-cart-customer-detach {
                flex: 0 0 auto;
                width: 22px; height: 22px;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 999px;
                color: var(--tblr-secondary);
                font-size: 1rem; line-height: 1;
            }
            .pos-cart-customer-detach:hover { background: rgba(214, 51, 70, .12); color: var(--tblr-danger); }
            .pos-cart-line-meta { font-size: .78rem; color: var(--tblr-secondary); }

            /* Cart breakdown rows (subtotal / tax / savings / discount) */
            .pos-summary-line { display: flex; justify-content: space-between; align-items: baseline; padding: .15rem 0; }
            /* Discount input row — softer borders, gentle active state. */
            .pos-discount-form .input-group,
            #pos-promo-form .input-group { border: 1px solid var(--tblr-border-color); border-radius: var(--tblr-border-radius, .375rem); overflow: hidden; }
            .pos-discount-form .input-group .form-control,
            .pos-discount-form .input-group .btn,
            #pos-promo-form .input-group .form-control,
            #pos-promo-form .input-group .btn {
                height: 38px;
                border: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            #pos-promo-form .input-group .form-control { background: transparent; }
            #pos-promo-form .input-group #pos-promo-apply-btn { background: var(--tblr-primary); color: #fff; font-weight: 500; border-left: 1px solid var(--tblr-primary) !important; }
            #pos-promo-form .input-group #pos-promo-cancel-btn { background: transparent; color: var(--tblr-secondary); border-left: 1px solid var(--tblr-border-color) !important; }
            #pos-promo-form .input-group #pos-promo-cancel-btn:hover { color: var(--tblr-danger); }
            .pos-discount-form .input-group .btn.pos-discount-type {
                min-width: 40px;
                font-weight: 600;
                background: transparent;
                color: var(--tblr-secondary);
                border-right: 1px solid var(--tblr-border-color) !important;
            }
            .pos-discount-form .input-group .btn.pos-discount-type.active {
                background: rgba(37, 99, 235, .08);
                color: var(--tblr-primary);
            }
            .pos-discount-form .input-group .form-control { background: transparent; }
            .pos-discount-form .input-group #pos-discount-apply-btn {
                background: var(--tblr-primary);
                color: #fff;
                border-left: 1px solid var(--tblr-primary) !important;
                font-weight: 500;
            }
            .pos-discount-form .input-group #pos-discount-cancel-btn {
                background: transparent;
                color: var(--tblr-secondary);
                border-left: 1px solid var(--tblr-border-color) !important;
            }
            .pos-discount-form .input-group #pos-discount-cancel-btn:hover { color: var(--tblr-danger); }
            /* Subtle remove (×) — square 22px, no fill, danger-on-hover only. */
            .pos-cart-remove {
                appearance: none;
                width: 22px; height: 22px; padding: 0;
                line-height: 1;
                display: inline-flex; justify-content: center;
                background: transparent;
                border: 1px solid transparent;
                color: var(--tblr-secondary);
                border-radius: 999px;
                font-size: 1rem;
                cursor: pointer;
                transition: color .12s ease, background-color .12s ease, border-color .12s ease;
            }
            .pos-cart-remove:hover {
                color: var(--tblr-danger);
                background: rgba(214, 51, 70, .08);
                border-color: rgba(214, 51, 70, .25);
            }
            /* Pencil edit button on cart rows — same shape as remove */
            .pos-cart-edit {
                appearance: none;
                width: 22px; height: 22px; padding: 0;
                line-height: 1;
                display: inline-flex; align-items: center; justify-content: center;
                background: transparent;
                border: 1px solid transparent;
                color: var(--tblr-secondary);
                border-radius: 999px;
                cursor: pointer;
                transition: color .12s ease, background-color .12s ease, border-color .12s ease;
            }
            .pos-cart-edit:hover {
                color: var(--tblr-primary);
                background: rgba(37, 99, 235, .08);
                border-color: rgba(37, 99, 235, .25);
            }

            /* ── Customize modal: small thumbnail in the header so variant + addons stay above the fold ── */
            /* Explicit padding so the thumbnail has equal breathing room on
               top, bottom, and left — Tabler's default modal-header padding
               can collapse on the leading edge when the first child is a
               flex item with no own margin. */
            .pos-customize-header {
                gap: .85rem;
                align-items: center;
                padding: 1rem 1.25rem;
            }
            .pos-customize-thumb {
                width: 48px; height: 48px;
                flex: 0 0 auto;
                background: linear-gradient(135deg, var(--tblr-bg-surface-secondary, #f6f8fb) 0%, var(--tblr-card-bg) 100%);
                border: 1px solid var(--tblr-border-color);
                border-radius: .5rem;
                display: inline-flex; align-items: center; justify-content: center;
                color: var(--tblr-primary);
                font-weight: 700; font-size: .85rem; letter-spacing: .02em;
                overflow: hidden;
            }
            .pos-customize-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
            .pos-customize-thumb > span {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: .35rem;
                font-size: .68rem;
                font-weight: 600;
                color: var(--tblr-secondary);
            }

            /* ── Addon list ── */
            .pos-addon-row {
                display: flex; align-items: center;
                gap: .65rem;
                padding: .35rem .25rem;
                border-radius: .25rem;
                cursor: pointer;
            }
            .pos-addon-row:hover { background: var(--tblr-bg-surface-secondary, #f6f8fb); }
            .pos-addon-row input[type="radio"],
            .pos-addon-row input[type="checkbox"] { flex: 0 0 auto; margin: 0; }
            .pos-addon-row .pos-addon-label { flex: 1 1 auto; font-size: .9rem; }
            .pos-addon-row .pos-addon-price { flex: 0 0 auto; font-size: .85rem; color: var(--tblr-secondary); }
            .pos-addon-row .pos-addon-price.is-free { color: var(--tblr-success); }
            .pos-addon-row.is-disabled { opacity: .5; cursor: not-allowed; }

            /* ── Checkout modal sections (clean separators, no nested cards) ── */
            .pos-checkout-body { padding-top: .75rem; }
            .pos-section { padding: 1rem 0; border-bottom: 1px solid var(--tblr-border-color); }
            .pos-section:first-child { padding-top: 0; }
            .pos-section.pos-section-last { border-bottom: 0; padding-bottom: 0; }
            .pos-section-label {
                font-size: .72rem; font-weight: 600;
                text-transform: uppercase; letter-spacing: .04em;
                color: var(--tblr-secondary);
                margin-bottom: .55rem;
            }

            /* Compact order summary inside the checkout modal */
            .pos-summary { display: flex; flex-direction: column; gap: .35rem; max-height: 220px; overflow: auto; }
            .pos-summary-row { display: flex; gap: .5rem; align-items: baseline; }
            .pos-summary-row .pos-summary-title { flex: 1 1 auto; min-width: 0; }
            .pos-summary-row .pos-summary-meta  { font-size: .76rem; color: var(--tblr-secondary); }
            .pos-summary-row .pos-summary-amt   { flex: 0 0 auto; font-weight: 600; }
            .pos-summary-total {
                display: flex; align-items: baseline;
                margin-top: .75rem; padding-top: .5rem;
                border-top: 1px dashed var(--tblr-border-color);
            }
            .pos-summary-total .h2 { margin-left: auto; }
            .pos-summary-breakdown { margin-top: .5rem; padding-top: .35rem; border-top: 1px dashed var(--tblr-border-color); }

            .pos-change-positive { color: var(--tblr-success); }

            /* ── UPI QR panel ── */
            .pos-upi-qr {
                width: 180px; height: 180px;
                background: #fff;
                border: 1px solid var(--tblr-border-color);
                border-radius: .5rem;
                padding: 8px;
                display: flex; align-items: center; justify-content: center;
                overflow: hidden;
            }
            .pos-upi-qr svg, .pos-upi-qr img { width: 100%; height: 100%; display: block; }
            #pos-payment-tab-upi.is-disabled,
            #pos-payment-tab-online.is-disabled { pointer-events: none; opacity: .55; }

            /* Status dot for online session "waiting" indicator */
            .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--tblr-warning); }
            .status-dot.pulse { animation: pos-pulse 1.4s ease-in-out infinite; }
            .status-dot.is-paid    { background: var(--tblr-success); animation: none; }
            .status-dot.is-failed  { background: var(--tblr-danger);  animation: none; }
            @keyframes pos-pulse {
                0%,100% { box-shadow: 0 0 0 0 rgba(247,184,75,.55); }
                50%     { box-shadow: 0 0 0 6px rgba(247,184,75,0); }
            }

            /* ── Customize modal ── */
            .pos-variant-pill { font-weight: 500; }

            /* ── Fullscreen mode ── */
            body.pos-fullscreen .navbar,
            body.pos-fullscreen .page-header,
            body.pos-fullscreen .navbar-vertical,
            body.pos-fullscreen .footer,
            body.pos-fullscreen aside.navbar,
            body.pos-fullscreen .navbar-side,
            body.pos-fullscreen .sidebar { display: none !important; }
            body.pos-fullscreen .page-wrapper,
            body.pos-fullscreen .page-body,
            body.pos-fullscreen .page { padding: 0 !important; margin: 0 !important; }
            body.pos-fullscreen .pos-shell { padding-top: .75rem; }

            /* ─────────────────────────────────────────────────────────
               Polish pass — focus rings, touch targets, skeleton, toasts.
               Kept in one block at the bottom so all polish lives together
               and overrides any earlier rule by source order.
               ───────────────────────────────────────────────────────── */

            /* Visible focus rings on every interactive POS control.
               Tabler defaults are subtle — on a busy toolbar the cashier
               can lose track of which button keyboard focus is on. */
            .pos-chip:focus-visible,
            .pos-cart-remove:focus-visible,
            .pos-cart-edit:focus-visible,
            .pos-action-bar .btn:focus-visible,
            .pos-cart .btn:focus-visible,
            .pos-cart .pos-qty-inc:focus-visible,
            .pos-cart .pos-qty-dec:focus-visible {
                outline: 2px solid var(--tblr-primary);
                outline-offset: 2px;
                box-shadow: none;
            }

            /* Touch-target sizing — most POS terminals are touchscreens.
               WCAG 2.5.5 calls for 44×44 minimum; we lift cramped controls
               only when the primary input is coarse, so mouse layouts stay
               compact. */
            @media (pointer: coarse) {
                .pos-chip { padding: .55rem 1rem; font-size: .9rem; }
                .pos-cart-remove,
                .pos-cart-edit { width: 36px; height: 36px; }
                .pos-cart .btn-group-sm > .btn { min-width: 40px; min-height: 40px; padding: .35rem .55rem; font-size: 1rem; }
                #pos-cart-clear-btn { padding: .25rem .5rem; }
                .pos-cart .list-group-item { padding: .85rem .9rem; }
            }

            /* Skeleton cards while products load — replaces the blank flash
               between submit and render. Matches .pos-product-card shape. */
            .pos-skeleton-card {
                background: var(--tblr-card-bg);
                border: 1px solid var(--tblr-border-color);
                border-radius: var(--tblr-border-radius, .5rem);
                overflow: hidden;
            }
            .pos-skeleton-thumb { aspect-ratio: 4 / 3; width: 100%; }
            .pos-skeleton-line {
                height: .75rem; margin: .55rem .75rem;
                border-radius: 4px;
            }
            .pos-skeleton-line.short { width: 50%; }
            .pos-skeleton-thumb,
            .pos-skeleton-line {
                background: linear-gradient(90deg,
                    var(--tblr-bg-surface-secondary, #f0f3f7) 0%,
                    var(--tblr-card-bg) 50%,
                    var(--tblr-bg-surface-secondary, #f0f3f7) 100%);
                background-size: 200% 100%;
                animation: pos-skeleton-shimmer 1.2s ease-in-out infinite;
            }
            @keyframes pos-skeleton-shimmer {
                0%   { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }

            /* ── Recent POS table — sortable headers + inline icon actions ── */
            .pos-recent-table th.pos-sortable {
                cursor: pointer;
                user-select: none;
                white-space: nowrap;
            }
            .pos-recent-table th.pos-sortable:hover { color: var(--tblr-primary); }
            .pos-recent-table th.pos-sortable .pos-sort-icon {
                display: inline-block;
                width: 0; height: 0;
                margin-left: .35rem;
                border-left: 4px solid transparent;
                border-right: 4px solid transparent;
                opacity: .25;
                transform: translateY(-2px);
            }
            .pos-recent-table th.pos-sortable.is-asc  .pos-sort-icon { border-bottom: 5px solid currentColor; opacity: 1; }
            .pos-recent-table th.pos-sortable.is-desc .pos-sort-icon { border-top:    5px solid currentColor; opacity: 1; }

            .pos-recent-actions {
                display: inline-flex; gap: .25rem; flex-wrap: nowrap;
                justify-content: flex-end;
            }
            .pos-recent-actions .pos-recent-action {
                appearance: none;
                width: 32px; height: 32px;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: .35rem;
                background: transparent;
                border: 1px solid var(--tblr-border-color);
                color: var(--tblr-secondary);
                cursor: pointer;
                transition: color .12s ease, background-color .12s ease, border-color .12s ease;
                text-decoration: none;
            }
            .pos-recent-actions .pos-recent-action:hover { background: rgba(37, 99, 235, .06); border-color: var(--tblr-primary); color: var(--tblr-primary); }
            .pos-recent-actions .pos-recent-action.is-refund:hover { background: rgba(214, 51, 70, .08); border-color: var(--tblr-danger); color: var(--tblr-danger); }
            .pos-recent-actions .pos-recent-action.is-history:hover { background: rgba(247, 184, 75, .08); border-color: var(--tblr-warning); color: var(--tblr-warning); }

            /* Toast host — bottom-right, above modals.
               Cards round + soft-shadow so they read as a notification, not
               a flash-message banner. */
            #pos-toast-host { position: fixed; right: 1rem; bottom: 1rem; z-index: 1080; max-width: 360px; pointer-events: none; }
            #pos-toast-host > .alert {
                pointer-events: auto;
                box-shadow: 0 12px 32px -12px rgba(15, 23, 42, .25);
                border: 1px solid var(--tblr-border-color);
                border-radius: .65rem;
            }
        </style>
    @endif
@endsection

@if(!$stores->isEmpty())
    @push('scripts')
        @php
            $defaultStore = $stores->first();
            $storePaymentConfig = $stores->mapWithKeys(function ($s) {
                $cfg = $s->pos_payment_config ?? [];
                return [$s->id => [
                    'upi_vpa'        => $s->pos_upi_vpa,
                    'upi_payee_name' => $s->pos_upi_payee_name ?: $s->name,
                    'currency_code'  => $s->currency_code,
                    'cash'           => filter_var($cfg['cash'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'upi'            => filter_var($cfg['upi'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'online_qr'      => filter_var($cfg['online_qr'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'split'          => filter_var($cfg['split'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'custom_methods' => collect($cfg['custom_methods'] ?? [])->filter(fn($m) => filter_var($m['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))->values()->all(),
                ]];
            });
        @endphp
        <script>
            window.__POS_CONFIG__ = {
                productsUrl:           "{{ route('seller.pos.api.products.search') }}",
                barcodeLookupUrl:      "{{ route('seller.pos.api.products.barcode') }}",
                categoriesUrl:         "{{ route('seller.pos.api.categories.index') }}",
                customersSearchUrl:    "{{ route('seller.pos.api.customers.search') }}",
                usersSearchUrl:        "{{ route('seller.pos.api.users.search') }}",
                customersRegisterUrl:  "{{ route('seller.pos.api.customers.register') }}",
                ordersCreateUrl:       "{{ route('seller.pos.api.orders.create') }}",
                ordersRecentUrl:       "{{ route('seller.pos.api.orders.recent') }}",
                promoValidateUrl:      "{{ route('seller.pos.api.promo.validate') }}",
                walletLookupUrlT:      "{{ route('seller.pos.api.customers.wallet', ['id' => '__ID__']) }}",
                cfdPushUrl:            "{{ route('seller.pos.api.cfd.push') }}",
                cfdShowUrlT:           "{{ url('/pos/display') }}/__TOKEN__",
                parkedListUrl:         "{{ route('seller.pos.api.parked.list') }}",
                parkedCreateUrl:       "{{ route('seller.pos.api.parked.create') }}",
                parkedDeleteUrlT:      "{{ route('seller.pos.api.parked.delete', ['id' => '__ID__']) }}",
                paymentSessionsUrl:    "{{ route('seller.pos.api.payment_sessions.create') }}",
                paymentSessionStatusUrlTemplate: "{{ route('seller.pos.api.payment_sessions.status', ['token' => '__TOKEN__']) }}",
                paymentSessionCancelUrlTemplate: "{{ route('seller.pos.api.payment_sessions.cancel', ['token' => '__TOKEN__']) }}",
                refundPreviewUrlT:     "{{ route('seller.pos.api.refunds.preview', ['id' => '__ID__']) }}",
                refundCreateUrlT:      "{{ route('seller.pos.api.refunds.create', ['id' => '__ID__']) }}",
                refundHistoryUrlT:     "{{ route('seller.pos.api.refunds.history', ['id' => '__ID__']) }}",
                receiptUrlTemplate:    "{{ route('seller.pos.orders.receipt', ['id' => '__ID__']) }}",
                initialStoreId:        {{ (int) $defaultStore->id }},
                currencyCode:          @json($defaultStore->currency_code ?? 'USD'),
                currencySymbol:        @json($systemSettings['currencySymbol']),
                storePaymentConfig:    @json($storePaymentConfig)
            };
        </script>
        <script src="{{ asset('assets/vendor/qrcode/qrcode.min.js') }}"></script>
        <script src="{{ hyperAsset('assets/js/seller-pos.js') }}" defer></script>
    @endpush
@endif
