{{-- The closing contact box: the agent, every Business Info number, WhatsApp, e-mail, office and a QR code to the page.
    Needs $heading, $agent (Agent::from or null), $agentPhoto, $url and the Datasheet contact details. --}}
{{-- Contact: agent, every number, QR --}}
<div class="section" style="margin-top: 3mm; page-break-inside: avoid;">
    <div class="box">
        <table class="contact">
            <tr>
                <td style="text-align: {{ $start }};">
                    <div class="contact-label">{{ __('Get in touch') }}</div>
                    <div style="color: #ffffff; font-size: 11pt; font-weight: bold; margin: 0.5mm 0 2mm;">{{ $heading }}</div>

                    @if ($agent)
                        <table style="margin: 1mm 0 2mm; background: #242424;">
                            <tr>
                                @if ($agentPhoto)
                                    <td style="width: 16mm; padding: 2mm; vertical-align: middle;"><img src="{{ $agentPhoto }}" style="width: 12mm; height: 12mm; border-radius: 6mm; border: 0.6mm solid #c8102e;" /></td>
                                @endif
                                <td style="padding: 2mm; border-{{ $start }}: 1mm solid #c8102e; vertical-align: middle; text-align: {{ $start }};">
                                    <div style="font-size: 10.5pt; font-weight: bold; color: #ffffff;">{{ $agent['name'] }}</div>
                                    <div style="font-size: 8pt; color: #dddddd;">{{ $agent['role'] ?: __('Property Advisor') }}</div>
                                    @if ($agent['rating'] !== null)
                                        <div style="font-size: 7.5pt; color: #f0c36d;">
                                            <span style="font-family: dejavusans; color: #f0c36d;">★</span> {{ number_format($agent['rating'], 1) }} / 5
                                            @if ($agent['reviews'] !== null)
                                                <span style="color: #d3d3d3;">· {{ number_format($agent['reviews']) }} {{ trans_choice(':count review|:count reviews', $agent['reviews'], ['count' => number_format($agent['reviews'])]) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($agent['phone'])
                                        <div style="font-size: 9.5pt; font-weight: bold; color: #ffffff; {{ $latin }}" dir="ltr">{{ $agent['phone'] }}</div>
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
