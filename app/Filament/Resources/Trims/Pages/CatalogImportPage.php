<?php

namespace App\Filament\Resources\Trims\Pages;

use App\Filament\Resources\Trims\TrimResource;
use App\Jobs\ProcessCatalogImportJob;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CatalogImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TrimResource::class;
    
    protected static ?string $title = 'Import Catalog';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.resources.trims.pages.catalog-import-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Upload Catalog')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel.sheet.macroEnabled.12',
                                'application/vnd.ms-excel.sheet.macroenabled.12',
                                'application/vnd.ms-excel',
                                'application/octet-stream',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                        
                        CheckboxList::make('columns')
                            ->label('Columns to Import (Brand, Model, Trim Name, Year are always imported)')
                            ->options([
                                'price_egp' => 'Official Price (السعر الرسمي)',
                                'markup_percentage' => 'Markup % (نسبة السعر التنفيذي)',
                                'total_price' => 'Total Price (إجمالى السعر)',
                                'booking_deposit' => 'Booking Deposit (مقدم الحجز)',
                                'is_on_hold' => 'HOLD Status',
                                'colors' => 'Colors (الألوان)',
                                'financing_notes' => 'Financing Notes (معلومات اضافية)',
                                'zero_interest_price' => 'Zero Interest Price',
                                'price_9pct' => 'Install Price 9%',
                            ])
                            ->columns(2)
                            ->gridDirection('row')
                            ->default([
                                'price_egp', 'markup_percentage', 'total_price', 'booking_deposit', 
                                'is_on_hold', 'colors', 'financing_notes', 'zero_interest_price', 'price_9pct'
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();
        $filePath = Storage::disk('local')->path($data['file']);
        
        // Dispatch job
        ProcessCatalogImportJob::dispatch($filePath, $data['columns'], auth()->id());

        Notification::make()
            ->title('Import Started')
            ->body('The catalog import is running in the background. You will receive a notification when it finishes.')
            ->success()
            ->send();
            
        $this->form->fill();
    }
}
