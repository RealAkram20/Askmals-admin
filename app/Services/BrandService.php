<?php

namespace App\Services;

use App\Enums\BrandStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Models\Brand;
use App\Traits\HasZoneAvailability;
use Illuminate\Database\Eloquent\Builder;

class BrandService
{
    use HasZoneAvailability;

    /**
     * Build base brand query constrained by active status and availability in given stores.
     *
     * A brand is considered available if it has at least one approved and active product
     * that has variants available in any of the specified stores.
     */
    protected function baseAvailabilityQuery(array $storeIds): Builder
    {
        return Brand::query()
            ->where('status', BrandStatusEnum::ACTIVE())
            ->distinct()
            ->whereHas('products', function ($productQuery) use ($storeIds) {
                $productQuery->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                    ->where('status', ProductStatusEnum::ACTIVE())
                    ->whereHas('variants.storeProductVariants', function ($variantQuery) use ($storeIds) {
                        $variantQuery->whereIn('store_id', $storeIds);
                    });
            });
    }

    /**
     * Canonical zone-aware brand query.
     *
     * Returns active brands with a `products_count` attribute reflecting how many
     * active+approved products with variants in the given stores they have, and
     * filters out brands with zero such products. Ordering, scope filtering and
     * pagination are intentionally left to the caller.
     *
     * Used by BrandApiController (customer) + ZonePreviewService (admin) so any
     * tweak to the base shape automatically reflects in both.
     */
    public function availableInStoresQuery(array $storeIds): Builder
    {
        return Brand::query()
            ->where('status', BrandStatusEnum::ACTIVE())
            ->withCount([
                'products as products_count' => function (Builder $q) use ($storeIds) {
                    if (empty($storeIds)) {
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $q->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                        ->where('status', ProductStatusEnum::ACTIVE())
                        ->whereHas(
                            'variants.storeProductVariants',
                            fn(Builder $sq) => $sq->whereIn('store_id', $storeIds),
                        );
                },
            ])
            ->having('products_count', '>', 0);
    }
}
