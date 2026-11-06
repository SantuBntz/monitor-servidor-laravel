<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor de Servidor</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 2rem; }
        h1 { margin-top: 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
        .card { background: #1e293b; border-radius: 12px; padding: 1rem 1.2rem; }
        .card h2 { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin: 0 0 .5rem; }
        .card p { font-size: 1.25rem; margin: 0; }
        footer { margin-top: 1.5rem; color: #94a3b8; font-size: .85rem; }
    </style>
</head>
<body>
    <h1>Monitor de Servidor</h1>

    @php
        $cards = [
            'hostname'    => 'Equipo',
            'os'          => 'Sistema',
            'php_version' => 'PHP',
            'disk_text'   => 'Disco C:',
            'memory_text' => 'Memoria',
            'cpu_text'    => 'CPU',
            'uptime_text' => 'Uptime',
            'temp_text'   => 'Temperatura',
        ];
    @endphp

    <div class="grid">
        @foreach ($cards as $key => $label)
            <div class="card">
                <h2>{{ $label }}</h2>
                <p data-k="{{ $key }}">{{ $m[$key] }}</p>
            </div>
        @endforeach
    </div>

    <footer>Última actualización: <span id="ts">{{ now()->format('H:i:s') }}</span> · se actualiza cada 5 s</footer>

    <script>
        async function refresh() {
            try {
                const res = await fetch('/api/metrics');
                const d = await res.json();
                document.querySelectorAll('[data-k]').forEach(el => {
                    el.textContent = d[el.dataset.k] ?? '—';
                });
                document.getElementById('ts').textContent = new Date().toLocaleTimeString();
            } catch (e) {
                console.error('Error al actualizar', e);
            }
        }
        setInterval(refresh, 5000);
    </script>
</body>
</html>