<x-app-layout>
    <div class="container p-3 my-5 mb-5 rounded shadow bg-light bg-body-tertiary" style="border-radius: 15px; height: 100%">

        <div class="container px-5 py-5 ">
            {{-- <div class="row">
                <div class="mb-5 col-12" style="text-align: end">
                    <a href="{{ route('equipo.create') }}">
                        <button class="btn btn-dark">
                            <i class="fa-regular fa-square-plus" style="color: #ffffff;"></i> Nuevo equipo
                        </button>
                    </a>
                </div>
            </div> --}}
            
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function(){
                    $('.alert').alert('close');
                }, 3000);
            </script>
            @endif
            
            <table id="table" class="display">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Orden WP</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordenes as $orden)
                    <tr>
                        <td>{{ $orden->cliente->nombre }}</td>
                        <td>{{ $orden->orden_id_wp ?? 'No disponible' }}</td>
                        <td>{{ $orden->estado }}</td>
                        <td>{{ $orden->fecha }}</td>
                        <td>{{ $orden->tipo }}</td>
{{-- 
                        <td style="text-align: end">
                            @if(auth()->check() && $equipo->idUsuario == auth()->user()->id)
                            <a href="{{ route('equipo.edit', $equipo->id) }}">
                                <button class="btn btn-dark">
                                    <i class="fa-solid fa-pen-to-square" style="color: #ffffff;"></i>
                                </button>
                            </a>
                            @endif
                        </td> --}}
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script>
$(document).ready(function() {
    let table = new DataTable('#table', {
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });
});


    </script>
</x-app-layout>