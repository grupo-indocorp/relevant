@role('ejecutivo')
    <x-sistema.modal title="Solicitar Cliente" dialog_id="dialog">
        <x-sistema.cliente.datos :$cliente></x-sistema.cliente.datos>
        <x-sistema.cliente.etapas></x-sistema.cliente.etapas>
        <x-sistema.cliente.comentarios></x-sistema.cliente.comentarios>
        <div class="flex justify-end">
            <button type="button" class="btn bg-gradient-primary m-0" onclick="submitSolicitar({{ $cliente->id }})">Solicitar</button>
        </div>
    </x-sistema.modal>
    <script>
        function submitSolicitar(cliente_id) {
            const dialog = document.querySelector("#dialog");
            dialog.querySelectorAll('.is-invalid, .invalid-feedback').forEach(element => {
                element.classList.contains('is-invalid') ? element.classList.remove('is-invalid') : element.remove();
            });
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: `cliente-consultor/${cliente_id}`,
                method: "PUT",
                data: {
                    view: 'update-solicitar',
                    comentario: $('#comentario').val(),
                    etapa_id: $('#etapa_id').val(),
                    contactabilidad_id: $('input[name="contactabilidad_id"]:checked').val(),
                },
                success: function( result ) {
                    location.reload();
                    closeModal();
                },
                error: function( data ) {
                    let errors = data.responseJSON;
                    if(errors) {
                        $.each(errors.errors, function(key, value){
                            $('#dialog #'+key).addClass('is-invalid');
                            $('#dialog #'+key).after('<span class="invalid-feedback" role="alert"><strong>'+ value +'</strong></span>');
                        });
                    }
                }
            });
        }
    </script>
@endrole
