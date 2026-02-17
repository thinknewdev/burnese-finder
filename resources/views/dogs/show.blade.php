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
                <div class="w-full h-64 md:h-80 rounded-lg overflow-hidden">
                    <img src="/images/bernese-1.png" alt="Bernese Mountain Dog" class="w-full h-full object-cover opacity-80">
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

                        @php
                            // Check if this dog is an active breeder (has litters since 2023)
                            $recentLittersAsSire = \App\Models\Litter::where('sire_id', $dog->bg_dog_id)
                                ->where('birth_year', '>=', 2023)
                                ->count();
                            $recentLittersAsDam = \App\Models\Litter::where('dam_id', $dog->bg_dog_id)
                                ->where('birth_year', '>=', 2023)
                                ->count();
                            $isActiveBreeder = ($recentLittersAsSire + $recentLittersAsDam) > 0 && !$dog->death_date;
                        @endphp

                        @if($isActiveBreeder)
                        <div class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg shadow-md">
                            <span class="text-lg">🐾</span>
                            <span class="font-semibold text-sm">Active Breeder</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded text-xs">{{ $recentLittersAsSire + $recentLittersAsDam }} recent litters</span>
                        </div>
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

                <!-- Breeding History (for active breeders) -->
                @if($isActiveBreeder)
                @php
                    $recentLitters = \App\Models\Litter::where(function($query) use ($dog) {
                        $query->where('sire_id', $dog->bg_dog_id)
                              ->orWhere('dam_id', $dog->bg_dog_id);
                    })
                    ->where('birth_year', '>=', 2020)
                    ->orderByDesc('birth_year')
                    ->limit(5)
                    ->get();
                @endphp
                @if($recentLitters->count() > 0)
                <div class="border border-green-200 bg-green-50 rounded-lg p-4 mb-4">
                    <h3 class="text-sm font-semibold text-green-800 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <span>🏆</span> Recent Breeding Activity
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentLitters as $litter)
                        <div class="flex justify-between items-center text-sm bg-white rounded px-3 py-2">
                            <div>
                                <span class="font-medium text-gray-700">{{ $litter->birth_year }}</span>
                                @if($litter->birth_date)
                                    <span class="text-gray-500">• {{ $litter->birth_date->format('M d') }}</span>
                                @endif
                                @if($litter->puppies_count)
                                    <span class="text-gray-500">• {{ $litter->puppies_count }} puppies</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                @if($litter->sire_id == $dog->bg_dog_id && $litter->dam_name)
                                    with {{ Str::limit($litter->dam_name, 25) }}
                                @elseif($litter->dam_id == $dog->bg_dog_id && $litter->sire_name)
                                    with {{ Str::limit($litter->sire_name, 25) }}
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                <!-- Health Clearances - Enhanced -->
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Health Clearances</h3>
                        @php
                            $healthTests = collect([
                                'hip_rating' => $dog->hip_rating,
                                'elbow_rating' => $dog->elbow_rating,
                                'heart_status' => $dog->heart_status,
                                'eye_status' => $dog->eye_status,
                                'dm_status' => $dog->dm_status,
                                'dna_status' => $dog->dna_status,
                            ])->filter();
                            $testsCount = $healthTests->count();
                            $maxTests = 6;
                            $completionPercent = $testsCount > 0 ? round(($testsCount / $maxTests) * 100) : 0;
                        @endphp
                        @if($testsCount > 0)
                        <span class="text-xs px-2 py-1 rounded {{ $completionPercent >= 80 ? 'bg-green-100 text-green-700' : ($completionPercent >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ $testsCount }}/{{ $maxTests }} tests
                        </span>
                        @endif
                    </div>
                    @if($testsCount > 0)
                    @php
                        // Helper: split "Result (cert#)" into result and cert parts
                        function splitCert($value) {
                            if (preg_match('/^(.+?)\s*\(([^)]+)\)(.*)$/', $value, $m)) {
                                return ['result' => trim($m[1] . $m[3]), 'cert' => trim($m[2])];
                            }
                            return ['result' => $value, 'cert' => null];
                        }
                    @endphp
                    <div class="bg-white rounded-lg border p-4 space-y-3">
                        @if($dog->hip_rating)
                        @php $hip = splitCert($dog->hip_rating); $hipLower = strtolower($hip['result']); @endphp
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🦴</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">Hip Dysplasia</div>
                                    @if($hip['cert'])<div class="text-xs text-gray-400 font-mono">{{ $hip['cert'] }}</div>@endif
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ str_contains($hipLower, 'excellent') ? 'bg-green-100 text-green-800' :
                                   (str_contains($hipLower, 'good') ? 'bg-green-50 text-green-700' :
                                   (str_contains($hipLower, 'fair') ? 'bg-yellow-100 text-yellow-800' :
                                   'bg-red-100 text-red-800')) }}">
                                {{ $hip['result'] }}
                            </span>
                        </div>
                        @endif
                        @if($dog->elbow_rating)
                        @php $elbow = splitCert($dog->elbow_rating); $elbowLower = strtolower($elbow['result']); @endphp
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">💪</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">Elbow Dysplasia</div>
                                    @if($elbow['cert'])<div class="text-xs text-gray-400 font-mono">{{ $elbow['cert'] }}</div>@endif
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ str_contains($elbowLower, 'normal') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $elbow['result'] }}
                            </span>
                        </div>
                        @endif
                        @if($dog->heart_status)
                        @php $heart = splitCert($dog->heart_status); $heartLower = strtolower($heart['result']); @endphp
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">❤️</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">Cardiac</div>
                                    @if($heart['cert'])<div class="text-xs text-gray-400 font-mono">{{ $heart['cert'] }}</div>@endif
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ str_contains($heartLower, 'normal') || str_contains($heartLower, 'clear') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $heart['result'] }}
                            </span>
                        </div>
                        @endif
                        @if($dog->eye_status)
                        @php $eye = splitCert($dog->eye_status); $eyeLower = strtolower($eye['result']); @endphp
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">👁️</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">Eye Clearance</div>
                                    @if($eye['cert'])<div class="text-xs text-gray-400 font-mono">{{ $eye['cert'] }}</div>@endif
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ str_contains($eyeLower, 'normal') || str_contains($eyeLower, 'clear') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $eye['result'] }}
                            </span>
                        </div>
                        @endif
                        @if($dog->dm_status)
                        @php
                            // DM may contain multiple results separated by " | "
                            $dmParts = explode(' | ', $dog->dm_status);
                            $dmLower = strtolower($dog->dm_status);
                            // Priority: Affected (worst) > Carrier > Clear
                            $dmColor = str_contains($dmLower, 'affected') ? 'bg-red-100 text-red-800' :
                                       (str_contains($dmLower, 'carrier') ? 'bg-yellow-100 text-yellow-700' :
                                       'bg-green-100 text-green-800');
                        @endphp
                        <div class="flex items-start justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🧬</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">DM (Degenerative Myelopathy)</div>
                                    <div class="text-xs text-gray-500">Genetic Test</div>
                                </div>
                            </div>
                            <div class="text-right space-y-1">
                                @foreach($dmParts as $dmPart)
                                @php $dmPartSplit = splitCert(trim($dmPart)); @endphp
                                <div>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $dmColor }}">
                                        {{ $dmPartSplit['result'] }}
                                    </span>
                                    @if($dmPartSplit['cert'])<span class="text-xs text-gray-400 font-mono ml-1">{{ $dmPartSplit['cert'] }}</span>@endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if($dog->dna_status)
                        @php $dna = splitCert($dog->dna_status); @endphp
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🔬</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-700">DNA Profile</div>
                                    @if($dna['cert'])<div class="text-xs text-gray-400 font-mono">{{ $dna['cert'] }}</div>@endif
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $dna['result'] }}
                            </span>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-6 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <span class="text-3xl opacity-30">🩺</span>
                        <p class="text-gray-400 text-sm mt-2">No health clearances on file</p>
                    </div>
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

                <!-- Pedigree - Enhanced 3 Generation Tree -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Pedigree (3 Generations)</h3>
                    @php
                        // Get parents
                        $sire = $dog->sire_id ? \App\Models\Dog::where('bg_dog_id', $dog->sire_id)->first() : null;
                        $dam = $dog->dam_id ? \App\Models\Dog::where('bg_dog_id', $dog->dam_id)->first() : null;

                        // Get grandparents
                        $siresSire = $sire && $sire->sire_id ? \App\Models\Dog::where('bg_dog_id', $sire->sire_id)->first() : null;
                        $siresDam = $sire && $sire->dam_id ? \App\Models\Dog::where('bg_dog_id', $sire->dam_id)->first() : null;
                        $damsSire = $dam && $dam->sire_id ? \App\Models\Dog::where('bg_dog_id', $dam->sire_id)->first() : null;
                        $damsDam = $dam && $dam->dam_id ? \App\Models\Dog::where('bg_dog_id', $dam->dam_id)->first() : null;
                    @endphp
                    @if($sire || $dam)
                    <div class="space-y-3 text-xs">
                        <!-- Sire's line -->
                        @if($sire || $dog->sire_name)
                        <div class="border-l-4 border-blue-400 pl-3 pb-2">
                            <div class="font-semibold text-blue-700 mb-1">♂ SIRE</div>
                            <div class="mb-2">
                                @if($sire)
                                    <a href="{{ route('dogs.show', $sire) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline font-medium">
                                        {{ Str::limit($sire->registered_name, 30) }}
                                    </a>
                                    @if($sire->grade)
                                        <span class="text-green-600 ml-1">({{ number_format($sire->grade, 1) }})</span>
                                    @endif
                                @else
                                    <span class="text-gray-600">{{ Str::limit($dog->sire_name, 30) }}</span>
                                @endif
                            </div>

                            @if($siresSire || $siresDam || ($sire && ($sire->sire_name || $sire->dam_name)))
                            <div class="ml-3 space-y-1 text-xs">
                                @if($siresSire || ($sire && $sire->sire_name))
                                <div class="text-gray-600">
                                    <span class="text-blue-500">♂♂</span>
                                    @if($siresSire)
                                        <a href="{{ route('dogs.show', $siresSire) }}" class="hover:underline">{{ Str::limit($siresSire->registered_name, 25) }}</a>
                                    @elseif($sire && $sire->sire_name)
                                        {{ Str::limit($sire->sire_name, 25) }}
                                    @endif
                                </div>
                                @endif
                                @if($siresDam || ($sire && $sire->dam_name))
                                <div class="text-gray-600">
                                    <span class="text-pink-500">♀♂</span>
                                    @if($siresDam)
                                        <a href="{{ route('dogs.show', $siresDam) }}" class="hover:underline">{{ Str::limit($siresDam->registered_name, 25) }}</a>
                                    @elseif($sire && $sire->dam_name)
                                        {{ Str::limit($sire->dam_name, 25) }}
                                    @endif
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- Dam's line -->
                        @if($dam || $dog->dam_name)
                        <div class="border-l-4 border-pink-400 pl-3">
                            <div class="font-semibold text-pink-700 mb-1">♀ DAM</div>
                            <div class="mb-2">
                                @if($dam)
                                    <a href="{{ route('dogs.show', $dam) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline font-medium">
                                        {{ Str::limit($dam->registered_name, 30) }}
                                    </a>
                                    @if($dam->grade)
                                        <span class="text-green-600 ml-1">({{ number_format($dam->grade, 1) }})</span>
                                    @endif
                                @else
                                    <span class="text-gray-600">{{ Str::limit($dog->dam_name, 30) }}</span>
                                @endif
                            </div>

                            @if($damsSire || $damsDam || ($dam && ($dam->sire_name || $dam->dam_name)))
                            <div class="ml-3 space-y-1 text-xs">
                                @if($damsSire || ($dam && $dam->sire_name))
                                <div class="text-gray-600">
                                    <span class="text-blue-500">♂♀</span>
                                    @if($damsSire)
                                        <a href="{{ route('dogs.show', $damsSire) }}" class="hover:underline">{{ Str::limit($damsSire->registered_name, 25) }}</a>
                                    @elseif($dam && $dam->sire_name)
                                        {{ Str::limit($dam->sire_name, 25) }}
                                    @endif
                                </div>
                                @endif
                                @if($damsDam || ($dam && $dam->dam_name))
                                <div class="text-gray-600">
                                    <span class="text-pink-500">♀♀</span>
                                    @if($damsDam)
                                        <a href="{{ route('dogs.show', $damsDam) }}" class="hover:underline">{{ Str::limit($damsDam->registered_name, 25) }}</a>
                                    @elseif($dam && $dam->dam_name)
                                        {{ Str::limit($dam->dam_name, 25) }}
                                    @endif
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-4 bg-gray-50 rounded border-2 border-dashed border-gray-200">
                        <span class="text-2xl opacity-30">🌳</span>
                        <p class="text-gray-400 text-xs mt-1">Pedigree not available</p>
                    </div>
                    @endif
                </div>

                <!-- Breeder/Kennel - Enhanced -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Breeder Information</h3>
                    @if($dog->breeder)
                    <div class="bg-white border-2 border-bernese-200 rounded-lg overflow-hidden hover:border-bernese-400 transition">
                        {{-- Header: kennel name or breeder name --}}
                        <div class="bg-bernese-50 px-4 py-3 border-b border-bernese-100">
                            @if($dog->breeder->kennel_name)
                            <div class="font-bold text-bernese-900 text-base">{{ $dog->breeder->kennel_name }}</div>
                            <div class="text-sm text-bernese-700">{{ $dog->breeder->full_name }}</div>
                            @else
                            <div class="font-bold text-bernese-900 text-base">{{ $dog->breeder->full_name }}</div>
                            @endif
                            @if($dog->breeder->city || $dog->breeder->state)
                            <div class="text-xs text-gray-500 mt-0.5">
                                📍 {{ collect([$dog->breeder->city, $dog->breeder->state])->filter()->implode(', ') }}
                            </div>
                            @endif
                        </div>

                        {{-- Body: contact + links --}}
                        <div class="px-4 py-3 space-y-2">
                            @if($dog->breeder->email)
                            <div class="text-sm">
                                <a href="mailto:{{ $dog->breeder->email }}" class="text-bernese-600 hover:text-bernese-800 flex items-center gap-2">
                                    <span class="text-base">📧</span>
                                    <span class="truncate">{{ $dog->breeder->email }}</span>
                                </a>
                            </div>
                            @endif
                            @if($dog->breeder->phone)
                            <div class="text-sm">
                                <a href="tel:{{ $dog->breeder->phone }}" class="text-bernese-600 hover:text-bernese-800 flex items-center gap-2">
                                    <span class="text-base">📞</span>
                                    <span>{{ $dog->breeder->phone }}</span>
                                </a>
                            </div>
                            @endif

                            {{-- Grade + links row --}}
                            <div class="flex items-center justify-between pt-1">
                                <div class="flex gap-3 text-xs">
                                    <a href="{{ route('breeders.show', $dog->breeder) }}"
                                       class="text-bernese-600 hover:text-bernese-800 font-medium">
                                        View Profile →
                                    </a>
                                    @if($dog->breeder->bg_person_id && $dog->breeder->bg_person_id > 0)
                                    <a href="https://www.bernergarde.org/DB/Person_Detail?PID={{ $dog->breeder->bg_person_id }}"
                                       target="_blank"
                                       class="text-gray-400 hover:text-gray-600 font-medium">
                                        BernerGarde (ID: {{ $dog->breeder->bg_person_id }}) ↗
                                    </a>
                                    @endif
                                </div>
                                @if($dog->breeder->grade)
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                                    {{ $dog->breeder->grade >= 70 ? 'bg-green-100 text-green-800' : ($dog->breeder->grade >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-800') }}">
                                    Grade {{ number_format($dog->breeder->grade, 1) }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @elseif($dog->breeder_name)
                    <div class="bg-white border-2 border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-100">
                            <div class="font-bold text-gray-800 text-base">{{ $dog->breeder_name }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Contact details not available</div>
                        </div>
                        <div class="px-4 py-3">
                            @if($dog->bg_dog_id)
                            <a href="https://www.bernergarde.org/DB/Dog_Detail?DogID={{ $dog->bg_dog_id }}"
                               target="_blank"
                               class="text-xs text-gray-400 hover:text-gray-600">
                                View dog on BernerGarde for full breeder details ↗
                            </a>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="bg-white border-2 border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-100">
                            <div class="font-bold text-gray-400 text-base">Breeder Not Listed</div>
                        </div>
                        <div class="px-4 py-3">
                            @if($dog->bg_dog_id)
                            <a href="https://www.bernergarde.org/DB/Dog_Detail?DogID={{ $dog->bg_dog_id }}"
                               target="_blank"
                               class="text-xs text-bernese-500 hover:text-bernese-700 flex items-center gap-1">
                                <span>View on BernerGarde for breeder details</span>
                                <span>↗</span>
                            </a>
                            @endif
                        </div>
                    </div>
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
                    <div class="w-10 h-10 rounded overflow-hidden">
                        <img src="/images/bernese-1.png" alt="Dog" class="w-full h-full object-cover opacity-70">
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
