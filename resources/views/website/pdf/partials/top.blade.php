{{-- Every page's footer (name, note, page number) and the first page's dark header with the logo. Needs $title, $label. --}}
<htmlpagefooter name="footer">
    <table style="font-size: 7pt; color: #8a8a8a; border-top: 0.25mm solid #dedede;">
        <tr>
            <td style="padding: 2mm 14mm 0; text-align: {{ $start }};">{{ __('Akar Town') }} · {{ $title }}</td>
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
        <td style="padding: 5mm 14mm; text-align: {{ $end }}; color: #d3d3d3; font-size: 8pt; letter-spacing: {{ $tracking }}; text-transform: {{ $uppercase }};">{{ $label }}</td>
    </tr>
</table>
