<?php $__env->startSection('title', $dog->registered_name ?? 'Dog Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto">
    <a href="<?php echo e(url()->previous()); ?>" class="text-bernese-600 hover:text-bernese-800 mb-4 inline-block">
        ← Back
    </a>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header with image and basic info side by side -->
        <div class="md:flex">
            <!-- Image -->
            <div class="md:w-1/3 bg-gradient-to-br from-bernese-800 to-bernese-900 flex items-center justify-center p-4">
                <?php if($dog->primary_image): ?>
                <img src="<?php echo e($dog->primary_image); ?>" alt="<?php echo e($dog->registered_name); ?>"
                    class="w-full h-64 md:h-80 object-cover rounded-lg shadow-lg">
                <?php else: ?>
                <div class="w-full h-64 md:h-80 bg-bernese-700 rounded-lg flex items-center justify-center">
                    <span class="text-bernese-400 text-6xl">🐕</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main info -->
            <div class="md:w-2/3 p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-bernese-900"><?php echo e($dog->registered_name ?? 'Unknown'); ?></h1>
                        <?php if($dog->call_name): ?>
                            <p class="text-lg text-gray-500">"<?php echo e($dog->call_name); ?>"</p>
                        <?php endif; ?>
                    </div>
                    <?php if($dog->grade): ?>
                    <div class="text-center bg-<?php echo e($dog->grade >= 70 ? 'green' : ($dog->grade >= 50 ? 'yellow' : 'red')); ?>-50 rounded-lg p-3">
                        <div class="text-3xl font-bold <?php echo e($dog->grade >= 70 ? 'text-green-600' : ($dog->grade >= 50 ? 'text-yellow-600' : 'text-red-600')); ?>">
                            <?php echo e(number_format($dog->grade, 1)); ?>

                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Grade</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick stats row -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <?php if($dog->sex): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm <?php echo e($dog->sex == 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'); ?>">
                        <?php echo e($dog->sex); ?>

                    </span>
                    <?php endif; ?>
                    <?php if($dog->birth_date): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                        Born <?php echo e($dog->birth_date->format('M Y')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if($dog->age_years): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                        <?php echo e($dog->age_years); ?> years old
                    </span>
                    <?php endif; ?>
                    <?php if($dog->death_date): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-600">
                        Deceased <?php echo e($dog->death_date->format('M Y')); ?>

                    </span>
                    <?php endif; ?>
                    <?php if($dog->color): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800">
                        <?php echo e($dog->color); ?>

                    </span>
                    <?php endif; ?>
                </div>

                <!-- Health Clearances -->
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Health Clearances</h3>
                    <?php if($dog->hip_rating || $dog->elbow_rating || $dog->heart_status || $dog->eye_status): ?>
                    <div class="grid grid-cols-2 gap-3">
                        <?php if($dog->hip_rating): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?php echo e(str_contains($dog->hip_rating, 'Excellent') || str_contains($dog->hip_rating, 'Good') ? 'bg-green-500' : 'bg-yellow-500'); ?>"></span>
                            <span class="text-sm"><strong>Hips:</strong> <?php echo e($dog->hip_rating); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($dog->elbow_rating): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?php echo e(str_contains($dog->elbow_rating, 'Normal') ? 'bg-green-500' : 'bg-yellow-500'); ?>"></span>
                            <span class="text-sm"><strong>Elbows:</strong> <?php echo e($dog->elbow_rating); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($dog->heart_status): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm"><strong>Heart:</strong> <?php echo e($dog->heart_status); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($dog->eye_status): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm"><strong>Eyes:</strong> <?php echo e($dog->eye_status); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-400 text-sm italic">No health clearances on file</p>
                    <?php endif; ?>
                </div>

                <?php if($dog->bg_dog_id): ?>
                <div class="mt-4 pt-4 border-t">
                    <a href="https://bernergarde.org/DB/Dog_Detail?DogID=<?php echo e($dog->bg_dog_id); ?>" target="_blank"
                        class="text-sm text-bernese-600 hover:text-bernese-800 inline-flex items-center gap-1">
                        View on BernerGarde <span class="text-xs">(ID: <?php echo e($dog->bg_dog_id); ?>)</span> →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Score breakdown and additional info -->
        <div class="border-t bg-gray-50 p-6">
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Scores -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Score Breakdown</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Health (40%)</span>
                            <span class="font-semibold text-bernese-700"><?php echo e(number_format($dog->health_score ?? 50, 1)); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Longevity (40%)</span>
                            <span class="font-semibold text-bernese-700"><?php echo e(number_format($dog->longevity_score ?? 50, 1)); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Breeder (20%)</span>
                            <span class="font-semibold text-bernese-700"><?php echo e(number_format($dog->breeder_score ?? $dog->breeder?->grade ?? 50, 1)); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Pedigree -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Pedigree</h3>
                    <?php if($dog->sire_name || $dog->dam_name): ?>
                    <div class="space-y-2">
                        <?php if($dog->sire_name): ?>
                        <div class="text-sm">
                            <span class="text-blue-600 font-medium">Sire:</span>
                            <span class="text-gray-700"><?php echo e($dog->sire_name); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($dog->dam_name): ?>
                        <div class="text-sm">
                            <span class="text-pink-600 font-medium">Dam:</span>
                            <span class="text-gray-700"><?php echo e($dog->dam_name); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-400 text-sm italic">Unknown pedigree</p>
                    <?php endif; ?>
                </div>

                <!-- Breeder -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Breeder</h3>
                    <?php if($dog->breeder): ?>
                    <a href="<?php echo e(route('breeders.show', $dog->breeder)); ?>" class="block hover:bg-white rounded p-2 -m-2 transition">
                        <div class="font-semibold text-bernese-700"><?php echo e($dog->breeder->full_name); ?></div>
                        <?php if($dog->breeder->kennel_name): ?>
                        <div class="text-sm text-gray-500"><?php echo e($dog->breeder->kennel_name); ?></div>
                        <?php endif; ?>
                        <?php if($dog->breeder->city || $dog->breeder->state): ?>
                        <div class="text-sm text-gray-400"><?php echo e(collect([$dog->breeder->city, $dog->breeder->state])->filter()->implode(', ')); ?></div>
                        <?php endif; ?>
                    </a>
                    <?php elseif($dog->breeder_name): ?>
                    <div class="text-gray-700"><?php echo e($dog->breeder_name); ?></div>
                    <?php else: ?>
                    <p class="text-gray-400 text-sm italic">Unknown breeder</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/dogs/show.blade.php ENDPATH**/ ?>