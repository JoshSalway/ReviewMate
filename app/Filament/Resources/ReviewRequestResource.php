<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewRequestResource\Pages;
use App\Models\ReviewRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewRequestResource extends Resource
{
    protected static ?string $model = ReviewRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Review Requests';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'info',
                        'opened' => 'warning',
                        'reviewed' => 'success',
                        'self_confirmed' => 'success',
                        'feedback_received' => 'primary',
                        'no_response' => 'gray',
                        'unverified_claim' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'info',
                        'sms' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'opened' => 'Opened',
                        'reviewed' => 'Reviewed',
                        'self_confirmed' => 'Self Confirmed',
                        'feedback_received' => 'Feedback Received',
                        'no_response' => 'No Response',
                        'unverified_claim' => 'Unverified Claim',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sent_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewRequests::route('/'),
            'view' => Pages\ViewReviewRequest::route('/{record}'),
        ];
    }
}
