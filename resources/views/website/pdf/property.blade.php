{{--
    A property's datasheet for mPDF (App\Support\Website\PropertyPdf): the site's colours, A4, in the chosen language
    (Arabic right to left). mPDF reads plain HTML and a part of CSS: layouts are tables (no flex / grid), sizes in mm/pt.
    @var array $p  PropertyDetails::for()
--}}
@php
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    $latin = 'font-family: archivo;';
    $tracking = $rtl ? '0' : '0.7pt';
    $uppercase = $rtl ? 'none' : 'uppercase';
    $stats = array_filter([
        __('Bedrooms') => $p['beds'],
        __('Bathrooms') => $p['baths'],
        __('Area') => $p['area'] ? $p['area'].' '.__('m²') : null,
        __('Parking') => $p['parking'],
        __('Floors') => $p['floors'],
    ], fn ($value) => $value !== null && $value !== '');
    $badges = array_filter([$p['offering'], $p['details'][__('Type')] ?? null, $p['details'][__('Status')] ?? null]);
    $description = trim(($p['intro'] ?? '').($p['more'] ?? ''));
    $lists = array_filter([__('Amenities') => $p['amenities'], __('Nearby Facilities') => $p['facilities']]);
    $details = $p['details'];
    if ($p['updated']) {
        $details[__('Updated')] = $p['updated'];
    }
    if ($p['latitude'] !== null && $p['longitude'] !== null) {
        $details[__('Coordinates')] = number_format($p['latitude'], 6).', '.number_format($p['longitude'], 6);
    }
