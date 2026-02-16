@extends('layouts.app')

@section('title', $dog->registered_name ?? 'Dog Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <a href="{{ url()->previous() }}" class="text-bernese-600 hover:text-bernese-800 mb-4 inline-block">
        ← Back
    </a>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header with image and basic info side by side -->
        <div class="md:flex">
            <!-- Image -->
            <div class="md:w-1/3 bg-gradient-to-br from-bernese-800 to-bernese-900 flex items-center justify-center p-4">
                @if($dog->primary_image)
                <img src="{{ $dog->primary_image }}" alt="{{ $dog->registered_name }}"
                    class="w-full h-64 md:h-80 object-cover rounded-lg shadow-lg">
                @else
                <div class="w-full h-64 md:h-80 bg-bernese-700 rounded-lg flex items-center justify-center">
                    <span class="text-bernese-400 text-6xl">🐕</span>
                </div>
                @endif
            </div>

            <!-- Main info -->
            <div class="md:w-2/3 p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-bernese-900">{{ $dog->registered_name ?? 'Unknown' }}</h1>
                        @if($dog->call_name)
                            <p class="text-lg text-gray-500">"{{ $dog->call_name }}"</p>
                        @endif
                        @if($dog->titles)
                            <p class="text-sm text-amber-600 font-medium mt-1">{{ $dog->titles }}</p>
                        @endif
                    </div>
                    @if($dog->grade)
                    <div class="text-center bg-{{ $dog->grade >= 70 ? 'green' : ($dog->grade >= 50 ? 'yellow' : 'red') }}-50 rounded-lg p-3">
                        <div class="text-3xl font-bold {{ $dog->grade >= 70 ? 'text-green-600' : ($dog->grade >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($dog->grade, 1) }}
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Grade</div>
                    </div>
                    @endif
                </div>

                <!-- Quick stats row -->
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($dog->sex)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm {{ $dog->sex == 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                        {{ $dog->sex }}
                    </span>
                    @endif
                    @if($dog->birth_date)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                        Born {{ $dog->birth_date->format('M d, Y') }}
                    </span>
                    @endif
                    @if($dog->death_date)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-600">
                        Deceased {{ $dog->death_date->format('M d, Y') }}
                        @if($dog->age_years)
                            ({{ $dog->age_years }} yrs)
                        @endif
                    </span>
                    @elseif($dog->birth_date)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-700">
                        {{ $dog->birth_date->age }} years old
                    </span>
                    @endif
                    @if($dog->color)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800">
                        {{ $dog->color }}
                    </span>
                    @endif
                    @if($dog->ofa_certified)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                        OFA Certified
                    </span>
                    @endif
                </div>

                <!-- Registration Info -->
                @if($dog->registration_number || $dog->dna_number || $dog->microchip)
                <div class="text-sm text-gray-600 mb-4 space-y-1">
                    @if($dog->registration_number)
                    <div><span class="font-medium">Registration:</span> {{ $dog->registration_number }}</div>
                    @endif
                    @if($dog->dna_number)
                    <div><span class="font-medium">DNA:</span> {{ $dog->dna_number }}</div>
                    @endif
                    @if($dog->microchip)
                    <div><span class="font-medium">Microchip:</span> {{ $dog->microchip }}</div>
                    @endif
                </div>
                @endif

                <!-- Health Clearances -->
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Health Clearances</h3>
                    @if($dog->hip_rating || $dog->elbow_rating || $dog->heart_status || $dog->eye_status || $dog->dm_status || $dog->dna_status)
                    <div class="grid grid-cols-2 gap-3">
                        @if($dog->hip_rating)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ str_contains(strtolower($dog->hip_rating), 'excellent') || str_contains(strtolower($dog->hip_rating), 'good') ? 'bg-green-500' : 'bg-yellow-500' }}"></span>
                            <span class="text-sm"><strong>Hips:</strong> {{ $dog->hip_rating }}</span>
                        </div>
                        @endif
                        @if($dog->elbow_rating)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ str_contains(strtolower($dog->elbow_rating), 'normal') ? 'bg-green-500' : 'bg-yellow-500' }}"></span>
                            <span class="text-sm"><strong>Elbows:</strong> {{ $dog->elbow_rating }}</span>
                        </div>
                        @endif
                        @if($dog->heart_status)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm"><strong>Heart:</strong> {{ $dog->heart_status }}</span>
                        </div>
                        @endif
                        @if($dog->eye_status)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm"><strong>Eyes:</strong> {{ $dog->eye_status }}</span>
                        </div>
                        @endif
                        @if($dog->dm_status)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ str_contains(strtolower($dog->dm_status), 'clear') || str_contains(strtolower($dog->dm_status), 'normal') ? 'bg-green-500' : (str_contains(strtolower($dog->dm_status), 'carrier') ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                            <span class="text-sm"><strong>DM:</strong> {{ $dog->dm_status }}</span>
                        </div>
                        @endif
                        @if($dog->dna_status)
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-sm"><strong>DNA:</strong> {{ $dog->dna_status }}</span>
                        </div>
                        @endif
                    </div>
                    @else
                    <p class="text-gray-400 text-sm italic">No health clearances on file</p>
                    @endif
                </div>

                @if($dog->bg_dog_id)
                <div class="mt-4 pt-4 border-t">
                    <a href="https://bernergarde.org/DB/Dog_Detail?DogID={{ $dog->bg_dog_id }}" target="_blank"
                        class="text-sm text-bernese-600 hover:text-bernese-800 inline-flex items-center gap-1">
                        View on BernerGarde <span class="text-xs">(ID: {{ $dog->bg_dog_id }})</span> →
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Details sections -->
        <div class="border-t bg-gray-50 p-6">
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Scores -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Score Breakdown</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Health (40%)</span>
                            <span class="font-semibold text-bernese-700">{{ number_format($dog->health_score ?? 50, 1) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Longevity (40%)</span>
                            <span class="font-semibold text-bernese-700">{{ number_format($dog->longevity_score ?? 50, 1) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Breeder (20%)</span>
                            <span class="font-semibold text-bernese-700">{{ number_format($dog->breeder_score ?? $dog->breeder?->grade ?? 50, 1) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pedigree -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Pedigree</h3>
                    @if($dog->sire_name || $dog->dam_name)
                    <div class="space-y-2">
                        @if($dog->sire_name)
                        <div class="text-sm">
                            <span class="text-blue-600 font-medium">Sire:</span>
                            @if($dog->sire_id && $sire = \App\Models\Dog::where('bg_dog_id', $dog->sire_id)->first())
                                <a href="{{ route('dogs.show', $sire) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline">{{ $dog->sire_name }}</a>
                            @else
                                <span class="text-gray-700">{{ $dog->sire_name }}</span>
                            @endif
                        </div>
                        @endif
                        @if($dog->dam_name)
                        <div class="text-sm">
                            <span class="text-pink-600 font-medium">Dam:</span>
                            @if($dog->dam_id && $dam = \App\Models\Dog::where('bg_dog_id', $dog->dam_id)->first())
                                <a href="{{ route('dogs.show', $dam) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline">{{ $dog->dam_name }}</a>
                            @else
                                <span class="text-gray-700">{{ $dog->dam_name }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    @else
                    <p class="text-gray-400 text-sm italic">Unknown pedigree</p>
                    @endif
                </div>

                <!-- Breeder -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Breeder</h3>
                    @if($dog->breeder)
                    <a href="{{ route('breeders.show', $dog->breeder) }}" class="block hover:bg-white rounded p-2 -m-2 transition">
                        <div class="font-semibold text-bernese-700">{{ $dog->breeder->full_name }}</div>
                        @if($dog->breeder->kennel_name)
                        <div class="text-sm text-gray-500">{{ $dog->breeder->kennel_name }}</div>
                        @endif
                        @if($dog->breeder->city || $dog->breeder->state)
                        <div class="text-sm text-gray-400">{{ collect([$dog->breeder->city, $dog->breeder->state])->filter()->implode(', ') }}</div>
                        @endif
                    </a>
                    @elseif($dog->breeder_name)
                    <div class="text-gray-700">{{ $dog->breeder_name }}</div>
                    @else
                    <p class="text-gray-400 text-sm italic">Unknown breeder</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Physical Characteristics & Additional Info -->
        @if($dog->weight || $dog->height || $dog->bite || $dog->tail || $dog->eye_color || $dog->owner_name || $dog->stud_book || $dog->frozen_semen || $dog->rescue_type)
        <div class="border-t p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Physical Characteristics -->
                @if($dog->weight || $dog->height || $dog->bite || $dog->tail || $dog->eye_color)
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Physical Characteristics</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        @if($dog->weight)
                        <div><span class="text-gray-500">Weight:</span> <span class="text-gray-800">{{ $dog->weight }}</span></div>
                        @endif
                        @if($dog->height)
                        <div><span class="text-gray-500">Height:</span> <span class="text-gray-800">{{ $dog->height }}</span></div>
                        @endif
                        @if($dog->bite)
                        <div><span class="text-gray-500">Bite:</span> <span class="text-gray-800">{{ $dog->bite }}</span></div>
                        @endif
                        @if($dog->tail)
                        <div><span class="text-gray-500">Tail:</span> <span class="text-gray-800">{{ $dog->tail }}</span></div>
                        @endif
                        @if($dog->eye_color)
                        <div><span class="text-gray-500">Eye Color:</span> <span class="text-gray-800">{{ $dog->eye_color }}</span></div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Additional Info -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Additional Info</h3>
                    <div class="space-y-2 text-sm">
                        @if($dog->owner_name)
                        <div><span class="text-gray-500">Owner:</span> <span class="text-gray-800">{{ $dog->owner_name }}</span></div>
                        @endif
                        @if($dog->stud_book)
                        <div><span class="text-gray-500">Stud Book:</span> <span class="text-gray-800">{{ $dog->stud_book }}</span></div>
                        @endif
                        @if($dog->frozen_semen)
                        <div class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs">Frozen Semen Available</div>
                        @endif
                        @if($dog->rescue_type)
                        <div><span class="text-gray-500">Rescue:</span> <span class="text-gray-800">{{ $dog->rescue_type }}</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Offspring Section -->
        @php
            $offspringAsSire = \App\Models\Dog::where('sire_id', $dog->bg_dog_id)->limit(10)->get();
            $offspringAsDam = \App\Models\Dog::where('dam_id', $dog->bg_dog_id)->limit(10)->get();
            $totalOffspring = \App\Models\Dog::where('sire_id', $dog->bg_dog_id)->orWhere('dam_id', $dog->bg_dog_id)->count();
        @endphp
        @if($offspringAsSire->count() > 0 || $offspringAsDam->count() > 0)
        <div class="border-t p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Offspring
                @if($totalOffspring > 0)
                <span class="text-gray-400 font-normal">({{ $totalOffspring }} total)</span>
                @endif
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($offspringAsSire->merge($offspringAsDam)->unique('id')->take(9) as $offspring)
                <a href="{{ route('dogs.show', $offspring) }}" class="flex items-center gap-3 p-2 rounded hover:bg-gray-100 transition">
                    @if($offspring->primary_image)
                    <img src="{{ $offspring->primary_image }}" class="w-10 h-10 rounded object-cover">
                    @else
                    <div class="w-10 h-10 rounded bg-gradient-to-br from-bernese-100 to-bernese-200 flex items-center justify-center">
                        <span class="text-sm opacity-50">🐕</span>
                    </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-bernese-800 truncate">{{ $offspring->registered_name ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $offspring->sex ?? '' }}
                            @if($offspring->birth_date) • {{ $offspring->birth_date->format('Y') }}@endif
                            @if($offspring->grade) • <span class="text-green-600">{{ number_format($offspring->grade, 1) }}</span>@endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @if($totalOffspring > 9)
            <div class="mt-3 text-center">
                <span class="text-sm text-gray-500">And {{ $totalOffspring - 9 }} more offspring...</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Litter Info -->
        @if($dog->litter_id)
        @php
            $litter = \App\Models\Litter::where('bg_litter_id', $dog->litter_id)->first();
            $siblings = $litter ? \App\Models\Dog::where('litter_id', $dog->litter_id)->where('id', '!=', $dog->id)->limit(10)->get() : collect();
        @endphp
        @if($litter || $siblings->count() > 0)
        <div class="border-t p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Litter Information</h3>
            @if($litter)
            <div class="text-sm text-gray-600 mb-3">
                @if($litter->birth_date)
                <span>Whelped: {{ $litter->birth_date->format('M d, Y') }}</span>
                @endif
                @if($litter->puppies_count)
                <span class="ml-4">{{ $litter->puppies_count }} puppies</span>
                @endif
            </div>
            @endif
            @if($siblings->count() > 0)
            <div class="text-sm font-medium text-gray-700 mb-2">Littermates:</div>
            <div class="flex flex-wrap gap-2">
                @foreach($siblings as $sibling)
                <a href="{{ route('dogs.show', $sibling) }}" class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-full hover:bg-gray-200 transition text-sm">
                    <span class="text-bernese-700">{{ $sibling->call_name ?? Str::limit($sibling->registered_name, 20) }}</span>
                    @if($sibling->sex)
                    <span class="text-xs {{ $sibling->sex == 'Male' ? 'text-blue-500' : 'text-pink-500' }}">{{ substr($sibling->sex, 0, 1) }}</span>
                    @endif
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
