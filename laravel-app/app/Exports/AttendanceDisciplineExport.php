<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use App\Services\AttendanceDisciplineService;
use App\Support\AttendanceDisciplineFilter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 60B — real XLSX export of the Dashboard Disiplin Kehadiran, written
 * with PhpSpreadsheet. Not a CSV with an .xlsx extension, and not a hand-rolled
 * OOXML package.
 *
 * The rows come from AttendanceDisciplineService::exportRows(), i.e. exactly the
 * same base query, filters, ordering and eager loading as the on-screen table,
 * so the file always matches the page it was downloaded from.
 *
 * Deliberately absent from the output: latitude/longitude, selfie paths or URLs,
 * passwords, remember tokens, Google ids, login IPs, payroll figures and leave
 * data. Only the columns listed in HEADINGS are ever written.
 */
class AttendanceDisciplineExport
{
    public const SHEET_TITLE = 'Disiplin Kehadiran';

    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * A leading =, +, -, @, tab or carriage return is what turns a spreadsheet
     * cell into an executable formula in Excel / LibreOffice / Sheets.
     *
     * Listed here for documentation and for the value-level guard in
     * writeText(); the actual protection is that *every* user-originated cell is
     * written with setCellValueExplicit(..., DataType::TYPE_STRING). An explicit
     * string cell is serialised into the shared-string table, never into an <f>
     * formula element, so the value survives as literal text no matter what it
     * starts with — and the original characters are preserved rather than being
     * mangled with an injected quote.
     *
     * HTML escaping is irrelevant here: XLSX is not HTML.
     */
    public const RISKY_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /** @var list<string> */
    public const HEADINGS = [
        'Nomor',
        'Tanggal',
        'NIK',
        'Nama Pegawai',
        'Departemen',
        'Posisi',
        'Jam Check-in',
        'Jam Checkout',
        'Status Persetujuan',
        'Klasifikasi Radius',
        'Jarak dari Kantor (meter)',
        'Akurasi GPS Check-in (meter)',
        'Akurasi GPS Checkout (meter)',
        'Alasan Luar Radius',
        'Rencana Kerja',
        'Hasil Pekerjaan',
        'Penyetuju',
        'Waktu Persetujuan',
        'Catatan Persetujuan',
        'Check-in Lengkap',
        'Checkout Lengkap',
    ];

    /** Column widths are fixed rather than auto-sized: autosize walks every cell
     *  in every column, which is the opposite of what a large export needs. */
    private const COLUMN_WIDTHS = [
        'A' => 8,  'B' => 12, 'C' => 18, 'D' => 26, 'E' => 20, 'F' => 20, 'G' => 13,
        'H' => 13, 'I' => 20, 'J' => 18, 'K' => 22, 'L' => 24, 'M' => 24, 'N' => 34,
        'O' => 40, 'P' => 40, 'Q' => 24, 'R' => 20, 'S' => 34, 'T' => 16, 'U' => 16,
    ];

    public function __construct(private readonly AttendanceDisciplineService $service) {}

    public function download(AttendanceDisciplineFilter $filter): StreamedResponse
    {
        $filename = $filter->filenameStem().'.xlsx';

        return response()->streamDownload(
            function () use ($filter): void {
                $spreadsheet = new Spreadsheet();

                try {
                    $this->build($spreadsheet, $filter);

                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                } finally {
                    // Release the worksheet graph explicitly — PhpSpreadsheet
                    // objects hold circular references, so without this the
                    // memory is not reclaimed until the process ends.
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                }
            },
            $filename,
            [
                'Content-Type'        => self::CONTENT_TYPE,
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function build(Spreadsheet $spreadsheet, AttendanceDisciplineFilter $filter): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_TITLE);

        foreach (self::HEADINGS as $index => $heading) {
            $sheet->setCellValueExplicit(
                [$index + 1, 1],
                $heading,
                DataType::TYPE_STRING,
            );
        }

        $sheet->getStyle('A1:U1')->getFont()->setBold(true);

        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $rowNumber = 2;
        $sequence  = 1;

        // An empty result set is a valid outcome, not an error: the file is
        // still produced, with the header row only.
        foreach ($this->service->exportRows($filter) as $record) {
            $this->writeRecord($sheet, $rowNumber, $sequence, $record);
            $rowNumber++;
            $sequence++;
        }

        $sheet->freezePane('A2');
        $sheet->setSelectedCell('A1');
    }

    private function writeRecord(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        int $sequence,
        AttendanceRecord $record,
    ): void {
        $employee = $record->employee;

        $this->writeNumber($sheet, 1, $row, $sequence);
        $this->writeText($sheet, 2, $row, $record->attendance_date?->format('Y-m-d'));
        $this->writeText($sheet, 3, $row, $employee?->nik);
        $this->writeText($sheet, 4, $row, $employee?->user?->name);
        $this->writeText($sheet, 5, $row, $employee?->department?->name);
        $this->writeText($sheet, 6, $row, $employee?->position?->name);
        $this->writeText($sheet, 7, $row, $record->check_in_time?->format('H:i'));
        $this->writeText($sheet, 8, $row, $record->check_out_time?->format('H:i'));
        $this->writeText($sheet, 9, $row, $this->service->statusLabel($record->status));
        $this->writeText($sheet, 10, $row, $this->service->radiusLabel($record));
        $this->writeNumber($sheet, 11, $row, $record->distance_from_office);
        $this->writeNumber($sheet, 12, $row, $record->check_in_accuracy);
        $this->writeNumber($sheet, 13, $row, $record->check_out_accuracy);
        $this->writeText($sheet, 14, $row, $record->out_of_radius_reason);
        $this->writeText($sheet, 15, $row, $record->check_in_work_plan);
        $this->writeText($sheet, 16, $row, $record->check_out_work_result);
        $this->writeText($sheet, 17, $row, $record->approver?->name);
        $this->writeText($sheet, 18, $row, $record->approved_at?->format('Y-m-d H:i'));
        $this->writeText($sheet, 19, $row, $record->approval_note);
        $this->writeText($sheet, 20, $row, $record->check_in_time !== null ? 'Ya' : 'Tidak');
        $this->writeText($sheet, 21, $row, $record->check_out_time !== null ? 'Ya' : 'Tidak');
    }

    /**
     * Every textual cell goes through here. TYPE_STRING is forced regardless of
     * content, which is what keeps "=SUM(A1:A9)", "+1", "-1", "@SUM", a leading
     * tab or a leading CR from ever being evaluated as a formula.
     *
     * Dates and times are written as text too, on purpose: it keeps them
     * readable and unambiguous instead of depending on the reader's locale to
     * reinterpret a serial number.
     */
    private function writeText(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $column,
        int $row,
        ?string $value,
    ): void {
        $sheet->setCellValueExplicit([$column, $row], $value ?? '', DataType::TYPE_STRING);
    }

    /** Server-computed decimals only (never free text), so a numeric cell is safe. */
    private function writeNumber(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $column,
        int $row,
        int|float|string|null $value,
    ): void {
        if ($value === null || $value === '') {
            // NULL accuracy/distance stays blank — it is "not recorded",
            // which is not the same statement as zero.
            $sheet->setCellValueExplicit([$column, $row], '', DataType::TYPE_STRING);

            return;
        }

        $sheet->setCellValueExplicit([$column, $row], (float) $value, DataType::TYPE_NUMERIC);
    }
}
