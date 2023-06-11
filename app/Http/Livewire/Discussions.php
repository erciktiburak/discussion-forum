<?php

namespace App\Http\Livewire;

use App\Models\Discussion;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class Discussions extends Component implements HasForms
{
    use InteractsWithForms;

    public $limitPerPage = 10;
    public $disableLoadMore = false;
    public $tag;
    public $selectedSort;
    public $q;
    public $totalCount = 0;

    public function mount()
    {
        $this->q = request('q');
        $this->form->fill([
            'sort' => 'latest'
        ]);
    }

    public function render()
    {
        $discussions = $this->loadData();
        return view('livewire.discussions', compact('discussions'));
    }

    public function loadMore()
    {
        $this->limitPerPage = $this->limitPerPage + 10;
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(12)
                ->schema([
                    Select::make('sort')
                        ->disableLabel()
                        ->disablePlaceholderSelection()
                        ->options([
                            'latest' => 'Recently Added',
                            'oldest' => 'From Old to New',
                            'trending' => 'Popular',
                            'most-liked' => 'Highly Rated',
                        ])
                        ->columnSpan([
                            12,
                            'lg' => 3
                        ])
                        ->reactive()
                        ->afterStateUpdated(function () {
                            $this->loadData();
                        })
                        ->extraAttributes([
                            'class' => 'disabled:bg-slate-100'
                        ])
                ])
        ];
    }

    public function loadData()
    {
        $data = $this->form->getState();
        $sort = $data['sort'] ?? 'latest';

        $query = Discussion::query();

        if (!auth()->user() || !auth()->user()->hasVerifiedEmail()) {
            $query->where('is_public', true);
        }

        if ($this->tag) {
            $query->whereHas('tags', function ($query) {
                return $query->where('tags.id', $this->tag);
            });
        }

        // Discussions with the NSFW tag are filtered and blocked on the homepage
        if ($this->tag != 11) {
            $query->whereDoesntHave('tags', function ($query) {
                return $query->where('tags.id', 11);
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                $this->selectedSort = 'From Old to New discussions';
                break;
            case 'trending':
                $query->orderBy('unique_visits', 'desc')
                    ->orderBy('created_at', 'desc');
                $this->selectedSort = 'Popular discussions';
                break;
            case 'most-liked':
                $query->withCount('likes')
                    ->orderBy('likes_count', 'desc')
                    ->orderBy('created_at', 'desc');
                $this->selectedSort = 'Highly Rated discussions';
                break;
            case 'nsfw-latest':
                $query->orderBy('created_at', 'desc')->where("is_nsfw", 1);
                $this->selectedSort = 'Latest NSFW discussions';
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                $this->selectedSort = 'Recent discussions';
                break;
        }

        if ($this->q) {
            $query->where(
                fn($query) => $query
                    ->where('name', 'like', '%' . $this->q . '%')
                    ->orWhere('content', 'like', '%' . $this->q . '%')
                    ->orWhereHas('tags', fn($query) => $query->where('name', 'like', '%' . $this->q . '%'))
            );
        }

        $data = $query->paginate($this->limitPerPage);
        if ($data->hasMorePages()) {
            $this->disableLoadMore = false;
        } else {
            $this->disableLoadMore = true;
        }

        $this->totalCount = $data->total();

        return $data;
    }
}
