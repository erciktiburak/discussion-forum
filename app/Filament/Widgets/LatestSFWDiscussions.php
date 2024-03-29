<?php

namespace App\Filament\Widgets;

use App\Models\Discussion;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LatestSFWDiscussions extends BaseWidget
{
    protected static ?int $sort = 6;

    protected function getTableQuery(): Builder
    {
        return Discussion::query()
            ->withCount('replies')
            ->withCount('comments')
            ->withCount('likes')
            ->latest()
            ->where("is_nsfw", 0);
            
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Discussion'),

            Tables\Columns\TextColumn::make('replies_count')
                ->label('Replies'),

            Tables\Columns\TextColumn::make('comments_count')
                ->label('Comments'),

            Tables\Columns\TextColumn::make('likes_count')
                ->label('Likes'),
            
            Tables\Columns\TextColumn::make('is_nsfw')
                ->label('NSFW')
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('View')
                ->icon('heroicon-s-eye')
                ->color('secondary')
                ->url(fn ($record) => route('discussion', [
                    'discussion' => $record,
                    'slug' => Str::slug($record->name)
                ]), true)
        ];
    }
}
