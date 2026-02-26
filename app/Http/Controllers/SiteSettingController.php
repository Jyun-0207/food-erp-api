<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    private const ALLOWED_KEYS = [
        // Company info
        'companyName', 'companyTaxId', 'companyAddress', 'companyPhone', 'companyEmail',
        'companyLogo', 'companyMapEmbedUrl', 'companyMapLat', 'companyMapLng',
        'businessHours', 'businessHoursNote',
        'socialLineUrl', 'socialInstagramUrl',
        // Website banners
        'heroBannerImage', 'heroBannerTitle', 'heroBannerSubtitle',
        'heroBanners', 'heroBannerInterval',
        'ctaBannerImage', 'ctaTitle', 'ctaSubtitle',
        // Order prefixes
        'salesOrderPrefix', 'salesOrderDigits',
        'purchaseOrderPrefix', 'purchaseOrderDigits',
        'workOrderPrefix', 'workOrderDigits',
        'batchNumberDigits',
        // Payment & delivery
        'paymentMethods', 'paymentMethodAccounts', 'deliveryMethods',
        // Shipping & inventory
        'shippingFee', 'freeShippingThreshold', 'inventoryStrategy',
        // Product settings
        'allergens',
    ];

    public function update(Request $request)
    {
        $data = $request->only(self::ALLOWED_KEYS);

        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $settings = SiteSetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }
}
