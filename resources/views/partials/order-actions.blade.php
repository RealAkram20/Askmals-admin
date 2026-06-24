@php
    use App\Enums\Order\OrderItemStatusEnum;

    $isSeller = $panel === 'seller';
    $sellerActions = [];

    // Build the seller dropdown menu items based on the item's current status.
    // Empty list = no dropdown rendered (just the View icon).
    if ($isSeller && $editPermission && !empty($status)) {
        switch ($status) {
            case OrderItemStatusEnum::AWAITING_STORE_RESPONSE():
                $sellerActions = [
                    ['label' => __('labels.accept'), 'icon' => 'ti-check', 'class' => 'text-success', 'modal' => '#acceptModel'],
                    ['label' => __('labels.reject'), 'icon' => 'ti-x', 'class' => 'text-danger', 'modal' => '#rejectModel'],
                ];
                break;

            case OrderItemStatusEnum::ACCEPTED():
                $sellerActions = [
                    ['label' => __('labels.preparing'), 'icon' => 'ti-tools-kitchen-2', 'class' => 'text-primary', 'modal' => '#preparingModel'],
                    ['label' => __('labels.cancel_item'), 'icon' => 'ti-ban', 'class' => 'text-danger', 'modal' => '#cancelItemModal'],
                ];
                break;

            case OrderItemStatusEnum::PREPARING():
                $sellerActions = [
                    ['label' => __('labels.cancel_item'), 'icon' => 'ti-ban', 'class' => 'text-danger', 'modal' => '#cancelItemModal'],
                ];
                break;

            case OrderItemStatusEnum::RETURNING_TO_STORE():
                $sellerActions = [
                    ['label' => __('labels.confirm_received'), 'icon' => 'ti-package-import', 'class' => 'text-success', 'modal' => '#confirmReturnModal'],
                ];
                break;
        }
    }
@endphp

<div class="btn-list flex-nowrap">
    {{-- View link is always present and always icon-only — keeps the row tidy. --}}
    <a href="{{ $route }}"
       class="btn btn-icon btn-outline-primary"
       title="{{ __('labels.view') }}"
       data-bs-toggle="tooltip">
        <i class="ti ti-eye"></i>
    </a>

    @if($panel === 'admin')
        <a href="{{ url('admin/orders/invoice?id=' . $uuid) }}"
           class="btn btn-icon btn-outline-secondary"
           title="{{ __('labels.invoice') }}"
           data-bs-toggle="tooltip">
            <i class="ti ti-invoice"></i>
        </a>
    @endif

    @if(!empty($sellerActions))
        <div class="dropdown">
            <button type="button"
                    class="btn btn-icon btn-ghost-secondary"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="{{ __('labels.actions') }}">
                <i class="ti ti-dots-vertical"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                @foreach($sellerActions as $action)
                    <button type="button"
                            class="dropdown-item {{ $action['class'] }}"
                            data-bs-toggle="modal"
                            data-bs-target="{{ $action['modal'] }}"
                            data-id="{{ $id }}">
                        <i class="ti {{ $action['icon'] }} me-2"></i>{{ $action['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
