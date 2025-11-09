<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Фильтр по периоду
        </x-slot>

        <x-slot name="description">
            Выберите период для отображения статистики
        </x-slot>

        {{ $this->form }}

        @if($this->dateFilter === 'custom')
            <div class="mt-4">
                <x-filament::button 
                    type="button" 
                    wire:click="applyCustomDateRange"
                    color="primary">
                    Применить
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

