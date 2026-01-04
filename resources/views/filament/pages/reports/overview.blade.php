<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::card>
            <div class="text-sm text-gray-500">Placeholder laporan</div>
            <div class="text-base text-gray-700">
                Tambahkan widget atau chart setelah data siap.
            </div>
        </x-filament::card>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-filament::card>
                <div class="text-lg font-semibold">Attendance Report</div>
                <div class="text-sm text-gray-600">Rekap kehadiran harian.</div>
                <div class="mt-3">
                    <x-filament::button tag="a" href="{{ route('filament.muyo.pages.attendance-report') }}">
                        Buka Report
                    </x-filament::button>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-lg font-semibold">Employee</div>
                <div class="text-sm text-gray-600">Data karyawan.</div>
                <div class="mt-3">
                    <x-filament::button tag="a" href="{{ route('filament.muyo.resources.employees.index') }}">
                        Buka Data
                    </x-filament::button>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-lg font-semibold">Leave Request</div>
                <div class="text-sm text-gray-600">Permohonan izin/sakit.</div>
                <div class="mt-3">
                    <x-filament::button tag="a" href="{{ route('filament.muyo.pages.leave-report') }}">
                        Buka Report
                    </x-filament::button>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-lg font-semibold">Payslips</div>
                <div class="text-sm text-gray-600">Ringkasan slip gaji.</div>
                <div class="mt-3 flex gap-2">
                    <x-filament::button tag="a" href="{{ route('filament.muyo.pages.payslip-report') }}">
                        Buka Report
                    </x-filament::button>
                    <x-filament::button color="gray" tag="a" href="{{ route('filament.muyo.resources.payslips.index') }}">
                        Data Slip
                    </x-filament::button>
                </div>
            </x-filament::card>
        </div>
    </div>
</x-filament-panels::page>
