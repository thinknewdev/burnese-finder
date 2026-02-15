<?php

namespace App\Http\Controllers;

use App\Models\Dog;
use App\Models\Breeder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $states = Breeder::whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        return view('search.index', compact('states'));
    }

    public function search(Request $request)
    {
        $results = [
            'dogs' => collect(),
            'breeders' => collect(),
        ];

        $query = $request->get('q', '');
        $completeOnly = $request->boolean('complete');

        if (strlen($query) >= 2) {
            $dogsQuery = Dog::with('breeder')->search($query);

            if ($completeOnly) {
                $dogsQuery->withCompleteData();
            }

            $results['dogs'] = $dogsQuery
                ->orderByDesc('grade')
                ->limit(50)
                ->get();

            $results['breeders'] = Breeder::search($query)
                ->orderByDesc('grade')
                ->limit(20)
                ->get();
        }

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        return view('search.results', compact('results', 'query', 'completeOnly'));
    }

    public function findBestDog(Request $request)
    {
        $query = Dog::with('breeder')
            ->whereNotNull('grade');

        // Only complete data by default for best dog search
        if ($request->boolean('complete', true)) {
            $query->withCompleteData();
        }

        // Location preference
        if ($request->filled('state')) {
            $query->whereHas('breeder', fn($q) => $q->where('state', $request->state));
        }

        // Sex preference
        if ($request->filled('sex')) {
            $query->where('sex', $request->sex);
        }

        // Minimum health requirements
        if ($request->boolean('require_hips')) {
            $query->whereNotNull('hip_rating')
                ->where('hip_rating', 'NOT LIKE', '%Severe%')
                ->where('hip_rating', 'NOT LIKE', '%Moderate%');
        }
        if ($request->boolean('require_elbows')) {
            $query->whereNotNull('elbow_rating');
        }

        // Age preference (for puppies vs adults)
        if ($request->filled('max_age')) {
            $query->where('age_years', '<=', $request->max_age);
        }

        // Must be alive
        $query->alive();

        $dogs = $query->orderByDesc('grade')
            ->limit($request->get('limit', 25))
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'count' => $dogs->count(),
                'dogs' => $dogs,
            ]);
        }

        return view('search.best', compact('dogs'));
    }
}
