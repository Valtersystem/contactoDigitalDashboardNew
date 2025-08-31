<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\RentalItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class AssetManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $assets = Asset::whereIn('status', ['Em Manutenção', 'Perdido'])
            ->with('product:id,name,replacement_value')
            ->latest()
            ->paginate(10, ['*'], 'assets_page');

        $bulkLosses = RentalItem::where(function ($query) {
                $query->where('quantity_damaged', '>', 0)
                      ->orWhere('quantity_lost', '>', 0);
            })
            ->whereHas('product', fn($q) => $q->where('tracking_type', 'BULK'))
            ->with(['product:id,name,replacement_value,sku', 'rental:id,rental_date,client_id', 'rental.client:id,business_name']) // Adicionado client
            ->latest('updated_at')
            ->paginate(10, ['*'], 'losses_page');

        return Inertia::render('AssetManagement/Index', [
            'assets' => $assets,
            'bulkLosses' => $bulkLosses,
        ]);
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Disponível,Em Manutenção,Perdido',
        ]);

        $asset->update(['status' => $validated['status']]);
        return back()->with('success', 'Status do ativo atualizado com sucesso.');
    }

    public function updateBulkStatus(Request $request, RentalItem $rental_item)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['damaged_to_available', 'lost_to_available', 'damaged_to_lost'])],
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $rental_item->product;
        $quantity = $validated['quantity'];

        DB::transaction(function () use ($rental_item, $product, $quantity, $validated) {
            switch ($validated['action']) {
                case 'damaged_to_available':
                    if ($quantity > $rental_item->quantity_damaged) {
                        throw ValidationException::withMessages(['quantity' => 'A quantidade excede o número de itens danificados.']);
                    }
                    $rental_item->decrement('quantity_damaged', $quantity);
                    $product->increment('stock_quantity', $quantity);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'rental_id' => $rental_item->rental_id,
                        'type' => 'Ajuste: Danificado para Disponível',
                        'quantity_change' => $quantity,
                        'stock_after_change' => $product->fresh()->stock_quantity,
                    ]);
                    break;

                case 'lost_to_available':
                     if ($quantity > $rental_item->quantity_lost) {
                        throw ValidationException::withMessages(['quantity' => 'A quantidade excede o número de itens perdidos.']);
                    }
                    $rental_item->decrement('quantity_lost', $quantity);
                    $product->increment('stock_quantity', $quantity);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'rental_id' => $rental_item->rental_id,
                        'type' => 'Ajuste: Perdido para Disponível (Encontrado)',
                        'quantity_change' => $quantity,
                        'stock_after_change' => $product->fresh()->stock_quantity,
                    ]);
                    break;

                case 'damaged_to_lost':
                    if ($quantity > $rental_item->quantity_damaged) {
                        throw ValidationException::withMessages(['quantity' => 'A quantidade excede o número de itens danificados.']);
                    }
                    $rental_item->decrement('quantity_damaged', $quantity);
                    $rental_item->increment('quantity_lost', $quantity);
                    break;
            }
        });

        return back()->with('success', 'Item(s) atualizado(s) com sucesso.');
    }
}
