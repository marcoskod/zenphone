<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SmsProviderException;
use App\Http\Controllers\Controller;
use App\Services\PricingService;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        protected SmsProviderInterface $sms,
        protected PricingService $pricing,
    ) {
    }

    /**
     * GET /api/services?country= - services orderable in a country, consumed by the
     * service-selector component.
     */
    public function services(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['required', 'string'],
        ]);

        try {
            $services = $this->sms->getServices($validated['country']);
        } catch (SmsProviderException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $services = array_map(fn (array $service) => $service + [
            'price_fcfa' => $this->pricing->calculatePrice($service['price_usd']),
        ], $services);

        return response()->json(['data' => $services]);
    }

    /**
     * GET /api/countries - country list for the country-selector component. `iso` lets
     * the frontend render flags without a hand-maintained lookup table.
     */
    public function countries(): JsonResponse
    {
        try {
            $countries = $this->sms->getCountries();
        } catch (SmsProviderException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['data' => $countries]);
    }

    /**
     * GET /api/price?service=&country= - live FCFA price (supplier USD price converted
     * via PricingService), called from Alpine on every service/country change.
     */
    public function price(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'string'],
            'country' => ['required', 'string'],
        ]);

        try {
            $priceUsd = $this->sms->getPrice($validated['country'], $validated['service']);
        } catch (SmsProviderException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        if ($priceUsd === null) {
            return response()->json(['message' => "Ce service n'est pas disponible pour ce pays."], 404);
        }

        return response()->json([
            'service' => $validated['service'],
            'country' => $validated['country'],
            'price_usd' => $priceUsd,
            'price_fcfa' => $this->pricing->calculatePrice($priceUsd),
        ]);
    }
}
