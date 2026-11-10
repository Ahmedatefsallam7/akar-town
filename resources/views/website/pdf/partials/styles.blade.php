{{-- The datasheets' shared styles (mPDF: a part of CSS). Needs $start, $end, $tracking, $uppercase. --}}
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
