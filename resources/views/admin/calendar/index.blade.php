@extends('adminlte::page')

@section('title', 'Calendario de Pedidos - Azúcar y Yo')

@section('content_header')
    <h1><i class="fas fa-calendar-alt"></i> Módulo de Calendario y Pedidos</h1>
@stop

@section('content')
<div class="row">
    <!-- Columna del Calendario Visual -->
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body">
                <!-- Aquí se renderizará el calendario interactivo -->
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pedidoModal" tabindex="-1" role="dialog" aria-labelledby="pedidoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div id="pedidoModalContent" class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2 mb-0">Cargando...</p>
            </div>
        </div>
    </div>
</div>
@stop

@section('adminlte_css_pre')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('css')
    <!-- FullCalendar CSS CDN -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        .fc .fc-day-past {
            background: #f4f5f7;
        }

        .fc .fc-day-past .fc-daygrid-day-number {
            color: #9aa0a6;
        }
    </style>
@stop

@section('js')
    <!-- FullCalendar JS CDN -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var modal = $('#pedidoModal');
            var modalContent = document.getElementById('pedidoModalContent');
            var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function showMessage(title, message, link) {
                modalContent.replaceChildren();
                var header = document.createElement('div');
                header.className = 'modal-header';
                var heading = document.createElement('h5');
                heading.className = 'modal-title';
                heading.textContent = title;
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'close';
                close.setAttribute('data-dismiss', 'modal');
                close.setAttribute('aria-label', 'Cerrar');
                close.innerHTML = '<span aria-hidden="true">&times;</span>';
                header.append(heading, close);

                var body = document.createElement('div');
                body.className = 'modal-body';
                var paragraph = document.createElement('p');
                paragraph.className = 'mb-0';
                paragraph.textContent = message;
                body.appendChild(paragraph);

                var footer = document.createElement('div');
                footer.className = 'modal-footer';
                if (link) {
                    var viewLink = document.createElement('a');
                    viewLink.className = 'btn btn-outline-primary';
                    viewLink.href = link;
                    viewLink.textContent = 'Ver pedido';
                    footer.appendChild(viewLink);
                }
                var closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn btn-secondary';
                closeButton.setAttribute('data-dismiss', 'modal');
                closeButton.textContent = 'Cerrar';
                footer.appendChild(closeButton);
                modalContent.append(header, body, footer);
                modal.modal('show');
            }

            async function cargarFormulario(url) {
                modalContent.innerHTML = '<div class="modal-body text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 mb-0">Cargando formulario...</p></div>';
                modal.modal('show');

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) {
                        throw new Error(response.status === 403
                            ? 'No tienes permiso para editar este pedido.'
                            : 'No se pudo cargar el formulario.');
                    }

                    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const content = parsed.querySelector('.modal-content');
                    if (!content) throw new Error('El formulario recibido no tiene un formato válido.');

                    modalContent.replaceWith(content);
                    modalContent = content;
                    window.initPedidoForm(modalContent);
                } catch (error) {
                    showMessage('No se pudo abrir', error.message, null);
                }
            }

            async function enviarFormulario(form) {
                const button = form.querySelector('#btn-guardar');
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const result = await response.json();

                if (!response.ok) {
                    const errores = Object.values(result.errors || {}).flat();
                    throw new Error(errores.join(' ') || result.message || 'No se pudo guardar el pedido.');
                }

                modal.modal('hide');
                calendar.refetchEvents();
                window.setTimeout(function () {
                    window.appToast({
                        type: 'success',
                        message: result.message || 'El pedido quedó registrado correctamente.'
                    });
                }, 250);
                if (button) button.disabled = false;
            }

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (form.matches('#pedidoModal #pedido-form')) {
                    event.preventDefault();
                    enviarFormulario(form).catch(function (error) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger';
                        alert.textContent = error.message;
                        form.prepend(alert);
                        const button = form.querySelector('#btn-guardar');
                        if (button) {
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-save mr-1"></i> Reintentar';
                        }
                    });
                }

                if (form.matches('#pedidoModal .js-cancel-pedido')) {
                    event.preventDefault();
                    window.appConfirm({
                        title: 'Cancelar pedido',
                        message: '¿Cancelar este pedido? Se conservará en el historial.',
                        confirmText: 'Cancelar pedido',
                        destructive: true
                    }).then(function (confirmed) {
                        if (!confirmed) return;
                        fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        }).then(async function (response) {
                            const result = await response.json();
                            if (!response.ok) throw new Error(result.message || 'No se pudo cancelar el pedido.');
                            modal.modal('hide');
                            calendar.refetchEvents();
                            window.appToast({
                                type: 'success',
                                message: result.message || 'El pedido fue cancelado y se conservó en el historial.'
                            });
                        }).catch(function (error) {
                            showMessage('No se pudo cancelar', error.message, null);
                        });
                    });
                }
            });
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                timeZone: 'America/La_Paz',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: "{{ route('admin.calendar.events') }}",
                dateClick: function (info) {
                    const localToday = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/La_Paz' }).format(new Date());

                    if (info.dateStr.slice(0, 10) < localToday) {
                        showMessage('Fecha anterior', 'No se pueden crear pedidos en fechas pasadas.', null);
                        return;
                    }

                    cargarFormulario("{{ route('admin.pedidos.create') }}?modal=1&fecha_entrega=" + encodeURIComponent(info.dateStr.slice(0, 10)));
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    const props = info.event.extendedProps;

                    if (props.tipo === 'pedido') {
                        cargarFormulario(props.edit_url + '?modal=1');
                        return;
                    }

                    showMessage(
                        'Evento de Google Calendar',
                        info.event.title + (props.descripcion ? ' — ' + props.descripcion : ' (evento externo).'),
                        null
                    );
                }
            });
            calendar.render();
        });
    </script>
@stop