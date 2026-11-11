{{--
    Ilustrasi orisinal: pintu gudang terkunci + reader RFID di dinding + kartu yang ditempelkan.
    Dekoratif (aria-hidden). Satu-satunya gerakan: LED reader berkedip pelan, mati bila "reduce motion" aktif.
--}}
<svg viewBox="0 0 400 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'h-auto w-full']) }}>
    <defs>
        <pattern id="hazard" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">
            <rect width="7" height="14" fill="#f2b705" />
            <rect x="7" width="7" height="14" fill="#0e1a26" />
        </pattern>
    </defs>

    {{-- Dinding & lantai --}}
    <rect x="24" y="286" width="352" height="2" rx="1" fill="#3b5167" />

    {{-- Kusen & daun pintu --}}
    <rect x="58" y="34" width="168" height="252" rx="4" fill="#26394d" stroke="#3b5167" stroke-width="2" />
    <rect x="72" y="48" width="140" height="238" rx="2" fill="#1f3145" stroke="#3b5167" stroke-width="1.5" />
    <rect x="96" y="74" width="92" height="54" rx="3" fill="#16253a" stroke="#3b5167" stroke-width="1.5" />
    <path d="M96 101h92M142 74v54" stroke="#26394d" stroke-width="1.5" />
    {{-- Panel bawah pintu --}}
    <rect x="96" y="150" width="92" height="112" rx="3" stroke="#2c4157" stroke-width="1.5" />
    {{-- Gagang + gembok elektronik --}}
    <rect x="186" y="168" width="18" height="34" rx="4" fill="#3b5167" />
    <rect x="190" y="176" width="10" height="5" rx="2.5" fill="#aebbc8" />
    <circle cx="195" cy="192" r="2.5" fill="#12824a" />
    {{-- Pita hazard di ambang pintu --}}
    <rect x="58" y="276" width="168" height="10" fill="url(#hazard)" />

    {{-- Reader RFID di dinding --}}
    <rect x="258" y="132" width="52" height="84" rx="10" fill="#26394d" stroke="#3b5167" stroke-width="2" />
    <circle cx="284" cy="150" r="5" fill="#f2b705" class="reader-led" />
    <path d="M274 172a14 14 0 0 1 20 0M278 179a8 8 0 0 1 12 0" stroke="#8396a8" stroke-width="2.5" stroke-linecap="round" />
    <circle cx="284" cy="186" r="2.5" fill="#8396a8" />
    <rect x="270" y="198" width="28" height="6" rx="3" fill="#1f3145" />

    {{-- Kartu yang ditempelkan + gelombang baca --}}
    <path d="M318 150a26 26 0 0 1 0 36M328 142a38 38 0 0 1 0 52" stroke="#f2b705" stroke-width="2.5" stroke-linecap="round" opacity=".55" />
    <g transform="rotate(-14 352 170)">
        <rect x="330" y="146" width="62" height="40" rx="6" fill="#f2b705" />
        <rect x="339" y="156" width="14" height="11" rx="2" fill="#0e1a26" opacity=".85" />
        <rect x="339" y="173" width="36" height="4" rx="2" fill="#0e1a26" opacity=".35" />
    </g>
</svg>
