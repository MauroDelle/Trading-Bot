<div wire:poll.2s class="p-8 max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-8 border-b border-gray-700 pb-4">
        <h1 class="text-3xl font-bold text-blue-400">🤖 SafeGrid Tracker</h1>
        <div class="flex items-center space-x-2">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-green-400 font-semibold text-sm">EN VIVO</span>
        </div>
    </div>

    <!-- Pestañas Multi-Token -->
    <div class="flex space-x-4 mb-6 border-b border-gray-700">
        @foreach($prices as $symbol => $price)
            <button wire:click="$set('activeTab', '{{ $symbol }}')" 
                    class="px-4 py-3 font-semibold transition-colors duration-200 {{ $activeTab === $symbol ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-400 hover:text-gray-200' }}">
                {{ $symbol }}
            </button>
        @endforeach
    </div>

    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <!-- Valor Total -->
        <div class="bg-gray-800 p-6 rounded-xl border {{ $profitUsdt >= 0 ? 'border-green-500/50' : 'border-red-500/50' }} shadow-lg relative overflow-hidden">
            <div class="absolute inset-0 {{ $profitUsdt >= 0 ? 'bg-green-500' : 'bg-red-500' }} opacity-5"></div>
            <p class="text-gray-400 text-sm font-semibold mb-1">VALOR TOTAL ACTUAL</p>
            <div class="flex items-end space-x-3">
                <p class="text-3xl font-bold text-white">${{ number_format($totalEquity, 2) }}</p>
                <span class="text-sm font-bold mb-1 {{ $profitUsdt >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ $profitUsdt >= 0 ? '+' : '' }}${{ number_format($profitUsdt, 2) }}
                </span>
            </div>
        </div>

        <!-- Inversión Inicial -->
        <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg">
            <p class="text-gray-400 text-sm font-semibold mb-1">INVERSIÓN INICIAL</p>
            <p class="text-3xl font-bold text-gray-300">${{ number_format($initialInvestment, 2) }}</p>
        </div>

        <!-- Composición de la Billetera -->
        <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg">
            <p class="text-gray-400 text-sm font-semibold mb-3">ACTIVOS EN BILLETERA</p>
            <div class="flex justify-between items-center mb-2 border-b border-gray-700 pb-2">
                <span class="text-sm text-gray-400">USDT Disponible</span>
                <span class="text-sm font-bold text-green-400">${{ number_format($usdtBalance, 2) }}</span>
            </div>
            @foreach($balances as $sym => $bal)
                @php $base = explode('/', $sym)[0]; @endphp
                <div class="flex justify-between items-center mt-1">
                    <span class="text-sm text-gray-400">{{ $base }} Comprado</span>
                    <span class="text-sm font-bold text-orange-400">{{ number_format($bal, 6) }}</span>
                </div>
            @endforeach
        </div>
        
        <!-- Precio de Mercado -->
        <div class="bg-gray-800 p-6 rounded-xl border border-blue-900 shadow-lg relative overflow-hidden">
            <div class="absolute inset-0 bg-blue-500 opacity-10"></div>
            <p class="text-blue-300 text-sm font-semibold mb-1">PRECIO MERCADO {{ $activeTab }}</p>
            <p class="text-3xl font-bold text-white">${{ number_format($prices[$activeTab] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Tabla del Grid -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-lg overflow-hidden">
        <div class="bg-gray-900 px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-200">Estado de Niveles del Grid</h2>
        </div>
        <div class="p-6 space-y-3">
            @foreach($gridLevels as $level)
                <div class="flex justify-between items-center bg-gray-700/30 p-4 rounded-lg border border-gray-600/50">
                    <span class="text-gray-300 font-mono">Nivel {{ $level['index'] }}</span>
                    <span class="text-xl font-bold text-white">${{ number_format($level['price'], 2) }}</span>
                    
                    @if($level['state'] === 'BOUGHT')
                        <span class="px-4 py-1 rounded-full bg-green-500/20 text-green-400 font-bold border border-green-500/30 shadow-[0_0_10px_rgba(74,222,128,0.2)]">COMPRADO</span>
                    @else
                        <span class="px-4 py-1 rounded-full bg-yellow-500/20 text-yellow-400 font-bold border border-yellow-500/30">PENDIENTE</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Últimos Movimientos (PostgreSQL Ledger) -->
    <div class="mt-10 bg-gray-800 rounded-xl border border-gray-700 shadow-lg overflow-hidden">
        <div class="bg-gray-900 px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-200">Últimos Movimientos (Ledger)</h2>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-400">
                <thead class="text-xs uppercase bg-gray-700/50 text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Par</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Nivel</th>
                        <th class="px-4 py-3">Precio</th>
                        <th class="px-4 py-3">Beneficio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestTrades as $trade)
                        <tr class="border-b border-gray-700 hover:bg-gray-700/20">
                            <td class="px-4 py-3">{{ date('d/m/Y H:i:s', strtotime($trade['created_at'])) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $trade['symbol'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-sm font-bold text-xs {{ $trade['side'] === 'BUY' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $trade['side'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">Nivel {{ $trade['grid_level'] }}</td>
                            <td class="px-4 py-3">${{ number_format($trade['price'], 2) }}</td>
                            <td class="px-4 py-3 {{ $trade['realized_profit'] > 0 ? 'text-green-400' : 'text-gray-500' }}">
                                {{ $trade['realized_profit'] ? '+$'.number_format($trade['realized_profit'], 2) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Sin movimientos recientes...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-center text-gray-500 text-sm mt-8">Arquitectura Desacoplada | Panel de Solo Lectura</p>
</div>