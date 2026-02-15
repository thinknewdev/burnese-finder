@extends('layouts.app')

@section('title', "Search Results: {$query}")

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <form action="{{ route('search') }}" method="GET" class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="flex">
                <input type="text" name="q" value="{{ $query }}" placeholder="Search dogs, breeders, kennels..."
                    class="flex-1 px-6 py-4 text-lg focus:outline-none">
                <button type="submit" class="bg-bernese-700 text-white px-8 py-4 font-semibold hover:bg-bernese-800 transition">
                    Search
                </button>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="complete" value="1" {{ ($completeOnly ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 text-bernese-600 rounded" onchange="this.form.submit()">
                    <span class="text-sm text-gray-700">Only show dogs with complete health data</span>
                </label>
            </div>
        </form>
    </div>

    <h1 class="text-2xl font-bold text-bernese-900 mb-6">Search Results for "{{ $query }}"</h1>

    @if($results['dogs']->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-bernese-800 mb-4">Dogs ({{ $results['dogs']->count() }})</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($results['dogs'] as $dog)
            <a href="{{ route('dogs.show', $dog) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden flex">
                @if($dog->primary_image)
                <div class="w-24 h-24 flex-shrink-0 bg-gray-100">
                    <img src="{{ $dog->primary_image }}" alt="{{ $dog->registered_name }}" class="w-full h-full object-cover">
                </div>
                @else
                <div class="w-24 h-24 flex-shrink-0 bg-gradient-to-br from-bernese-100 to-bernese-200 flex items-center justify-center">
                    <span class="text-3xl opacity-50">🐕</span>
                </div>
                @endif
                <div class="p-3 flex-1 min-w-0">
                    <div class="flex justify-between items-start">
                        <h3 class="font-semibold text-bernese-900 truncate">{{ $dog->registered_name ?? 'Unknown' }}</h3>
                        @if($dog->grade)
                        <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium flex-shrink-0
                            {{ $dog->grade >= 70 ? 'bg-green-100 text-green-800' : ($dog->grade >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ number_format($dog->grade, 1) }}
                        </span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        @if($dog->sex) {{ $dog->sex }} @endif
                        @if($dog->hip_rating) • Hips: {{ Str::limit($dog->hip_rating, 15) }} @endif
                    </div>
                    @if($dog->breeder_name)
                    <div class="text-sm text-bernese-600 truncate">{{ $dog->breeder_name }}</div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($results['breeders']->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-bernese-800 mb-4">Breeders ({{ $results['breeders']->count() }})</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($results['breeders'] as $breeder)
            <a href="{{ route('breeders.show', $breeder) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
                <h3 class="font-semibold text-bernese-900">{{ $breeder->full_name }}</h3>
                @if($breeder->kennel_name)
                <p class="text-sm text-gray-500">{{ $breeder->kennel_name }}</p>
                @endif
                <div class="text-sm text-gray-600 mt-2">
                    @if($breeder->city || $breeder->state)
                    {{ collect([$breeder->city, $breeder->state])->filter()->implode(', ') }}
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($results['dogs']->count() == 0 && $results['breeders']->count() == 0)
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-gray-400 text-6xl mb-4">🔍</div>
        <h2 class="text-xl font-semibold text-gray-600 mb-2">No results found</h2>
        <p class="text-gray-500">Try a different search term</p>
    </div>
    @endif
</div>
@endsection
