<x-filament-widgets::widget>
    <x-filament::section>
        @if($existe)
            {{-- Header del Widget --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-600">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Tu Resumen IA - {{ $periodo }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Análisis generado por Claude • Actualizado {{ $generado_en }}
                        </p>
                    </div>
                </div>

                {{-- Badge de estado --}}
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Actualizado
                    </span>
                </div>
            </div>

            {{-- Contenido del Resumen --}}
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-6">
                    {!! $contenido_html !!}
                </div>
            </div>

            {{-- Footer con info adicional --}}
            <div class="mt-6 flex items-center justify-between border-t pt-4 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tokens utilizados: {{ number_format($tokens_usados) }}
                    </span>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Powered by <span class="font-semibold text-primary-600 dark:text-primary-400">Claude AI</span>
                </div>
            </div>

        @else
            {{-- Estado Vacío - Sin resumen para el mes actual --}}
            <div class="flex flex-col items-center justify-center py-12">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900 dark:to-primary-800">
                    <svg class="h-10 w-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Genera tu Análisis IA
                </h3>

                <p class="mt-2 max-w-md text-center text-sm text-gray-500 dark:text-gray-400">
                    Obtén insights personalizados, recomendaciones y análisis detallados de tu negocio powered by Claude AI.
                    Haz clic en el botón para generar tu análisis del mes actual.
                </p>

                <div class="mt-6 flex items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-5 w-5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Análisis inteligente
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-5 w-5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Recomendaciones accionables
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="h-5 w-5 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Comparativas históricas
                    </div>
                </div>

                {{-- Botón para generar análisis --}}
                <div class="mt-8">
                    <button
                        wire:click="generarAnalisis"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-primary-700 hover:to-primary-800 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed dark:from-primary-500 dark:to-primary-600"
                    >
                        <svg wire:loading.remove class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <svg wire:loading class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>Generar Análisis IA</span>
                        <span wire:loading>Generando...</span>
                    </button>
                </div>

                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    El análisis se generará en segundos. Recarga la página una vez completado.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
