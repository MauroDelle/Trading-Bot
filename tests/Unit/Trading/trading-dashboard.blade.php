<div wire:poll.2s class="min-h-screen bg-gray-900 text-gray-100 p-8 font-sans">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">
            SafeGrid Trading Dashboard
        </h1>
        <p class="text-gray-400 text-sm mt-1">Monitoreo en Vivo • 100% Paper Trading</p>
    </header>

    <!-- Métricas Clave -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10"><x-heroicon-o-currency-dollar class="w-16 h-16"/></div>
            <h3 class="text-gray-400 text-sm font-semibold mb-2">Precio Actual Mercado (BTC/USDT)</h3>
            <div class="text-3xl font-bold text-white flex items-center gap-2">
                <span class="text-gray-500">$</span>{{ number_format($currentPrice, 2) }}
                @if($currentPrice > 0)
                    <span class="flex w-3 h-3 bg-emerald-500 rounded-full animate-pulse" title="Live"></span>
                @endif
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg">
            <h3 class="text-gray-400 text-sm font-semibold mb-2">Saldo Ficticio Disponible</h3>
            <div class="text-3xl font-bold text-emerald-400">
                {{ number_format($usdtBalance, 2) }} <span class="text-lg font-medium text-emerald-600">USDT</span>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg">
            <h3 class="text-gray-400 text-sm font-semibold mb-2">Activo Acumulado</h3>
            <div class="text-3xl font-bold text-blue-400">
                {{ number_format($btcBalance, 4) }} <span class="text-lg font-medium text-blue-600">BTC</span>
            </div>
        </div>
    </div>

    <!-- Visualización del Grid -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 bg-gray-800/50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">Niveles de Cuadrícula (Grid Config)</h2>
            <span class="text-xs text-gray-400">Auto-refresh: 2s</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-900/50 text-gray-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">Nivel</th>
                        <th class="px-6 py-3 font-medium">Precio (USDT)</th>
                        <th class="px-6 py-3 font-medium">Estado del Grid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($gridLevels as $level)
                        <tr class="hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 text-gray-300 font-mono">Nivel {{ $level['index'] }}</td>
                            <td class="px-6 py-4 font-mono text-white">${{ number_format($level['price'], 2) }}</td>
                            <td class="px-6 py-4">
                                @if($level['state'] === 'BOUGHT')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-2 h-2 mr-1.5 bg-emerald-400 rounded-full shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span> Comprado (BOUGHT)
                                    </span>
                                @elseif($level['state'] === 'SOLD')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        <span class="w-2 h-2 mr-1.5 bg-blue-400 rounded-full"></span> Vendido (SOLD)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                        <span class="w-2 h-2 mr-1.5 bg-gray-400 rounded-full"></span> Pendiente (PENDING)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>