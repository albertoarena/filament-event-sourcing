<x-filament-panels::page>
    <div class="fi-fes-replay-projectors space-y-3">
        @forelse ($this->getProjectors() as $projector)
            <div class="fi-fes-replay-projector flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <p class="font-medium">{{ class_basename($projector) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $projector }}</p>
                </div>

                {{ ($this->replayAction)(['projector' => $projector]) }}
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No projectors are registered.</p>
        @endforelse
    </div>
</x-filament-panels::page>
