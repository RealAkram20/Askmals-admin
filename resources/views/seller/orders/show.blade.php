@php use App\Enums\Order\OrderItemStatusEnum;use App\Enums\Order\OrderStatusEnum; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['orders']['active'] ?? ""])

@section('title', __('labels.order_details'))

@section('header_data')
    @php
        $page_title = __('labels.order_details');
        $page_pretitle = __('labels.seller') . " " . __('labels.order_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.orders'), 'url' => route('seller.orders.index')],
        ['title' => __('labels.order_details'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-lg-auto">
                        <div class="page-pretitle">{{ __('labels.seller') }} · {{ __('labels.orders') }}</div>
                        <h2 class="page-title d-flex align-items-center flex-wrap gap-2">
                            <span>{{ __('labels.order') }} #{{ $order['order_id'] }}</span>
                            <span class="badge {{ $order['status'] }} text-uppercase">
                                {{ Str::ucfirst(Str::replace('_', ' ', $order['status'])) }}
                            </span>
                            @if(!empty($order['is_rush_order']))
                                <span class="badge bg-orange-lt text-orange">
                                    <i class="ti ti-bolt me-1"></i>{{ __('labels.is_rush_order') ?? 'Rush' }}
                                </span>
                            @endif
                        </h2>
                        <div class="text-secondary small mt-1 d-flex flex-wrap gap-3">
                            <span><i class="ti ti-user me-1"></i>{{ $order['billing_name'] ?? '—' }}</span>
                            <span><i class="ti ti-credit-card me-1"></i>{{ Str::upper($order['payment_method'] ?? '—') }}</span>
                            <span><i class="ti ti-clock me-1"></i>{{ $order['created_at'] ?? '—' }}</span>
                            @if(!empty($order['delivery_zone']['name']))
                                <span><i class="ti ti-map-pin me-1"></i>{{ $order['delivery_zone']['name'] }}</span>
                            @endif
                            @if(!empty($order['uuid']))
                                <span class="font-monospace text-truncate" title="{{ $order['uuid'] }}">
                                    <i class="ti ti-hash me-1"></i>{{ Str::limit($order['uuid'], 24, '…') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="col-auto ms-auto d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="text-secondary small">{{ __('labels.total_price') }}</div>
                            <div class="h2 mb-0">{{ $systemSettings['currencySymbol'] . number_format((float) ($order['total_price'] ?? 0), 2) }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-auto d-print-none">
                        <div class="btn-list" id="sellerOrderActions" data-order-id="{{ $order['id'] }}">
                            <a href="{{ route('seller.orders.index') }}"
                               class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>{{ __('labels.back_to_orders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- END PAGE HEADER --}}

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    {{-- Order Summary — colored status strip + icon header.
                         Status + total are already in the page header so we
                         drop the duplicate rows here for a tighter datagrid. --}}
                    <div class="col-12 col-lg-6">
                        @php
                            $sellerPaymentStatus = strtolower((string) ($order['payment_status'] ?? ''));
                            $sellerPaymentBadgeClass = match (true) {
                                in_array($sellerPaymentStatus, ['paid', 'completed', 'success'], true)         => 'bg-green-lt',
                                in_array($sellerPaymentStatus, ['failed', 'declined'], true)                   => 'bg-red-lt',
                                in_array($sellerPaymentStatus, ['refunded', 'partially_refunded'], true)       => 'bg-blue-lt',
                                default                                                                        => 'bg-yellow-lt',
                            };
                            $sellerStatusStripClass = match (true) {
                                in_array($order['status'] ?? '', ['cancelled', 'rejected_by_seller', 'failed'], true) => 'bg-red',
                                in_array($order['status'] ?? '', ['delivered'], true)                                  => 'bg-success',
                                in_array($order['status'] ?? '', ['out_for_delivery', 'collected', 'assigned'], true)  => 'bg-blue',
                                default                                                                                => 'bg-primary',
                            };
                        @endphp
                        <div class="card">
                            <div class="card-status-start {{ $sellerStatusStripClass }}"></div>
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-receipt me-1"></i>{{ __('labels.order_summary') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_number') }}</div>
                                        <div class="datagrid-content">#{{ $order['order_id'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_date') }}</div>
                                        <div class="datagrid-content">{{ $order['created_at'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_method') }}</div>
                                        <div class="datagrid-content text-uppercase">{{ $order['payment_method'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span class="badge {{ $sellerPaymentBadgeClass }}">
                                                {{ $order['payment_status'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.total_price') }}</div>
                                        <div class="datagrid-content fw-semibold">
                                            {{ $systemSettings['currencySymbol'] . number_format($order['total_price'], 2) }}
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.uuid') }}</div>
                                        <div class="datagrid-content text-truncate font-monospace small" title="{{ $order['uuid'] }}">
                                            {{ $order['uuid'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(!empty($order['order_note']))
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="ti ti-message-2 me-1"></i>{{ __('labels.order_note') }}
                                    </h3>
                                </div>
                                <div class="card-body py-2">
                                    <p class="text-secondary mb-0">{{ $order['order_note'] }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-12 col-lg-6">
                        {{-- Customer card — avatar-led, mobile is a tel: link,
                             email is a mailto: link. Same rhythm as admin. --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-user-circle me-1"></i>{{ __('labels.customer_information') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <span class="avatar avatar-md me-3 bg-primary-lt">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <div class="flex-fill">
                                        <div class="fw-semibold text-capitalize">{{ $order['billing_name'] ?? '—' }}</div>
                                        <div class="text-secondary small d-flex flex-wrap gap-3 mt-1">
                                            @if(!empty($order['email']))
                                                <a href="mailto:{{ $order['email'] }}" class="text-decoration-none">
                                                    <i class="ti ti-mail me-1"></i>{{ $order['email'] }}
                                                </a>
                                            @endif
                                            @if(!empty($order['billing_phone']))
                                                <a href="tel:{{ $order['billing_phone'] }}" class="text-decoration-none">
                                                    <i class="ti ti-phone me-1"></i>{{ $order['billing_phone'] }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping address — recipient called out separately
                             when it differs from billing name. --}}
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-map-pin me-1"></i>{{ __('labels.shipping_address') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                @if(!empty($order['shipping_name']) && $order['shipping_name'] !== ($order['billing_name'] ?? null))
                                    <div class="fw-semibold text-capitalize mb-1">
                                        <i class="ti ti-package me-1 text-secondary"></i>{{ $order['shipping_name'] }}
                                    </div>
                                @endif
                                <address class="mb-2 small text-secondary">
                                    {{ $order['shipping_address_1'] }}@if($order['shipping_address_2']), {{ $order['shipping_address_2'] }}@endif<br>
                                    @if($order['shipping_landmark'])
                                        {{ $order['shipping_landmark'] }}<br>
                                    @endif
                                    {{ $order['shipping_city'] }}, {{ $order['shipping_state'] }} {{ $order['shipping_zip'] }}<br>
                                    {{ $order['shipping_country'] }}
                                </address>
                                @if(!empty($order['shipping_phone']))
                                    <a href="tel:{{ $order['shipping_phone'] }}" class="text-decoration-none small">
                                        <i class="ti ti-phone me-1"></i>{{ $order['shipping_phone'] }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Delivery card — read-only on the seller side. Shows
                             which rider is coming, their phone (clickable), the
                             delivery zone, and ETA. Empty state when unassigned. --}}
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ti ti-truck-delivery me-1"></i>{{ __('labels.delivery_information') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                @if(!empty($order['delivery_boy']) && !empty($order['delivery_boy']['id']))
                                    <div class="d-flex align-items-start mb-3">
                                        <span class="avatar avatar-md me-3 bg-blue-lt">
                                            <i class="ti ti-motorbike"></i>
                                        </span>
                                        <div class="flex-fill">
                                            <div class="fw-semibold text-capitalize">
                                                {{ $order['delivery_boy']['full_name'] ?? __('labels.delivery_boy') }}
                                                @if(!empty($order['delivery_boy']['is_blocked']))
                                                    <span class="badge bg-danger-lt text-danger ms-1">
                                                        <i class="ti ti-lock me-1"></i>{{ __('labels.blocked') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-secondary small">
                                                @if(!empty($order['delivery_boy']['mobile']))
                                                    <a href="tel:{{ $order['delivery_boy']['mobile'] }}" class="text-decoration-none me-3">
                                                        <i class="ti ti-phone me-1"></i>{{ $order['delivery_boy']['mobile'] }}
                                                    </a>
                                                @endif
                                                @if(!empty($order['delivery_boy']['email']))
                                                    <a href="mailto:{{ $order['delivery_boy']['email'] }}" class="text-decoration-none">
                                                        <i class="ti ti-mail me-1"></i>{{ $order['delivery_boy']['email'] }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center text-secondary mb-3">
                                        <span class="avatar avatar-md me-3 bg-secondary-lt">
                                            <i class="ti ti-user-question"></i>
                                        </span>
                                        <div>
                                            <div class="fw-semibold">{{ __('labels.no_rider_assigned') }}</div>
                                            <div class="small">{{ __('labels.no_rider_assigned_hint') }}</div>
                                        </div>
                                    </div>
                                @endif

                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.delivery_zone') }}</div>
                                        <div class="datagrid-content">
                                            {{ $order['delivery_zone']['name'] ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.estimated_delivery_time') }}</div>
                                        <div class="datagrid-content">
                                            {{ $order['estimated_delivery_time'] ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        // Bulk UI is hidden once the order has exited the seller-actionable
                        // window. Mirrors the admin "$isOrderTerminal" gating.
                        $sellerOrderTerminal = in_array($order['status'] ?? null, [
                            OrderStatusEnum::COLLECTED(),
                            OrderStatusEnum::OUT_FOR_DELIVERY(),
                            OrderStatusEnum::DELIVERED(),
                            OrderStatusEnum::FAILED(),
                            OrderStatusEnum::CANCELLED(),
                        ], true);
                        $bulkEnabled = !$sellerOrderTerminal;
                    @endphp

                    <!-- Order Items Card -->
                    <div class="col-12 mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.order_items') }}</h3>
                                @if($bulkEnabled)
                                    <div class="card-actions">
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                id="sellerBulkUpdateBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#sellerBulkUpdateStatusModal"
                                                disabled>
                                            <i class="ti ti-refresh me-1"></i>{{ __('labels.bulk_update_status') }}
                                            <span class="badge bg-white text-primary ms-2 d-none" id="sellerBulkSelectedCount">0</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            @if($bulkEnabled)
                                                <th width="30">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           id="sellerSelectAllItems"
                                                           aria-label="{{ __('labels.select_all') }}">
                                                </th>
                                            @endif
                                            <th>{{ __('labels.product') }}</th>
                                            <th>{{ __('labels.variant') }}</th>
                                            <th>{{ __('labels.price') }}</th>
                                            <th>{{ __('labels.status') }}</th>
                                            <th>{{ __('labels.quantity') }}</th>
                                            <th>{{ __('labels.subtotal') }}</th>
                                            <th class="text-end">{{ __('labels.actions') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($order['items'] as $item)
                                            @php
                                                $itemId = $item['orderItem']['id'] ?? null;
                                                $hasTransitions = !empty($itemTransitions[$itemId] ?? []);
                                            @endphp
                                            <tr>
                                                @if($bulkEnabled)
                                                    <td>
                                                        <input type="checkbox"
                                                               class="form-check-input seller-item-checkbox"
                                                               name="item_ids[]"
                                                               value="{{ $itemId }}"
                                                               @disabled(!$hasTransitions)>
                                                    </td>
                                                @endif
                                                <td>{{ $item['product']['title'] ?? 'N/A' }}
                                                    @if(!empty($item['attachments']))
                                                        <br class="mt-7">
                                                        <span class="fw-medium">Attachments</span>
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($item['attachments'] as $attachment)
                                                                <li><a href="{{ $attachment }}"
                                                                       target="_blank"
                                                                       data-fslightbox="gallery">view</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                    @if(!empty($item['addons']))
                                                        <div class="mt-2">
                                                            <span class="fw-medium">{{ __('labels.addons') }}</span>
                                                            <ul class="list-unstyled small mb-0 ps-3">
                                                                @foreach($item['addons'] as $addon)
                                                                    <li>
                                                                        @if(!empty($addon['group']['title']))
                                                                            <span class="text-muted">{{ $addon['group']['title'] }} —</span>
                                                                        @endif
                                                                        {{ $addon['item']['title'] ?? '—' }}
                                                                        <span class="text-muted">· {{ $systemSettings['currencySymbol'] . number_format((float) $addon['price'], 2) }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td>{{ $item['variant']['title'] ?? 'N/A' }}</td>
                                                <td>{{$systemSettings['currencySymbol'] . number_format($item['price'] + $item['tax_amount'], 2) }}</td>
                                                <td><span class="badge {{ $item['orderItem']['status'] }}">
                                                {{ $item['orderItem']['status_formatted'] }}
                                                </span></td>
                                                <td>{{ $item['quantity'] }}</td>
                                                <td>
                                                    {{ $systemSettings['currencySymbol'] . number_format($item['sub_total'], 2) }}
                                                    @if(!empty($item['addons_total']) && (float) $item['addons_total'] > 0)
                                                        <div class="small text-muted">
                                                            {{ __('labels.addons_total') }}: {{ $systemSettings['currencySymbol'] . number_format((float) $item['addons_total'], 2) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                @php $itemStatus = $item['orderItem']['status'] ?? null; @endphp
                                                <td class="text-end">
                                                    @if(in_array($itemStatus, [\App\Enums\Order\OrderItemStatusEnum::ACCEPTED(), \App\Enums\Order\OrderItemStatusEnum::PREPARING()], true))
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#cancelItemModal"
                                                                data-id="{{ $item['orderItem']['id'] }}">
                                                            <i class="ti ti-ban me-1"></i>{{ __('labels.cancel_item') }}
                                                        </button>
                                                    @elseif($itemStatus === \App\Enums\Order\OrderItemStatusEnum::RETURNING_TO_STORE())
                                                        <button type="button"
                                                                class="btn btn-sm btn-success"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#confirmReturnModal"
                                                                data-id="{{ $item['orderItem']['id'] }}">
                                                            <i class="ti ti-package-import me-1"></i>{{ __('labels.confirm_received') }}
                                                        </button>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        @php
                                            // Without checkbox col: product, variant, price, status = 4
                                            // With checkbox col: +1 = 5
                                            $sellerColspan = $bulkEnabled ? 5 : 4;
                                        @endphp
                                        <tr>
                                            <td colspan="{{ $sellerColspan }}" class="text-end"><strong>{{ __('labels.total') }}:</strong>
                                            </td>
                                            <td><strong>{{ collect($order['items'])->sum('quantity')  }}</strong></td>
                                            <td>
                                                <strong>{{$systemSettings['currencySymbol'] . number_format($order['total_price'], 2) }}</strong>
                                            </td>
                                            <td></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- The legacy inline "Update Status" card was retired here — the
                         shared bulk-update modal now lives in the items card header. --}}
                </div>
            </div>
        </div>
    </div>

    @include('seller.orders.partials.order-cancel-item-modal')
    @include('seller.orders.partials.order-confirm-return-modal')
    @if(!$sellerOrderTerminal)
        @include('seller.orders.partials.bulk-update-status-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/order.js') }}"></script>
    @if(!$sellerOrderTerminal)
        <script>
            window.sellerOrderEndpoints = {
                itemsBulkUpdateStatus: '{{ route('seller.orders.items_bulk_update_status', ['id' => $order['id'] ?? 0]) }}',
            };
            // Same shape the admin page uses: { itemId: [{value,label,acting_as}, ...] }
            window.sellerOrderItemTransitions = @json($itemTransitions ?? []);
        </script>
    @endif
    <script src="{{ asset('assets/js/seller-orders.js') }}" defer></script>
@endpush
