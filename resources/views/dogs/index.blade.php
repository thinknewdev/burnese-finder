@extends('layouts.app')

@section('title', 'All Dogs')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-6">All Dogs</h1>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid md:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                class="border rounded px-3 py-2">

            <select name="sex" class="border rounded px-3 py-2">
                <option value="">Any Sex</option>
                <option value="Male" {{ request('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ request('sex') == 'Female' ? 'selected' : '' }}>Female</option>
            </select>

            <select name="sort" class="border rounded px-3 py-2">
                <option value="grade" {{ request('sort') == 'grade' ? 'selected' : '' }}>Sort by Grade</option>
                <option value="registered_name" {{ request('sort') == 'registered_name' ? 'selected' : '' }}>Sort by Name</option>
                <option value="birth_date" {{ request('sort') == 'birth_date' ? 'selected' : '' }}>Sort by Age</option>
            </select>

            <label class="flex items-center space-x-2">
                <input type="checkbox" name="alive_only" value="1" {{ request('alive_only') ? 'checked' : '' }}>
                <span>Alive Only</span>
            </label>

            <button type="submit" class="bg-bernese-700 text-white rounded px-4 py-2 hover:bg-bernese-800">
                Filter
            </button>
        </div>
    </form>

    <!-- Dogs Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($dogs as $dog)
        <a href="{{ route('dogs.show', $dog) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden flex flex-col">
            @if($dog->primary_image)
            <div class="h-40 bg-gray-100">
                <img src="{{ $dog->primary_image }}" alt="{{ $dog->registered_name }}" class="w-full h-full object-cover">
            </div>
            @else
            <div class="h-24 overflow-hidden">
                <img src="/images/bernese-1.png" alt="Bernese Mountain Dog" class="w-full h-full object-cover opacity-75">
            </div>
            @endif
            <div class="p-4 flex-1">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-bernese-900 truncate">{{ $dog->registered_name ?? 'Unknown' }}</h3>
                        @if($dog->call_name)
                            <p class="text-sm text-gray-500">"{{ $dog->call_name }}"</p>
                        @endif
                    </div>
                    @if($dog->grade)
                    <span class="ml-2 px-2 py-1 rounded text-sm font-medium flex-shrink-0
                        {{ $dog->grade >= 70 ? 'bg-green-100 text-green-800' : ($dog->grade >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($dog->grade, 1) }}
                    </span>
                    @endif
                </div>

                <div class="text-sm text-gray-600 space-y-1">
                    @if($dog->sex)
                        <div>{{ $dog->sex }} {{ $dog->age_years ? "• {$dog->age_years} years" : '' }}</div>
                    @endif
                    @if($dog->hip_rating)
                        <div class="truncate">Hips: {{ $dog->hip_rating }}</div>
                    @endif
                    @if($dog->breeder_name)
                        <div class="text-bernese-600 truncate">{{ $dog->breeder_name }}</div>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            No dogs found matching your criteria.
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $dogs->withQueryString()->links() }}
    </div>
</div>
@endsection
