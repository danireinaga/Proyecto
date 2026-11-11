<x-guest-layout>
    <div class="fixed inset-0 min-h-screen w-full flex flex-col justify-center items-center bg-gradient-to-br from-[#fbe4f0] via-[#f3d6ec] to-[#e1c4f5] p-6">
        <div class="w-full sm:max-w-md px-8 py-8 bg-white/95 backdrop-blur-md shadow-2xl overflow-hidden sm:rounded-3xl border border-purple-100 z-10">
            
            <!-- Encabezado de la Marca -->
            <div class="mb-6 text-center">
                <img src="{{ asset('img/logo_reposteria_transparente.png') }}" alt="Azúcar y Yo"
                    class="mx-auto mb-3 h-24 max-w-full object-contain">
                <h2 class="text-3xl font-black tracking-wider text-[#3B1C4A]">AZUCAR Y YO</h2>
                <p class="text-xs font-semibold uppercase tracking-widest text-purple-700/70 mt-1">Panel de Control y Gestión</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Correo Electrónico -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#3B1C4A] mb-1.5">Correo Electrónico</label>
                    <input id="email" class="w-full px-4 py-3 rounded-xl border border-purple-200 bg-purple-50/30 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3B1C4A] focus:border-transparent transition duration-200 shadow-sm text-sm" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@azucaryyo.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-500" />
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#3B1C4A] mb-1.5">Contraseña</label>
                    <input id="password" class="w-full px-4 py-3 rounded-xl border border-purple-200 bg-purple-50/30 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#3B1C4A] focus:border-transparent transition duration-200 shadow-sm text-sm" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-500" />
                </div>

                <!-- Botón de Ingreso Principal -->
                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 px-4 bg-[#3B1C4A] hover:bg-[#2A1335] text-white font-bold rounded-xl shadow-lg shadow-purple-900/25 transform transition active:scale-[0.98] duration-200 flex items-center justify-center space-x-2 text-sm tracking-wide">
                        <span>Iniciar Sesión</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>