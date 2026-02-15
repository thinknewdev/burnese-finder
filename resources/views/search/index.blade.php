@extends('layouts.app')

@section('title', 'Find Your Perfect Bernese')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-bernese-900 mb-4">Find Your Perfect Bernese</h1>
        <p class="text-xl text-gray-600">Search and grade Bernese Mountain Dogs by health, longevity, and breeder quality</p>
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

    <!-- Quick Stats -->
    <div class="grid md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Dog::count() }}</div>
            <div class="text-gray-600">Dogs in Database</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-4xl font-bold text-green-600">{{ \App\Models\Dog::withCompleteData()->count() }}</div>
            <div class="text-gray-600">With Health Data</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Breeder::count() }}</div>
            <div class="text-gray-600">Registered Breeders</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-4xl font-bold text-bernese-700">{{ \App\Models\Litter::count() }}</div>
            <div class="text-gray-600">Recorded Litters</div>
        </div>
    </div>

    <!-- Find Best Dog Tool -->
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-bernese-900 mb-6">Find Your Best Match</h2>

        <form action="{{ route('find-best') }}" method="GET">
            <div class="grid md:grid-cols-2 gap-6">
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
                    <select name="sex" class="w-full border rounded-lg px-4 py-2">
                        <option value="">Either</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="require_hips" value="1" class="rounded">
                        <span>Require Hip Clearance</span>
                    </label>
                </div>

                <div>
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

    <!-- Top Rated Preview -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-bernese-900 mb-6">Top Rated Dogs (With Complete Health Data)</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach(\App\Models\Dog::withCompleteData()->whereNotNull('grade')->orderByDesc('grade')->limit(6)->get() as $dog)
            <a href="{{ route('dogs.show', $dog) }}" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                @if($dog->primary_image)
                <div class="h-32 bg-gray-100">
                    <img src="{{ $dog->primary_image }}" alt="{{ $dog->registered_name }}" class="w-full h-full object-cover">
                </div>
                @else
                <div class="h-20 bg-gradient-to-br from-bernese-100 to-bernese-200 flex items-center justify-center">
                    <span class="text-3xl opacity-50">🐕</span>
                </div>
                @endif
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-semibold text-bernese-900 truncate">{{ $dog->registered_name ?? 'Unknown' }}</h3>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-medium">
                            {{ number_format($dog->grade, 1) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600">
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
            <a href="{{ route('dogs.top') }}" class="text-bernese-700 hover:text-bernese-900 font-semibold">
                View All Top Rated Dogs →
            </a>
        </div>
    </div>
</div>
@endsection
