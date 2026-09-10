<?php

namespace App\Filament\Actions;

use Filament\Actions\ImportAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Imports\ImportColumn;
use Filament\Forms\Components\Select;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use League\Csv\Reader as CsvReader;
use Illuminate\Validation\ValidationException;

class ExcelImportAction extends ImportAction
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->schema(fn (ExcelImportAction $action): array => array_merge([
            FileUpload::make('file')
                ->label(__('filament-actions::import.modal.form.file.label'))
                ->placeholder(__('filament-actions::import.modal.form.file.placeholder'))
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                    'application/vnd.ms-excel', 
                    'text/csv', 
                    'text/x-csv', 
                    'application/csv', 
                    'application/x-csv', 
                    'text/comma-separated-values', 
                    'text/x-comma-separated-values', 
                    'text/plain'
                ])
                ->rules($action->getFileValidationRules())
                ->afterStateUpdated(function (FileUpload $component, Component $livewire, Set $set, ?TemporaryUploadedFile $state) use ($action): void {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    try {
                        $livewire->validateOnly($component->getStatePath());
                    } catch (ValidationException $exception) {
                        $component->state([]);
                        throw $exception;
                    }

                    $csvStream = $this->getUploadedFileStream($state);

                    if (! $csvStream) {
                        return;
                    }

                    $csvReader = CsvReader::from($csvStream);

                    if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                        $csvReader->setDelimiter($csvDelimiter);
                    }

                    $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                    $csvColumns = $csvReader->getHeader();

                    $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                    $lowercaseCsvColumnKeys = array_combine(
                        $lowercaseCsvColumnValues,
                        $csvColumns,
                    );

                    $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                        $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                        Arr::first(
                            array_intersect(
                                $lowercaseCsvColumnValues,
                                $column->getGuesses(),
                            ),
                        )
                        ] ?? null;

                        return $carry;
                    }, []));
                })
                ->storeFiles(false)
                ->visibility('private')
                ->required()
                ->hiddenLabel(),
            Fieldset::make(__('filament-actions::import.modal.form.columns.label'))
                ->columns(1)
                ->inlineLabel()
                ->schema(function (Get $get) use ($action): array {
                    $csvFile = $get('file');

                    if (! $csvFile instanceof TemporaryUploadedFile) {
                        return [];
                    }

                    $csvStream = $this->getUploadedFileStream($csvFile);

                    if (! $csvStream) {
                        return [];
                    }

                    $csvReader = CsvReader::from($csvStream);

                    if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                        $csvReader->setDelimiter($csvDelimiter);
                    }

                    $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                    $csvColumns = $csvReader->getHeader();
                    $csvColumnOptions = array_combine($csvColumns, $csvColumns);

                    return array_map(
                        fn (ImportColumn $column): Select => $column->getSelect()->options($csvColumnOptions),
                        $action->getImporter()::getColumns(),
                    );
                })
                ->statePath('columnMap')
                ->visible(fn (Get $get): bool => $get('file') instanceof TemporaryUploadedFile),
        ], $action->getImporter()::getOptionsFormComponents()));
    }

    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        
        $path = $file->getRealPath() ?: $file->path();
        $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        
        if (in_array($extension, ['xls', 'xlsx'])) {
            $csvPath = $path . '.csv';
            
            if (!file_exists($csvPath)) {
                try {
                    // Use OpenSpout for memory-efficient and fast XLSX processing to prevent timeouts
                    if ($extension === 'xlsx' && class_exists(\OpenSpout\Reader\XLSX\Reader::class)) {
                        $reader = new \OpenSpout\Reader\XLSX\Reader();
                        $reader->open($path);
                        
                        $writer = new \OpenSpout\Writer\CSV\Writer();
                        $writer->openToFile($csvPath);
                        
                        foreach ($reader->getSheetIterator() as $sheet) {
                            foreach ($sheet->getRowIterator() as $row) {
                                $writer->addRow($row);
                            }
                            break; // Only read the first sheet
                        }
                        
                        $writer->close();
                        $reader->close();
                    } else {
                        // Fallback to PhpSpreadsheet for older .xls files
                        $spreadsheet = IOFactory::load($path);
                        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
                        $writer->setUseBOM(true);
                        $writer->save($csvPath);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('ExcelImportAction Conversion Error: ' . $e->getMessage(), [
                        'file' => $path,
                        'exception' => $e
                    ]);
                    throw $e;
                }
            }
            
            return fopen($csvPath, 'r');
        }

        return parent::getUploadedFileStream($file);
    }
    
    public function getFileValidationRules(): array
    {
        $rules = parent::getFileValidationRules();
        
        foreach ($rules as $key => $rule) {
            if (is_string($rule) && str_starts_with($rule, 'extensions:')) {
                unset($rules[$key]);
            }
            if (is_string($rule) && str_starts_with($rule, 'mimetypes:')) {
                unset($rules[$key]);
            }
        }
        
        return array_values($rules);
    }
}
