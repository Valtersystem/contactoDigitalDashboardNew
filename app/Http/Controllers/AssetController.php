<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    /**
     * Mostra a lista de ativos para um produto específico.
     */
    public function index(Product $product): Response
    {
        // Garante que só se pode gerir ativos de produtos serializados
        if ($product->tracking_type !== 'SERIALIZED') {
            abort(404);
        }

        return Inertia::render('Assets/Index', [
            'product' => $product,
            // Carrega os ativos associados a este produto
            'assets' => $product->assets()->latest()->paginate(15),
        ]);
    }

    /**
     * Guarda um novo ativo para um produto específico.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:50|unique:assets,serial_number',
            'status' => 'sometimes|string', // Opcional, o default é 'Disponível'
            'notes' => 'nullable|string',
        ]);

        $product->assets()->create($validated);

        return back()->with('success', 'Ativo adicionado com sucesso.');
    }

    public function updateStatus(Request $request, Asset $asset): RedirectResponse
    {
        // Apenas permitir alterar o status se o ativo não estiver associado a um aluguel ativo
        if ($asset->status === 'Alugado') {
            return back()->with('error', 'Não pode alterar o status de um ativo que está atualmente alugado.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Disponível', 'Em Manutenção', 'Perdido'])],
        ]);

        $asset->update(['status' => $validated['status']]);

        return back()->with('success', 'Status do ativo atualizado com sucesso.');
    }


    /**
     * Apaga um ativo específico.
     */
    public function destroy(Asset $asset): RedirectResponse
    {
        // Adicionar lógica para verificar se o ativo está num aluguel no futuro
        // if ($asset->isRented()) {
        //     return back()->with('error', 'Não é possível apagar um ativo que está alugado.');
        // }

        $asset->delete();

        return back()->with('success', 'Ativo apagado com sucesso.');
    }
}
