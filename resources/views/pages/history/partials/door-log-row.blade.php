<tr data-door-log-id="{{ $log->id }}" @class(['bg-bad-50/50' => $log->result === 'denied'])>
    <td data-label="Waktu"><x-cell.datetime :value="$log->at" seconds /></td>
    <td data-label="Nama"><x-cell.person :name="$log->name" :role="$log->role" /></td>
    <td data-label="Pintu"><x-status-badge :status="$log->door" /></td>
    <td data-label="Arah"><x-status-badge :status="$log->direction" /></td>
    <td data-label="Cara akses" class="text-sm">
        @if (($log->method ?? null) === 'two_step')
            Verifikasi 2 langkah
            <span class="block text-ink-500">{{ $log->companion ? 'Didampingi ' . $log->companion : 'Tanpa pendamping' }}</span>
        @else
            Kartu RFID langsung
        @endif
    </td>
    <td data-label="Hasil">
        <x-status-badge :status="$log->result" />
        @if ($log->result === 'denied' && $log->reason)
            <x-cell.text :value="$log->reason" max="30ch" lines="2" class="mt-1 text-[13px] text-bad-700" />
        @endif
    </td>
</tr>
