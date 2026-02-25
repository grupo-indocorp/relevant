<x-sistema.modal title="Asignar Clientes" style="width: 500px;" dialog_id="dialog">
    <div class="form-group mb-3">
        <label for="file" class="form-label">Selecciona el archivo Excel:</label>
        <input type="file" name="file" id="file" class="form-control" required>
    </div>
    <div class="form-group flex flex-col">
        <label for="import_user_id" class="form-control-label">Ejecutivo:</label>
        <select class="form-control"
            name="import_user_id"
            id="import_user_id"
            style="width: 250px;">
            <option></option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}">
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group flex flex-col">
        <label for="import_etiqueta_id" class="form-control-label">Etiqueta:</label>
        <select class="form-control"
            name="import_etiqueta_id"
            id="import_etiqueta_id"
            style="width: 250px;">
            <option></option>
            @foreach ($etiquetas as $item)
                <option value="{{ $item->id }}">
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="flex justify-end">
        <button type="button" class="btn bg-gradient-primary m-0" onclick="importCliente()">Subir Clientes</button>
    </div>
</x-sistema.modal>
<script>
    function importCliente() {
        // Capturamos el archivo
        let fileInput = $('#file')[0].files[0];
        let userId = $('#import_user_id').val();
        let etiquetaId = $('#import_etiqueta_id').val();

        if (!fileInput) {
            alert("Por favor selecciona un archivo primero");
            return;
        }
        if (!userId) {
            alert("Por favor selecciona un ejecutivo");
            return;
        }
        if (!etiquetaId) {
            alert("Por favor selecciona una etiqueta");
            return;
        }

        // Creamos el FormData
        let formData = new FormData();
        formData.append('file', fileInput);
        formData.append('import_user_id', userId);
        formData.append('import_etiqueta_id', etiquetaId);
        formData.append('_token', '{{ csrf_token() }}'); // CSRF obligatorio en Laravel

        $.ajax({
            url: "{{ route('import.cliente') }}",
            type: "POST",
            data: formData,
            processData: false, // evita que jQuery procese los datos
            contentType: false, // evita que jQuery ponga content-type incorrecto
            success: function(response) {
                location.reload();
                closeModal();
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert("Error al importar clientes");
            }
        });
    }
</script>
