<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DateRangeFilterEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderCreatedByEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Requests\Admin\Order\AdminAddOrderNoteRequest;
use App\Http\Requests\Admin\Order\AdminBulkUpdateItemStatusRequest;
use App\Http\Requests\Admin\Order\AdminForceCancelRequest;
use App\Http\Requests\Admin\Order\AdminForceRefundRequest;
use App\Http\Requests\Admin\Order\AdminReassignRiderRequest;
use App\Http\Requests\Seller\Order\SellerBulkUpdateItemStatusRequest;
use App\Http\Requests\Order\CancelSellerOrderItemRequest;
use App\Http\Requests\Order\ConfirmStoreReturnRequest;
use App\Http\Resources\OrderResource;
use App\Enums\SpatieMediaCollectionName;
use App\Enums\Order\OrderStatusEnum;
use App\Models\DeliveryBoyLocation;
use App\Models\Order;
use App\Models\OrderAuditLog;
use App\Models\OrderItem;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\DeliveryZoneService;
use App\Services\OrderService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    public bool $editPermission = false;
    protected OrderService $orderService;
    protected CurrencyService $currencyService;

    public function __construct(OrderService $orderService, CurrencyService $currencyService)
    {
        $this->orderService = $orderService;
        $this->currencyService = $currencyService;
        $user = auth()->user();
        if ($user) {
            if ($this->getPanel() === 'admin') {
                // Phase 3 — single ORDER_EDIT permission gates every admin write.
                $this->editPermission = $this->hasPermission(AdminPermissionEnum::ORDER_EDIT());
            } else {
                $this->editPermission = $this->hasPermission(SellerPermissionEnum::ORDER_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            }
        }
    }

    /**
     * Display a listing of the seller's orders.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', SellerOrder::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_date', 'name' => 'order_date', 'title' => __('labels.order_date'), 'orderable' => false, 'searchable' => false],
            ['data' => 'order_details', 'name' => 'order_details', 'title' => __('labels.order_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'product_details', 'name' => 'product_details', 'title' => __('labels.product_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions'), 'orderable' => false, 'searchable' => false],
        ];

        $orderColumns = [
            ['data' => 'expand', 'name' => 'expand', 'title' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.order_id')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('labels.uuid'), 'orderable' => false],
            ['data' => 'order_date', 'name' => 'created_at', 'title' => __('labels.order_date')],
            ['data' => 'buyer', 'name' => 'buyer', 'title' => __('labels.buyer_name'), 'orderable' => false],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => __('labels.payment_method'), 'orderable' => false],
            ['data' => 'items_count', 'name' => 'items_count', 'title' => __('labels.items'), 'orderable' => false, 'searchable' => false],
            ['data' => 'total', 'name' => 'total_price', 'title' => __('labels.total'), 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions'), 'orderable' => false, 'searchable' => false],
        ];

        return view($this->panelView('orders.index'), compact('columns', 'orderColumns'));
    }

    /**
     * DataTable feed for the order-grouped listing tab.
     * One row per order (admin: per Order, seller: per SellerOrder).
     */
    public function getOrdersGrouped(Request $request): JsonResponse
    {
        $draw = (int) $request->get('draw');
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';
        $status = $request->get('status');
        $paymentType = $request->get('payment_type');
        $dateRange = $request->get('range');
        $orderType = $request->get('order_type');
        $deliveryZoneId = $request->get('delivery_zone_id');
        $storeId = $request->get('store_id');

        $isSeller = $this->getPanel() === 'seller';

        if ($isSeller) {
            $seller = auth()->user()?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
            }

            $query = SellerOrder::with(['order'])
                ->withCount('items')
                ->where('seller_id', $seller->id);

            if ($status !== null && $status !== '') {
                $query->whereHas('items.orderItem', fn ($q) => $q->where('status', $status));
            }
            if ($paymentType !== null && $paymentType !== '') {
                $query->whereHas('order', fn ($q) => $q->where('payment_method', $paymentType));
            }
            if ($orderType === 'pos') {
                $query->whereHas('order', fn ($q) => $q->where('created_by', OrderCreatedByEnum::SELLER()));
            } elseif ($orderType === 'regular') {
                $query->whereHas('order', fn ($q) => $q->where(fn ($qq) => $qq->whereNull('created_by')->orWhere('created_by', OrderCreatedByEnum::CUSTOMER())));
            }
            if ($deliveryZoneId !== null && $deliveryZoneId !== '') {
                $query->whereHas('order', fn ($q) => $q->where('delivery_zone_id', (int) $deliveryZoneId));
            }
            if ($storeId !== null && $storeId !== '') {
                $query->whereHas('items.orderItem', fn ($q) => $q->where('store_id', (int) $storeId));
            }
        } else {
            $query = Order::query()->withCount('items');

            if ($status !== null && $status !== '') {
                $query->whereHas('items', fn ($q) => $q->where('status', $status));
            }
            if ($paymentType !== null && $paymentType !== '') {
                $query->where('payment_method', $paymentType);
            }
            if ($orderType === 'pos') {
                $query->where('created_by', OrderCreatedByEnum::SELLER());
            } elseif ($orderType === 'regular') {
                $query->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', OrderCreatedByEnum::CUSTOMER()));
            }
            if ($deliveryZoneId !== null && $deliveryZoneId !== '') {
                $query->where('delivery_zone_id', (int) $deliveryZoneId);
            }
        }

        if ($dateRange !== null && $dateRange !== '') {
            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
        }

        $totalRecords = $query->count();

        if (!empty($searchValue)) {
            if ($isSeller) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('id', 'like', "%$searchValue%")
                        ->orWhere('total_price', 'like', "%$searchValue%")
                        ->orWhereHas('order', function ($oq) use ($searchValue) {
                            $oq->where('id', 'like', "%$searchValue%")
                                ->orWhere('uuid', 'like', "%$searchValue%")
                                ->orWhere('shipping_name', 'like', "%$searchValue%");
                        });
                });
            } else {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('id', 'like', "%$searchValue%")
                        ->orWhere('uuid', 'like', "%$searchValue%")
                        ->orWhere('shipping_name', 'like', "%$searchValue%")
                        ->orWhere('final_total', 'like', "%$searchValue%");
                });
            }
        }

        $filteredRecords = $query->count();

        $columnMap = [
            1 => 'id',
            3 => 'created_at',
        ];
        $orderColumnIndex = (int) ($request->get('order')[0]['column'] ?? 3);
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        $orderColumn = $columnMap[$orderColumnIndex] ?? 'created_at';

        $rows = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->map(fn ($row) => $this->formatOrderGroupedRow($row, $isSeller));

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function formatOrderGroupedRow($row, bool $isSeller): array
    {
        $expand = '<button type="button" class="btn btn-icon btn-ghost-secondary expand-order-items" data-order-id="' . (int) $row->id . '" aria-label="' . e(__('labels.order_items')) . '"><i class="ti ti-chevron-right"></i></button>';

        if ($isSeller) {
            $id = $row->order_id ?? $row->id;
            $order = $row->order;
            $publicId = $order?->uuid ?? $order?->id ?? '—';
            $buyer = $order?->shipping_name ?? '—';
            $paymentMethod = $order?->payment_method ?? '—';
            $statusValue = $order?->status;
            $total = $row->total_price ?? 0;
            $detailRoute = route('seller.orders.show', $row->id);
        } else {
            $publicId = $row->uuid ?? $row->id;
            $buyer = $row->shipping_name ?? '—';
            $paymentMethod = $row->payment_method ?? '—';
            $statusValue = $row->status;
            $total = $row->final_total ?? 0;
            $detailRoute = route('admin.orders.show', $row->id);
        }

        $itemsCount = (int) ($row->items_count ?? 0);
        $itemsBadge = '<span class="badge bg-secondary-lt"><i class="ti ti-package me-1"></i>'
            . $itemsCount . ' ' . ($itemsCount === 1 ? e(__('labels.item')) : e(__('labels.items'))) . '</span>';

        $createdBy = $isSeller ? ($order?->created_by ?? null) : ($row->created_by ?? null);
        $isPosOrder = $createdBy instanceof OrderCreatedByEnum
            ? $createdBy === OrderCreatedByEnum::SELLER
            : ($createdBy === OrderCreatedByEnum::SELLER());
        $posBadge = $isPosOrder
            ? ' <span class="badge bg-purple-lt ms-1">' . e(__('labels.pos')) . '</span>'
            : '';

        $statusValue = $statusValue->value ?? $statusValue;
        return [
            'expand' => $expand,
            'id' => $id ?? $row->id,
            'order_id' => '<span class="fw-medium text-primary">#' . e($publicId) . '</span>' . $posBadge,
            'order_date' => $row->created_at?->format('Y-m-d H:i') ?? '—',
            'buyer' => '<span class="text-capitalize">' . e($buyer) . '</span>',
            'payment_method' => '<span class="badge bg-blue-lt text-uppercase">' . e($paymentMethod) . '</span>',
            'items_count' => $itemsBadge,
            'total' => '<span class="fw-semibold">' . e($this->currencyService->format($total)) . '</span>',
            'status' => view('partials.order-status', ['status' => $statusValue ?? ''])->render(),
            'actions' => '<a href="' . $detailRoute . '" class="btn btn-icon btn-outline-primary" title="' . e(__('labels.view')) . '"><i class="ti ti-eye"></i></a>',
        ];
    }

    /**
     * Returns the items belonging to an order, scoped per-panel, for the
     * inline expand row in the orders-grouped listing tab.
     */
    public function getOrderItemsForExpand(int $id): JsonResponse
    {
        try {
            $isSeller = $this->getPanel() === 'seller';

            if ($isSeller) {
                $seller = auth()->user()?->seller();
                if (!$seller) {
                    return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
                }
                $sellerOrder = SellerOrder::with([
                    'order',
                    'items.orderItem.store',
                    'items.orderItem.addons.addonGroup',
                    'items.orderItem.addons.addonItem',
                    'items.product',
                    'items.variant',
                ])
                    ->where('id', $id)
                    ->where('seller_id', $seller->id)
                    ->firstOrFail();

                $this->authorize('viewAny', $sellerOrder);

                $items = $sellerOrder->items->map(fn ($item) => $this->mapExpandItem($item->orderItem, $item->product, $item->variant));
                $detailRoute = route('seller.orders.show', $sellerOrder->id);
                $orderUuid = $sellerOrder->order?->uuid;
            } else {
                $order = Order::with([
                    'items.product',
                    'items.variant',
                    'items.store',
                    'items.addons.addonGroup',
                    'items.addons.addonItem',
                ])->findOrFail($id);

                $this->authorize('viewAny', $order);

                $items = $order->items->map(fn ($item) => $this->mapExpandItem($item, $item->product, $item->variant));
                $detailRoute = route('admin.orders.show', $order->id);
                $orderUuid = $order->uuid;
            }

            $html = view('partials.order-items-expand', [
                'items' => $items,
                'panel' => $this->getPanel(),
                'editPermission' => $isSeller && $this->editPermission,
                'detailRoute' => $detailRoute,
                'orderUuid' => $orderUuid,
                'currencyService' => $this->currencyService,
            ])->render();

            return ApiResponseType::sendJsonResponse(true, 'labels.success', ['html' => $html]);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    private function mapExpandItem($orderItem, $product, $variant): array
    {
        $image = !empty($variant?->image) ? $variant->image : ($product?->main_image ?? null);
        $addons = $orderItem?->addons ?? collect();

        $attachments = [];
        if ($orderItem) {
            foreach ($orderItem->getMedia(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS()) as $media) {
                $attachments[] = $media->getUrl();
            }
        }

        return [
            'id' => $orderItem?->id,
            'image' => $image,
            'product_title' => $product?->title ?? '—',
            'variant_title' => $variant?->title ?? null,
            'sku' => $orderItem?->sku,
            'quantity' => $orderItem?->quantity ?? 0,
            'subtotal' => $orderItem?->subtotal ?? 0,
            'status' => $orderItem?->status,
            'store' => $orderItem?->store?->name,
            'addons' => $addons,
            'attachments' => $attachments,
        ];
    }

    /**
     * Get orders datatable data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrders(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $status = $request->get('status');
        $paymentType = $request->get('payment_type');
        $dateRange = $request->get('range');
        $orderType = $request->get('order_type');
        $deliveryZoneId = $request->get('delivery_zone_id');
        $storeId = $request->get('store_id');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'order_id', 'price', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerOrderItem::with([
            'sellerOrder.order',
            'orderItem',
            'orderItem.store',
            'orderItem.addons.addonGroup',
            'orderItem.addons.addonItem',
            'variant',
            'product',
        ])
            ->whereHas('product', function ($q) {
                $q->whereNotNull('id');
            });

        if ($orderType === 'pos') {
            $query->whereHas('sellerOrder.order', fn ($q) => $q->where('created_by', OrderCreatedByEnum::SELLER()));
        } elseif ($orderType === 'regular') {
            $query->whereHas('sellerOrder.order', fn ($q) => $q->where(fn ($qq) => $qq->whereNull('created_by')->orWhere('created_by', OrderCreatedByEnum::CUSTOMER())));
        }

        if ($deliveryZoneId !== null && $deliveryZoneId !== '') {
            $query->whereHas('sellerOrder.order', fn ($q) => $q->where('delivery_zone_id', (int) $deliveryZoneId));
        }

        if ($storeId !== null && $storeId !== '') {
            $query->whereHas('orderItem', fn ($q) => $q->where('store_id', (int) $storeId));
        }

        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: []
                );
            }

            $query->whereHas('sellerOrder', function ($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            });
            $query->whereHas('orderItem', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        }
        $totalRecords = $query->count();

        // Filter by status if provided
        if ($status !== null && $status !== '') {
            $query->whereHas('orderItem', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }
        // Filter by status if provided
        if ($paymentType !== null && $paymentType !== '') {
            $query->whereHas('sellerOrder', function ($q) use ($paymentType) {
                $q->whereHas('order', function ($q) use ($paymentType) {
                    $q->where('payment_method', $paymentType);
                });
            });
        }

        // Filter by date range if provided
        if ($dateRange !== null && $dateRange !== '') {

            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
        }

        // Search functionality
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('seller_order_id', 'like', "%$searchValue%")
                    ->orWhereHas('sellerOrder', function ($orderQuery) use ($searchValue) {
                        $orderQuery->where('total_price', 'like', "%$searchValue%")
                            ->orWhereHas('order', function ($orderQuery) use ($searchValue) {
                                $orderQuery->where('shipping_name', 'like', "%$searchValue%");
                                $orderQuery->orWhere('id', 'like', "%$searchValue%");
                            });
                    })
                    ->orWhereHas('orderItem', function ($orderItemQuery) use ($searchValue) {
                        $orderItemQuery->where('status', 'like', "%$searchValue%")
                            ->orWhere('id', 'like', "%$searchValue%");

                    })
                    ->orWhereHas('product', function ($productQuery) use ($searchValue) {
                        $productQuery->where('title', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('variant', function ($variantQuery) use ($searchValue) {
                        $variantQuery->where('title', 'like', "%$searchValue%");
                    });
            });
        }
        $filteredRecords = $query->count();

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($sellerOrderItem) {
                return $this->getOrderReturnData($sellerOrderItem);
            });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getOrderReturnData($sellerOrderItem): array
    {
        $variantTitle = $sellerOrderItem->product->type === ProductTypeEnum::SIMPLE() ? "" : ($sellerOrderItem->variant->title ?? "");
        $storeName = $sellerOrderItem->orderItem->store ? $sellerOrderItem->orderItem->store->name : 'N/A';
        $orderNote = !empty($sellerOrderItem->sellerOrder->order->order_note) ? "<textarea class='form-control' rows='1' readonly disabled>order note:- {$sellerOrderItem->sellerOrder->order->order_note}</textarea>" : null;
        $addonsMarkup = $this->renderAddonsSnippet($sellerOrderItem->orderItem);
        $attachmentsMarkup = $this->renderAttachmentsBadge($sellerOrderItem->orderItem);
        $status = $sellerOrderItem->orderItem->status->value ?? $sellerOrderItem->orderItem->status;
        $orderCreatedBy = $sellerOrderItem->sellerOrder->order->created_by ?? null;
        $isPos = $orderCreatedBy instanceof OrderCreatedByEnum
            ? $orderCreatedBy === OrderCreatedByEnum::SELLER
            : ($orderCreatedBy === OrderCreatedByEnum::SELLER());
        $posBadge = $isPos ? ' <span class="badge bg-purple-lt">' . e(__('labels.pos')) . '</span>' : '';

        return [
            'id' => $sellerOrderItem->order_item_id,
            'order_date' =>
                "<div><p class='m-0 fw-medium'>" . $sellerOrderItem->created_at->diffForHumans() . "</p>
                        {$sellerOrderItem->created_at->format('Y-m-d H:i:s')}
                        </div>",
            'order_details' => "<div class='d-flex justify-content-start align-items-center'><div class='pe-2'>" .
                view('partials.image', [
                    'image' => !empty($sellerOrderItem->variant->image) ? $sellerOrderItem->variant->image : $sellerOrderItem->product->main_image,
                ])->render() .
                "</div><div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": {$sellerOrderItem->sellerOrder->order_id} {$posBadge}</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($sellerOrderItem->sellerOrder->order->shipping_name) . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($sellerOrderItem->sellerOrder->order->payment_method) . "</p>
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . ($sellerOrderItem->sellerOrder->order->is_rush_order ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $status)) . "</p>"
                . $orderNote .
                "</div></div>",
            'product_details' => "<div>
                        <a href='" . route($this->getPanel() . '.products.show', ['id' => $sellerOrderItem->product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$sellerOrderItem->product->title}</a>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.variant_name') . ": $variantTitle</p>
                        <p class='m-0 fw-medium text-capitalize'>" . __('labels.store_name') . ": $storeName</p>
                        <p class='m-0'>" . __('labels.sku') . ": {$sellerOrderItem->orderItem->sku}</p>
                        <p class='m-0 fw-medium'>" . __('labels.quantity') . ": {$sellerOrderItem->orderItem->quantity}</p>
                        <p class='m-0 fw-medium'>" . __('labels.item_sub_total') . ": " . $this->currencyService->format($sellerOrderItem->orderItem->subtotal) . "</p>
                        $addonsMarkup
                        $attachmentsMarkup
                        </div>",
            'status' => view('partials.order-status', [
                'status' => $status,
            ])->render(),
            'actions' => view('partials.order-actions', [
                'panel' => $this->getPanel(),
                'uuid' => $sellerOrderItem->sellerOrder->order->uuid,
                'id' => $sellerOrderItem->orderItem->id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route($this->panelView('orders.show'), $this->getPanel() === 'seller' ? $sellerOrderItem->sellerOrder->id : $sellerOrderItem->sellerOrder->order_id),
                'title' => __('labels.edit_order') . $sellerOrderItem->sellerOrder->id,
                'status' => $sellerOrderItem->orderItem->status,
                'editPermission' => $this->getPanel() === 'admin' ? false : $this->editPermission,
            ])->render(),
        ];
    }

    private function getDateRange($dateRange): ?Carbon
    {
        $fromDate = null;
        $now = Carbon::now();
        switch ($dateRange) {
            case DateRangeFilterEnum::LAST_30_MINUTES():
                $fromDate = $now->copy()->subMinutes(30);
                break;
            case DateRangeFilterEnum::LAST_1_HOUR():
                $fromDate = $now->copy()->subHour();
                break;
            case DateRangeFilterEnum::LAST_5_HOURS():
                $fromDate = $now->copy()->subHours(5);
                break;
            case DateRangeFilterEnum::LAST_1_DAY():
                $fromDate = $now->copy()->subDay();
                break;
            case DateRangeFilterEnum::LAST_7_DAYS():
                $fromDate = $now->copy()->subDays(7);
                break;
            case DateRangeFilterEnum::LAST_30_DAYS():
                $fromDate = $now->copy()->subDays(30);
                break;
            case DateRangeFilterEnum::LAST_365_DAYS():
                $fromDate = $now->copy()->subDays(365);
                break;
        }
        return $fromDate;
    }


    /**
     * Display the specified order.
     *
     * @param int $id
     * @return View
     */
    public function show($id): View
    {
        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                abort(404, __('labels.seller_not_found'));
            }
            $order = SellerOrder::where('id', $id)
                ->with([
                    'order',
                    'items.product',
                    'items.variant',
                    'items.orderItem',
                    'items.orderItem.addons.addonGroup',
                    'items.orderItem.addons.addonItem',
                    'order.items.store',
                    'order.deliveryBoy.user',
                    'order.deliveryZone',
                ])
                ->where('seller_id', $seller->id)
                ->firstOrFail();
        } else {
            $order = Order::with([
                'items',
                'items.product',
                'items.variant',
                'items.store',
                // Admin Stores card needs the seller + user behind each store.
                'items.store.seller.user',
                'items.addons.addonGroup',
                'items.addons.addonItem',
                'promoLine',
                // Admin detail page renders rider + zone in the Delivery card,
                // plus persisted assignment earnings for the rider chip & the
                // Force Cancel "pay rider" preview.
                'deliveryBoy.user',
                'deliveryZone',
                'deliveryBoyAssignments',
            ])
                ->findOrFail($id);

            // Pre-compute the delivery route so getDeliveryBoyEarningsAttribute()
            // can return a live breakdown even when no DeliveryBoyAssignment row
            // exists yet (admin viewing an order before any rider has accepted).
            if ($order->shipping_latitude && $order->shipping_longitude) {
                try {
                    $storeIds = $order->items->pluck('store_id')->unique()->filter()->values()->toArray();
                    if (!empty($storeIds)) {
                        $order->delivery_route = \App\Services\DeliveryZoneService::calculateDeliveryRoute(
                            (float) $order->shipping_latitude,
                            (float) $order->shipping_longitude,
                            $storeIds,
                            $order
                        );
                    }
                } catch (\Throwable $e) {
                    // Route is presentational only — never fail the page render.
                    Log::warning('Admin show delivery_route calc failed', [
                        'order_id' => $order->id, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        $this->authorize('viewAny', $order);
        // Transform the order data using the resource
        $orderData = new OrderResource($order);

        $auditLogs = collect();
        if ($this->getPanel() === 'admin') {
            $orderId = $order instanceof Order ? $order->id : ($order->order_id ?? null);
            if ($orderId) {
                $auditLogs = OrderAuditLog::with('admin')
                    ->where('order_id', $orderId)
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        // Build valid-next-status map for each item so the inline
        // status dropdown only shows transitions the hierarchy allows.
        $itemTransitions = [];
        if ($this->getPanel() === 'admin' && $this->editPermission) {
            $transitionMap = [
                OrderItemStatusEnum::AWAITING_STORE_RESPONSE() => [
                    ['value' => 'accept', 'label' => __('labels.accept'), 'acting_as' => 'seller'],
                    ['value' => 'reject', 'label' => __('labels.reject'), 'acting_as' => 'seller'],
                ],
                OrderItemStatusEnum::ACCEPTED() => [
                    ['value' => 'preparing', 'label' => __('labels.preparing'), 'acting_as' => 'seller'],
                ],
                OrderItemStatusEnum::PREPARING() => [
                    ['value' => 'collected', 'label' => __('labels.collected'), 'acting_as' => 'rider'],
                ],
                OrderItemStatusEnum::COLLECTED() => [
                    ['value' => 'delivered', 'label' => __('labels.delivered'), 'acting_as' => 'rider'],
                    ['value' => 'delivery_failed', 'label' => __('labels.delivery_failed'), 'acting_as' => 'rider'],
                ],
                OrderItemStatusEnum::RETURNING_TO_STORE() => [
                    ['value' => 'confirm_return', 'label' => __('labels.confirm_return'), 'acting_as' => 'seller'],
                ],
            ];

            $rawOrder = $order instanceof Order ? $order : ($order->order ?? null);
            $items = $rawOrder ? $rawOrder->items : collect();
            foreach ($items as $item) {
                $itemTransitions[$item->id] = $transitionMap[$item->status] ?? [];
            }
        }

        // Seller panel sees only seller-side transitions; rider/admin-only
        // actions are filtered out so the bulk modal cannot surface them.
        if ($this->getPanel() === 'seller') {
            $sellerTransitionMap = [
                OrderItemStatusEnum::AWAITING_STORE_RESPONSE() => [
                    ['value' => 'accept', 'label' => __('labels.accept'), 'acting_as' => 'seller'],
                    ['value' => 'reject', 'label' => __('labels.reject'), 'acting_as' => 'seller'],
                ],
                OrderItemStatusEnum::ACCEPTED() => [
                    ['value' => 'preparing', 'label' => __('labels.preparing'), 'acting_as' => 'seller'],
                ],
                OrderItemStatusEnum::RETURNING_TO_STORE() => [
                    ['value' => 'confirm_return', 'label' => __('labels.confirm_return'), 'acting_as' => 'seller'],
                ],
            ];

            // For the seller panel the show() route returns a SellerOrder; its
            // items collection is already seller-scoped, no extra filtering needed.
            $sellerItems = $order->items ?? collect();
            foreach ($sellerItems as $sellerItem) {
                $oi = $sellerItem->orderItem ?? null;
                if (!$oi) {
                    continue;
                }
                $itemTransitions[$oi->id] = $sellerTransitionMap[$oi->status] ?? [];
            }
        }

        return view($this->panelView('orders.show'), [
            'order' => $orderData->toArray(request()),
            'editPermission' => $this->editPermission,
            'auditLogs' => $auditLogs,
            'itemTransitions' => $itemTransitions,
        ]);
    }

    /**
     * Update the order status.
     *
     * @param int $id
     * @param string $status
     * @return JsonResponse
     */
    public function updateStatus(int $id, string $status): JsonResponse
    {
        try {
            // Admin path — Phase 3. Previously this method crashed for admins
            // because it called auth()->user()->seller() (BUG-6).
            if ($this->getPanel() === 'admin') {
                $orderItem = OrderItem::find($id);
                if (!$orderItem) {
                    return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
                }
                $this->authorize('forceUpdateStatus', $orderItem);

                $result = $this->orderService->updateOrderStatusByAdmin(
                    orderItemId: $id,
                    newStatus: $status,
                    adminId: (int) auth()->id(),
                    reason: request('reason', __('labels.admin_force_status_default_reason')),
                );

                return ApiResponseType::sendJsonResponse(
                    success: $result['success'],
                    message: $result['message'],
                    data: $result['data'] ?? [],
                );
            }

            // Seller path (unchanged).
            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'));
            }

            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.order_item_not_found'),
                    data: []
                );
            }

            $this->authorize('updateStatus', $orderItem);

            $result = $this->orderService->updateOrderStatusBySeller($id, $status, $seller->id);
            if (!$result['success']) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: $result['message'],
                    data: $result['data'],
                );
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.unauthorized_action'),
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.order_status_update_failed'),
                data: []
            );
        }
    }

    /**
     * Cancel an item the seller had already accepted. Pre-collect only —
     * post-collect cancellations go through the rider/admin return-to-store
     * flow.
     *
     * @param int $id Order item id (matches OrderItem.id, not SellerOrderItem.id)
     */
    public function cancelItem(CancelSellerOrderItemRequest $request, int $id): JsonResponse
    {
        try {
            $seller = auth()->user()?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
            }

            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();
            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }

            $this->authorize('updateStatus', $orderItem);
            $result = $this->orderService->cancelOrderItemBySeller(
                orderItemId: $id,
                sellerId: $seller->id,
                reason: $request->validated('reason'),
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Seller cancel-item endpoint failed', [
                'order_item_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Seller confirms physical receipt of items returning to the store.
     *
     * @param int $id Order item id
     */
    public function confirmReturn(ConfirmStoreReturnRequest $request, int $id): JsonResponse
    {
        try {
            $seller = auth()->user()?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
            }

            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();
            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }

            $this->authorize('updateStatus', $orderItem);

            $result = $this->orderService->confirmStoreReturn(
                orderItemId: $id,
                sellerId: $seller->id,
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Seller confirm-return endpoint failed', [
                'order_item_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    // =====================================================================
    // Phase 3 — admin override endpoints. Admin-only. Each writes an audit
    // log row via the service. Refunds + restock fire via the existing pipeline
    // where applicable.
    // =====================================================================

    public function adminForceCancel(AdminForceCancelRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }
            $orderItem = OrderItem::find($id);
            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }
            $this->authorize('forceCancel', $orderItem);

            $result = $this->orderService->cancelOrderItemByAdmin(
                orderItemId: $id,
                adminId: (int) auth()->id(),
                reason: $request->validated('reason'),
            );

            return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin force-cancel endpoint failed', ['order_item_id' => $id, 'error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    public function adminForceRefund(AdminForceRefundRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }
            $order = Order::find($id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }
            $this->authorize('forceRefund', $order);

            $result = $this->orderService->forceRefundByAdmin(
                orderId: $id,
                amount: (float) $request->validated('amount'),
                adminId: (int) auth()->id(),
                reason: $request->validated('reason'),
            );

            return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin force-refund endpoint failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    public function adminReassignRider(AdminReassignRiderRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }
            $order = Order::find($id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }
            $this->authorize('reassignRider', $order);

            $result = $this->orderService->reassignRiderByAdmin(
                orderId: $id,
                newDeliveryBoyId: $request->validated('delivery_boy_id'),
                adminId: (int) auth()->id(),
                reason: $request->validated('reason'),
            );

            return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin reassign-rider endpoint failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    public function adminAddNote(AdminAddOrderNoteRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }
            $order = Order::find($id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }
            $this->authorize('addNote', $order);

            $result = $this->orderService->addOrderNoteByAdmin(
                orderId: $id,
                adminId: (int) auth()->id(),
                note: $request->validated('note'),
            );

            return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin add-note endpoint failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Settle (approve or reject) the rider's earnings on a CANCELLED_BY_ADMIN
     * assignment. The Delivery card on admin order detail surfaces the
     * decision buttons when the assignment is in CANCELLED_BY_ADMIN + PENDING
     * state. Required remark is captured in the audit log.
     */
    public function adminSettleRiderEarnings(\App\Http\Requests\Admin\Order\SettleRiderEarningsRequest $request, int $assignmentId): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }

            // Reuse the existing forceRefund policy ability — same admin
            // ORDER_EDIT permission gates every monetary admin override.
            $assignment = \App\Models\DeliveryBoyAssignment::find($assignmentId);
            if (!$assignment) {
                return ApiResponseType::sendJsonResponse(false, __('labels.assignment_not_found'), []);
            }
            $order = Order::find($assignment->order_id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }
            $this->authorize('forceRefund', $order);

            $result = $this->orderService->settleRiderEarnings(
                assignmentId: $assignmentId,
                decision: $request->validated('decision'),
                reason: $request->validated('reason'),
                adminId: (int) auth()->id(),
            );

            return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin settle-rider-earnings endpoint failed', [
                'assignment_id' => $assignmentId, 'error' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Update an order item status on behalf of the seller or rider.
     *
     * @param Request $request
     * @param int $itemId
     * @return JsonResponse
     */
    public function adminUpdateItemStatus(Request $request, int $itemId): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }

            $orderItem = OrderItem::find($itemId);
            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }

            $this->authorize('forceUpdateStatus', $orderItem);

            $validated = $request->validate([
                'status' => 'required|string|in:accept,reject,preparing,collected,delivered,delivery_failed,confirm_return',
                'remark' => 'nullable|string|max:1000',
                'delivery_fail_reason' => 'nullable|string|in:customer_unavailable,customer_refused,wrong_address,unsafe_location',
            ]);

            $result = $this->orderService->updateOrderItemStatusByAdmin(
                orderItemId: $itemId,
                targetStatus: $validated['status'],
                adminId: (int) auth()->id(),
                remark: $validated['remark'] ?? null,
                deliveryFailReason: $validated['delivery_fail_reason'] ?? null,
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin update-item-status endpoint failed', [
                'order_item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Bulk variant of adminUpdateItemStatus — admin applies the same on-behalf
     * status transition to many items in one request. Items that fail (invalid
     * transition, wrong role context, etc.) are reported per-item without
     * blocking the rest.
     *
     * @param  AdminBulkUpdateItemStatusRequest $request
     * @param  int                              $id  Order ID — scopes the items.
     * @return JsonResponse
     */
    public function adminBulkUpdateItemStatus(AdminBulkUpdateItemStatusRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }

            $order = Order::find($id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }

            $validated = $request->validated();
            $rawIds = collect($validated['item_ids'])->map(fn ($v) => (int) $v)->unique()->values();

            // Scope check — every submitted item must belong to this order so
            // an admin can't sneak cross-order writes through one request.
            $belonging = OrderItem::whereIn('id', $rawIds)
                ->where('order_id', $order->id)
                ->pluck('id')
                ->all();
            if (count($belonging) !== $rawIds->count()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.bulk_update_items_not_in_order'),
                    data: [],
                );
            }

            // Authorize against the first item — the policy gate is "admin can
            // edit orders" so all items inherit the same decision.
            $first = OrderItem::find($rawIds->first());
            if ($first) {
                $this->authorize('forceUpdateStatus', $first);
            }

            $result = $this->orderService->bulkUpdateOrderItemStatusByAdmin(
                orderItemIds: $belonging,
                targetStatus: $validated['status'],
                adminId: (int) auth()->id(),
                remark: $validated['remark'] ?? null,
                deliveryFailReason: $validated['delivery_fail_reason'] ?? null,
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin bulk update-item-status endpoint failed', [
                'order_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Seller variant — apply the same status transition to many items in one
     * request. Scoped to items the authenticated seller owns under the given
     * SellerOrder id. Returns the same aggregated shape as the admin bulk
     * endpoint so both panels can share the modal flow.
     *
     * @param  SellerBulkUpdateItemStatusRequest $request
     * @param  int                               $id  SellerOrder id (matches the show route).
     * @return JsonResponse
     */
    public function sellerBulkUpdateItemStatus(SellerBulkUpdateItemStatusRequest $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'seller') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }

            $seller = auth()->user()?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
            }

            $sellerOrder = SellerOrder::where('id', $id)
                ->where('seller_id', $seller->id)
                ->first();
            if (!$sellerOrder) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }

            $validated = $request->validated();
            $rawIds = collect($validated['item_ids'])->map(fn ($v) => (int) $v)->unique()->values();

            // Item-level scoping — every submitted id must be a SellerOrderItem
            // under this SellerOrder. Stops cross-seller writes.
            $belonging = SellerOrderItem::where('seller_order_id', $sellerOrder->id)
                ->whereIn('order_item_id', $rawIds)
                ->pluck('order_item_id')
                ->all();
            if (count($belonging) !== $rawIds->count()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.bulk_update_items_not_in_order'),
                    data: [],
                );
            }

            $result = $this->orderService->bulkUpdateOrderItemStatusBySeller(
                orderItemIds: $belonging,
                targetStatus: $validated['status'],
                sellerId: $seller->id,
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (\Exception $e) {
            Log::error('Seller bulk update-item-status endpoint failed', [
                'seller_order_id' => $id,
                'error'           => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Mark order payment as received — moves PENDING → AWAITING_STORE_RESPONSE.
     *
     * @param Request $request
     * @param int $id Order ID
     * @return JsonResponse
     */
    public function adminMarkPaymentReceived(Request $request, int $id): JsonResponse
    {
        try {
            if ($this->getPanel() !== 'admin') {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
            }

            $order = Order::find($id);
            if (!$order) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), []);
            }

            $this->authorize('forceUpdateStatus', $order);

            $validated = $request->validate([
                'remark' => 'nullable|string|max:1000',
            ]);

            $result = $this->orderService->markPaymentReceivedByAdmin(
                orderId: $id,
                adminId: (int) auth()->id(),
                remark: $validated['remark'] ?? null,
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'] ?? [],
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), []);
        } catch (\Exception $e) {
            Log::error('Admin mark-payment-received failed', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), []);
        }
    }

    /**
     * Build the small addons list that hangs under the product details cell
     * in the orders datatable. Returns an empty string when the order item
     * has no addons — keeps the DOM clean for non-addon orders.
     *
     * Output shape (HTML, escaped):
     *   <div class="mt-1">
     *     <span>Add-ons:</span>
     *     <ul>
     *       <li>Group — Item · $1.50</li>
     *       ...
     *     </ul>
     *   </div>
     */
    private function renderAddonsSnippet(?OrderItem $orderItem): string
    {
        if (!$orderItem) {
            return '';
        }

        $addons = $orderItem->relationLoaded('addons')
            ? $orderItem->getRelation('addons')
            : $orderItem->addons()->with(['addonGroup', 'addonItem'])->get();

        if (!$addons || $addons->isEmpty()) {
            return '';
        }

        $rows = $addons->map(function ($addon) {
            $groupTitle = $addon->addonGroup?->title;
            $itemTitle = $addon->addonItem?->title ?? '—';
            $price = $this->currencyService->format((float)$addon->price);
            $label = $groupTitle ? e($groupTitle) . ' — ' . e($itemTitle) : e($itemTitle);

            return "<li>{$label} <span class='text-muted'>· {$price}</span></li>";
        })->implode('');

        return "<div class='mt-1'>
                    <span class='fw-medium'>" . __('labels.addons') . ":</span>
                    <ul class='list-unstyled mb-0 ps-3 small'>{$rows}</ul>
                </div>";
    }

    /**
     * Compact attachment badge for the orders datatable. Renders one visible
     * "Prescription (N)" chip plus N-1 hidden anchors so all attachments form
     * a single fslightbox gallery scoped to the order item, not the whole page.
     * Returns '' when the item has no attachments.
     */
    private function renderAttachmentsBadge(?OrderItem $orderItem): string
    {
        if (!$orderItem) {
            return '';
        }

        $media = $orderItem->getMedia(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
        if ($media->isEmpty()) {
            return '';
        }

        $count = $media->count();
        $gallery = 'order-item-attachments-' . (int) $orderItem->id;
        $label = __('labels.prescription') . ' (' . $count . ')';

        $first = $media->shift();
        $html = '<a href="' . e($first->getUrl()) . '" data-fslightbox="' . e($gallery) . '" class="badge bg-orange-lt mt-2 me-1" target="_blank">'
            . '<i class="ti ti-paperclip me-1"></i>' . e($label)
            . '</a>';
        foreach ($media as $m) {
            $html .= '<a href="' . e($m->getUrl()) . '" data-fslightbox="' . e($gallery) . '" hidden></a>';
        }
        return $html;
    }

    public function orderInvoice(Request $request): View
    {
        try {
            $orderId = $request->input('id');
            $sellerOrder = sellerOrder::with(
                'order',
                'seller',
                'order.promoLine',
                'items.product',
                'items.orderItem.store',
                'items.variant',
                'items.orderItem',
                // Pull the addon snapshot + related labels so the invoice
                // can render "Add-on: Toppings / Extra cheese — $1.50"
                // under each line without extra queries.
                'items.orderItem.addons.addonGroup',
                'items.orderItem.addons.addonItem',
            )
                ->whereHas('order', function ($q) use ($orderId) {
                    $q->where('uuid', $orderId);
                })
//                ->where('order_id', $orderId)
                ->get();
            if (count($sellerOrder) === 0) {
                abort(404, __('labels.order_not_found'));
            }
            // Attach seller authorized signature image URL for each seller
            foreach ($sellerOrder as $so) {
                if ($so->seller) {
                    $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()) ?? null;
                }
            }
            $orderData = $sellerOrder[0]['order'];
            return view('layouts.order-invoice', [
                'order' => $orderData,
                'sellerOrder' => $sellerOrder,
            ]);
        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    /**
     * Return live-tracking payload for an order's delivery.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function liveTracking(int $id): JsonResponse
    {
        try {
            $order = Order::with([
                'items.store',
            ])->findOrFail($id);

            $this->authorize('viewAny', $order);

            $activeStatuses = [
                OrderStatusEnum::ASSIGNED(),
                OrderStatusEnum::COLLECTED(),
                OrderStatusEnum::OUT_FOR_DELIVERY(),
            ];

            if (!in_array($order->status, $activeStatuses, true)) {
                return ApiResponseType::sendJsonResponse(false, 'labels.tracking_not_available', null);
            }

            // Rider current location
            $riderLocation = null;
            if ($order->delivery_boy_id) {
                $loc = DeliveryBoyLocation::where('delivery_boy_id', $order->delivery_boy_id)->first();
                if ($loc) {
                    $riderLocation = [
                        'latitude' => (float) $loc->latitude,
                        'longitude' => (float) $loc->longitude,
                        'recorded_at' => $loc->recorded_at,
                    ];
                }
            }

            // Build route starting from the rider's current position
            $storeIds = $order->items->pluck('store_id')->unique()->toArray();
            $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                (float) $order->shipping_latitude,
                (float) $order->shipping_longitude,
                $storeIds,
                $order,
                $riderLocation ? (float) $riderLocation['latitude'] : null,
                $riderLocation ? (float) $riderLocation['longitude'] : null,
            );

            // Filter out the customer entry (store_id === null) and map to frontend shape
            $routeStores = array_filter(
                $deliveryRoute['route_details'] ?? [],
                fn ($detail) => !empty($detail['store_id'])
            );
            $stores = array_values(array_map(fn ($detail) => [
                'id' => $detail['store_id'],
                'name' => $detail['store_name'] ?? '',
                'latitude' => (float) ($detail['latitude'] ?? 0),
                'longitude' => (float) ($detail['longitude'] ?? 0),
                'is_collected' => (bool) ($detail['is_collected'] ?? false),
            ], $routeStores));

            $data = [
                'rider' => $riderLocation,
                'stores' => $stores,
                'customer' => [
                    'latitude' => (float) $order->shipping_latitude,
                    'longitude' => (float) $order->shipping_longitude,
                    'name' => $order->shipping_name ?? '',
                ],
                'order_status' => $order->status,
            ];

            return ApiResponseType::sendJsonResponse(true, 'labels.success', $data);
        } catch (\Throwable $e) {
            Log::error('Order live tracking error', ['order_id' => $id, 'error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, 'labels.something_went_wrong', null);
        }
    }
}
