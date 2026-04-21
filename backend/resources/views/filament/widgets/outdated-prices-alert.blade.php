<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
    @endphp

    @if($data['showAlert'])
        <div class="rounded-lg border border-warning-600 bg-warning-50 dark:bg-warning-950 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0" style="width:1.5rem;height:1.5rem;overflow:hidden">
                    <svg class="h-6 w-6 text-warning-600 dark:text-warning-400" style="width:1.5rem;height:1.5rem;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-warning-800 dark:text-warning-200">
                        Precios Desactualizados
                    </h3>
                    <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                        Hay <strong>{{ number_format($data['outdatedCount']) }}</strong> precios que no se han actualizado en los últimos 30 días.
                        Se recomienda revisar y actualizar estos precios para mantener la competitividad.
                    </p>
                    <div class="mt-3">
                        <a href="{{ route('filament.admin.resources.product-prices.index') }}" 
                           class="text-sm font-medium text-warning-700 dark:text-warning-300 hover:text-warning-900 dark:hover:text-warning-100">
                            Ver precios →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-lg border border-success-600 bg-success-50 dark:bg-success-950 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0" style="width:1.5rem;height:1.5rem;overflow:hidden">
                    <svg class="h-6 w-6 text-success-600 dark:text-success-400" style="width:1.5rem;height:1.5rem;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-success-800 dark:text-success-200">
                        Precios Actualizados
                    </h3>
                    <p class="mt-1 text-sm text-success-700 dark:text-success-300">
                        Todos los precios activos han sido actualizados en los últimos 30 días.
                    </p>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
