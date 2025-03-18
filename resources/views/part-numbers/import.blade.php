<x-guest-layout>
    <div class="pt-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex justify-center items-center">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg w-full max-w-lg">
            <h2 class="text-3xl font-semibold text-center text-gray-700 dark:text-white mb-6">Subir Archivo Excel</h2>

            <!-- Mostrar mensaje de éxito o error si existe -->
            @if (session('success'))
            <div class="bg-green-200 text-green-800 p-4 rounded-lg mb-4">
                {{ session('success') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="bg-red-200 text-red-800 p-4 rounded-lg mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('part-numbers.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Campo de selección de archivo -->
                <div class="mb-6">
                    <label for="file" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">Seleccionar archivo</label>
                    <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv"
                        class="block w-full text-gray-700 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>

                <!-- Botón de enviar -->
                <button type="submit" class="w-full bg-blue-500 text-white py-3 px-6 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 ease-in-out">
                    Subir Excel
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
