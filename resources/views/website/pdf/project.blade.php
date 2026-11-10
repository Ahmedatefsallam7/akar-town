{{--
    A project's datasheet for mPDF (App\Support\Website\ProjectPdf), in the property sheet's look (shared partials): cover,
    title, developer, starting price, key figures, gallery, about, overview, unit types, payment plan, master plan,
    location, amenities, nearby places and the contact box. Arabic right to left.
    @var array $p  ProjectDetails::for()
--}}
@php
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    $latin = 'font-family: archivo;';
    $tracking = $rtl ? '0' : '0.7pt';
    $uppercase = $rtl ? 'none' : 'uppercase';
    $stats = array_filter([
        __('Units') => $p['units'] ?: null,
        __('Unit Sizes') => $p['sizes'] ? $p['sizes'].' '.__('m²') : null,
        __('Delivery') => $p['delivery'] ? implode(' ', $p['delivery']) : null,
        __('Down Payment') => $p['downPayment'] !== null ? $p['downPayment'].'%' : null,
        __('Installments') => $p['installmentYears'] ? trans_choice(':count year|:count years', $p['installmentYears'], ['count' => $p['installmentYears']]) : null,
    ]);
    $badges = array_filter([$p['status'], $p['usage']]);
    $overview = array_filter([
        __('Developer') => $p['developer'],
        __('Status') => $p['status'],
        __('Usage') => $p['usage'],
        __('Delivery') => $p['delivery'] ? implode(' ', $p['delivery']) : null,
        __('Units') => $p['units'] ?: null,
        __('Unit Sizes') => $p['sizes'] ? $p['sizes'].' '.__('m²') : null,
        __('Starting Price') => $p['price'] ? $p['price'].' '.__('EGP') : null,
        __('Down Payment') => $p['downPayment'] !== null ? $p['downPayment'].'%' : null,
    ]);
    $lists = array_filter([__('Amenities') => $p['amenities'], __('Nearby Facilities') => $p['nearby']]);
    $typeColumns = [__('Type'), __('Units'), __('Area').' ('.__('m²').')', __('Bedrooms'), __('Bathrooms'), __('Starting Price')];
