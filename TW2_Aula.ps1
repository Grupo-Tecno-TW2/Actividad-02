[CmdletBinding()]
param(
    [ValidateSet('Iniciar','Verificar','Sintaxis','Parar','Estado')]
    [string]$Accion = 'Iniciar',
    [ValidateRange(1,9)][int]$Laboratorio = 6,
    [string]$Raiz = $PSScriptRoot,
    [ValidateRange(1024,65535)][int]$Puerto = 8000
)
$ErrorActionPreference = 'Stop'
function Ejecutar-Docker {
    param([string[]]$Argumentos)
    & docker @Argumentos
    if ($LASTEXITCODE -ne 0) { throw "Docker fallo. Revise el mensaje anterior antes de continuar." }
}
function Detener-TW2 {
    $ids = @(& docker ps -q)
    if ($LASTEXITCODE -ne 0) { throw 'Abra Docker Desktop y espere a que el motor funcione.' }
    foreach ($id in $ids) {
        if ([string]::IsNullOrWhiteSpace($id)) { continue }
        $info = (& docker inspect $id | ConvertFrom-Json)[0]
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el contenedor.' }
        $proyecto = [string]$info.Config.Labels.'com.docker.compose.project'
        $nombre = [string]$info.Name
        $esTW2 = ($proyecto -match '^tw2($|[-_])') -or ($nombre -match '^/tw2([-_]|$)')
        if (-not $esTW2) {
            foreach ($montaje in $info.Mounts) {
                if ([string]$montaje.Source -match '(?i)(^|[/\\])TW2([/\\]|$)') { $esTW2 = $true }
            }
        }
        if ($esTW2) {
            Write-Host "Deteniendo $nombre sin borrar archivos ni volumenes..."
            Ejecutar-Docker -Argumentos @('stop',$id)
        }
    }
}
try {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) { throw 'Falta Docker Desktop o docker no esta en PATH.' }
    Ejecutar-Docker -Argumentos @('info','--format','{{.ServerVersion}}')
    if ($Accion -eq 'Parar') { Detener-TW2; exit 0 }
    if ($Accion -eq 'Estado') { Ejecutar-Docker -Argumentos @('ps','-a','--format','table {{.Names}}\t{{.Status}}\t{{.Ports}}'); exit 0 }
    if (-not (Test-Path -LiteralPath $Raiz -PathType Container)) { throw "No existe la raiz $Raiz." }
    $Raiz = (Resolve-Path -LiteralPath $Raiz).Path
    $labNombre = 'lab{0:D2}' -f $Laboratorio
    $labCarpeta = Join-Path $Raiz $labNombre
    $carpeta = Join-Path $labCarpeta 'inicio'
    if (-not (Test-Path (Join-Path $carpeta 'verificar.php'))) {
        $origen = Join-Path $PSScriptRoot "$labNombre/inicio"
        if ((Test-Path $labCarpeta) -or -not (Test-Path $origen)) {
            throw "No se encontro $carpeta\verificar.php. Revise la ruta; no se reemplazaron ejercicios."
        }
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot $labNombre) -Destination $labCarpeta -Recurse
        Write-Host "Se agrego $labNombre con sus TODO iniciales."
    }
    $compose = Join-Path $carpeta 'docker-compose.yml'
    if ($Accion -eq 'Iniciar') {
        foreach ($rel in @('Dockerfile','docker/php.ini','comun/Verificador.php')) {
            $destino = Join-Path $Raiz $rel
            if (-not (Test-Path -LiteralPath $destino)) {
                $fuente = Join-Path $PSScriptRoot $rel
                if (-not (Test-Path -LiteralPath $fuente)) { throw "Falta $fuente en el kit." }
                New-Item -ItemType Directory -Path (Split-Path $destino) -Force | Out-Null
                Copy-Item -LiteralPath $fuente -Destination $destino
            }
        }
        $plantilla = Join-Path $PSScriptRoot "entorno/compose-$labNombre.yml"
        if (-not (Test-Path -LiteralPath $plantilla)) { throw "Falta la plantilla $plantilla." }
        $nuevo = [IO.File]::ReadAllText($plantilla).Replace('127.0.0.1:8000:8000',"127.0.0.1:${Puerto}:8000")
        if (Test-Path -LiteralPath $compose) {
            $actual = [IO.File]::ReadAllText($compose)
            if ($actual -ne $nuevo) {
                Copy-Item -LiteralPath $compose -Destination "$compose.respaldo-$(Get-Date -Format yyyyMMdd-HHmmss)"
            }
        }
        [IO.File]::WriteAllText($compose,$nuevo,(New-Object Text.UTF8Encoding($false)))
    }
    if (-not (Test-Path -LiteralPath $compose)) { throw 'Primero use la accion Iniciar para preparar Compose.' }
    $base = @('compose','-f',$compose)
    if ($Accion -eq 'Iniciar') {
        Ejecutar-Docker -Argumentos ($base + @('config','--quiet'))
        Detener-TW2
        Write-Host "Iniciando laboratorio $Laboratorio desde $carpeta..."
        # Solo construye cuando no existe la imagen comun.
        $imagenes = @(& docker image ls --format '{{.Repository}}:{{.Tag}}')
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar las imagenes.' }
        if ($imagenes -notcontains 'tw2-php:8.3') { Ejecutar-Docker -Argumentos ($base + @('build','php')) }
        & docker @base up -d --wait --wait-timeout 180
        if ($LASTEXITCODE -ne 0) {
            & docker @base ps
            & docker @base logs --tail 50
            throw 'El arranque fallo. No ejecute Composer ni los verificadores hasta resolverlo.'
        }
        Ejecutar-Docker -Argumentos ($base + @('exec','-T','php','php','-v'))
        if ($Laboratorio -ge 6) {
            Ejecutar-Docker -Argumentos ($base + @('exec','-T','php','composer','dump-autoload'))
        }
        $url = "http://127.0.0.1:$Puerto/"
        if ($Laboratorio -ge 8) { $url += 'productos' }
        Start-Process $url
        Write-Host "LISTO. Edite $carpeta y guarde todos los archivos antes de verificar."
        Write-Host "Pruebas: use este script con -Accion Verificar -Laboratorio $Laboratorio -Raiz `"$Raiz`"."
    } elseif ($Accion -eq 'Verificar') {
        Write-Host 'El verificador puede restaurar datos de laboratorio. Guarde antes lo que quiera conservar.'
        $evidencias = Join-Path $carpeta 'evidencias'
        New-Item -ItemType Directory -Path $evidencias -Force | Out-Null
        $salida = Join-Path $evidencias ("verificacion-$(Get-Date -Format yyyyMMdd-HHmmss).txt")
        & docker @base exec -T php php verificar.php | Tee-Object -FilePath $salida
        $codigoSalida = $LASTEXITCODE
        Write-Host "Resultado guardado en $salida"
        exit $codigoSalida
    } elseif ($Accion -eq 'Sintaxis') {
        $fallos = 0
        foreach ($sub in @('src','public','plantillas','config')) {
            $ruta = Join-Path $carpeta $sub
            if (-not (Test-Path $ruta)) { continue }
            foreach ($archivo in (Get-ChildItem -LiteralPath $ruta -Filter '*.php' -Recurse -File)) {
                $relativa = $archivo.FullName.Substring($carpeta.Length+1).Replace('\','/')
                & docker @base exec -T php php -l $relativa
                if ($LASTEXITCODE -ne 0) { $fallos++ }
            }
        }
        if ($fallos -gt 0) { throw "Hay $fallos archivo(s) con problemas de sintaxis." }
        Write-Host 'Sintaxis revisada. Falta comprobar comportamiento con Verificar.'
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
