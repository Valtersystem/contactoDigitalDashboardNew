<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response; // Importe Response
use Illuminate\Http\RedirectResponse; // Importe RedirectResponse
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('clients/Index', [
            'clients' => Client::latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'nif' => 'required|string|max:9|unique:clients,nif',
            'business_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Cliente criado com sucesso.');
    }

    public function edit(Client $client): Response
    {

        return Inertia::render('Clients/Edit', [
            'client' => $client
        ]);
    }

    /**
     * Atualiza o cliente na base de dados.
     */
    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'nif' => ['required', 'string', 'max:9', Rule::unique('clients')->ignore($client->id)],
            'business_name' => 'required|string|max:191',
            'email' => ['nullable', 'email', 'max:191', Rule::unique('clients')->ignore($client->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Cliente atualizado com sucesso.');
    }


    public function show(Client $client): Response
    {
        $client->load(['rentals' => function ($query) {
            $query->latest()->with(['rentalItems' => function ($itemQuery) {
                // Adicionado image_url à consulta do produto
                $itemQuery->with('product:id,name,tracking_type,image_url', 'asset:id,serial_number');
            }]);
        }]);

        // Filtra os alugueis ativos
        $activeRentals = $client->rentals->whereIn('status', ['Alugado', 'Atrasado']);
        // Extrai os itens desses alugueis
        $activeRentalItems = $activeRentals->pluck('rentalItems')->flatten();

        $stats = [
            'total_rentals' => $client->rentals->count(),
            'active_rentals' => $activeRentals->count(),
            'completed_rentals' => $client->rentals->where('status', 'Devolvido')->count(),
        ];

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'stats' => $stats,
            'activeRentalItems' => $activeRentalItems, // Envia os itens ativos para a view
        ]);
    }

    public function destroy(Client $client): RedirectResponse
    {

        if ($client->rentals()->exists()) {
            return back()->with('error', 'Não é possível apagar um cliente que já possui alugueis.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente apagado com sucesso.');
    }
}
