<?php

namespace App\Models;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    protected $primaryKey = 'variable';  // Define the primary key

    public $incrementing = false;  // Tell Laravel it's NOT an auto-incrementing key
    protected $keyType = 'string';
    protected $fillable = ['variable', 'value'];

    public function getValueAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getAllowedCountriesAttribute()
    {
        if (!empty($this->value['allowedCountries'])) {
            return Country::whereIn('name', $this->value['allowedCountries'])->pluck('iso2')->toArray();
        }
        return null;
    }

    public static function systemType(): string
    {
        $systemSettings = self::systemSettings();
        return !empty($systemSettings['systemVendorType']) ? $systemSettings['systemVendorType'] : SystemVendorTypeEnum::MULTIPLE();
    }

    public static function systemSettings()
    {
        $settingService = app(SettingService::class);
        $resource = $settingService->getSettingByVariable('system');
        return $resource ? ($resource->toArray(request())['value'] ?? []) : [];
    }

    public static function isDemoModeEnabled(): bool
    {
        try {
            $settings = self::systemSettings();
            return (bool) ($settings['demoMode'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function isSystemVendorTypeMultiple(): bool
    {
        $systemVendorType = self::systemType();
        return $systemVendorType === SystemVendorTypeEnum::MULTIPLE();
    }

    public static function isSystemVendorTypeSingle(): bool
    {
        $systemVendorType = self::systemType();
        return $systemVendorType === SystemVendorTypeEnum::SINGLE();
    }

    public static function isSubscriptionEnabled(): bool
    {
        try {
            $service = app(SettingService::class);
            $resource = $service->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());
            $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
            return ($settings['enableSubscription'] ?? null) === true;
        } catch (\Throwable) {
            // On any error, fail open (do not enforce limits)
            return false;
        }
    }

    public static function adminSeller()
    {
        try {
            $userAuth = Auth::user();
            $seller = SellerUser::where('user_id', $userAuth->id)->with('seller');
            if (Setting::isSystemVendorTypeSingle()
                && $userAuth
                && $userAuth->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())
                && $seller->exists()) {
                return $seller->first()->seller;
            }
            return null;
        } catch (\Exception) {
            return null;
        }
    }

    public static function canImpersonate(): bool
    {
        $userAuth = Auth::user();
        return Setting::isSystemVendorTypeSingle()
            && $userAuth
            && $userAuth->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())
            && SellerUser::where('user_id', $userAuth->id)->exists();
    }

    /**
     * Returns the current application version.
     * Priority: config('app.version') > latest applied SystemUpdate > '0.0.0'
     */
    public static function getCurrentVersion(): string
    {
        // Prefer the last applied update version
        $lastApplied = SystemUpdate::where('status', 'applied')->orderByDesc('id')->first();
        if ($lastApplied && is_string($lastApplied->version) && $lastApplied->version !== '') {
            return $lastApplied->version;
        }
        // Fallback to config value
        $fromConfig = config('app.version');
        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }
        return '0.0.0';
    }

    /**
     * Returns an array of enabled payment gateway keys (matching PaymentTypeEnum values).
     *
     * @return string[]
     */
    public static function getEnabledPaymentGateway($onlyGateway = false): array
    {
        try {
            $settingService = app(SettingService::class);
            $resource = $settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $paymentSettings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];

            $gateways = [
                PaymentTypeEnum::STRIPE(),
                PaymentTypeEnum::RAZORPAY(),
                PaymentTypeEnum::PAYSTACK(),
                PaymentTypeEnum::FLUTTERWAVE(),
            ];

            if (!$onlyGateway) {
                $gateways[] = PaymentTypeEnum::COD();
                $gateways[] = PaymentTypeEnum::WALLET();
            }

            return array_values(array_filter($gateways, fn($gateway) => !empty($paymentSettings[$gateway])));
        } catch (\Throwable $e) {
            Log::error('Failed to load enabled payment gateways: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return int|null
     */
    public static function posWalkinUserId(): ?int
    {
        $values = app(\App\Services\SettingService::class)->getSettingValues('pos_settings');

        return $values['walkin_user_id'] ?? null;
    }

    /**
     * @return string
     */
    public static function currencySymbol(): string
    {
        $setting = self::where('variable', 'system')->first();
        $val = $setting?->value;

        return is_array($val) ? ($val['currency_symbol'] ?? '') : '';
    }
}
