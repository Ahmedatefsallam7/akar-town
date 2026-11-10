{{-- The address, coordinates and a QR code to the map. Needs $place, $latitude, $longitude, $mapUrl. --}}
{{-- Address, coordinates and a scannable map link. --}}
@if ($place || ($latitude !== null && $longitude !== null))
    <div class="section" style="page-break-inside: avoid;">
        <h2>{{ __('Location') }}</h2>
        <table style="background: #f8f6f3; border: 0.3mm solid #e6e2dc;">
            <tr>
                <td style="padding: 4mm; text-align: {{ $start }}; vertical-align: middle;">
                    @if ($place)
                        <div style="font-size: 9.5pt; font-weight: bold; color: #171717;">{{ $place }}</div>
                    @endif
                    @if ($latitude !== null && $longitude !== null)
                        <div style="margin-top: 2mm; color: #686868; font-size: 8pt;">{{ __('Coordinates') }}: <span style="{{ $latin }}" dir="ltr">{{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</span></div>
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
