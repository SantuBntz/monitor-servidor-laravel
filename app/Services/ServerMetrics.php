<?php

namespace App\Services;

use Carbon\Carbon;

class ServerMetrics
{
    // Script FIJO: nunca se le agregan datos del usuario (seguridad).
    private const SCRIPT = <<<'POWERSHELL'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$os   = Get-CimInstance Win32_OperatingSystem
$cpu  = Get-CimInstance Win32_Processor | Select-Object -First 1
$disk = Get-CimInstance Win32_LogicalDisk -Filter "DeviceID='C:'"
$temp = $null
try {
    $t = Get-CimInstance -Namespace root/wmi -ClassName MSAcpi_ThermalZoneTemperature -ErrorAction Stop | Select-Object -First 1
    if ($t) { $temp = [math]::Round(($t.CurrentTemperature / 10) - 273.15, 1) }
} catch {}
[PSCustomObject]@{
    hostname     = $env:COMPUTERNAME
    os           = $os.Caption
    mem_total_kb = $os.TotalVisibleMemorySize
    mem_free_kb  = $os.FreePhysicalMemory
    cpu_name     = $cpu.Name
    cpu_load     = $cpu.LoadPercentage
    disk_total   = $disk.Size
    disk_free    = $disk.FreeSpace
    last_boot    = $os.LastBootUpTime.ToString('o')
    cpu_temp_c   = $temp
} | ConvertTo-Json -Compress
POWERSHELL;

    private function readWindows(): array
    {
        $encoded = base64_encode(mb_convert_encoding(self::SCRIPT, 'UTF-16LE', 'UTF-8'));
        $raw = shell_exec('powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand ' . $encoded);

        return json_decode((string) $raw, true) ?? [];
    }

    public function snapshot(): array
    {
        $d   = $this->readWindows();
        $gb  = fn ($bytes) => round($bytes / 1024 ** 3, 1);
        $pct = fn ($used, $total) => $total > 0 ? round($used / $total * 100, 1) : 0;

        // La memoria viene en KB; el disco, en bytes.
        $memTotal = ($d['mem_total_kb'] ?? 0) * 1024;
        $memUsed  = $memTotal - (($d['mem_free_kb'] ?? 0) * 1024);
        $diskTotal = $d['disk_total'] ?? 0;
        $diskUsed  = $diskTotal - ($d['disk_free'] ?? 0);

        $secs = isset($d['last_boot'])
            ? max(0, now()->timestamp - Carbon::parse($d['last_boot'])->timestamp)
            : 0;

        $temp = $d['cpu_temp_c'] ?? null;

        return [
            'hostname'     => $d['hostname'] ?? 'Desconocido',
            'os'           => $d['os'] ?? 'Desconocido',
            'php_version'  => PHP_VERSION,
            'cpu_name'     => $d['cpu_name'] ?? 'Desconocido',
            'cpu_load'     => $d['cpu_load'] ?? 0,
            'mem_used_pct' => $pct($memUsed, $memTotal),
            'disk_used_pct'=> $pct($diskUsed, $diskTotal),
            'uptime_seconds' => $secs,
            'cpu_temp_c'   => $temp,

            // Textos listos para mostrar en las tarjetas
            'cpu_text'    => ($d['cpu_load'] ?? 0) . ' %',
            'memory_text' => $gb($memUsed) . ' / ' . $gb($memTotal) . ' GB (' . $pct($memUsed, $memTotal) . ' %)',
            'disk_text'   => $gb($diskUsed) . ' / ' . $gb($diskTotal) . ' GB (' . $pct($diskUsed, $diskTotal) . ' %)',
            'uptime_text' => sprintf('%dd %dh %dm', intdiv($secs, 86400), intdiv($secs % 86400, 3600), intdiv($secs % 3600, 60)),
            'temp_text'   => $temp !== null ? $temp . ' °C' : 'No disponible',
        ];
    }
}