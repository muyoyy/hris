@php
    $attendance = $this->getAttendanceSummary();
    $leave = $this->getLeaveSummary();
    $payslip = $this->getPayslipSummary();
@endphp

<x-filament::page>
    <div class="grid gap-6">
        <div class="bg-white rounded-xl shadow p-4">
            {{ $this->form }}
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <x-filament::card>
                <div class="text-sm text-gray-500">Kehadiran</div>
                <div class="text-2xl font-semibold">{{ array_sum($attendance) }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Leave Requests</div>
                <div class="text-2xl font-semibold">{{ array_sum($leave) }}</div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-sm text-gray-500">Payslip Issued</div>
                <div class="text-2xl font-semibold">{{ $payslip['count'] }} (Rp {{ number_format($payslip['net_pay_sum'], 0, ',', '.') }})</div>
            </x-filament::card>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <x-filament::card>
                <div class="font-semibold mb-2">Ringkasan Absensi</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @foreach (['HADIR','TELAT','ALPHA','IZIN','SAKIT'] as $status)
                            <tr>
                                <td class="py-1">{{ $status }}</td>
                                <td class="py-1 text-right font-semibold">{{ $attendance[$status] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-2">Ringkasan Leave</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @foreach (['PENDING','APPROVED','REJECTED'] as $status)
                            <tr>
                                <td class="py-1">{{ $status }}</td>
                                <td class="py-1 text-right font-semibold">{{ $leave[$status] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-2">Ringkasan Payslip Issued</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        <tr>
                            <td class="py-1">Total Issued</td>
                            <td class="py-1 text-right font-semibold">{{ $payslip['count'] }}</td>
                        </tr>
                        <tr>
                            <td class="py-1">Total Net Pay</td>
                            <td class="py-1 text-right font-semibold">Rp {{ number_format($payslip['net_pay_sum'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-filament::card>
        </div>
    </div>
</x-filament::page>
