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
}
