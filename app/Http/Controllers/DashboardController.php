<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        // --- Métricas para Visão Geral ---
        $totalClients = Client::count();
        $totalActiveProducts = Product::where('is_active', true)->count();
        $lateRentalsCount = Rental::where('status', 'Alugado')
            ->where('expected_return_date', '<', Carbon::now())
            ->count();

        // Quantidade de itens danificados/perdidos
        $damagedOrLostAssetsCount = Asset::whereIn('status', ['Em Manutenção', 'Perdido'])->count();
        $damagedOrLostBulkCount = RentalItem::whereHas('product', fn($q) => $q->where('tracking_type', 'BULK')) ->sum(DB::raw('quantity_damaged + quantity_lost'));
        $totalLossCount = $damagedOrLostAssetsCount + (int)$damagedOrLostBulkCount;

        // --- Métricas de Inventário e Financeiras ---
        $productsWithoutPrice = Product::where('replacement_value', '<=', 0)->count();
        $totalBulkValue = Product::where('tracking_type', 'BULK')->where('is_active', true)->sum(DB::raw('stock_quantity * replacement_value'));
        $totalSerializedValue = Asset::where('status', 'Disponível')->with('product:id,replacement_value')->get()->sum(fn($asset) => $asset->product->replacement_value);
        $totalInventoryValue = $totalBulkValue + $totalSerializedValue;

        // Valor Danificado
        $damagedAssetsValue = Asset::where('status', 'Em Manutenção')->with('product:id,replacement_value')->get()->sum(fn($asset) => $asset->product->replacement_value);
        $damagedBulkValue = RentalItem::where('quantity_damaged', '>', 0)->whereHas('product', fn($q) => $q->where('tracking_type', 'BULK'))->with('product:id,replacement_value')->get()->sum(fn($item) => $item->quantity_damaged * $item->product->replacement_value);
        $totalDamagedValue = $damagedAssetsValue + $damagedBulkValue;

        // Valor Perdido
        $lostAssetsValue = Asset::where('status', 'Perdido')->with('product:id,replacement_value')->get()->sum(fn($asset) => $asset->product->replacement_value);
        $lostBulkValue = RentalItem::where('quantity_lost', '>', 0)->whereHas('product', fn($q) => $q->where('tracking_type', 'BULK'))->with('product:id,replacement_value')->get()->sum(fn($item) => $item->quantity_lost * $item->product->replacement_value);
        $totalLostValue = $lostAssetsValue + $lostBulkValue;

        // --- Dados para Agendamentos ---
        $upcomingReturns = Rental::where('status', '!=', 'Devolvido')
            ->whereDate('expected_return_date', '>=', today())
            ->orderBy('expected_return_date', 'asc')
            ->with('client:id,business_name')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalClients' => $totalClients,
                'totalProducts' => $totalActiveProducts,
                'lateRentalsCount' => $lateRentalsCount,
                'totalLossCount' => $totalLossCount,
                'productsWithoutPrice' => $productsWithoutPrice,
                'totalInventoryValue' => number_format($totalInventoryValue, 2, ',', '.'),
                'totalDamagedValue' => number_format($totalDamagedValue, 2, ',', '.'),
                'totalLostValue' => number_format($totalLostValue, 2, ',', '.'),
            ],
            'upcomingReturns' => $upcomingReturns,
        ]);
    }
}
