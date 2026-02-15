<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use Illuminate\Http\Request;

class BreederController extends Controller
{
    public function index(Request $request)
    {
        $query = Breeder::withCount(['dogs', 'litters']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filters
        if ($request->filled('state')) {
            $query->byState($request->state);
        }
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        if ($request->filled('min_grade')) {
            $query->where('grade', '>=', $request->min_grade);
        }
        if ($request->filled('min_dogs')) {
            $query->where('dogs_bred_count', '>=', $request->min_dogs);
        }

        // Sorting
        $sortField = $request->get('sort', 'grade');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $breeders = $query->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($breeders);
        }

        return view('breeders.index', compact('breeders'));
    }

    public function show(Breeder $breeder)
    {
        $breeder->load(['dogs' => fn($q) => $q->orderByDesc('grade')->limit(20)]);

        if (request()->wantsJson()) {
            return response()->json($breeder);
        }

        return view('breeders.show', compact('breeder'));
    }

    public function topRated(Request $request)
    {
        $limit = $request->get('limit', 50);

        $breeders = Breeder::withCount(['dogs', 'litters'])
            ->whereNotNull('grade')
            ->orderByDesc('grade')
            ->limit($limit)
            ->get();

        if ($request->wantsJson()) {
            return response()->json($breeders);
        }

        return view('breeders.top', compact('breeders'));
    }
}
