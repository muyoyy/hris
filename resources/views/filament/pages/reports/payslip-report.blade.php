<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        @php($summary = $this->getSummary())
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::card>
                <div class="text-sm text-gray-500">Total Payslips</div>
                <div class="text-2xl font-semibold">{{ $summary['total'] }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Total Net Pay</div>
                <div class="text-2xl font-semibold">Rp {{ number_format($summary['net_pay'], 0, ',', '.') }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Issued</div>
                <div class="text-2xl font-semibold text-green-600">{{ $summary['issued'] }}</div>
            </x-filament::card>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
