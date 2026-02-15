@extends('layouts.app')

@section('title', 'Best Dog Matches')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-2">Your Best Matches</h1>
    <p class="text-gray-600 mb-6">Found {{ $dogs->count() }} dogs matching your criteria</p>

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

                    <div class="grid md:grid-cols-3 gap-4 text-sm">
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
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-gray-400 text-6xl mb-4">🐕</div>
        <h2 class="text-xl font-semibold text-gray-600 mb-2">No matches found</h2>
        <p class="text-gray-500 mb-4">Try adjusting your search criteria</p>
        <a href="{{ route('home') }}" class="inline-block bg-bernese-700 text-white px-6 py-2 rounded hover:bg-bernese-800">
            Try Again
        </a>
    </div>
    @endif
</div>
@endsection
