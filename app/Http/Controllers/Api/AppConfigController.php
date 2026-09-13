<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Support\CatalogEvents;
use Illuminate\Http\JsonResponse;

class AppConfigController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->ok([
            'catalog_version' => CatalogEvents::version(),
            'compare_max' => (int) AppSetting::get('compare_max', 3),
            'support_phone' => (string) AppSetting::get('support_phone', '19022'),
            'support_whatsapp' => (string) AppSetting::get('support_whatsapp', '+201000000000'),
            'finance' => AppSetting::get('finance', []),
            'financing_banner' => AppSetting::get('financing_banner'),
        ]);
    }
}
