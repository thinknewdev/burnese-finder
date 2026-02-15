@extends('layouts.app')

@section('title', $breeder->full_name)

@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ url()->previous() }}" class="text-bernese-600 hover:text-bernese-800 mb-4 inline-block">
        ← Back
    </a>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-bernese-900">{{ $breeder->full_name }}</h1>
                    @if($breeder->kennel_name)
                        <p class="text-xl text-gray-500">{{ $breeder->kennel_name }}</p>
                    @endif
                </div>
                @if($breeder->grade)
                <div class="text-center">
                    <div class="text-4xl font-bold {{ $breeder->grade >= 70 ? 'text-green-600' : ($breeder->grade >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($breeder->grade, 1) }}
                    </div>
                    <div class="text-sm text-gray-500">Breeder Grade</div>
                </div>
                @endif
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Contact Info -->
                <div>
                    <h2 class="text-lg font-semibold text-bernese-800 mb-3">Contact Information</h2>
                    <dl class="space-y-2">
                        @if($breeder->city || $breeder->state)
                        <div class="flex">
                            <dt class="w-24 text-gray-500">Location:</dt>
                            <dd>{{ collect([$breeder->city, $breeder->state, $breeder->country])->filter()->implode(', ') }}</dd>
                        </div>
                        @endif
                        @if($breeder->email)
                        <div class="flex">
                            <dt class="w-24 text-gray-500">Email:</dt>
                            <dd><a href="mailto:{{ $breeder->email }}" class="text-bernese-600 hover:underline">{{ $breeder->email }}</a></dd>
                        </div>
                        @endif
                        @if($breeder->phone)
                        <div class="flex">
                            <dt class="w-24 text-gray-500">Phone:</dt>
                            <dd>{{ $breeder->phone }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Stats -->
                <div>
                    <h2 class="text-lg font-semibold text-bernese-800 mb-3">Statistics</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded p-4 text-center">
                            <div class="text-2xl font-bold text-bernese-700">{{ $breeder->dogs_bred_count }}</div>
                            <div class="text-sm text-gray-500">Dogs Bred</div>
                        </div>
                        <div class="bg-gray-50 rounded p-4 text-center">
                            <div class="text-2xl font-bold text-bernese-700">{{ $breeder->litters_count }}</div>
                            <div class="text-sm text-gray-500">Litters</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dogs -->
            @if($breeder->dogs->count() > 0)
            <div class="mt-8 pt-6 border-t">
                <h2 class="text-lg font-semibold text-bernese-800 mb-4">Dogs by This Breeder</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($breeder->dogs as $dog)
                    <a href="{{ route('dogs.show', $dog) }}" class="flex items-center justify-between bg-gray-50 rounded p-3 hover:bg-gray-100 transition">
                        <div>
                            <div class="font-medium text-bernese-900">{{ $dog->registered_name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $dog->sex }}
                                @if($dog->hip_rating) • Hips: {{ Str::limit($dog->hip_rating, 15) }} @endif
                            </div>
                        </div>
                        @if($dog->grade)
                        <span class="px-2 py-1 rounded text-sm font-medium
                            {{ $dog->grade >= 70 ? 'bg-green-100 text-green-800' : ($dog->grade >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ number_format($dog->grade, 1) }}
                        </span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
