<?php $__env->startSection('title', 'Active Breeding Dogs'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-bernese-900 mb-2">Active Breeding Dogs</h1>
    <p class="text-gray-600 mb-6">Dogs alive with recent litters (<?php echo e($dogs->count()); ?> found)</p>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="<?php echo e(route('active-breeding')); ?>" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Since Year</label>
                <input type="number" name="since_year" value="<?php echo e($sinceYear); ?>"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500"
                       min="2000" max="<?php echo e(now()->year); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                <select name="sex" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="">All</option>
                    <option value="Male" <?php echo e($sex === 'Male' ? 'selected' : ''); ?>>Male</option>
                    <option value="Female" <?php echo e($sex === 'Female' ? 'selected' : ''); ?>>Female</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                <select name="state" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="">All States</option>
                    <?php $__currentLoopData = $states; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($st); ?>" <?php echo e($state === $st ? 'selected' : ''); ?>><?php echo e($st); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select name="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:border-bernese-500 focus:ring-bernese-500">
                    <option value="recent_litter" <?php echo e($sortBy === 'recent_litter' ? 'selected' : ''); ?>>Most Recent Litter</option>
                    <option value="grade" <?php echo e($sortBy === 'grade' ? 'selected' : ''); ?>>Highest Grade</option>
                    <option value="health" <?php echo e($sortBy === 'health' ? 'selected' : ''); ?>>Health Score</option>
                </select>
            </div>

            <div class="md:col-span-4">
                <button type="submit" class="bg-bernese-700 text-white px-6 py-2 rounded hover:bg-bernese-800 transition">
                    Apply Filters
                </button>
                <a href="<?php echo e(route('active-breeding')); ?>" class="ml-2 text-gray-600 hover:text-gray-800">Reset</a>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if($dogs->count() > 0): ?>
    <div class="space-y-4">
        <?php $__currentLoopData = $dogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $dog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-4 mb-3">
                        <span class="text-2xl font-bold text-gray-300">#<?php echo e($index + 1); ?></span>
                        <div>
                            <a href="<?php echo e(route('dogs.show', $dog)); ?>" class="text-xl font-semibold text-bernese-900 hover:text-bernese-700">
                                <?php echo e($dog->registered_name ?? 'Unknown'); ?>

                            </a>
                            <?php if($dog->call_name): ?>
                                <span class="text-gray-500">"<?php echo e($dog->call_name); ?>"</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4 text-sm mb-3">
                        <div>
                            <span class="text-gray-500">Sex:</span> <?php echo e($dog->sex ?? 'Unknown'); ?>

                            <?php if($dog->age_years): ?>
                                <span class="mx-2">•</span>
                                <span class="text-gray-500">Age:</span> <?php echo e($dog->age_years); ?> years
                            <?php endif; ?>
                        </div>
                        <?php if($dog->hip_rating): ?>
                        <div>
                            <span class="text-gray-500">Hips:</span>
                            <span class="<?php echo e(str_contains($dog->hip_rating, 'Excellent') || str_contains($dog->hip_rating, 'Good') ? 'text-green-600 font-medium' : ''); ?>">
                                <?php echo e($dog->hip_rating); ?>

                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if($dog->elbow_rating): ?>
                        <div>
                            <span class="text-gray-500">Elbows:</span>
                            <span class="<?php echo e(str_contains($dog->elbow_rating, 'Normal') ? 'text-green-600 font-medium' : ''); ?>">
                                <?php echo e($dog->elbow_rating); ?>

                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Litter Information -->
                    <?php if(isset($dog->recent_litters) && $dog->recent_litters->count() > 0): ?>
                    <div class="bg-bernese-50 rounded p-3 text-sm">
                        <div class="font-medium text-bernese-900 mb-1">
                            📋 Recent Litters (<?php echo e($dog->total_litters); ?>)
                            <?php if($dog->most_recent_litter_year): ?>
                                <span class="text-bernese-600">• Most Recent: <?php echo e($dog->most_recent_litter_year); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-gray-600">
                            Years: <?php echo e($dog->recent_litters->pluck('birth_year')->unique()->sort()->reverse()->implode(', ')); ?>

                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($dog->breeder): ?>
                    <div class="mt-3 text-sm">
                        <span class="text-gray-500">Breeder:</span>
                        <a href="<?php echo e(route('breeders.show', $dog->breeder)); ?>" class="text-bernese-600 hover:underline">
                            <?php echo e($dog->breeder->kennel_name ?? $dog->breeder->full_name); ?>

                        </a>
                        <?php if($dog->breeder->state): ?>
                            <span class="text-gray-400">• <?php echo e($dog->breeder->state); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="text-center ml-6">
                    <div class="text-3xl font-bold <?php echo e($dog->grade >= 70 ? 'text-green-600' : ($dog->grade >= 50 ? 'text-yellow-600' : 'text-red-600')); ?>">
                        <?php echo e(number_format($dog->grade, 1)); ?>

                    </div>
                    <div class="text-xs text-gray-500">Grade</div>
                    <?php if($dog->health_score): ?>
                    <div class="text-sm text-gray-600 mt-1">
                        Health: <?php echo e(number_format($dog->health_score, 0)); ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-gray-400 text-6xl mb-4">🐕</div>
        <h2 class="text-xl font-semibold text-gray-600 mb-2">No active breeding dogs found</h2>
        <p class="text-gray-500 mb-4">Try adjusting your filters to see more results</p>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/search/active-breeding.blade.php ENDPATH**/ ?>