using System.Text.Json;
using RelojSync;

Console.OutputEncoding = System.Text.Encoding.UTF8;

if (args.Length >= 2 && args[0] == "probe")
{
    // Solo lectura: RelojSync probe <ip> [puerto] [cuantos]
    string ip = args[1];
    ushort puerto = args.Length > 2 ? ushort.Parse(args[2]) : (ushort)51211;
    int cuantos = args.Length > 3 ? int.Parse(args[3]) : 20;

    var sw = System.Diagnostics.Stopwatch.StartNew();
    using var reloj = new Reloj(ip, puerto);
    Console.WriteLine($"Conectado. DeviceId={reloj.DeviceId} ({sw.ElapsedMilliseconds} ms)");

    var cola = new Queue<Sdk.BS2Event>();
    long total = 0, validas = 0;
    foreach (var lote in reloj.LeerEventos(0))
    {
        foreach (var e in lote)
        {
            total++;
            if (Sdk.EsChecadaValida(e.code)) validas++;
            cola.Enqueue(e);
            if (cola.Count > cuantos) cola.Dequeue();
        }
    }
    Console.WriteLine($"Eventos en el reloj: {total} (checadas válidas: {validas}) en {sw.Elapsed.TotalSeconds:N1} s");
    foreach (var e in cola)
        Console.WriteLine($"  id={e.id,8}  {Sdk.Fecha(e):yyyy-MM-dd HH:mm:ss} UTC  usuario='{Sdk.UserId(e)}'  code=0x{e.code:X4}{(Sdk.EsChecadaValida(e.code) ? "  CHECADA" : "")}");
    return 0;
}

if (args.Length >= 2 && args[0] == "probe1")
{
    // Solo lectura, relojes BioStar 1 (FaceStation): RelojSync probe1 <ip> [puerto] [cuantos]
    int puerto1 = args.Length > 2 ? int.Parse(args[2]) : 1470;
    int cuantos1 = args.Length > 3 ? int.Parse(args[3]) : 20;
    using var reloj1 = new Reloj1(args[1], puerto1);
    var eventos = reloj1.LeerEventos();
    Console.WriteLine($"Conectado. DeviceId={reloj1.DeviceId}. Eventos: {eventos.Count} (checadas válidas: {eventos.Count(e => Sdk1.EsChecadaValida(e.Evento))})");
    foreach (var e in eventos.TakeLast(cuantos1))
        Console.WriteLine($"  {e.FechaLocal:yyyy-MM-dd HH:mm:ss} local  usuario={e.Usuario}  evento=0x{e.Evento:X2}{(Sdk1.EsChecadaValida(e.Evento) ? "  CHECADA" : "")}");
    return 0;
}

if (args.Length >= 2 && args[0] == "probe1u")
{
    int puerto1 = args.Length > 2 ? int.Parse(args[2]) : 1470;
    using var reloj1 = new Reloj1(args[1], puerto1);
    var lista = reloj1.Usuarios();
    Console.WriteLine($"Usuarios en FaceStation: {lista.Count} (con rostro: {lista.Count(u => u.Rostros > 0)})");
    foreach (var u in lista.Take(15))
        Console.WriteLine($"  ID={u.Id,-8} Rostros={u.Rostros} Nombre='{u.Nombre}'");
    return 0;
}

if (args.Length >= 4 && args[0] == "detectar")
{
    // Para el catálogo web: RelojSync detectar <BS2|BS1|HV> <ip> <puerto>  ->  "ID=<deviceId>"
    try
    {
        string id = args[1] switch
        {
            "BS1" => new Func<string>(() => { using var r1 = new Reloj1(args[2], int.Parse(args[3])); return r1.DeviceId.ToString(); })(),
            "HV"  => RelojHanvon.ObtenerId(args[2], int.Parse(args[3])),
            _     => new Func<string>(() => { using var r2 = new Reloj(args[2], ushort.Parse(args[3])); return r2.DeviceId.ToString(); })(),
        };
        if (id == "") throw new InvalidOperationException("El reloj respondió pero no regresó su ID.");
        Console.WriteLine($"ID={id}");
        return 0;
    }
    catch (Exception ex)
    {
        Console.WriteLine($"ERROR={ex.Message}");
        return 1;
    }
}

