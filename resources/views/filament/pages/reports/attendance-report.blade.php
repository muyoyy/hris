<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        @php($summary = $this->getSummary())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500">Total Records</div>
                <div class="text-2xl font-semibold">{{ $summary['total'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Hadir</div>
                <div class="text-2xl font-semibold text-green-600">{{ $summary['hadir'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Telat</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $summary['telat'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Alpha</div>
                <div class="text-2xl font-semibold text-red-600">{{ $summary['alpha'] }}</div>
            </x-filament::card>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
