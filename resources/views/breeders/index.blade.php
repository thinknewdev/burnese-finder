@extends('layouts.app')

@section('title', 'All Breeders')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-6">All Breeders</h1>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                class="border rounded px-3 py-2">

            <select name="state" class="border rounded px-3 py-2">
                <option value="">Any State</option>
                @foreach(\App\Models\Breeder::whereNotNull('state')->distinct()->pluck('state')->sort() as $state)
                    <option value="{{ $state }}" {{ request('state') == $state ? 'selected' : '' }}>{{ $state }}</option>
                @endforeach
            </select>

            <select name="sort" class="border rounded px-3 py-2">
                <option value="grade" {{ request('sort') == 'grade' ? 'selected' : '' }}>Sort by Grade</option>
                <option value="dogs_bred_count" {{ request('sort') == 'dogs_bred_count' ? 'selected' : '' }}>Sort by Dogs Bred</option>
                <option value="last_name" {{ request('sort') == 'last_name' ? 'selected' : '' }}>Sort by Name</option>
            </select>

            <button type="submit" class="bg-bernese-700 text-white rounded px-4 py-2 hover:bg-bernese-800">
                Filter
            </button>
        </div>
    </form>

    <!-- Breeders Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($breeders as $breeder)
        <a href="{{ route('breeders.show', $breeder) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="font-semibold text-bernese-900">{{ $breeder->full_name }}</h3>
                    @if($breeder->kennel_name)
                        <p class="text-sm text-gray-500">{{ $breeder->kennel_name }}</p>
                    @endif
                </div>
                @if($breeder->grade)
                <span class="px-2 py-1 rounded text-sm font-medium
                    {{ $breeder->grade >= 70 ? 'bg-green-100 text-green-800' : ($breeder->grade >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ number_format($breeder->grade, 1) }}
                </span>
                @endif
            </div>

            <div class="text-sm text-gray-600 space-y-1">
                @if($breeder->city || $breeder->state)
                    <div>{{ collect([$breeder->city, $breeder->state, $breeder->country])->filter()->implode(', ') }}</div>
                @endif
                <div class="flex space-x-4">
                    <span>{{ $breeder->dogs_count ?? $breeder->dogs_bred_count }} dogs</span>
                    <span>{{ $breeder->litters_count }} litters</span>
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            No breeders found matching your criteria.
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $breeders->withQueryString()->links() }}
    </div>
</div>
@endsection
