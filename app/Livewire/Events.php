<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Events extends Component
{
    public ?int $editingId = null;
    public array $form = [];

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
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.title' => 'required|string|max:200',
            'form.body' => 'nullable|string|max:20000',
            'form.event_date' => 'nullable|date',
            'form.event_time' => 'nullable|string|max:60',
            'form.location' => 'nullable|string|max:300',
        ])['form'];

        $data['body'] = $data['body'] ?? '';
        $data['event_date'] = $data['event_date'] ?: null;

        if ($this->editingId) {
            Event::findOrFail($this->editingId)->update($data);
        } else {
            Event::create($data + ['created_by' => auth()->id()]);
        }

        $this->editingId = null;
        $this->form = [];
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
