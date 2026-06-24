@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? "", 'sub_page' => $menuAdmin['customers']['route']['customers']['sub_active'] ?? null])

@section('title', $customer->name ?? __('labels.customer_details'))

@section('header_data')
    @php
        $page_title = $customer->name ?? __('labels.customer');
        $page_pretitle = __('labels.customer_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.customers'), 'url' => route('admin.customers.index')],
        ['title' => $customer->name ?? __('labels.customer'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title text-capitalize">{{ $customer->name ?? '' }} - {{ __('labels.customer') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
        </div>

        <!-- PAGE BODY -->
        <div class="page-body">
            {{-- PROFILE CARD --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar avatar-xl rounded"
                                  style="background-image: url('{{ $profileImageUrl }}')">
                            </span>
                        </div>
                        <div class="col">
                            <h2 class="mb-1">{{ $customer->name ?? 'N/A' }}</h2>
                            <div class="text-secondary mb-1">
                                <i class="ti ti-mail me-1"></i>{{ $customer->email ?? 'N/A' }}
                                <span class="mx-2">|</span>
                                <i class="ti ti-phone me-1"></i>{{ $customer->mobile ?? 'N/A' }}
                            </div>
                            <div class="mt-2">
                                <span class="badge border p-2 {{ $customer->status ? 'bg-success-lt border-success-subtle' : 'bg-danger-lt border-danger-subtle' }}">
                                    {{ $customer->status ? __('labels.active') : __('labels.inactive') }}
                                </span>
                                @if($customer->email_verified_at)
                                    <span class="badge border p-2 bg-success-lt border-success-subtle">
                                        <i class="ti ti-mail-check me-1"></i>{{ __('labels.email_verified') }}
                                    </span>
                                @else
                                    <span class="badge border p-2 bg-warning-lt border-warning-subtle">
                                        <i class="ti ti-mail-x me-1"></i>{{ __('labels.email_not_verified') }}
                                    </span>
                                @endif
                                @if($customer->mobile_verified_at)
                                    <span class="badge border p-2 bg-success-lt border-success-subtle">
                                        <i class="ti ti-phone-check me-1"></i>{{ __('labels.mobile_verified') }}
                                    </span>
                                @else
                                    <span class="badge border p-2 bg-warning-lt border-warning-subtle">
                                        <i class="ti ti-phone-x me-1"></i>{{ __('labels.mobile_not_verified') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <div class="text-secondary small">{{ __('labels.registration_date') }}</div>
                                <div class="fw-bold">{{ $customer->created_at->format('d M Y') }}</div>
                                @if($customer->referral_code)
                                    <div class="text-secondary small mt-1">{{ __('labels.referral_code') }}: <code>{{ $customer->referral_code }}</code></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STAT CARDS ROW --}}
            <div class="row row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-primary text-white avatar"><i class="ti ti-shopping-cart"></i></span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold fs-3">{{ $orderStats['total'] }}</div>
                                    <div class="text-secondary">{{ __('labels.total_orders') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-success text-white avatar"><i class="ti ti-circle-check"></i></span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold fs-3">{{ $orderStats['delivered'] }}
                                        <span class="text-secondary fs-5">({{ $orderStats['completion_rate'] }}%)</span>
                                    </div>
                                    <div class="text-secondary">{{ __('labels.completed_orders') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-info text-white avatar"><i class="ti ti-receipt-2"></i></span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold fs-3">{{ getCurrencySymbol() . number_format($orderStats['total_spent'], 2) }}</div>
                                    <div class="text-secondary">{{ __('labels.total_spent') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-cyan text-white avatar"><i class="ti ti-wallet"></i></span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold fs-3">{{  getCurrencySymbol() . number_format($customer->wallet?->balance ?? 0, 2) }}</div>
                                    <div class="text-secondary">{{ __('labels.wallet_balance') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABS --}}
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab" role="tab" aria-selected="true">
                                <i class="ti ti-info-circle me-1"></i>{{ __('labels.overview') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-orders" class="nav-link" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-shopping-cart me-1"></i>{{ __('labels.orders') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-wallet" class="nav-link" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-wallet me-1"></i>{{ __('labels.wallet') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-addresses" class="nav-link" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-map-pin me-1"></i>{{ __('labels.addresses') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-notifications" class="nav-link" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-bell me-1"></i>{{ __('labels.notifications') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        {{-- TAB: OVERVIEW --}}
                        <div class="tab-pane active show" id="tab-overview" role="tabpanel">
                            <div class="row row-cards">
                                {{-- Personal Information --}}
                                <div class="col-lg-6">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.personal_information') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table mb-0" style="border: none;">
                                                    <tbody>
                                                    <tr>
                                                        <td class="fw-bold border-0" style="width: 180px;">{{ __('labels.full_name') }}</td>
                                                        <td class="border-0">{{ $customer->name ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.email') }}</td>
                                                        <td class="border-0">{{ $customer->email ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.mobile') }}</td>
                                                        <td class="border-0">{{ $customer->mobile ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.country') }}</td>
                                                        <td class="border-0">{{ $customer->country ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.country_code') }}</td>
                                                        <td class="border-0">{{ $customer->country_code ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.referral_code') }}</td>
                                                        <td class="border-0">{{ $customer->referral_code ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.friends_code') }}</td>
                                                        <td class="border-0">{{ $customer->friends_code ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.reward_points') }}</td>
                                                        <td class="border-0">
                                                            <span class="badge bg-purple-lt">{{ getCurrencySymbol() . $customer->reward_points ?? 0 }}</span>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Verification Details --}}
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.verification_details') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table mb-0" style="border: none;">
                                                    <tbody>
                                                    <tr>
                                                        <td class="fw-bold border-0" style="width: 180px;">{{ __('labels.email_verified') }}</td>
                                                        <td class="border-0">
                                                            @if($customer->email_verified_at)
                                                                <span class="badge bg-success-lt">{{ $customer->email_verified_at->format('d M Y H:i') }}</span>
                                                            @else
                                                                <span class="badge bg-warning-lt">{{ __('labels.not_verified') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.mobile_verified') }}</td>
                                                        <td class="border-0">
                                                            @if($customer->mobile_verified_at)
                                                                <span class="badge bg-success-lt">{{ $customer->mobile_verified_at->format('d M Y H:i') }}</span>
                                                            @else
                                                                <span class="badge bg-warning-lt">{{ __('labels.not_verified') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.login_type') }}</td>
                                                        <td class="border-0">{{ ucfirst($customer->logged_in_type?->value ?? 'N/A') }}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Order Breakdown + Map --}}
                                <div class="col-lg-6">
                                    {{-- Primary Address Map --}}
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.primary_address') }}</h4>
                                        </div>
                                        <div class="card-body p-0">
                                            <div id="customer-map" style="height: 300px; width: 100%;"></div>
                                        </div>
                                    </div>

                                    {{-- Order Breakdown --}}
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.order_breakdown') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $total = max($orderStats['total'], 1);
                                                $breakdowns = [
                                                    ['label' => __('labels.delivered'), 'count' => $orderStats['delivered'], 'class' => 'bg-success'],
                                                    ['label' => __('labels.pending'), 'count' => $orderStats['pending'], 'class' => 'bg-warning'],
                                                    ['label' => __('labels.in_progress'), 'count' => $orderStats['in_progress'], 'class' => 'bg-info'],
                                                    ['label' => __('labels.cancelled'), 'count' => $orderStats['cancelled'], 'class' => 'bg-danger'],
                                                ];
                                            @endphp
                                            @foreach($breakdowns as $item)
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>{{ $item['label'] }}</span>
                                                        <span class="fw-bold">{{ $item['count'] }}</span>
                                                    </div>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar {{ $item['class'] }}"
                                                             style="width: {{ round(($item['count'] / $total) * 100) }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB: ORDERS --}}
                        <div class="tab-pane" id="tab-orders" role="tabpanel">
                            <x-datatable
                                id="customer-orders-table"
                                :route="route('admin.customers.orders-datatable', $customer->id)"
                                :columns="$orderColumns"
                            />
                        </div>

                        {{-- TAB: WALLET --}}
                        <div class="tab-pane" id="tab-wallet" role="tabpanel">
                            <div class="row row-cards mb-4">
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="fw-bold fs-3">{{  getCurrencySymbol() . number_format($customer->wallet?->balance ?? 0, 2) }}</div>
                                            <div class="text-secondary">{{ __('labels.current_balance') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="fw-bold fs-3">{{  getCurrencySymbol() . $customer->reward_points ?? 0 }}</div>
                                            <div class="text-secondary">{{ __('labels.reward_points') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Wallet Transactions DataTable --}}
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">{{ __('labels.recent_transactions') }}</h4>
                                </div>
                                <div class="card-body">
                                    <x-datatable
                                        id="customer-wallet-table"
                                        :route="route('admin.customers.wallet-datatable', $customer->id)"
                                        :columns="$walletColumns"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- TAB: ADDRESSES --}}
                        <div class="tab-pane" id="tab-addresses" role="tabpanel">
                            @if($addresses->count() > 0)
                                <div class="row row-cards">
                                    @foreach($addresses as $address)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-2">
                                                        @php
                                                            $typeIcon = match(strtolower($address->address_type ?? '')) {
                                                                'home' => 'ti-home',
                                                                'office', 'work' => 'ti-building',
                                                                default => 'ti-map-pin',
                                                            };
                                                        @endphp
                                                        <i class="ti {{ $typeIcon }} me-2 text-primary"></i>
                                                        <span class="badge bg-blue-lt">{{ ucfirst($address->address_type ?? 'Other') }}</span>
                                                    </div>
                                                    <div class="mb-1">{{ $address->address_line1 ?? '' }}</div>
                                                    @if($address->address_line2)
                                                        <div class="mb-1 text-secondary">{{ $address->address_line2 }}</div>
                                                    @endif
                                                    <div class="text-secondary small">
                                                        {{ collect([$address->city, $address->state, $address->zipcode])->filter()->implode(', ') }}
                                                    </div>
                                                    @if($address->landmark)
                                                        <div class="text-muted small mt-1">
                                                            <i class="ti ti-flag me-1"></i>{{ $address->landmark }}
                                                        </div>
                                                    @endif
                                                    @if($address->mobile)
                                                        <div class="text-muted small mt-1">
                                                            <i class="ti ti-phone me-1"></i>{{ $address->mobile }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">
                                    <div class="empty-icon"><i class="ti ti-map-pin-off"></i></div>
                                    <p class="empty-title">{{ __('labels.no_addresses_found') }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- TAB: NOTIFICATIONS --}}
                        <div class="tab-pane" id="tab-notifications" role="tabpanel">
                            <x-datatable
                                id="customer-notifications-table"
                                :route="route('admin.customers.notifications-datatable', $customer->id)"
                                :columns="$notificationColumns"
                            />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}"/>
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}"></script>
    @php
        $primaryAddress = $addresses->first();
        $hasLocation = $primaryAddress && $primaryAddress->latitude && $primaryAddress->longitude;
    @endphp
    <script>
        window.__CUSTOMER_DETAIL_CONFIG__ = {
            hasLocation: {{ $hasLocation ? 'true' : 'false' }},
            latitude: {{ $primaryAddress->latitude ?? 0 }},
            longitude: {{ $primaryAddress->longitude ?? 0 }},
            name: @json($customer->name ?? 'Customer'),
        };
    </script>
    <script src="{{ asset('assets/js/customer-detail.js') }}" defer></script>
@endpush
