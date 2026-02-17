@extends('layouts.app')

@section('title', 'Top Rated Dogs')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-2">Top Rated Dogs</h1>
    <p class="text-gray-600 mb-6">Dogs ranked by health clearances, longevity, and breeder quality</p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Health</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hips/Elbows</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Breeder</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($dogs as $index => $dog)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-lg font-bold text-gray-400">#{{ $index + 1 }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($dog->primary_image)
                        <img src="{{ $dog->primary_image }}" alt="" class="w-12 h-12 rounded object-cover">
                        @else
                        <div class="w-12 h-12 rounded overflow-hidden">
                            <img src="/images/bernese-1.png" alt="Bernese Mountain Dog" class="w-full h-full object-cover opacity-75">
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dogs.show', $dog) }}" class="text-bernese-700 hover:text-bernese-900 font-medium">
                            {{ $dog->registered_name ?? 'Unknown' }}
                        </a>
                        @if($dog->call_name)
                            <div class="text-sm text-gray-500">"{{ $dog->call_name }}"</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            {{ $dog->grade >= 70 ? 'bg-green-100 text-green-800' : ($dog->grade >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ number_format($dog->grade, 1) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        {{ number_format($dog->health_score ?? 0, 1) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($dog->hip_rating)
                            <div>{{ Str::limit($dog->hip_rating, 20) }}</div>
                        @endif
                        @if($dog->elbow_rating)
                            <div class="text-gray-500">{{ Str::limit($dog->elbow_rating, 20) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($dog->breeder)
                            <a href="{{ route('breeders.show', $dog->breeder) }}" class="text-bernese-600 hover:underline">
                                {{ $dog->breeder->kennel_name ?? $dog->breeder->full_name }}
                            </a>
                        @else
                            {{ $dog->breeder_name ?? '-' }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
