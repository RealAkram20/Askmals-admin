<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\Advertisement\AdCampaignStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\AdminAdCampaignActionRequest;
use App\Models\AdCampaign;
use App\Services\AdCampaignDashboardService;
use App\Services\AdCampaignService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdCampaignController extends Controller
{
    use AuthorizesRequests, PanelAware, ChecksPermissions;

    protected bool $viewPermission          = false;
    protected bool $approvePermission       = false;
    protected bool $rejectPermission        = false;
    protected bool $forceStopPermission     = false;
    protected bool $dashboardViewPermission = false;

    public function __construct(
        protected AdCampaignService $adCampaignService,
        protected AdCampaignDashboardService $dashboardService,
    ) {
        $this->viewPermission          = $this->hasPermission(AdminPermissionEnum::AD_CAMPAIGN_VIEW());
        $this->approvePermission       = $this->hasPermission(AdminPermissionEnum::AD_CAMPAIGN_APPROVE());
        $this->rejectPermission        = $this->hasPermission(AdminPermissionEnum::AD_CAMPAIGN_REJECT());
        $this->forceStopPermission     = $this->hasPermission(AdminPermissionEnum::AD_CAMPAIGN_FORCE_STOP());
        $this->dashboardViewPermission = $this->hasPermission(AdminPermissionEnum::AD_CAMPAIGN_DASHBOARD_VIEW());
    }

    /**
     * All campaigns — filterable by status.
     */
    public function index(Request $request): View
    {
        abort_unless($this->viewPermission, 403, __('labels.unauthorized_access'));

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'product', 'name' => 'product', 'title' => __('labels.ad_campaign_product')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.ad_campaign_status')],
            ['data' => 'budget', 'name' => 'budget', 'title' => __('labels.ad_campaign_budget_label'), 'orderable' => true, 'searchable' => false],
            ['data' => 'clicks', 'name' => 'clicks', 'title' => __('labels.ad_campaign_clicks'), 'orderable' => false, 'searchable' => false],
            ['data' => 'impressions', 'name' => 'impressions', 'title' => __('labels.ad_campaign_impressions'), 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.ad_campaign_created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $statusOptions = [
            '' => __('labels.ad_campaign_all'),
            AdCampaignStatusEnum::PENDING_APPROVAL() => __('labels.ad_campaign_pending'),
            AdCampaignStatusEnum::RUNNING() => __('labels.ad_campaign_running'),
            AdCampaignStatusEnum::PAUSED() => __('labels.paused'),
            AdCampaignStatusEnum::FORCE_STOPPED() => __('labels.force_stopped'),
            AdCampaignStatusEnum::REJECTED() => __('labels.rejected'),
        ];

        return view('admin.ads.campaigns.index', [
            'columns'             => $columns,
            'statusOptions'       => $statusOptions,
            'approvePermission'   => $this->approvePermission,
            'rejectPermission'    => $this->rejectPermission,
            'forceStopPermission' => $this->forceStopPermission,
        ]);
    }

    /**
     * Approve, reject, or force-stop a campaign.
     */
    public function action(AdminAdCampaignActionRequest $request, int $id): JsonResponse
    {
        $admin  = auth()->user();
        $action = $request->validated()['action'];
        $reason = $request->validated()['reason'] ?? '';

        try {
            $result = match ($action) {
                'approve'    => $this->handleApprove($id, $admin),
                'reject'     => $this->handleReject($id, $admin, $reason),
                'force_stop' => $this->handleForceStop($id, $admin, $reason),
            };

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data'],
            );
        } catch (\Exception $e) {
            Log::error('Admin AdCampaignController::action failed', ['error' => $e->getMessage()]);
            return ApiResponseType::sendJsonResponse(false, 'labels.something_went_wrong', []);
        }
    }

    /**
     * DataTable JSON for admin campaign listing.
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        abort_unless($this->viewPermission, 403, __('labels.unauthorized_access'));

        $draw   = $request->get('draw');
        $start  = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 5;
        $orderDirection   = $request->get('order')[0]['dir'] ?? 'desc';

        $sortableColumns = ['id', 'id', 'id', 'status', 'budget', 'created_at', 'id'];
        $orderColumn     = $sortableColumns[$orderColumnIndex] ?? 'created_at';

        $query = AdCampaign::query()->with(['product.media', 'seller', 'stats']);

        $totalRecords = AdCampaign::count();

        // Status filter
        $statusFilter = $request->get('status');
        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Seller filter
        $sellerFilter = $request->get('seller_id');
        if (! empty($sellerFilter)) {
            $query->where('seller_id', $sellerFilter);
        }

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('title', 'like', "%{$searchValue}%"));
            });
        }

        $filteredRecords = $query->count();

        $approvePermission   = $this->approvePermission;
        $rejectPermission    = $this->rejectPermission;
        $forceStopPermission = $this->forceStopPermission;

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($campaign) use ($approvePermission, $rejectPermission, $forceStopPermission) {
                $status      = $campaign->status;
                $progress    = $campaign->spend_progress;
                $totalClicks = $campaign->stats->sum('clicks');
                $totalImpr   = $campaign->stats->sum('impressions');

                return [
                    'id'     => $campaign->id,
                    'product'    => view('admin.ads.campaigns._product_cell', compact('campaign'))->render(),
                    'seller'     => $campaign->seller?->user?->name ?? '—',
                    'status'     => view('admin.ads.campaigns._status_cell', compact('campaign', 'status'))->render(),
                    'budget'     => view('admin.ads.campaigns._budget_cell', compact('campaign', 'progress'))->render(),
                    'clicks'     => number_format($totalClicks),
                    'impressions' => number_format($totalImpr),
                    'created_at' => $campaign->created_at->format('d M Y'),
                    'action'     => view('admin.ads.campaigns._action_cell', compact('campaign', 'status', 'approvePermission', 'rejectPermission', 'forceStopPermission'))->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Ad campaign statistics dashboard.
     */
    public function dashboard(): View
    {
        abort_unless($this->dashboardViewPermission, 403, __('labels.unauthorized_access'));

        $data = $this->dashboardService->getDashboardData(sellerId: null, days: 7);

        return view('admin.ads.campaigns.dashboard', [
            'data' => $data,
        ]);
    }

    /**
     * AJAX endpoint for dashboard period changes.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        abort_unless($this->dashboardViewPermission, 403, __('labels.unauthorized_access'));

        try {
            $days = (int) $request->input('days', 7);
            $data = $this->dashboardService->getDashboardData(sellerId: null, days: $days);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Admin ad dashboard data failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => __('labels.something_went_wrong')], 500);
        }
    }

    // ---------- private helpers ----------

    private function handleApprove(int $id, $admin): array
    {
        abort_unless($this->approvePermission, 403, __('labels.unauthorized_access'));
        return $this->adCampaignService->approve($id, $admin);
    }

    private function handleReject(int $id, $admin, string $reason): array
    {
        abort_unless($this->rejectPermission, 403, __('labels.unauthorized_access'));
        return $this->adCampaignService->reject($id, $admin, $reason);
    }

    private function handleForceStop(int $id, $admin, string $reason): array
    {
        abort_unless($this->forceStopPermission, 403, __('labels.unauthorized_access'));
        return $this->adCampaignService->forceStop($id, $admin, $reason);
    }
}
