<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        @php($summary = $this->getSummary())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::card>
                <div class="text-sm text-gray-500">Pending</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $summary['pending'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Approved</div>
                <div class="text-2xl font-semibold text-green-600">{{ $summary['approved'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Rejected</div>
                <div class="text-2xl font-semibold text-red-600">{{ $summary['rejected'] }}</div>
            </x-filament::card>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
