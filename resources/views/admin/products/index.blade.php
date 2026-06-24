@php
    use App\Enums\Product\ProductVarificationStatusEnum;
    $isApprovalView = request()->query('verification_status') === ProductVarificationStatusEnum::PENDING();
@endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $menuAdmin['products']['route'][$isApprovalView ? 'pending_approval_products' : 'products']['sub_active']])


@section('title', __('labels.products'))

@section('header_data')
    @php
        $page_title = __('labels.products');
        $page_pretitle = __('labels.admin') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ __('labels.products') }}</h3>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                            <div class="card-actions">
                                <div class="row g-2">
                                    @if($createPermission ?? false)
                                        <div class="col-auto">
                                            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                                <i class="ti ti-plus me-1"></i>{{ __('labels.add_new_product') }}
                                            </a>
                                        </div>
                                    @endif
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                            </svg>
                                            {{ __('labels.refresh') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-table">
                            <div class="card-actions mt-3 ms-2">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select" id="productTypeFilter">
                                            <option value="">{{ __('labels.product_type') }}</option>
                                            @foreach(\App\Enums\Product\ProductTypeEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productStatusFilter">
                                            <option value="">{{ __('labels.product_status') }}</option>
                                            @foreach(\App\Enums\Product\ProductStatusEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productVerificationStatusFilter">
                                            <option value="">{{ __('labels.verification_status') }}</option>
                                            @foreach(ProductVarificationStatusEnum::values() as $type)
                                                <option
                                                    value="{{ $type }}">{{ ucfirst(\Illuminate\Support\Str::replace("_", " ",$type)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productCategoryFilter"
                                                placeholder="{{ __('labels.category') }}">
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productStoreFilter"
                                                placeholder="{{ __('labels.store') }}">
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productSellerFilter"
                                                placeholder="{{ __('labels.seller') }}">
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productBadgeFilter">
                                            <option value="">{{ __('labels.badge') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Bulk badge action toolbar (visible when rows are selected) --}}
                            <div class="d-none mx-3 my-3" id="bulk-badge-toolbar">
                                <div class="d-flex align-items-center gap-2 flex-wrap p-2 form-control rounded">
                                    <span class="text-muted small me-2">
                                        <span id="bulk-selected-count">0</span> {{ __('labels.products') }} {{ __('labels.selected') }}
                                    </span>
                                    @if($editPermission ?? false)
                                        {{-- hidden trigger, clicked after JS validation passes --}}
                                        <span hidden data-bs-toggle="modal" data-bs-target="#bulk-badge-modal" id="bulk-badge-modal-trigger"></span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="bulk-assign-badge-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round" class="icon me-1">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                <path d="M3 6a3 3 0 0 1 3 -3h2.25a3 3 0 0 1 2.122 .879l7.5 7.5a3 3 0 0 1 0 4.242l-2.25 2.25a3 3 0 0 1 -4.242 0l-7.5 -7.5a3 3 0 0 1 -.879 -2.122v-2.25z"/>
                                            </svg>
                                            {{ __('labels.bulk_assign_badge') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="bulk-remove-badge-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round" class="icon me-1">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                            </svg>
                                            {{ __('labels.bulk_remove_badge') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="bulk-deselect-btn">
                                            {{ __('labels.deselect_all') ?? 'Deselect All' }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="row w-full p-3">
                                <x-datatable id="products-table" :columns="$columns"
                                             route="{{ route('admin.products.datatable') }}"
                                             :options="['order' => [[1, 'desc']],'pageLength' => 10,]"/>
                                @php
                                    $productPricingLabels = json_encode([
                                        'variant' => __('labels.variant'),
                                        'store' => __('labels.store'),
                                        'seller' => __('labels.seller'),
                                        'price' => __('labels.price'),
                                        'special_price' => __('labels.special_price'),
                                        'cost' => __('labels.cost'),
                                        'stock' => __('labels.stock'),
                                        'sku' => __('labels.sku'),
                                        'loading' => __('labels.loading'),
                                        'empty' => __('labels.no_store_pricing_configured'),
                                        'error' => __('labels.failed_to_load_pricing'),
                                        'currency' => getCurrencySymbol(),
                                    ], JSON_UNESCAPED_UNICODE);
                                @endphp
                                <div id="product-pricing-meta" hidden
                                     data-pricing-url="{{ route('admin.products.pricing', ['id' => '__ID__']) }}"
                                     data-pricing-labels="{{ $productPricingLabels }}"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="view-product-offcanvas" aria-labelledby="offcanvasEndLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="offcanvasEndLabel">Product Details</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="card card-sm border-0">
                <label class="fw-medium pb-1">Image</label>
                <div class="img-box-200px-h card-img">
                    <img id="product-image" src=""/>
                </div>
                <div class="card-body px-0">
                    <div>
                        <h4 id="product-name" class="fs-3"></h4>
                        <p id="product-description" class="fs-4"></p>
                        <p class="col-md-8 d-flex justify-content-between">Status: <span id="product-status"
                                                                                         class="badge bg-green-lt text-uppercase fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Category: <span id="product-category"
                                                                                           class="fw-medium"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Bulk assign badge modal --}}
    <div class="modal modal-blur fade" id="bulk-badge-modal" tabindex="-1" role="dialog" aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.bulk_assign_badge') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label required">{{ __('labels.select_badge') }}</label>
                    <select class="form-select" id="bulk-badge-select">
                        <option value="">{{ __('labels.select_badge') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                    <button type="button" class="btn btn-primary" id="bulk-badge-confirm">
                        {{ __('labels.assign_badge') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Single-product assign badge modal --}}
    <div class="modal modal-blur fade" id="assign-badge-modal" tabindex="-1" role="dialog" aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.assign_badge') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assign-badge-product-id" value="">
                    <label class="form-label">{{ __('labels.select_badge') }}</label>
                    <select class="form-select" id="assign-badge-select">
                        <option value="">{{ __('labels.no_badge') }}</option>
                    </select>
                    <div class="form-hint mt-2">
                        {{ __('labels.leave_badge_empty_hint') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                    <button type="button" class="btn btn-primary" id="assign-badge-confirm">
                        {{ __('labels.assign_badge') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.productBadgeLabels = {
            selectProductsFirst: @json(__('labels.select_products_first')),
            selectBadgeFirst: @json(__('labels.select_badge_first')),
            bulkAssignUrl: @json(route('admin.badges.products.bulk-assign')),
            bulkRemoveUrl: @json(route('admin.badges.products.bulk-remove')),
            badgeSearchUrl: @json(route('admin.badges.search')),
            badgePlaceholder: @json(__('labels.badge')),
            selectBadgePlaceholder: @json(__('labels.select_badge')),
            noBadgePlaceholder: @json(__('labels.no_badge')),
        };
    </script>
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
    <script src="{{hyperAsset('assets/js/product-badge-search.js')}}" defer></script>
@endpush
