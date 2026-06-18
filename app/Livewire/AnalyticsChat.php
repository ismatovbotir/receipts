<?php

namespace App\Livewire;

use App\Services\DataFetcher;
use App\Services\GeminiService;
use Livewire\Component;

class AnalyticsChat extends Component
{
    public string $question   = '';
    public ?array $result     = null;
    public ?string $error     = null;
    public array $history     = [];
    public bool $keyMissing   = false;

    public function mount(): void
    {
        $this->keyMissing = empty(config('services.gemini.key'));
    }

    public array $suggestions = [
        "Bu oy eng ko'p sotilgan mahsulotlar?",
        'Kassirlar samaradorligini ko\'rsating',
        "To'lov usullari bo'yicha tahlil",
        'Qaysi kuni eng ko\'p savdo bo\'ldi?',
        "Eng ko'p daromad keltirgan do'kon?",
        'Soatlar bo\'yicha savdo dinamikasi',
        'Kategoriyalar bo\'yicha taqsimot',
        'Oxirgi 30 kunda savdo trendi',
    ];

    public function ask(): void
    {
        $this->validate(['question' => 'required|string|min:3|max:500']);

        $this->error = null;

        try {
            $data   = app(DataFetcher::class)->fetch($this->question);
            $result = app(GeminiService::class)->askWithData($this->question, $data);

            if ($result['error']) {
                $this->error = $result['error'];
            } else {
                $this->history[] = [
                    'q' => $this->question,
                    'r' => $result,
                ];
                $this->result = $result;

                if ($result['chart_type'] !== 'none' && $result['chart_data']) {
                    $this->dispatch('ai-chart-render',
                        chartType: $result['chart_type'],
                        chartData: $result['chart_data']
                    );
                } else {
                    $this->dispatch('ai-chart-clear');
                }
            }
        } catch (\Throwable $e) {
            $this->error = 'Xatolik yuz berdi: ' . $e->getMessage();
        }

        $this->question = '';
    }

    public function askSuggestion(int $index): void
    {
        $this->question = $this->suggestions[$index] ?? '';
        $this->ask();
    }

    public function clearHistory(): void
    {
        $this->history = [];
        $this->result  = null;
        $this->error   = null;
        $this->dispatch('ai-chart-clear');
    }

    public function render()
    {
        return view('livewire.analytics-chat');
    }
}
