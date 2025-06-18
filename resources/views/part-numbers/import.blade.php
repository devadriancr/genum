<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br flex items-center justify-center p-4">
        <!-- Eliminamos transform hover:scale-[1.01] -->
        <div class="w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden dark:bg-gray-800">
            <!-- Header con degradado -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center">
                <h1 class="text-3xl font-bold text-white">G E N U M</h1>
            </div>

            <!-- Contenido del formulario -->
            <div class="p-8">
                <!-- Mensajes de estado -->
                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-medium">Error en el formulario</span>
                        </div>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('part-numbers.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Campo de archivo -->
                        <div class="flex flex-col">
                            <label for="file_excel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Archivo Excel</label>
                            <div class="relative flex-grow">
                                <input type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" required
                                       class="block w-full h-[46px] text-gray-700 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-300 ease-in-out file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        <!-- Select de días de stock -->
                        <div class="flex flex-col">
                            <label for="stock_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Días de Stock</label>
                            <select id="stock_days" name="stock_days" required
                                    class="block w-full h-[46px] text-gray-700 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-300 ease-in-out">
                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('stock_days') == $i ? 'selected' : '' }}>{{ $i }} día{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Botón de enviar  -->
                    <div class="pt-4">
                        <button type="submit" class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-300 ease-in-out shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Procesar Archivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