@endphp
<html dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { color: #1a1a1a; font-size: 10pt; line-height: 1.7; }
        .wrap { padding: 0 14mm; }
        .red { color: #c8102e; }
        .muted { color: #6e6e6e; }
        .eyebrow { font-size: 7.5pt; font-weight: bold; letter-spacing: {{ $tracking }}; text-transform: {{ $uppercase }}; color: #c8102e; }
        h1 { font-size: 23pt; font-weight: bold; margin: 0 0 2mm; line-height: 1.45; color: #111111; }
        h2 { font-size: 11pt; font-weight: bold; margin: 0 0 3mm; padding: 2mm 3mm; border-{{ $start }}: 1mm solid #c8102e; background: #f8f6f3; color: #171717; }
        .section { margin-top: 5mm; }
        .badge { background: #f4f1ec; color: #0d0d0d; font-size: 8pt; font-weight: bold; padding: 1.5mm 2.5mm; border-radius: 1.5mm; }
        table { border-collapse: collapse; width: 100%; }
        .stats td { background: #111111; color: #ffffff; text-align: center; padding: 4mm 1.5mm; border-{{ $end }}: 0.3mm solid #333333; }
        .stats .value { font-size: 14pt; font-weight: bold; }
        .stats .label { font-size: 7.5pt; color: #d3d3d3; text-transform: {{ $uppercase }}; letter-spacing: {{ $tracking }}; }
        .details td { padding: 2mm 3.5mm; border-bottom: 0.25mm solid #e6e2dc; font-size: 9.2pt; vertical-align: top; }
        .details .label { color: #686868; width: 18%; }
        .details .value { font-weight: bold; width: 32%; color: #171717; }
        .price-card { background: #f8f6f3; border: 0.3mm solid #e6e2dc; padding: 4mm 5mm; }
        .price-line { width: auto; margin-top: 1mm; direction: ltr; }
        .price-line td { padding: 0; vertical-align: baseline; }
        .price { font-size: 18pt; font-weight: bold; color: #0d0d0d; white-space: nowrap; }
        .chip { font-size: 9pt; padding: 2mm 1mm; }
        .step td { text-align: center; padding: 4mm 2mm; background: #f8f6f3; border: 0.4mm solid #ffffff; }
        .step .value { font-size: 15pt; font-weight: bold; color: #c8102e; }
        .contact td { vertical-align: top; }
        .box { background: #111111; color: #ffffff; padding: 4mm 5mm; border-radius: 2.5mm; }
        .box .muted { color: #b9b9b9; }
        .phone { color: #ffffff; font-size: 9.5pt; font-weight: bold; line-height: 1.3; padding: 0.3mm 0; }
        .contact-label { color: #c8102e; font-size: 7.5pt; font-weight: bold; letter-spacing: {{ $tracking }}; text-transform: {{ $uppercase }}; }
    </style>
</head>
<body>
    <htmlpagefooter name="footer">
        <table style="font-size: 7pt; color: #8a8a8a; border-top: 0.25mm solid #dedede;">
            <tr>
                <td style="padding: 2mm 14mm 0; text-align: {{ $start }};">{{ __('Akar Town') }} · {{ $p['title'] }}</td>
                    <td style="padding: 2mm 0 0; text-align: center; font-size: 6.5pt;">{{ __('All figures are indicative and subject to change.') }}</td>
                <td style="padding: 2mm 14mm 0; text-align: {{ $end }}; {{ $latin }}">{PAGENO} / {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="footer" value="on" show-this-page="1" />

    {{-- Header --}}
    <table style="background: #0d0d0d; border-bottom: 1.2mm solid #c8102e;">
        <tr>
            <td style="padding: 5mm 14mm; text-align: {{ $start }};"><img src="{{ $logo }}" style="height: 7mm;" /></td>
            <td style="padding: 5mm 14mm; text-align: {{ $end }}; color: #d3d3d3; font-size: 8pt; letter-spacing: {{ $tracking }}; text-transform: {{ $uppercase }};">{{ __('Property Datasheet') }}</td>
        </tr>
    </table>

    @if ($cover)
        <img src="{{ $cover }}" style="width: 210mm; height: 110mm;" />
    @endif

    <div class="wrap">
        {{-- Title, place, badges, price --}}
        <table style="margin-top: 6mm;">
            <tr>
                <td style="vertical-align: top; text-align: {{ $start }}; padding-{{ $end }}: 6mm;">
                    @if ($p['project'] || $p['developer'])
                        <div class="eyebrow">{{ collect([$p['project'], $p['developer']])->filter()->implode(' · ') }}</div>
                    @endif
                    <h1 style="margin-bottom: 3mm;">{{ $p['title'] }}</h1>
                    @if ($p['place'])
                        <div class="muted" style="font-size: 8.8pt; line-height: 1.5;">{{ $p['place'] }}</div>
                    @endif
                    @if ($p['short'])
                        <div class="muted" style="font-size: 8.5pt; line-height: 1.55; margin-top: 2mm;">{{ $p['short'] }}</div>
                    @endif
                    @if ($badges)
                        <table style="width: auto; margin-top: 3mm; border-collapse: separate; border-spacing: 1.5mm 0;">
                            <tr>
                                @foreach ($badges as $badge)
                                    <td style="padding: 1.5mm 2.5mm; border: 0.25mm solid #e6e2dc; background: #f4f1ec; color: #0d0d0d; font-size: 8pt; font-weight: bold; white-space: nowrap;" dir="{{ $rtl ? 'rtl' : 'ltr' }}">{{ $badge }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif
                </td>
                <td style="width: 62mm; vertical-align: top;">
                    <div class="price-card">
                        <div style="height: 1mm; margin: -4mm -5mm 3mm; background: #c8102e;"></div>
                        <div class="muted" style="font-size: 8pt; text-transform: {{ $uppercase }}; letter-spacing: {{ $tracking }};">{{ __('Asking Price') }}</div>
                        @if ($p['price'])
                            @if ($rtl)
                                <div class="price" style="{{ $latin }}; margin-top: 1mm;" dir="ltr">{{ $p['price'] }}</div>
                                <div style="font-size: 9pt; font-weight: bold; color: #555555; margin-top: 0.5mm;" dir="rtl">{{ __('Egyptian Pound') }}</div>
                            @else
                                <table class="price-line">
                                    <tr>
                                        <td><span class="price" style="{{ $latin }}">{{ $p['price'] }}</span></td>
                                        <td style="padding-left: 2mm; font-size: 9pt; font-weight: bold; white-space: nowrap;">{{ __('EGP') }}</td>
                                    </tr>
                                </table>
                            @endif
                            @if ($p['perMeter'])
                                @if ($rtl)
                                    <table style="width: auto; margin-top: 1.5mm;">
                                        <tr>
                                            <td style="font-size: 8pt; line-height: 1.4; color: #6e6e6e;" dir="ltr">{{ $p['perMeter'] }} {{ __('EGP') }} / {{ __('m²') }}</td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="muted" style="font-size: 8pt; line-height: 1.4; margin-top: 1mm;">{{ __('EGP :price / m²', ['price' => $p['perMeter']]) }}</div>
                                @endif
                            @endif
                        @else
                            <div class="price" style="font-size: 12pt;">{{ __('Price on request') }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Key figures --}}
        @if ($stats)
            <table class="stats" style="margin-top: 4mm;">
                <tr>
                    @foreach ($stats as $label => $value)
                        <td style="width: {{ 100 / count($stats) }}%;">
                            <div class="value">{{ $value }}</div>
                            <div class="label">{{ $label }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        {{-- More photos --}}
        @if ($thumbs)
            <div style="margin-top: 4mm; color: #6e6e6e; font-size: 7.5pt; font-weight: bold; text-transform: {{ $uppercase }}; letter-spacing: {{ $tracking }};">{{ __('Photo Gallery') }}</div>
            <table style="margin-top: 1.5mm; table-layout: fixed;">
                @foreach (array_chunk($thumbs, 3) as $row)
                    <tr style="page-break-inside: avoid;">
                        @foreach ($row as $thumb)
                            <td style="width: 33.33%; padding: 0 1.2mm 2mm; text-align: center;">
                                <div style="padding: 1mm; background: #f4f1ec; border: 0.3mm solid #e6e2dc;">
                                    <img src="{{ $thumb }}" width="56mm" height="35mm" style="display: block; width: 56mm; height: 35mm;" />
                                </div>
                            </td>
                        @endforeach
                        @for ($i = count($row); $i < 3; $i++)
                            <td style="width: 33.33%;"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        @endif

        {{-- Floor and master plans: prepared proportionally, never cropped. --}}
        @if ($plans)
            <div class="section" style="page-break-inside: avoid;">
                <h2>{{ __('Plans') }}</h2>
                <table style="table-layout: fixed;">
                    <tr>
                        @foreach ($plans as $plan)
                            <td style="width: {{ 100 / count($plans) }}%; padding: 2mm; text-align: center; vertical-align: top;">
                                <div style="margin-bottom: 2mm; color: #555555; font-size: 8pt; font-weight: bold;">{{ $plan['label'] }}</div>
                                <div style="padding: 2mm; border: 0.3mm solid #e6e2dc; background: #ffffff;">
                                    <img src="{{ $plan['image']['path'] }}" width="{{ $plan['image']['width'] }}mm" height="{{ $plan['image']['height'] }}mm" style="display: block; margin: auto;" />
                                </div>
                            </td>
                        @endforeach
                        @if (count($plans) === 1)
                            <td style="width: 50%;"></td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        {{-- Address, coordinates and a scannable map link. --}}
        @if ($p['place'] || ($p['latitude'] !== null && $p['longitude'] !== null))
            <div class="section" style="page-break-inside: avoid;">
                <h2>{{ __('Location') }}</h2>
                <table style="background: #f8f6f3; border: 0.3mm solid #e6e2dc;">
                    <tr>
                        <td style="padding: 4mm; text-align: {{ $start }}; vertical-align: middle;">
                            @if ($p['place'])
                                <div style="font-size: 9.5pt; font-weight: bold; color: #171717;">{{ $p['place'] }}</div>
                            @endif
                            @if ($p['latitude'] !== null && $p['longitude'] !== null)
                                <div style="margin-top: 2mm; color: #686868; font-size: 8pt;">{{ __('Coordinates') }}: <span style="{{ $latin }}" dir="ltr">{{ number_format($p['latitude'], 6) }}, {{ number_format($p['longitude'], 6) }}</span></div>
                            @endif
                            <a href="{{ $mapUrl }}" target="_blank" style="display: inline-block; margin-top: 2mm; color: #c8102e; font-size: 8pt; font-weight: bold; text-decoration: none;">{{ __('Open in Google Maps') }}</a>
                        </td>
                        <td style="width: 32mm; padding: 3mm; text-align: center; vertical-align: middle;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="text-align: center;">
                                        <barcode code="{{ $mapUrl }}" type="QR" size="1" error="M" disableborder="1" />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 1.5mm; color: #686868; font-size: 6.5pt; line-height: 1.4; text-align: center;">
                                        {{ __('Scan to open map') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- About --}}
        @if ($description)
            <div class="section">
                <h2>{{ __('About This Property') }}</h2>
                <div style="color: #3a3a3a; text-align: justify;">{!! $description !!}</div>
            </div>
        @endif

        {{-- Details --}}
        @if ($details)
            <div class="section">
                <h2>{{ __('Property Details') }}</h2>
                <table class="details">
                    @foreach (array_chunk($details, 2, true) as $row)
                        <tr>
                            @foreach ($row as $label => $value)
                                <td class="label">{{ $label }}</td>
                                <td class="value">{{ $value }}</td>
                            @endforeach
                            @if (count($row) === 1)
                                <td class="label"></td><td class="value"></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        {{-- Payment plan --}}
        @if ($p['payment'])
            <div class="section">
                <h2>{{ __('Payment Plan') }}</h2>
                <table class="step">
                    <tr>
                        @foreach ($p['payment'] as $step)
                            <td style="width: {{ 100 / count($p['payment']) }}%;">
                                <div class="value" style="{{ $latin }}">{{ $step['value'] }}</div>
                                <div style="font-weight: bold; font-size: 9pt;">{{ $step['name'] }}</div>
                                <div class="muted" style="font-size: 7.5pt;">{{ $step['note'] }}</div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>
        @endif

        {{-- Amenities / nearby --}}
        @foreach ($lists as $heading => $items)
            <div class="section">
                <h2>{{ $heading }}</h2>
                <table>
                    @foreach (array_chunk($items, 3) as $row)
                        <tr>
                            @foreach ($row as $item)
                                <td class="chip" style="width: 33.3%;"><span style="font-family: dejavusans; color: #c8102e; font-weight: bold;">✓</span>&nbsp; {{ $item }}</td>
                            @endforeach
                            @for ($i = count($row); $i < 3; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach

        {{-- Contact: agent, every number, QR --}}
        <div class="section" style="margin-top: 3mm; page-break-inside: avoid;">
            <div class="box">
                <table class="contact">
                    <tr>
                        <td style="text-align: {{ $start }};">
                            <div class="contact-label">{{ __('Get in touch') }}</div>
                            <div style="color: #ffffff; font-size: 11pt; font-weight: bold; margin: 0.5mm 0 2mm;">{{ __('Interested in this property?') }}</div>

                            @if ($p['agent'])
                                <table style="margin: 1mm 0 2mm; background: #242424;">
                                    <tr>
                                        @if ($agentPhoto)
                                            <td style="width: 16mm; padding: 2mm; vertical-align: middle;"><img src="{{ $agentPhoto }}" style="width: 12mm; height: 12mm; border-radius: 6mm; border: 0.6mm solid #c8102e;" /></td>
                                        @endif
                                        <td style="padding: 2mm; border-{{ $start }}: 1mm solid #c8102e; vertical-align: middle; text-align: {{ $start }};">
                                            <div style="font-size: 10.5pt; font-weight: bold; color: #ffffff;">{{ $p['agent']['name'] }}</div>
                                            <div style="font-size: 8pt; color: #dddddd;">{{ $p['agent']['role'] ?: __('Property Advisor') }}</div>
                                            @if ($p['agent']['rating'] !== null)
                                                <div style="font-size: 7.5pt; color: #f0c36d;">
                                                    <span style="font-family: dejavusans; color: #f0c36d;">★</span> {{ number_format($p['agent']['rating'], 1) }} / 5
                                                    @if ($p['agent']['reviews'] !== null)
                                                        <span style="color: #d3d3d3;">· {{ number_format($p['agent']['reviews']) }} {{ trans_choice(':count review|:count reviews', $p['agent']['reviews'], ['count' => number_format($p['agent']['reviews'])]) }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if ($p['agent']['phone'])
                                                <div style="font-size: 9.5pt; font-weight: bold; color: #ffffff; {{ $latin }}" dir="ltr">{{ $p['agent']['phone'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @foreach ($phones as $number)
                                <div class="phone"><span style="font-family: dejavusans; color: #ff5268;">☎</span>&nbsp; <span style="color: #ffffff; {{ $latin }}" dir="ltr">{{ $number }}</span></div>
                            @endforeach
                            @if ($whatsapp)
                                <div class="phone"><span style="color: #d3d3d3; font-size: 8.5pt; font-weight: normal;">{{ __('WhatsApp') }}:</span> <span style="color: #ffffff; {{ $latin }}" dir="ltr">{{ $whatsapp }}</span></div>
                            @endif
                            @if ($email)
                                <div style="margin-top: 1.5mm; color: #ffffff; font-size: 9.5pt; {{ $latin }}">{{ $email }}</div>
                            @endif
                            @if ($office)
                                <div style="color: #d3d3d3; font-size: 8.5pt; margin-top: 1.5mm;">{{ $office }}</div>
                            @endif
                        </td>
                        <td style="width: 34mm; text-align: center; vertical-align: middle;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="background: #ffffff; padding: 2mm; border-radius: 2mm; text-align: center;">
                                        <barcode code="{{ $url }}" type="QR" size="1.1" error="M" disableborder="1" />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 3mm; color: #e0e0e0; font-size: 7pt; line-height: 1.4; text-align: center;">
                                        {{ __('Scan to view online') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
