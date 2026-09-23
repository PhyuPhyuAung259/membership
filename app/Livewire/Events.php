<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Events extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;
    public array $form = [];

    public $image = null;
    public ?string $existingImagePath = null;

    public function getEventsProperty()
    {
        return Event::withCount(['broadcasts' => fn ($q) => $q->whereNotNull('sent_at')])
            ->orderByRaw('COALESCE(event_date, created_at::date) DESC')
            ->limit(100)
            ->get();
    }

    public function startEdit(?int $id = null): void
    {
        $this->editingId = $id;
        $event = $id ? Event::findOrFail($id) : null;

        $this->form = [
            'title' => $event->title ?? '',
            'body' => $event->body ?? '',
            'event_date' => $event?->event_date?->format('Y-m-d') ?? '',
            'event_time' => $event->event_time ?? '',
            'location' => $event->location ?? '',
        ];

        $this->image = null;
        $this->existingImagePath = $event->image_path ?? null;
    }

    public function removeImage(): void
    {
        $this->image = null;
        $this->existingImagePath = null;
    }

    public function closeForm(): void
    {
        $this->form = [];
        $this->image = null;
        $this->existingImagePath = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.title' => 'required|string|max:200',
            'form.body' => 'nullable|string|max:20000',
            'form.event_date' => 'nullable|date',
            'form.event_time' => 'nullable|string|max:60',
            'form.location' => 'nullable|string|max:300',
            'image' => 'nullable|image|max:2048',
        ])['form'];

        $data['body'] = $data['body'] ?? '';
        $data['event_date'] = $data['event_date'] ?: null;

        if ($this->image) {
            $data['image_path'] = $this->image->store('events', 'public');
        } elseif ($this->editingId && $this->existingImagePath === null) {
            $data['image_path'] = null;
        }

        if ($this->editingId) {
            Event::findOrFail($this->editingId)->update($data);
        } else {
            Event::create($data + ['created_by' => auth()->id()]);
        }

        $this->editingId = null;
        $this->closeForm();
        session()->flash('status', 'Event saved.');
    }

    public function delete(int $id): void
    {
        Event::findOrFail($id)->delete();
        session()->flash('status', 'Event deleted. Announcements already sent are kept.');
    }

    public function render()
    {
        return view('livewire.events', ['events' => $this->events]);
    }
}
