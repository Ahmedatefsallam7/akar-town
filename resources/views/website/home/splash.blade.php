{{--
    Splash screen (the design's #splash, home page only): columns of property sketches racing upward, the logo fades
    in at 1.2s, and two white panels slide apart to reveal the page at 2.2s (or on click / Escape).
    Behaviour: splashScreen in resources/js/website/splash.js.
--}}
@php
    $sketches = [
        // 0 Residential Tower
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#E8E4DC"/><rect x="48" y="30" width="80" height="160" rx="4" fill="#C8BFB0"/><rect x="60" y="50" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="84" y="50" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="100" y="50" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="60" y="80" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="84" y="80" width="16" height="20" rx="2" fill="#B5C4D0"/><rect x="100" y="80" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="60" y="110" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="84" y="110" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="100" y="110" width="16" height="20" rx="2" fill="#B5C4D0"/><rect x="60" y="140" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="84" y="140" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="100" y="140" width="16" height="20" rx="2" fill="#9B8F7E"/><rect x="72" y="168" width="32" height="22" rx="2" fill="#7A6E60"/><rect x="20" y="190" width="136" height="8" rx="2" fill="#B0A898"/><rect x="30" y="186" width="12" height="12" rx="6" fill="#8FA89B"/><rect x="134" y="186" width="12" height="12" rx="6" fill="#8FA89B"/></svg>',
        // 1 Luxury Villa
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#F0EDE6"/><rect x="24" y="90" width="128" height="100" rx="3" fill="#D4C9B8"/><polygon points="24,90 88,40 152,90" fill="#BEB3A0"/><rect x="60" y="130" width="56" height="60" rx="2" fill="#A89880"/><rect x="72" y="100" width="20" height="24" rx="2" fill="#8FA89B"/><rect x="104" y="100" width="20" height="24" rx="2" fill="#8FA89B"/><rect x="80" y="140" width="16" height="16" rx="8" fill="#C4A882"/><rect x="20" y="185" width="136" height="6" rx="3" fill="#C8C0B4"/><rect x="14" y="188" width="148" height="4" rx="2" fill="#B0A898"/><rect x="34" y="82" width="8" height="12" fill="#A89880"/><rect x="134" y="82" width="8" height="12" fill="#A89880"/></svg>',
        // 2 Living Room
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#F5F2EE"/><rect x="0" y="140" width="176" height="80" fill="#E8E0D4"/><rect x="16" y="150" width="60" height="36" rx="6" fill="#C8BEB0"/><rect x="84" y="150" width="76" height="36" rx="6" fill="#C8BEB0"/><rect x="40" y="138" width="96" height="20" rx="4" fill="#B8AFA4"/><rect x="60" y="120" width="56" height="24" rx="3" fill="#9B8F7E"/><rect x="20" y="60" width="136" height="70" rx="4" fill="#DDD8D0"/><rect x="30" y="68" width="116" height="54" rx="2" fill="#C4B8A8"/><rect x="40" y="75" width="30" height="40" rx="2" fill="#A89880"/><rect x="80" y="75" width="58" height="18" rx="2" fill="#B8AFA4"/><rect x="80" y="100" width="30" height="12" rx="2" fill="#9B8F7E"/><rect x="72" y="50" width="32" height="14" rx="2" fill="#D4C9B8"/></svg>',
        // 3 Master Bedroom
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#EEE9E2"/><rect x="20" y="100" width="136" height="90" rx="4" fill="#D8D0C4"/><rect x="20" y="85" width="136" height="20" rx="4" fill="#C4B8A8"/><rect x="36" y="70" width="40" height="18" rx="9" fill="#BEB3A0"/><rect x="100" y="70" width="40" height="18" rx="9" fill="#BEB3A0"/><rect x="44" y="110" width="36" height="60" rx="3" fill="#C8BEB0"/><rect x="96" y="110" width="36" height="60" rx="3" fill="#C8BEB0"/><rect x="68" y="130" width="40" height="12" fill="#B8AFA4"/><rect x="12" y="108" width="14" height="50" rx="3" fill="#E0D8CC"/><rect x="150" y="108" width="14" height="50" rx="3" fill="#E0D8CC"/><rect x="14" y="120" width="10" height="8" rx="4" fill="#C4A882"/><rect x="152" y="120" width="10" height="8" rx="4" fill="#C4A882"/><rect x="50" y="192" width="76" height="6" rx="3" fill="#C8C0B4"/></svg>',
        // 4 Property Listing Card
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" rx="8" fill="#FFFFFF"/><rect x="0" y="0" width="176" height="110" rx="8" fill="#D4C9B8"/><rect x="0" y="80" width="176" height="38" fill="#D4C9B8"/><rect x="12" y="120" width="80" height="10" rx="3" fill="#3D3530"/><rect x="12" y="136" width="120" height="7" rx="3" fill="#9B8F7E"/><rect x="12" y="148" width="90" height="7" rx="3" fill="#9B8F7E"/><rect x="12" y="166" width="50" height="10" rx="3" fill="#C4A050"/><rect x="100" y="162" width="64" height="18" rx="9" fill="#3D3530"/><rect x="12" y="184" width="35" height="6" rx="3" fill="#B8AFA4"/><rect x="54" y="184" width="35" height="6" rx="3" fill="#B8AFA4"/><rect x="96" y="184" width="35" height="6" rx="3" fill="#B8AFA4"/><circle cx="148" cy="20" r="12" fill="rgba(255,255,255,0.3)"/></svg>',
        // 5 Golden Hour Skyline
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#E8DCC8"/><rect x="0" y="130" width="176" height="90" fill="#C4B898"/><rect x="20" y="60" width="28" height="100" fill="#B0A888"/><rect x="54" y="40" width="20" height="120" fill="#A89870"/><rect x="80" y="70" width="36" height="90" fill="#B8A880"/><rect x="122" y="50" width="24" height="110" fill="#A89870"/><rect x="152" y="80" width="20" height="80" fill="#B0A888"/><rect x="0" y="155" width="176" height="65" fill="#C8B890"/><circle cx="88" cy="45" r="30" fill="#D4A840" opacity="0.6"/><rect x="24" y="68" width="4" height="4" fill="#C4A050"/><rect x="58" y="48" width="4" height="4" fill="#C4A050"/><rect x="90" y="78" width="4" height="4" fill="#C4A050"/><rect x="126" y="58" width="4" height="4" fill="#C4A050"/></svg>',
        // 6 Modern Kitchen
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#F2F0ED"/><rect x="0" y="110" width="176" height="110" fill="#E0DBD4"/><rect x="8" y="120" width="70" height="90" rx="2" fill="#C8C0B4"/><rect x="86" y="120" width="82" height="90" rx="2" fill="#C8C0B4"/><rect x="8" y="110" width="160" height="14" rx="2" fill="#B0A898"/><rect x="18" y="130" width="48" height="34" rx="2" fill="#D8D0C4"/><rect x="96" y="130" width="60" height="34" rx="2" fill="#D8D0C4"/><rect x="60" y="40" width="56" height="65" rx="3" fill="#E8E4DC"/><rect x="68" y="48" width="40" height="49" fill="#C8C0B4"/><rect x="20" y="55" width="32" height="50" rx="2" fill="#D4C9B8"/><rect x="124" y="55" width="32" height="50" rx="2" fill="#D4C9B8"/><circle cx="88" cy="115" r="6" fill="#9B8F7E"/><circle cx="104" cy="115" r="6" fill="#9B8F7E"/><circle cx="72" cy="115" r="6" fill="#9B8F7E"/></svg>',
        // 7 Floor Plan
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#F7F5F2"/><rect x="20" y="20" width="136" height="180" rx="2" fill="none" stroke="#9B8F7E" stroke-width="2"/><rect x="20" y="20" width="80" height="90" fill="none" stroke="#9B8F7E" stroke-width="1.5"/><rect x="100" y="20" width="56" height="90" fill="none" stroke="#9B8F7E" stroke-width="1.5"/><rect x="20" y="110" width="56" height="90" fill="none" stroke="#9B8F7E" stroke-width="1.5"/><rect x="76" y="110" width="80" height="90" fill="none" stroke="#9B8F7E" stroke-width="1.5"/><rect x="35" y="35" width="50" height="60" rx="1" fill="#E8E4DC"/><rect x="110" y="35" width="30" height="60" rx="1" fill="#E8E4DC"/><rect x="30" y="125" width="36" height="60" rx="1" fill="#E8E4DC"/><rect x="86" y="125" width="60" height="60" rx="1" fill="#E8E4DC"/><line x1="45" y1="110" x2="55" y2="110" stroke="#F7F5F2" stroke-width="2"/><line x1="90" y1="20" x2="100" y2="20" stroke="#F7F5F2" stroke-width="2"/></svg>',
        // 8 Rooftop Terrace
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#EAE6DE"/><rect x="0" y="140" width="176" height="80" fill="#D4C9B8"/><rect x="0" y="136" width="176" height="8" rx="2" fill="#C0B5A4"/><rect x="16" y="100" width="28" height="42" rx="2" fill="#C8BEB0"/><rect x="50" y="80" width="76" height="62" rx="3" fill="#D8D0C4"/><rect x="132" y="100" width="28" height="42" rx="2" fill="#C8BEB0"/><rect x="60" y="90" width="56" height="42" rx="2" fill="#E8E4DC"/><rect x="68" y="98" width="16" height="26" rx="2" fill="#9B8F7E"/><rect x="92" y="98" width="16" height="26" rx="2" fill="#9B8F7E"/><rect x="20" y="150" width="16" height="24" rx="3" fill="#8FA89B"/><rect x="140" y="150" width="16" height="24" rx="3" fill="#8FA89B"/><rect x="70" y="60" width="36" height="24" rx="2" fill="#C8BEB0"/><circle cx="88" cy="44" r="10" fill="#D4A840" opacity="0.5"/></svg>',
        // 9 Amenities Grid
        '<svg viewBox="0 0 176 220" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="176" height="220" fill="#F0EDE6"/><rect x="12" y="12" width="70" height="90" rx="6" fill="#D8D0C4"/><rect x="94" y="12" width="70" height="90" rx="6" fill="#D8D0C4"/><rect x="12" y="118" width="70" height="90" rx="6" fill="#D8D0C4"/><rect x="94" y="118" width="70" height="90" rx="6" fill="#D8D0C4"/><circle cx="47" cy="52" r="18" fill="#C4B898"/><rect x="24" y="136" width="46" height="36" rx="3" fill="#C4B898"/><rect x="30" y="142" width="14" height="24" rx="2" fill="#9B8F7E"/><rect x="50" y="148" width="14" height="18" rx="2" fill="#9B8F7E"/><circle cx="129" cy="52" r="14" fill="#C4B898"/><rect x="120" y="66" width="18" height="24" rx="2" fill="#9B8F7E"/><rect x="100" y="136" width="58" height="8" rx="4" fill="#C4B898"/><rect x="100" y="150" width="46" height="8" rx="4" fill="#B8AFA4"/><rect x="100" y="164" width="52" height="8" rx="4" fill="#B8AFA4"/><rect x="100" y="178" width="38" height="8" rx="4" fill="#B8AFA4"/></svg>',
    ];
    $columns = [[0,1,2,3,4,5,6,7],[5,6,7,8,9,0,1,2],[3,4,5,6,7,8,9,0],[7,8,9,0,1,2,3,4],[2,3,4,5,6,7,8,9]];
    $hideAt = ['max-xl:hidden', 'max-[700px]:hidden', '', 'max-[700px]:hidden', 'max-xl:hidden'];
@endphp

<div
    x-data="splashScreen"
    x-show="! gone"
    @click="exit()"
    @keydown.escape.window="exit()"
    class="fixed inset-0 z-9999 flex cursor-pointer items-center justify-center overflow-hidden"
    :class="state === 'exit' ? 'pointer-events-none bg-transparent' : 'bg-[#f7f7f7]'"
    role="presentation"
    aria-hidden="true"
>
    <div class="absolute inset-0 flex items-start justify-center gap-[14px] px-4 opacity-30">
        @foreach ($columns as $c => $ids)
            <div data-column class="{{ $hideAt[$c] }} flex flex-[0_0_176px] flex-col gap-[14px] will-change-transform max-[700px]:basis-[148px]" :class="state === 'exit' && 'opacity-0 transition-opacity duration-300'">
                @foreach ([...$ids, ...$ids] as $id)
                    <div class="shrink-0 overflow-hidden leading-[0] [&_svg]:block [&_svg]:h-auto [&_svg]:w-[176px] max-[700px]:[&_svg]:w-[148px]">{!! $sketches[$id] !!}</div>
                @endforeach
            </div>
        @endforeach
    </div>

    @foreach (['left-0' => '-translate-x-full', 'right-0' => 'translate-x-full'] as $side => $slide)
        <div
            class="{{ $side }} pointer-events-none absolute inset-y-0 z-1 w-1/2 bg-white"
            :class="{
                'opacity-0 transition-opacity duration-400': state === 'start',
                'opacity-100 transition-opacity duration-400': state === 'ready',
                'opacity-100 {{ $slide }} [transition:translate_0.45s_var(--ease-smooth)]': state === 'exit',
            }"
        ></div>
    @endforeach

    <div class="pointer-events-none relative z-2 flex items-center justify-center">
        <div
            class="flex flex-col items-center gap-5"
            :class="{
                'opacity-0 [transform:translateY(16px)_scale(0.97)] [transition:opacity_0.3s_cubic-bezier(0.16,1,0.3,1)_0.05s,transform_0.35s_cubic-bezier(0.16,1,0.3,1)_0.05s]': state === 'start',
                'opacity-100 [transform:translateY(0)_scale(1)] [transition:opacity_0.3s_cubic-bezier(0.16,1,0.3,1)_0.05s,transform_0.35s_cubic-bezier(0.16,1,0.3,1)_0.05s]': state === 'ready',
                'opacity-0 [transform:scale(1.03)_translateY(-6px)] [transition:opacity_0.4s_ease,transform_0.5s_cubic-bezier(0.4,0,1,1)]': state === 'exit',
            }"
        >
            <svg class="block w-[clamp(280px,50vw,560px)]" viewBox="0 0 2185 380" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M90.97 258.84H160.93L236.12 169.2V379.42H296.66V5.21997L90.97 258.84Z" fill="#231F20"/>
                <path d="M64.78 291.12L0 371H66.84L133.85 291.12H64.78Z" fill="#FF0000"/>
                <path d="M390.87 186.6L544.58 373.2H472.34L339.38 212.73L317.86 187.07L339.38 160.95L472.34 0.00999451H544.58L390.87 186.61V186.6Z" fill="#231F20"/>
                <path d="M772.951 153.462L752.741 160.698C739.129 146.227 720.111 138.991 695.632 138.991C677.085 138.991 661.976 143.759 650.277 153.268C638.606 162.805 632.756 174.92 632.756 189.613C632.756 204.306 638.55 217.086 650.111 226.429C661.671 235.772 677.279 240.429 696.935 240.429C720.665 240.429 739.351 233.082 752.935 218.362L772.036 228.924C762.388 240.179 751.465 248.302 739.295 253.32C727.125 258.338 712.57 260.833 695.632 260.833C671.901 260.833 652.162 254.9 636.471 243.035C618.534 229.423 609.58 211.625 609.58 189.613C609.58 167.601 618.673 150.302 636.831 136.579C652.661 124.575 672.261 118.587 695.632 118.587C729.259 118.587 755.042 130.203 772.978 153.462H772.951Z" fill="#231F20"/>
                <path d="M1015.47 189.71C1015.47 211.722 1006.43 229.52 988.385 243.132C972.555 254.997 952.899 260.93 929.418 260.93C905.937 260.93 885.893 254.942 870.064 242.938C852.016 229.326 842.978 211.611 842.978 189.71C842.978 167.809 852.127 150.51 870.424 136.676C886.503 124.561 906.159 118.49 929.391 118.49C952.622 118.49 972.361 124.561 988.191 136.676C1006.38 150.538 1015.44 168.197 1015.44 189.71H1015.47ZM992.294 189.71C992.294 174.019 985.612 161.266 972.278 151.508C960.773 143.108 946.495 138.894 929.446 138.894C910.761 138.894 895.569 143.69 883.814 153.255C872.06 162.847 866.21 174.989 866.21 189.71C866.21 204.431 871.949 217.183 883.454 226.526C894.959 235.869 910.289 240.526 929.446 240.526C948.602 240.526 963.711 235.841 975.161 226.443C986.583 217.045 992.321 204.791 992.321 189.71H992.294Z" fill="#231F20"/>
                <path d="M1269.99 256.841H1247.73V147.058H1247.18L1177.45 234.607L1108.29 147.058H1107.73V256.841H1085.47V122.579H1113.28L1177.62 203.724L1242.24 122.579H1269.97V256.841H1269.99Z" fill="#231F20"/>
                <path d="M1446.45 122.607C1460.17 122.607 1471.04 126.405 1479.11 134.001C1487.15 141.597 1491.17 151.771 1491.17 164.496C1491.17 177.858 1486.98 188.06 1478.64 195.102C1470.29 202.144 1458.15 205.664 1442.18 205.664H1362.25V256.841H1339.99V122.579H1446.45V122.607ZM1442.57 185.676C1459.76 185.676 1468.35 178.746 1468.35 164.912C1468.35 150.08 1459.26 142.65 1441.1 142.65H1362.28V185.676H1442.6H1442.57Z" fill="#231F20"/>
                <path d="M1731.22 256.841H1706.88L1691.41 226.789H1600.98L1585.51 256.841H1561.17L1632.19 122.579H1660.19L1731.22 256.841ZM1680.6 206.773L1646.39 141.708L1611.76 206.773H1680.6Z" fill="#231F20"/>
                <path d="M1953.67 256.841H1925.97L1823.48 146.726V256.841H1801.22V122.579H1828.86L1931.43 232.75V122.579H1953.69V256.841H1953.67Z" fill="#231F20"/>
                <path d="M2192.83 122.579L2119.39 203.807V256.841H2097.13V203.807L2023.69 122.579H2051.44L2108.39 185.288L2165.08 122.579H2192.83Z" fill="#231F20"/>
            </svg>
        </div>
    </div>
</div>
