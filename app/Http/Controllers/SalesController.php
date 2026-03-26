<?php

namespace App\Http\Controllers;

use App\Models\SalesReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    public function index(Request $request): Response
    {
        $reports = SalesReport::select([
                'id',
                'day',
                'month',
                'year',
                'status',
                'breeder',
                'code',
                'photo',
                'name',
                'qty',
                'sell_jpy',
                'sell_idr',
                'margin',
                'fee',
                'stock_jpy',
                'stock_idr',
                'sell_to',
                'profit',
                'user_id',
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('day')
            ->paginate(10);

        return Inertia::render('Sales/Index', [
            'reports' => $reports,
        ]);
    }
}