if (args.Length >= 2 && args[0] == "bs2usuarios")
{
    // Diagnóstico, solo lectura: RelojSync bs2usuarios <ip> [muestra]
    using var r = new Reloj(args[1], 51211);
    var lista = UsuariosBS2.Listar(r);
    Console.WriteLine($"Usuarios: {lista.Count}, con rostro: {lista.Count(u => u.Rostros > 0)}, con huella: {lista.Count(u => u.Huellas > 0)}");
    foreach (var u in lista.Take(8)) Console.WriteLine($"  {u.Id,-10} rostros={u.Rostros} huellas={u.Huellas} tarjetas={u.Tarjetas} '{u.Nombre}'");
    if (args.Length > 2) foreach (var l in UsuariosBS2.Muestra(r, int.Parse(args[2]))) Console.WriteLine("  " + l);
    return 0;
}

if (args.Length >= 4 && args[0] == "hvget")
{
    // Diagnóstico, solo comandos de lectura: RelojSync hvget <ip> <puerto> "GetEmployee(id=\"1\")"
    if (!args[3].StartsWith("Get", StringComparison.Ordinal)) { Console.WriteLine("Solo comandos Get..."); return 1; }
    Console.WriteLine(RelojHanvon.Comando(args[1], int.Parse(args[2]), args[3]));
    return 0;
}

if (args.Length >= 3 && args[0] == "hvinfo")
{
    // Diagnóstico, solo lectura: RelojSync hvinfo <ip> <puerto>
    Console.WriteLine(RelojHanvon.Info(args[1], int.Parse(args[2])));
    return 0;
}

string? Conexion()
{
    string ruta = Path.Combine(AppContext.BaseDirectory, "relojsync.json");
    if (File.Exists(ruta))
        return JsonDocument.Parse(File.ReadAllText(ruta)).RootElement.GetProperty("conexion").GetString()!;
    Console.Error.WriteLine($"Falta {ruta} (ver relojsync.example.json).");
    return null;
}

if (args.Length >= 2 && (args[0] == "hora" || args[0] == "ponerhora" || args[0] == "borrar"))
{
    // Para la pantalla "Comunicación con relojes". Salida KEY=valor para que la lea PHP.
    //   RelojSync hora <deviceId>       solo lectura: hora del reloj y diferencia contra esta PC
    //   RelojSync ponerhora <deviceId>  pone el reloj a la hora de esta PC
    //   RelojSync borrar <deviceId>     DESCARGA todo y luego borra las checadas del reloj
    string? conexion = Conexion();
    if (conexion == null) return 1;
    var s = new Sincronizador(conexion);
    long id = long.Parse(args[1]);
    try
    {
        switch (args[0])
        {
            case "hora":
                var (hora, dif) = s.Hora(id);
                Console.WriteLine($"HORA={hora:yyyy-MM-dd HH:mm:ss}");
                Console.WriteLine($"DIF={dif}");
                break;
            case "ponerhora":
                var antes = s.PonerHora(id);
                Console.WriteLine($"ANTES={(antes == DateTime.MinValue ? "" : antes.ToString("yyyy-MM-dd HH:mm:ss"))}");
                var (nueva, dif2) = s.Hora(id);
                Console.WriteLine($"HORA={nueva:yyyy-MM-dd HH:mm:ss}");
                Console.WriteLine($"DIF={dif2}");
                break;
            case "borrar":
                int guardados = s.Borrar(id);
                Console.WriteLine($"GUARDADOS={guardados}");
                break;
        }
        return 0;
    }
    catch (Exception ex)
    {
        Console.WriteLine($"ERROR={ex.Message}");
        return 1;
    }
}

