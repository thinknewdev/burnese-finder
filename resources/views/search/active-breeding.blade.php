@extends('layouts.app')

@section('title', 'Active Breeding Dogs')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-2">Active Breeding Dogs</h1>
    <p class="text-gray-600 mb-6">Dogs alive with recent litters ({{ $dogs->count() }} found)</p>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('active-breeding') }}" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Since Year</label>
                <input type="number" name="since_year" value="{{ $sinceYear }}"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500"
                       min="2000" max="{{ now()->year }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                <select name="sex" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="">All</option>
                    <option value="Male" {{ $sex === 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ $sex === 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                <select name="state" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="">All States</option>
                    @foreach($states as $st)
                        <option value="{{ $st }}" {{ $state === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select name="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="recent_litter" {{ $sortBy === 'recent_litter' ? 'selected' : '' }}>Most Recent Litter</option>
                    <option value="grade" {{ $sortBy === 'grade' ? 'selected' : '' }}>Highest Grade</option>
                    <option value="health" {{ $sortBy === 'health' ? 'selected' : '' }}>Health Score</option>
                </select>
            </div>

            <div class="md:col-span-4">
                <button type="submit" class="bg-bernese-700 text-white px-6 py-2 rounded hover:bg-bernese-800 transition">
                    Apply Filters
                </button>
                <a href="{{ route('active-breeding') }}" class="ml-2 text-gray-600 hover:text-gray-800">Reset</a>
            </div>
        </form>
    </div>

    <!-- Results -->
    @if($dogs->count() > 0)
    <div class="space-y-4">
        @foreach($dogs as $index => $dog)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-4 mb-3">
                        <span class="text-2xl font-bold text-gray-300">#{{ $index + 1 }}</span>
                        <div>
                            <a href="{{ route('dogs.show', $dog) }}" class="text-xl font-semibold text-bernese-900 hover:text-bernese-700">
                                {{ $dog->registered_name ?? 'Unknown' }}
                            </a>
                            @if($dog->call_name)
                                <span class="text-gray-500">"{{ $dog->call_name }}"</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4 text-sm mb-3">
                        <div>
                            <span class="text-gray-500">Sex:</span> {{ $dog->sex ?? 'Unknown' }}
                            @if($dog->age_years)
                                <span class="mx-2">•</span>
                                <span class="text-gray-500">Age:</span> {{ $dog->age_years }} years
                            @endif
                        </div>
                        @if($dog->hip_rating)
                        <div>
                            <span class="text-gray-500">Hips:</span>
                            <span class="{{ str_contains($dog->hip_rating, 'Excellent') || str_contains($dog->hip_rating, 'Good') ? 'text-green-600 font-medium' : '' }}">
                                {{ $dog->hip_rating }}
                            </span>
                        </div>
                        @endif
                        @if($dog->elbow_rating)
                        <div>
                            <span class="text-gray-500">Elbows:</span>
                            <span class="{{ str_contains($dog->elbow_rating, 'Normal') ? 'text-green-600 font-medium' : '' }}">
                                {{ $dog->elbow_rating }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Litter Information -->
                    @if(isset($dog->recent_litters) && $dog->recent_litters->count() > 0)
                    <div class="bg-bernese-50 rounded p-3 text-sm">
                        <div class="font-medium text-bernese-900 mb-1">
                            📋 Recent Litters ({{ $dog->total_litters }})
                            @if($dog->most_recent_litter_year)
                                <span class="text-bernese-600">• Most Recent: {{ $dog->most_recent_litter_year }}</span>
                            @endif
                        </div>
                        <div class="text-gray-600">
                            Years: {{ $dog->recent_litters->pluck('birth_year')->unique()->sort()->reverse()->implode(', ') }}
                        </div>
                    </div>
                    @endif

                    @if($dog->breeder)
                    <div class="mt-3 text-sm">
                        <span class="text-gray-500">Breeder:</span>
                        <a href="{{ route('breeders.show', $dog->breeder) }}" class="text-bernese-600 hover:underline">
                            {{ $dog->breeder->kennel_name ?? $dog->breeder->full_name }}
                        </a>
                        @if($dog->breeder->state)
                            <span class="text-gray-400">• {{ $dog->breeder->state }}</span>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="text-center ml-6">
                    <div class="text-3xl font-bold {{ $dog->grade >= 70 ? 'text-green-600' : ($dog->grade >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($dog->grade, 1) }}
                    </div>
                    <div class="text-xs text-gray-500">Grade</div>
                    @if($dog->health_score)
                    <div class="text-sm text-gray-600 mt-1">
                        Health: {{ number_format($dog->health_score, 0) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="w-32 h-32 mx-auto mb-4 rounded-full overflow-hidden opacity-40">
            <img src="/images/bernese-1.png" alt="Bernese Mountain Dog" class="w-full h-full object-cover">
        </div>
        <h2 class="text-xl font-semibold text-gray-600 mb-2">No active breeding dogs found</h2>
        <p class="text-gray-500 mb-4">Try adjusting your filters to see more results</p>
    </div>
    @endif
</div>
@endsection