@endphp
<html dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    @include('website.pdf.partials.styles')
</head>
<body>
    @include('website.pdf.partials.top', ['title' => $p['title'], 'label' => __('Project Datasheet')])

    @if ($cover)
        <img src="{{ $cover }}" style="width: 210mm; height: 110mm;" />
    @endif

    <div class="wrap">
        {{-- Title, developer, place, badges, starting price --}}
        <table style="margin-top: 6mm;">
            <tr>
                <td style="vertical-align: top; text-align: {{ $start }}; padding-{{ $end }}: 6mm;">
                    @if ($p['developer'])
                        <div class="eyebrow">{{ $p['developer'] }}</div>
                    @endif
                    <h1 style="margin-bottom: 3mm;">{{ $p['title'] }}</h1>
                    @if ($p['address'])
                        <div class="muted" style="font-size: 8.8pt; line-height: 1.5;">{{ $p['address'] }}</div>
                    @endif
                    @if ($p['short'])
                        <div class="muted" style="font-size: 8.5pt; line-height: 1.55; margin-top: 2mm;">{{ $p['short'] }}</div>
                    @endif
                    @if ($badges)
                        <table style="width: auto; margin-top: 3mm; border-collapse: separate; border-spacing: 1.5mm 0;">
                            <tr>
                                @foreach ($badges as $badge)
                                    <td style="padding: 1.5mm 2.5mm; border: 0.25mm solid #e6e2dc; background: #f4f1ec; color: #0d0d0d; font-size: 8pt; font-weight: bold; white-space: nowrap;">{{ $badge }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif
                </td>
                <td style="width: 62mm; vertical-align: top;">
                    <div class="price-card">
                        <div style="height: 1mm; margin: -4mm -5mm 3mm; background: #c8102e;"></div>
                        <div class="muted" style="font-size: 8pt; text-transform: {{ $uppercase }}; letter-spacing: {{ $tracking }};">{{ __('Starting Price') }}</div>
                        @if ($p['price'])
                            <div class="price" style="{{ $latin }}; margin-top: 1mm;" dir="ltr">{{ $p['price'] }}</div>
                            <div style="font-size: 9pt; font-weight: bold; color: #555555; margin-top: 0.5mm;">{{ $rtl ? __('Egyptian Pound') : __('EGP') }}</div>
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

        {{-- About --}}
        @if (trim(strip_tags($p['description'])) !== '')
            <div class="section">
                <h2>{{ __('About This Project') }}</h2>
                <div style="color: #3a3a3a; text-align: justify;">{!! $p['description'] !!}</div>
            </div>
        @endif

        {{-- Overview --}}
        @if ($overview)
            <div class="section">
                <h2>{{ __('Project Overview') }}</h2>
                <table class="details">
                    @foreach (array_chunk($overview, 2, true) as $row)
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

        {{-- Unit types (the project's published units, grouped) --}}
        @if ($p['types'])
            <div class="section">
                <h2>{{ __('Unit Types') }}</h2>
                <table style="font-size: 8.8pt;">
                    <tr>
                        @foreach ($typeColumns as $column)
                            <td style="padding: 2mm 2.5mm; background: #111111; color: #ffffff; font-weight: bold; font-size: 8pt; text-align: {{ $loop->first ? $start : 'center' }};">{{ $column }}</td>
                        @endforeach
                    </tr>
                    @foreach ($p['types'] as $type)
                        <tr style="page-break-inside: avoid;">
                            <td style="padding: 2mm 2.5mm; border-bottom: 0.25mm solid #e6e2dc; font-weight: bold; text-align: {{ $start }};">{{ $type['name'] }}</td>
                            @foreach ([$type['units'], $type['area'], $type['beds'], $type['baths']] as $value)
                                <td style="padding: 2mm 2.5mm; border-bottom: 0.25mm solid #e6e2dc; text-align: center; {{ $latin }}" dir="ltr">{{ $value ?? '—' }}</td>
                            @endforeach
                            <td style="padding: 2mm 2.5mm; border-bottom: 0.25mm solid #e6e2dc; text-align: center; font-weight: bold; color: #c8102e; {{ $latin }}" dir="ltr">{{ $type['price'] ? $type['price'].' '.__('EGP') : '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        {{-- Payment plan --}}
        @if ($p['downPayment'] !== null || $p['installmentYears'] || $p['delivery'])
            <div class="section" style="page-break-inside: avoid;">
                <h2>{{ __('Payment Plan') }}</h2>
                <table class="step">
                    <tr>
                        @if ($p['downPayment'] !== null)
                            <td><div class="value" style="{{ $latin }}">{{ $p['downPayment'] }}%</div><div style="font-weight: bold; font-size: 9pt;">{{ __('Down Payment') }}</div><div class="muted" style="font-size: 7.5pt;">{{ __('Upon contract signing') }}</div></td>
                        @endif
                        @if ($p['installmentYears'])
                            <td><div class="value" style="{{ $latin }}">{{ $p['installmentYears'] }}</div><div style="font-weight: bold; font-size: 9pt;">{{ __('Installments') }}</div><div class="muted" style="font-size: 7.5pt;">{{ trans_choice('Over :count year|Over :count years', $p['installmentYears'], ['count' => $p['installmentYears']]) }}</div></td>
                        @endif
                        @if ($p['delivery'])
                            <td><div class="value" style="{{ $latin }}">{{ $p['delivery'][0] }}</div><div style="font-weight: bold; font-size: 9pt;">{{ __('Handover') }}</div><div class="muted" style="font-size: 7.5pt;">{{ implode(' ', $p['delivery']) }}</div></td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        @include('website.pdf.partials.plans')

        @include('website.pdf.partials.location', ['place' => $p['address'], 'latitude' => $p['latitude'], 'longitude' => $p['longitude']])

        @include('website.pdf.partials.lists')

        @include('website.pdf.partials.contact', ['heading' => __('Interested in this project?'), 'agent' => $p['agent']])
    </div>
</body>
</html>
