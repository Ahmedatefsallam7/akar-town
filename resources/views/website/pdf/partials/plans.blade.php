{{-- Plan drawings in their own shape, side by side. Needs $plans (label + Datasheet::plan()). --}}
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
