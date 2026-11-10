{{-- The gallery photos, three a row. Needs $thumbs (prepared files). --}}
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
