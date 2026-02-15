<?php

namespace App\Http\Controllers;

use App\Models\Dog;
use Illuminate\Http\Request;

class DogController extends Controller
{
    public function index(Request $request)
    {
        $query = Dog::with('breeder');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filters
        if ($request->filled('sex')) {
            $query->bySex($request->sex);
        }
        if ($request->filled('state')) {
            $query->whereHas('breeder', fn($q) => $q->where('state', $request->state));
        }
        if ($request->filled('min_grade')) {
            $query->where('grade', '>=', $request->min_grade);
        }
        if ($request->filled('has_health')) {
            $query->withHealthClearances();
        }
        if ($request->boolean('alive_only')) {
            $query->alive();
        }

        // Sorting
        $sortField = $request->get('sort', 'grade');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $dogs = $query->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($dogs);
        }

        return view('dogs.index', compact('dogs'));
    }

    public function show(Dog $dog)
    {
        $dog->load('breeder');

        if (request()->wantsJson()) {
            return response()->json($dog);
        }

        return view('dogs.show', compact('dog'));
    }

    public function topRated(Request $request)
    {
        $limit = $request->get('limit', 50);

        $dogs = Dog::with('breeder')
            ->whereNotNull('grade')
            ->orderByDesc('grade')
            ->limit($limit)
            ->get();

        if ($request->wantsJson()) {
            return response()->json($dogs);
        }

        return view('dogs.top', compact('dogs'));
    }
}
