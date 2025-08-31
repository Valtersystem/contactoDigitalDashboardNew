<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\StockMovement; // Adicionado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule; // Adicionado

class RentalController extends Controller
{
    /**
     * Mostra o formulário para criar um novo aluguel.
     */
    public function create(): Response
    {
        $clients = Client::orderBy('business_name')->get(['id', 'business_name']);
        $products = Product::where('is_active', true)
            ->with(['assets' => function ($query) {
                $query->where('status', 'Disponível');
            }])
            ->orderBy('name')
            ->get();

        return Inertia::render('Rentals/Create', [
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    /**
     * Guarda um novo aluguel na base de dados.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'rental_date' => 'required|date',
            'expected_return_date' => 'required|date|after_or_equal:rental_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.asset_id' => 'nullable|exists:assets,id',
        ]);

        DB::transaction(function () use ($validated) {
            $rental = Rental::create([
                'client_id' => $validated['client_id'],
                'rental_date' => $validated['rental_date'],
                'expected_return_date' => $validated['expected_return_date'],
                'notes' => $validated['notes'],
                'status' => 'Alugado',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                RentalItem::create([
                    'rental_id' => $rental->id,
                    'product_id' => $product->id,
                    'quantity_rented' => $item['quantity'] ?? null,
                    'asset_id' => $item['asset_id'] ?? null,
                ]);

                if ($product->tracking_type === 'BULK') {
                    $product->decrement('stock_quantity', $item['quantity']);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'rental_id' => $rental->id,
                        'type' => 'Saída de Aluguel',
                        'quantity_change' => -$item['quantity'],
                        'stock_after_change' => $product->fresh()->stock_quantity,
                    ]);
                } else {
                    Asset::where('id', $item['asset_id'])->update(['status' => 'Alugado']);
                }
            }
        });

        return redirect()->route('rentals.index')->with('success', 'Aluguel registado com sucesso!');
    }

    /**
     * Mostra a lista de todos os alugueis.
     */
    public function index(): Response
    {
        $rentals = Rental::with('client')->latest()->paginate(10);
        return Inertia::render('Rentals/Index', [
            'rentals' => $rentals,
        ]);
    }

    /**
     * Mostra o formulário para registar a devolução de um aluguel.
     */
    public function showReturnForm(Rental $rental): Response
    {
        $rental->load('client', 'rentalItems.product', 'rentalItems.asset');

        return Inertia::render('Rentals/Return', [
            'rental' => $rental,
        ]);
    }

    /**
     * Processa a devolução de um aluguel.
     */
    public function processReturn(Request $request, Rental $rental): RedirectResponse
    {
        // 1. Validação dos dados do formulário
        $validated = $request->validate([
            'actual_return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rental_item_id' => ['required', Rule::exists('rental_items', 'id')->where('rental_id', $rental->id)],
            'items.*.quantity_returned' => 'required|integer|min:0',
            'items.*.quantity_damaged' => 'required|integer|min:0',
            'items.*.quantity_lost' => 'required|integer|min:0',
        ]);

        // 2. Usar uma transação para garantir a consistência dos dados
        DB::transaction(function () use ($validated, $rental) {
            $totalItems = $rental->rentalItems()->count();
            $itemsFullyReturned = 0;

            // 3. Itera sobre cada item devolvido
            foreach ($validated['items'] as $returnItemData) {
                $rentalItem = RentalItem::with('product', 'asset')->find($returnItemData['rental_item_id']);

                if (!$rentalItem) continue;

                // 4. Atualiza as quantidades no item do aluguel
                $rentalItem->update([
                    'quantity_returned' => $returnItemData['quantity_returned'],
                    'quantity_damaged' => $returnItemData['quantity_damaged'],
                    'quantity_lost' => $returnItemData['quantity_lost'],
                ]);

                $product = $rentalItem->product;

                // 5. Atualiza o estoque ou o status do ativo
                if ($product->tracking_type === 'BULK') {
                    if ($returnItemData['quantity_returned'] > 0) {
                        $product->increment('stock_quantity', $returnItemData['quantity_returned']);
                        StockMovement::create([
                            'product_id' => $product->id,
                            'rental_id' => $rental->id,
                            'type' => 'Devolução',
                            'quantity_change' => $returnItemData['quantity_returned'],
                            'stock_after_change' => $product->fresh()->stock_quantity,
                        ]);
                    }
                } else { // SERIALIZED
                    if ($rentalItem->asset) {
                        $newStatus = 'Disponível';
                        if ($returnItemData['quantity_damaged'] > 0) {
                            $newStatus = 'Em Manutenção';
                        } elseif ($returnItemData['quantity_lost'] > 0) {
                            $newStatus = 'Perdido';
                        }
                        $rentalItem->asset->update(['status' => $newStatus]);
                    }
                }

                $totalAccounted = $returnItemData['quantity_returned'] + $returnItemData['quantity_damaged'] + $returnItemData['quantity_lost'];
                if ($totalAccounted >= ($rentalItem->quantity_rented ?? 1)) {
                    $itemsFullyReturned++;
                }
            }

            // 6. Atualiza o status geral do aluguel se todos os itens foram retornados
            if ($itemsFullyReturned >= $totalItems) {
                $rental->update([
                    'status' => 'Devolvido',
                    'actual_return_date' => $validated['actual_return_date'],
                    'notes' => $rental->notes . "\n\nObservações da devolução:\n" . $validated['notes'],
                ]);
            }
        });

        return redirect()->route('rentals.index')->with('success', 'Devolução processada com sucesso!');
    }
}
