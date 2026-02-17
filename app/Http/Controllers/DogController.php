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

        // Build 3-generation pedigree tree, stripping .0 suffix from IDs
        $findDog = fn($id) => $id
            ? Dog::where('bg_dog_id', preg_replace('/\.0+$/', '', (string) $id))->first()
            : null;

        $sire     = $findDog($dog->sire_id);
        $dam      = $findDog($dog->dam_id);

        $siresSire = $sire ? $findDog($sire->sire_id) : null;
        $siresDam  = $sire ? $findDog($sire->dam_id)  : null;
        $damsSire  = $dam  ? $findDog($dam->sire_id)  : null;
        $damsDam   = $dam  ? $findDog($dam->dam_id)   : null;

        $pedigree = [
            'sire'      => $sire,
            'dam'       => $dam,
            'sire_name' => $dog->sire_name,
            'dam_name'  => $dog->dam_name,

            'sires_sire'      => $siresSire,
            'sires_sire_name' => $sire?->sire_name,
            'sires_dam'       => $siresDam,
            'sires_dam_name'  => $sire?->dam_name,

            'dams_sire'      => $damsSire,
            'dams_sire_name' => $dam?->sire_name,
            'dams_dam'       => $damsDam,
            'dams_dam_name'  => $dam?->dam_name,

            // Great-grandparents
            'ss_s'      => $siresSire ? $findDog($siresSire->sire_id) : null,
            'ss_s_name' => $siresSire?->sire_name,
            'ss_d'      => $siresSire ? $findDog($siresSire->dam_id)  : null,
            'ss_d_name' => $siresSire?->dam_name,

            'sd_s'      => $siresDam ? $findDog($siresDam->sire_id) : null,
            'sd_s_name' => $siresDam?->sire_name,
            'sd_d'      => $siresDam ? $findDog($siresDam->dam_id)  : null,
            'sd_d_name' => $siresDam?->dam_name,

            'ds_s'      => $damsSire ? $findDog($damsSire->sire_id) : null,
            'ds_s_name' => $damsSire?->sire_name,
            'ds_d'      => $damsSire ? $findDog($damsSire->dam_id)  : null,
            'ds_d_name' => $damsSire?->dam_name,

            'dd_s'      => $damsDam ? $findDog($damsDam->sire_id) : null,
            'dd_s_name' => $damsDam?->sire_name,
            'dd_d'      => $damsDam ? $findDog($damsDam->dam_id)  : null,
            'dd_d_name' => $damsDam?->dam_name,
        ];

        if (request()->wantsJson()) {
            return response()->json($dog);
        }

        return view('dogs.show', compact('dog', 'pedigree'));
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
