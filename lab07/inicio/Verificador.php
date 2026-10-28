<?php
// Soporte reconstruido para los verificadores suministrados. No es el original docente.
// Mantiene sus aserciones; ejecuta un servidor aislado y no sigue redirecciones.
declare(strict_types=1);
final class Verificador
{
    private mixed $proceso = null;
    private string $base = '';
    private string $registro = '';
    private int $total = 0;
    private int $correctas = 0;
    public function servidor(string $public, ?string $router = null, array $entorno = []): void
    {
        $this->detener();
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) { throw new RuntimeException("Puerto: $error"); }
        $direccion = stream_socket_get_name($socket, false);
        fclose($socket);
        $this->base = 'http://' . $direccion;
        $this->registro = tempnam(sys_get_temp_dir(), 'tw2-http-');
        $comando = [PHP_BINARY, '-S', $direccion, '-t', $public];
        if ($router !== null) { $comando[] = $router; }
        $env = array_merge(getenv(), $entorno);
        $this->proceso = proc_open($comando, [0 => ['pipe', 'r'],
            1 => ['file', $this->registro, 'a'], 2 => ['file', $this->registro, 'a']],
            $pipes, dirname($public), $env);
        if (!is_resource($this->proceso)) { throw new RuntimeException('No se pudo iniciar PHP'); }
        fclose($pipes[0]);
        for ($i = 0; $i < 100; $i++) {
            $estado = proc_get_status($this->proceso);
            if (!$estado['running']) { break; }
            $s = @stream_socket_client('tcp://' . $direccion, $n, $msg, 0.1);
            if ($s !== false) { fclose($s); return; }
            usleep(50000);
        }
        $detalle = (string) file_get_contents($this->registro);
        $this->detener();
        throw new RuntimeException("El servidor no inició: $detalle");
    }
    public function pedir(string $ruta, string $metodo = 'GET', array|string $datos = []): array
    {
        if ($this->base === '') { throw new RuntimeException('Primero llama servidor()'); }
        $ch = curl_init($this->base . $ruta);
        $opciones = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $metodo];
        if ($metodo !== 'GET') {
            $archivos = is_array($datos) && count(array_filter($datos, fn($v) => $v instanceof CURLFile)) > 0;
            $opciones[CURLOPT_POSTFIELDS] = $archivos ? $datos : (is_array($datos) ? http_build_query($datos) : $datos);
            if (!$archivos) { $opciones[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded']; }
        }
        curl_setopt_array($ch, $opciones);
        $salida = curl_exec($ch);
        if ($salida === false) { $error = curl_error($ch); curl_close($ch); throw new RuntimeException($error); }
        $estado = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $longitud = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return ['estado' => $estado, 'cabeceras' => substr($salida, 0, $longitud), 'cuerpo' => substr($salida, $longitud)];
    }
    public function revisar(string $nombre, callable $prueba): void
    {
        $this->total++;
        try { $ok = (bool) $prueba(); $detalle = ''; }
        catch (Throwable $e) { $ok = false; $detalle = ' — ' . $e::class . ': ' . $e->getMessage(); }
        if ($ok) { $this->correctas++; }
        echo ($ok ? '[OK] ' : '[FALLO] ') . $nombre . $detalle . PHP_EOL;
    }
    public function fin(): int
    {
        echo "Resultado: {$this->correctas} de {$this->total} comprobaciones" . PHP_EOL;
        $this->detener();
        return $this->correctas === $this->total ? 0 : 1;
    }
    private function detener(): void
    {
        if (is_resource($this->proceso)) { proc_terminate($this->proceso); proc_close($this->proceso); }
        $this->proceso = null;
        if ($this->registro !== '' && is_file($this->registro)) { unlink($this->registro); }
        $this->registro = '';
    }
    public function __destruct() { $this->detener(); }
}