if (args.Length >= 2 && (args[0] == "usuarios" || args[0] == "alta" || args[0] == "baja" || args[0] == "copiarrostros" || args[0] == "copiarvarios"))
{
    // Usuarios del reloj. Salida: una línea JSON=... para PHP.
    //   RelojSync usuarios <deviceId>
    //   RelojSync alta <deviceId> <archivo.json [{"id":"..","nombre":".."}]>
    //   RelojSync baja <deviceId> <archivo.json ["id",...]>
    //   RelojSync copiarrostros <origen> <destino> <archivo.json ["id",...] | todos> [reemplazar]
    string? conexion = Conexion();
    if (conexion == null) return 1;
    var s = new Sincronizador(conexion);
    var opciones = new JsonSerializerOptions { PropertyNamingPolicy = JsonNamingPolicy.CamelCase };
    try
    {
        object r;
        long id = long.Parse(args[1]);
        switch (args[0])
        {
            case "usuarios":
                r = s.Usuarios(id);
                break;
            case "alta":
                var altas = JsonDocument.Parse(File.ReadAllText(args[2])).RootElement.EnumerateArray()
                    .Select(e => (e.GetProperty("id").GetString()!, e.GetProperty("nombre").GetString() ?? "")).ToList();
                r = s.Alta(id, altas);
                break;
            case "baja":
                r = s.Baja(id, LeerIds(args[2])!);
                break;
            case "copiarvarios":
                // copiarvarios <origen> <destinos.json [deviceId,...]> [reemplazar] [consinrostro] [archivo_progreso.json]
                var destinos = JsonDocument.Parse(File.ReadAllText(args[2])).RootElement.EnumerateArray()
                    .Select(e => e.ValueKind == JsonValueKind.String ? long.Parse(e.GetString()!) : e.GetInt64()).ToList();
                bool reemplazarV = args.Skip(3).Any(a => a == "reemplazar");
                bool conSinRostroV = args.Skip(3).Any(a => a == "consinrostro");
                bool borrarV = args.Skip(3).Any(a => a == "borrarprimero");
                string? progresoV = args.Skip(3).FirstOrDefault(a => a.EndsWith(".json"));
                s.CopiarVarios(id, destinos, reemplazarV, conSinRostroV, progresoV, borrarV);
                Console.WriteLine("JSON={\"ok\":true}");
                return 0;
            default:
                // copiarrostros <origen> <destino> <todos|archivo.json> [reemplazar] [consinrostro] [archivo_progreso.json]
                bool reemplazar = args.Skip(4).Any(a => a == "reemplazar");
                bool conSinRostro = args.Skip(4).Any(a => a == "consinrostro");
                string? progreso = args.Skip(4).FirstOrDefault(a => a.EndsWith(".json"));
                r = s.CopiarRostros(id, long.Parse(args[2]), args[3] == "todos" ? null : LeerIds(args[3]), reemplazar, progreso, conSinRostro);
                break;
        }
        Console.WriteLine("JSON=" + JsonSerializer.Serialize(r, opciones));
        return 0;
    }
    catch (Exception ex)
    {
        Console.WriteLine($"ERROR={ex.Message}");
        return 1;
    }
}

static List<string>? LeerIds(string archivo) =>
    JsonDocument.Parse(File.ReadAllText(archivo)).RootElement.EnumerateArray().Select(e => e.GetString()!).ToList();

if (args.Length >= 1 && (args[0] == "sync" || args[0] == "descargar"))
{
    // RelojSync sync [deviceId]       descarga y pasa a dbo.checadas
    // RelojSync descargar [deviceId]  solo descarga a dbo.RelojEvento
    string? conexion = Conexion();
    if (conexion == null) return 1;
    long? deviceId = args.Length > 1 ? long.Parse(args[1]) : null;

    try
    {
        return new Sincronizador(conexion).Ejecutar(deviceId, procesar: args[0] == "sync");
    }
    catch (Exception ex)
    {
        Console.Error.WriteLine($"{DateTime.Now:yyyy-MM-dd HH:mm:ss} ERROR: {ex.Message}");
        return 1;
    }
}

Console.Error.WriteLine("Uso:\n  RelojSync sync [deviceId]\n  RelojSync descargar [deviceId]\n  RelojSync probe <ip> [puerto] [cuantos]\n  RelojSync probe1 <ip> [puerto] [cuantos]   (FaceStation / BioStar 1)");
return 1;
