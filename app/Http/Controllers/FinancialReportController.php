<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportController extends Controller
{
    public function index(): Response
    {
        $monthlyForecast = Subscription::where('status', 'active')
            ->join('products', 'subscriptions.product_id', '=', 'products.id')
            ->select(
                DB::raw("DATE_FORMAT(next_billing_date, '%Y-%m') as month"),
                DB::raw('SUM(products.replacement_value) as total_revenue')
            )
            ->where('next_billing_date', '>=', Carbon::now()->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($forecast) {
                $forecast->total_revenue = (float) $forecast->total_revenue; // Converte para número
                return $forecast;
            });

        $overdueSubscriptions = Subscription::with('client:id,business_name', 'product:id,name')
            ->where(function ($query) {
                $query->where('status', 'unpaid')
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')
                            ->where('next_billing_date', '<', today());
                    });
            })
            ->get();

        $upcomingRenewals = Subscription::with('client:id,business_name', 'product:id,name')
            ->where('status', 'active')
            ->whereBetween('next_billing_date', [today(), today()->addDays(30)])
            ->orderBy('next_billing_date', 'asc')
            ->get();


        return Inertia::render('Reports/Index', [
            'monthlyForecast' => $monthlyForecast,
            'overdueSubscriptions' => $overdueSubscriptions,
            'upcomingRenewals' => $upcomingRenewals,
        ]);
    }
}
