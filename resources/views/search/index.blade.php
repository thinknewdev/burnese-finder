@extends('layouts.app')

@section('title', 'Find Your Perfect Bernese')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Hero Section -->
    <div class="text-center mb-8">
        <h1 class="text-5xl font-bold text-bernese-900 mb-4">Find Your Perfect Bernese</h1>
        <p class="text-xl text-gray-600">Search and grade Bernese Mountain Dogs by health, longevity, and breeder quality</p>
    </div>

    <!-- Active Breeding Search - HIGHLIGHTED HERO SECTION -->
    <div class="bg-gradient-to-r from-bernese-700 to-bernese-900 rounded-xl shadow-2xl p-8 mb-12 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">🐾 Active Breeding Dogs</h2>
                <p class="text-bernese-100">Find alive dogs with recent litters - perfect for breeding programs</p>
            </div>
            <div class="bg-white/20 backdrop-blur rounded-lg px-6 py-4 text-center">
                <div class="text-4xl font-bold">{{ \App\Models\Dog::aliveWithRecentLitters(2023)->count() }}</div>
                <div class="text-sm text-bernese-100">Active Breeders</div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                <div class="text-2xl font-bold">{{ \App\Models\Dog::aliveWithRecentLitters(2024)->count() }}</div>
                <div class="text-sm text-bernese-100">Litters in 2024</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                <div class="text-2xl font-bold">{{ \App\Models\Dog::aliveWithRecentLitters(2025)->count() }}</div>
                <div class="text-sm text-bernese-100">Litters in 2025</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                <div class="text-2xl font-bold">{{ \App\Models\Litter::where('birth_year', '>=', 2023)->whereNotNull('sire_id')->whereNotNull('dam_id')->count() }}</div>
                <div class="text-sm text-bernese-100">Tracked Litters</div>
            </div>
        </div>

        <a href="{{ route('active-breeding') }}" class="block w-full bg-white text-bernese-900 text-center py-4 rounded-lg font-bold text-lg hover:bg-bernese-50 transition transform hover:scale-105 shadow-xl">
            Browse Active Breeding Dogs →
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="grid md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Dog::count() }}</div>
            <div class="text-gray-600">Dogs in Database</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl font-bold text-green-600">{{ \App\Models\Dog::withCompleteData()->count() }}</div>
            <div class="text-gray-600">With Health Data</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Breeder::count() }}</div>
            <div class="text-gray-600">Registered Breeders</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Litter::count() }}</div>
            <div class="text-gray-600">Recorded Litters</div>
        </div>
    </div>

    <!-- Quick Search -->
    <form action="{{ route('search') }}" method="GET" class="mb-12">
        <div class="flex shadow-lg rounded-lg overflow-hidden">
            <input type="text" name="q" placeholder="Search dogs, breeders, or kennels..."
                class="flex-1 px-6 py-4 text-lg focus:outline-none">
            <button type="submit" class="bg-bernese-700 text-white px-8 py-4 font-semibold hover:bg-bernese-800 transition">
                Search
            </button>
        </div>
    </form>

    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <!-- Find Best Dog Tool -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-bernese-900 mb-6">Find Your Best Match</h2>

            <form action="{{ route('find-best') }}" method="GET">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                        <select name="state" class="w-full border rounded-lg px-4 py-2">
                            <option value="">Any Location</option>
                            @foreach($states as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                        <select name="w-full border rounded-lg px-4 py-2">
                            <option value="">Either</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="require_hips" value="1" class="rounded">
                            <span>Require Hip Clearance</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="require_elbows" value="1" class="rounded">
                            <span>Require Elbow Clearance</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full bg-bernese-700 text-white py-3 rounded-lg font-semibold hover:bg-bernese-800 transition">
                        Find Best Dogs
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-bernese-900 mb-6">Quick Access</h2>
            <div class="space-y-3">
                <a href="{{ route('active-breeding') }}" class="block bg-bernese-50 hover:bg-bernese-100 text-bernese-900 p-4 rounded-lg transition">
                    <div class="font-semibold text-lg">🐕 Active Breeding Dogs</div>
                    <div class="text-sm text-gray-600">Dogs with recent litters</div>
                </a>
                <a href="{{ route('dogs.top') }}" class="block bg-gray-50 hover:bg-gray-100 text-gray-900 p-4 rounded-lg transition">
                    <div class="font-semibold text-lg">⭐ Top Rated Dogs</div>
                    <div class="text-sm text-gray-600">Highest health & longevity scores</div>
                </a>
                <a href="{{ route('breeders.index') }}" class="block bg-gray-50 hover:bg-gray-100 text-gray-900 p-4 rounded-lg transition">
                    <div class="font-semibold text-lg">🏠 Browse Breeders</div>
                    <div class="text-sm text-gray-600">Find reputable breeders</div>
                </a>
                <a href="{{ route('dogs.index') }}" class="block bg-gray-50 hover:bg-gray-100 text-gray-900 p-4 rounded-lg transition">
                    <div class="font-semibold text-lg">📋 All Dogs</div>
                    <div class="text-sm text-gray-600">Complete database</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Top Rated Preview -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-bernese-900 mb-6">Top Rated Dogs (With Complete Health Data)</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach(\App\Models\Dog::withCompleteData()->whereNotNull('grade')->orderByDesc('grade')->limit(6)->get() as $dog)
            <a href="{{ route('dogs.show', $dog) }}" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-xl transition transform hover:scale-105">
                @if($dog->primary_image)
                <div class="h-48 bg-gray-100">
                    <img src="{{ $dog->primary_image }}" alt="{{ $dog->registered_name }}" class="w-full h-full object-cover">
                </div>
                @else
                <div class="h-48 bg-gradient-to-br from-bernese-100 to-bernese-200 flex items-center justify-center">
                    <span class="text-6xl opacity-50">🐕</span>
                </div>
                @endif
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-semibold text-bernese-900 truncate flex-1">{{ $dog->registered_name ?? 'Unknown' }}</h3>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-bold ml-2">
                            {{ number_format($dog->grade, 1) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600">
                        @if($dog->sex)
                            <div>{{ $dog->sex }}</div>
                        @endif
                        @if($dog->hip_rating)
                            <div class="truncate">Hips: {{ $dog->hip_rating }}</div>
                        @endif
                        @if($dog->breeder_name)
                            <div class="truncate">Breeder: {{ $dog->breeder_name }}</div>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-6">
            <a href="{{ route('dogs.top') }}" class="text-bernese-700 hover:text-bernese-900 font-semibold text-lg">
                View All Top Rated Dogs →
            </a>
        </div>
    </div>
</div>
@endsection
