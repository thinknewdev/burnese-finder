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
                            <span class="text-sm text-gray-600">Health Clearances (50%)</span>
                            <span class="font-semibold {{ ($dog->health_score ?? 50) >= 70 ? 'text-green-600' : (($dog->health_score ?? 50) >= 40 ? 'text-bernese-700' : 'text-red-600') }}">
                                {{ number_format($dog->health_score ?? 50, 1) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Own Longevity (30%)</span>
                            <span class="font-semibold text-bernese-700">{{ number_format($dog->longevity_score ?? 50, 1) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            @php $pedigreeScore = $dog->pedigree_longevity_score ?? 50; @endphp
                            <span class="text-sm text-gray-600">
                                Pedigree Longevity (20%)
                                @if($pedigreeScore == 50)
                                <span class="text-xs text-gray-400 ml-1" title="Based on sire &amp; dam lifespan — defaults to neutral when parents have unknown lifespan">(?)</span>
                                @endif
                            </span>
                            <span class="font-semibold {{ $pedigreeScore >= 70 ? 'text-green-600' : ($pedigreeScore >= 40 ? 'text-bernese-700' : 'text-red-600') }}">
                                {{ number_format($pedigreeScore, 1) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pedigree - Parent Summary -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Parents</h3>
                    @if($pedigree['sire'] || $pedigree['sire_name'] || $pedigree['dam'] || $pedigree['dam_name'])
                    <div class="space-y-2 text-xs">
                        @if($pedigree['sire'] || $pedigree['sire_name'])
                        <div class="border-l-4 border-blue-400 pl-3 py-1">
                            <div class="text-blue-600 font-semibold text-xs mb-0.5">♂ SIRE</div>
                            @if($pedigree['sire'])
                                <a href="{{ route('dogs.show', $pedigree['sire']) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline font-medium leading-tight block">{{ Str::limit($pedigree['sire']->registered_name, 32) }}</a>
                                <div class="flex gap-2 mt-1 flex-wrap">
                                    @if($pedigree['sire']->grade)
                                    <span class="px-1.5 py-0.5 rounded text-xs font-semibold {{ $pedigree['sire']->grade >= 60 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        Grade {{ number_format($pedigree['sire']->grade, 1) }}
                                    </span>
                                    @endif
                                    @if($pedigree['sire']->hip_rating)
                                    <span class="text-gray-500">{{ Str::before($pedigree['sire']->hip_rating, ' (') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-500">{{ Str::limit($pedigree['sire_name'], 32) }}</span>
                            @endif
                        </div>
                        @endif
                        @if($pedigree['dam'] || $pedigree['dam_name'])
                        <div class="border-l-4 border-pink-400 pl-3 py-1">
                            <div class="text-pink-600 font-semibold text-xs mb-0.5">♀ DAM</div>
                            @if($pedigree['dam'])
                                <a href="{{ route('dogs.show', $pedigree['dam']) }}" class="text-bernese-700 hover:text-bernese-900 hover:underline font-medium leading-tight block">{{ Str::limit($pedigree['dam']->registered_name, 32) }}</a>
                                <div class="flex gap-2 mt-1 flex-wrap">
                                    @if($pedigree['dam']->grade)
                                    <span class="px-1.5 py-0.5 rounded text-xs font-semibold {{ $pedigree['dam']->grade >= 60 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        Grade {{ number_format($pedigree['dam']->grade, 1) }}
                                    </span>
                                    @endif
                                    @if($pedigree['dam']->hip_rating)
                                    <span class="text-gray-500">{{ Str::before($pedigree['dam']->hip_rating, ' (') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-500">{{ Str::limit($pedigree['dam_name'], 32) }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-4 bg-gray-50 rounded border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 text-xs">Parentage not on record</p>
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

        <!-- Full-Width Pedigree Chart -->
        @php
            $hasPedigreeData = $pedigree['sire'] || $pedigree['sire_name'] || $pedigree['dam'] || $pedigree['dam_name'];
        @endphp
        @if($hasPedigreeData)
        <div class="border-t p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Pedigree Chart</h3>

            @php
            // Helper: render a pedigree node (dog record or fallback text)
            function pedigreeCell($dog, $fallbackName, $sexClass, $label) {
                $hasDog = !is_null($dog);
                $hasName = $hasDog || !empty($fallbackName);
                if (!$hasName) return '<div class="h-full flex items-center justify-center text-gray-300 text-xs italic px-2">—</div>';
                $out = '<div class="px-2 py-1.5 h-full flex flex-col justify-center gap-0.5">';
                $out .= '<div class="text-' . ($dog?->sex === 'Female' ? 'pink' : 'blue') . '-500 text-xs font-semibold leading-none mb-0.5">' . $label . '</div>';
                if ($hasDog) {
                    $name = Str::limit($dog->registered_name, 28);
                    $url = route('dogs.show', $dog);
                    $out .= '<a href="' . $url . '" class="text-bernese-700 hover:text-bernese-900 hover:underline font-medium text-xs leading-tight">' . htmlspecialchars($name) . '</a>';
                    if ($dog->grade) {
                        $color = $dog->grade >= 60 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                        $out .= '<span class="text-xs px-1 py-0 rounded ' . $color . ' w-fit mt-0.5">Gr ' . number_format($dog->grade, 1) . '</span>';
                    }
                    $hip = $dog->hip_rating ? Str::before($dog->hip_rating, ' (') : null;
                    if ($hip) {
                        $out .= '<span class="text-gray-400 text-xs leading-tight">Hip: ' . htmlspecialchars($hip) . '</span>';
                    }
                    if ($dog->dm_status) {
                        $dm = Str::before($dog->dm_status, ' (');
                        $dm = Str::before($dm, ' |');
                        $dmColor = str_contains(strtolower($dm), 'clear') ? 'text-green-500' : (str_contains(strtolower($dm), 'affected') ? 'text-red-500' : 'text-gray-400');
                        $out .= '<span class="text-xs ' . $dmColor . ' leading-tight">DM: ' . htmlspecialchars(Str::limit($dm, 15)) . '</span>';
                    }
                } else {
                    $out .= '<span class="text-gray-500 text-xs leading-tight">' . htmlspecialchars(Str::limit($fallbackName, 28)) . '</span>';
                }
                $out .= '</div>';
                return $out;
            }
            @endphp

            {{-- Pedigree table: Gen 1 (parents) | Gen 2 (grandparents) | Gen 3 (great-grandparents) --}}
            {{-- 8 rows, G1 spans 4, G2 spans 2, G3 spans 1 --}}
            <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs" style="min-width: 560px">
                <colgroup>
                    <col style="width: 33%">
                    <col style="width: 33%">
                    <col style="width: 34%">
                </colgroup>
                {{-- Row 1: Sire | Sire's Sire | Sire's Sire's Sire --}}
                <tr class="border-b border-gray-100">
                    <td rowspan="4" class="border-r border-gray-200 align-middle" style="border-bottom: 1px solid #e5e7eb">
                        {!! pedigreeCell($pedigree['sire'], $pedigree['sire_name'], 'blue', '♂ SIRE') !!}
                    </td>
                    <td rowspan="2" class="border-r border-gray-200 align-middle bg-blue-50/30" style="border-bottom: 1px solid #e5e7eb">
                        {!! pedigreeCell($pedigree['sires_sire'], $pedigree['sires_sire_name'], 'blue', '♂♂ Sire\'s Sire') !!}
                    </td>
                    <td class="align-middle bg-blue-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['ss_s'], $pedigree['ss_s_name'], 'blue', '♂♂♂') !!}
                    </td>
                </tr>
                {{-- Row 2: | | Sire's Sire's Dam --}}
                <tr class="border-b border-gray-100">
                    <td class="align-middle bg-pink-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['ss_d'], $pedigree['ss_d_name'], 'pink', '♀♂♂') !!}
                    </td>
                </tr>
                {{-- Row 3: | Sire's Dam | Sire's Dam's Sire --}}
                <tr class="border-b border-gray-100">
                    <td rowspan="2" class="border-r border-gray-200 align-middle bg-pink-50/30" style="border-bottom: 1px solid #e5e7eb">
                        {!! pedigreeCell($pedigree['sires_dam'], $pedigree['sires_dam_name'], 'pink', '♀♂ Sire\'s Dam') !!}
                    </td>
                    <td class="align-middle bg-blue-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['sd_s'], $pedigree['sd_s_name'], 'blue', '♂♀♂') !!}
                    </td>
                </tr>
                {{-- Row 4: | | Sire's Dam's Dam --}}
                <tr class="border-b border-gray-200">
                    <td class="align-middle bg-pink-50/20 border-b border-gray-200">
                        {!! pedigreeCell($pedigree['sd_d'], $pedigree['sd_d_name'], 'pink', '♀♀♂') !!}
                    </td>
                </tr>
                {{-- Row 5: Dam | Dam's Sire | Dam's Sire's Sire --}}
                <tr class="border-b border-gray-100">
                    <td rowspan="4" class="border-r border-gray-200 align-middle bg-pink-50/10" style="border-bottom: 1px solid #e5e7eb">
                        {!! pedigreeCell($pedigree['dam'], $pedigree['dam_name'], 'pink', '♀ DAM') !!}
                    </td>
                    <td rowspan="2" class="border-r border-gray-200 align-middle bg-blue-50/30" style="border-bottom: 1px solid #e5e7eb">
                        {!! pedigreeCell($pedigree['dams_sire'], $pedigree['dams_sire_name'], 'blue', '♂♀ Dam\'s Sire') !!}
                    </td>
                    <td class="align-middle bg-blue-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['ds_s'], $pedigree['ds_s_name'], 'blue', '♂♂♀') !!}
                    </td>
                </tr>
                {{-- Row 6: | | Dam's Sire's Dam --}}
                <tr class="border-b border-gray-100">
                    <td class="align-middle bg-pink-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['ds_d'], $pedigree['ds_d_name'], 'pink', '♀♂♀') !!}
                    </td>
                </tr>
                {{-- Row 7: | Dam's Dam | Dam's Dam's Sire --}}
                <tr class="border-b border-gray-100">
                    <td rowspan="2" class="border-r border-gray-200 align-middle bg-pink-50/30">
                        {!! pedigreeCell($pedigree['dams_dam'], $pedigree['dams_dam_name'], 'pink', '♀♀ Dam\'s Dam') !!}
                    </td>
                    <td class="align-middle bg-blue-50/20 border-b border-gray-100">
                        {!! pedigreeCell($pedigree['dd_s'], $pedigree['dd_s_name'], 'blue', '♂♀♀') !!}
                    </td>
                </tr>
                {{-- Row 8: | | Dam's Dam's Dam --}}
                <tr>
                    <td class="align-middle bg-pink-50/20">
                        {!! pedigreeCell($pedigree['dd_d'], $pedigree['dd_d_name'], 'pink', '♀♀♀') !!}
                    </td>
                </tr>
            </table>
            </div>
            <p class="text-xs text-gray-400 mt-2">Grade shown where dog record exists in database. Hip and DM results from OFA certifications.</p>
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
