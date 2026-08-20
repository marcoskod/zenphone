<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FiveSimException;
use App\Http\Controllers\Controller;
use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(protected FiveSimServiceInterface $fiveSim)
    {
    }

    /**
     * GET /api/services?country=&operator= - formatted product list for a country,
     * consumed by the service-selector component.
     */
    public function services(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['required', 'string'],
            'operator' => ['nullable', 'string'],
        ]);

        try {
            $products = $this->fiveSim->getProducts($validated['country'], $validated['operator'] ?? 'any');
        } catch (FiveSimException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $services = collect($products)
            ->map(function ($details, $code) {
                return [
                    'code' => $code,
                    'label' => ucfirst(str_replace('_', ' ', $code)),
                    'category' => is_array($details) ? ($details['Category'] ?? null) : null,
                    'qty' => is_array($details) ? ($details['Qty'] ?? 0) : 0,
                    'price_usd' => is_array($details) ? ($details['Price'] ?? null) : null,
                ];
            })
            ->values();

        return response()->json(['data' => $services]);
    }

    /**
     * GET /api/countries - formatted country list, consumed by the country-selector
     * component. FiveSimService::getCountries()'s exact response schema was not
     * confirmed against the live 5sim docs (see FiveSimService's code comment), so this
     * is defensive: it accepts either a "text_en"/"name" field per entry or falls back
     * to titleizing the country slug itself.
     */
    public function countries(): JsonResponse
    {
        try {
            $countries = $this->fiveSim->getCountries();
        } catch (FiveSimException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $formatted = collect($countries)
            ->map(function ($details, $code) {
                $name = is_array($details) ? ($details['text_en'] ?? $details['name'] ?? null) : null;

                return [
                    'code' => $code,
                    'name' => $name ?? ucfirst(str_replace('_', ' ', $code)),
                ];
            })
            ->values();

        return response()->json(['data' => $formatted]);
    }
}
