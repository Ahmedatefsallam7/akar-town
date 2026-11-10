{{-- Ticked lists (amenities, nearby places), three a row, each kept with its heading. Needs $lists (heading => items). --}}
{{-- Amenities / nearby --}}
@foreach ($lists as $heading => $items)
    <div class="section" style="page-break-inside: avoid;">
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
