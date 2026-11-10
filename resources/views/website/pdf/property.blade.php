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
    @include('website.pdf.partials.styles')
</head>
<body>
    @include('website.pdf.partials.top', ['title' => $p['title'], 'label' => __('Property Datasheet')])

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

        @include('website.pdf.partials.gallery')

        @include('website.pdf.partials.plans')

        @include('website.pdf.partials.location', ['place' => $p['place'], 'latitude' => $p['latitude'], 'longitude' => $p['longitude']])

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
            <div class="section" style="page-break-inside: avoid;">
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

        @include('website.pdf.partials.lists')

        @include('website.pdf.partials.contact', ['heading' => __('Interested in this property?'), 'agent' => $p['agent']])
    </div>
</body>
</html>
