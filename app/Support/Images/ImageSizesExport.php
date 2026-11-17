<?php

namespace App\Support\Images;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Settings → Image Sizes → Export Excel (AKAR-50): the sheet a designer or photographer works from. One row for each
 * place on the website and each upload field shown there, with the sizes now in use. All in the language the admin
 * picks (English or Arabic; an Arabic sheet reads right to left).
 */
class ImageSizesExport
{
    public const LOCALES = ['en', 'ar'];

    /** The columns, in order (their names: admin.image_sizes.export.columns.*). */
    private const COLUMNS = [
        'group' => 16, 'place' => 28, 'page' => 18, 'display' => 22, 'shape' => 20, 'phone_shape' => 20, 'width' => 16,
        'sharp_width' => 20, 'field' => 30, 'recommended' => 18, 'ratio' => 10, 'smallest' => 20, 'formats' => 18,
        'mb' => 16, 'changed' => 14,
    ];

    public static function download(string $locale): StreamedResponse
    {
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'en';

        return response()->streamDownload(function () use ($locale) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            static::write($writer, $locale);
            $writer->close();
        }, static::fileName($locale), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * "image-sizes-ar-2026-11-17.xlsx".
     */
    public static function fileName(string $locale): string
    {
        return "image-sizes-{$locale}-".now()->format('Y-m-d').'.xlsx';
    }

    /**
     * The sheet's rows (headings first), also used by the tests.
     *
     * @return list<list<string|int>>
     */
    public static function rows(string $locale): array
    {
        $t = fn (string $key, array $replace = []) => __("admin.image_sizes.{$key}", $replace, $locale);
        $fields = ImageSizes::fields();
        $rows = [array_map(fn (string $column) => $t("export.columns.{$column}"), array_keys(self::COLUMNS))];

        foreach (ImageSizes::places() as $place) {
            foreach ($place['fields'] as $key) {
                $field = $fields[$key];
                $shape = $place['display'] === 'shape';

                $rows[] = [
                    $t("groups.{$field['group']}"),
                    $t("places.{$place['key']}"),
                    $t("pages.{$place['page']}"),
                    $t("display.{$place['display']}"),
                    $shape ? $t("shapes.{$place['shape']}") : $t('fixed'),
                    $shape && $place['phone'] ? $t("shapes.{$place['phone_shape']}") : '',
                    $place['width'],
                    $place['width'] * 2,
                    $t("fields.{$key}"),
                    ImageText::dimensions($field['recommended']),
                    ImageText::ratio($field['recommended']),
                    ImageText::minimum($field['min'], $locale),
                    ImageText::formats($field['formats']),
                    $field['mb'],
                    $field['changed'] || $place['changed'] ? $t('export.yes') : $t('export.no'),
                ];
            }
        }

        return $rows;
    }

    private static function write(Writer $writer, string $locale): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName(__('admin.image_sizes.export.sheet', [], $locale));
        $sheet->setSheetView((new SheetView)->setFreezeRow(2)->setRightToLeft($locale === 'ar'));
        foreach (array_values(self::COLUMNS) as $column => $width) {
            $sheet->setColumnWidth($width, $column + 1);
        }

        $heading = (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('1F2937')->setShouldWrapText();
        $body = (new Style)->setShouldWrapText();

        foreach (static::rows($locale) as $index => $values) {
            $writer->addRow(Row::fromValues($values, $index === 0 ? $heading : $body));
        }
    }
}
