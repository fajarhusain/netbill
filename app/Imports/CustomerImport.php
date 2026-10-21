<?php

namespace App\Imports;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Package;
use App\Services\CodeGeneratorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public int $created = 0;

    public int $skipped = 0;

    public array $errors = [];

    private array $seenCodes = [];

    private array $packageCache = [];

    public function __construct(
        private CodeGeneratorService $codes,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $name = $this->toString($row['nama'] ?? null);

            if ($name === '') {
                $this->skipped++;

                continue;
            }

            $code = $this->toString($row['kode'] ?? null);
            if ($code === '') {
                $code = $this->codes->generateCustomerCode();
            }

            if (Customer::where('customer_code', $code)->exists() || in_array($code, $this->seenCodes, true)) {
                $this->skipped++;

                continue;
            }

            $this->seenCodes[] = $code;

            $package = $this->resolvePackage($row['paket'] ?? null);
            $monthlyFee = $this->toInt($row['tarif'] ?? null);
            if ($monthlyFee <= 0 && $package) {
                $monthlyFee = $package->monthly_price;
            }

            $startedAt = $this->toDate($row['tanggal_mulai'] ?? null);
            $dueDay = $this->toInt($row['tanggal_jatuh_tempo'] ?? null);
            if ($dueDay < 1 || $dueDay > 28) {
                $dueDay = 10;
            }

            $status = $this->toString($row['status'] ?? null);
            $status = in_array($status, ['active', 'inactive', 'suspended'], true) ? $status : 'active';

            Customer::create([
                'customer_code' => $code,
                'barcode_token' => $this->codes->generateBarcodeToken(),
                'name' => $name,
                'phone' => $this->toString($row['telepon'] ?? null),
                'address' => $this->toString($row['alamat'] ?? null),
                'dusun' => $this->toString($row['dusun'] ?? null) ?: null,
                'package_id' => $package?->id,
                'monthly_fee' => $monthlyFee > 0 ? $monthlyFee : ($package?->monthly_price ?? 0),
                'started_at' => $startedAt,
                'due_day' => $dueDay,
                'status' => CustomerStatus::from($status),
                'notes' => $this->toString($row['catatan'] ?? null) ?: null,
            ]);

            $this->created++;
        }
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string'],
            'tarif' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama wajib diisi.',
            'tarif.integer' => 'Kolom Tarif harus berupa angka.',
        ];
    }

    private function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);

        return (int) $cleaned;
    }

    private function toDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolvePackage(mixed $value): ?Package
    {
        $name = $this->toString($value);
        if ($name === '') {
            return null;
        }

        if (! isset($this->packageCache[$name])) {
            $this->packageCache[$name] = Package::where('name', $name)->first();
        }

        return $this->packageCache[$name] ?: null;
    }
}
