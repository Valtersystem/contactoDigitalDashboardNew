<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $subscriptions = Subscription::with('client:id,business_name', 'product:id,name')
            ->latest()
            ->paginate(10);

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Subscriptions/Create', [
            'clients' => Client::orderBy('business_name')->get(['id', 'business_name']),
            'products' => Product::where('tracking_type', 'LICENSE')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'replacement_value']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date',
            'license_key' => 'nullable|string',
        ]);

        Subscription::create([
            'client_id' => $validated['client_id'],
            'product_id' => $validated['product_id'],
            'start_date' => $validated['start_date'],
            'next_billing_date' => Carbon::parse($validated['start_date'])->addMonth(),
            'license_key' => $validated['license_key'],
            'status' => 'active',
        ]);

        return redirect()->route('subscriptions.index')->with('success', 'Assinatura criada com sucesso.');
    }

    /**
     * Display the specified resource.
     * (Não vamos usar esta, mas é necessária para a rota resource)
     */
    public function show(Subscription $subscription)
    {
        // Poderíamos redirecionar para a página de edição ou para a lista
        return redirect()->route('subscriptions.edit', $subscription);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription): Response
    {
        $subscription->load('client', 'product');

        return Inertia::render('Subscriptions/Edit', [
            'subscription' => $subscription,
            'products' => Product::where('tracking_type', 'LICENSE')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'replacement_value']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'status' => 'required|in:active,cancelled,unpaid',
            'next_billing_date' => 'required|date',
            'license_key' => 'nullable|string',
        ]);

        // Se o status for alterado para 'cancelled', definimos a data de cancelamento
        if ($validated['status'] === 'cancelled' && $subscription->status !== 'cancelled') {
            $validated['cancellation_date'] = now();
        }

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Assinatura atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription): RedirectResponse
    {
        // Em vez de apagar, cancelamos a assinatura
        $subscription->update([
            'status' => 'cancelled',
            'cancellation_date' => now(),
        ]);

        return back()->with('success', 'Assinatura cancelada com sucesso.');
    }
}
