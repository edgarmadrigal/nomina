using System.Data;
using Microsoft.Data.SqlClient;

namespace RelojSync;

/// <summary>
/// Descarga los eventos nuevos de cada reloj activo de dbo.RelojDispositivo,
/// los guarda en dbo.RelojEvento y ejecuta dbo.ProcesaChecadasReloj.
/// </summary>
internal sealed class Sincronizador(string conexion)
{
    /// <param name="EventoIdBase">Se suma al id de evento de los BS2 al guardarlo. Cambia cada vez que se
    /// borra el registro del reloj, para que si el reloj reinicia la numeración las checadas nuevas no
    /// choquen con las viejas en la PK de RelojEvento (se descartarían como repetidas).</param>
    record Dispositivo(long DeviceId, string Descripcion, string Ip, int Puerto, string ZonaHoraria, long UltimoEventoId, string Sdk, long EventoIdBase);

    public int Ejecutar(long? soloDeviceId, bool procesar = true)
    {
        int errores = 0;
        foreach (var d in Dispositivos(soloDeviceId))
        {
            try
            {
                DateTime inicio = HoraServidor();
                int n = DescargarConReintentos(d);
                int checadas = ActualizarEstado(d.DeviceId, null, inicio);
                Log($"{d.Descripcion} ({d.Ip}): {n} eventos nuevos, {checadas} checadas.");
            }
            catch (Exception ex)
            {
                errores++;
                string msg = Explicar(ex.Message);
                Log($"ERROR {d.Descripcion} ({d.Ip}): {msg}");
                ActualizarEstado(d.DeviceId, msg, null);
            }
        }

        if (!procesar) return errores == 0 ? 0 : 2;
        var r = Procesar(soloDeviceId);
        Log($"Checadas insertadas: {r.insertadas}, ya existían: {r.yaExistian}, repetidas en 2 empresas: {r.repetidos}.");
        return errores == 0 ? 0 : 2;
    }

    const int INTENTOS = 3;

    /// <summary>
    /// Algunos relojes rechazan la conexión de vez en cuando (red inestable, o
    /// Nomiplus conectado al mismo tiempo): se reintenta antes de marcar error.
    /// Lo ya guardado de un intento fallido no se duplica (PK de RelojEvento).
    /// </summary>
    int DescargarConReintentos(Dispositivo d)
    {
        for (int intento = 1; ; intento++)
        {
            try { return Descargar(d); }
            catch (Exception ex) when (intento < INTENTOS && EsErrorDeConexion(ex))
            {
                Log($"{d.Descripcion} ({d.Ip}): intento {intento} falló ({ex.Message}), reintentando...");
                Thread.Sleep(3000);
            }
        }
    }

    static bool EsErrorDeConexion(Exception ex) =>
        ex is not SqlException && (ex.Message.Contains("conectar") || ex.Message.Contains("código -1") ||
                                   ex is System.Net.Sockets.SocketException || ex is IOException || ex is AggregateException);

    /// <summary>Traduce los errores técnicos a una explicación para el usuario (se conserva el detalle técnico).</summary>
    static string Explicar(string msg)
    {
        string? causa = null;
        if (msg.Contains("se esperaba"))
            causa = "En esa IP respondió otro reloj. Revisa la IP en el catálogo.";
        else if (msg.Contains("código -102") || msg.Contains("código -101") || msg.Contains("No se pudo conectar") || msg.Contains("No connection could be made"))
            causa = $"El reloj no aceptó la conexión después de {INTENTOS} intentos. Puede estar apagado, sin red, " +
                    "o atendiendo otra conexión en ese momento (por ejemplo Nomiplus). Intenta de nuevo en unos minutos.";
        else if (msg.Contains("código -105") || msg.Contains("código -106") || msg.Contains("código -107") || msg.Contains("código -2"))
            causa = "Se perdió la comunicación con el reloj a media descarga (red inestable). Lo ya descargado se guardó; intenta de nuevo.";
        else if (msg.Contains("SQL") || msg.Contains("database") || msg.Contains("servidor"))
            causa = "No se pudo guardar en la base de datos.";
        return causa == null ? msg : $"{causa} [Detalle: {msg}]";
    }

    List<Dispositivo> Dispositivos(long? soloDeviceId, bool soloActivos = true)
    {
        using var cn = Abrir();
        using var cmd = new SqlCommand(
            "SELECT deviceId, ISNULL(descripcion,''), ip, puerto, zonaHoraria, ultimoEventoId, sdk, eventoIdBase " +
            "FROM dbo.RelojDispositivo WHERE (activo = 1 OR @todos = 1) AND (@id IS NULL OR deviceId = @id)", cn);
        cmd.Parameters.Add("@id", SqlDbType.BigInt).Value = (object?)soloDeviceId ?? DBNull.Value;
        cmd.Parameters.Add("@todos", SqlDbType.Bit).Value = !soloActivos;
        var lista = new List<Dispositivo>();
        using var rd = cmd.ExecuteReader();
        while (rd.Read())
            lista.Add(new(rd.GetInt64(0), rd.GetString(1), rd.GetString(2), rd.GetInt32(3), rd.GetString(4), rd.GetInt64(5), rd.GetString(6), rd.GetInt64(7)));
        return lista;
    }

    Dispositivo Uno(long deviceId) =>
        Dispositivos(deviceId, soloActivos: false).FirstOrDefault()
        ?? throw new InvalidOperationException($"No existe el reloj {deviceId} en el catálogo.");

    // ------------------------------------------------------------------ comunicación (hora / borrar)

    /// <summary>Hora del reloj (en su hora local) y la diferencia en segundos contra la hora de esta PC.</summary>
    public (DateTime horaReloj, int diferencia) Hora(long deviceId)
    {
        var d = Uno(deviceId);
        var tz = TimeZoneInfo.FindSystemTimeZoneById(d.ZonaHoraria);
        var ahora = TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz);
        DateTime reloj;
        switch (d.Sdk)
        {
            case "BS1":
                using (var r1 = Conectar1(d)) reloj = r1.HoraLocal();
                break;
            case "HV":
                reloj = RelojHanvon.Hora(d.Ip, d.Puerto) ?? throw new InvalidOperationException("Este reloj no informa su hora.");
                break;
            default:
                using (var r2 = Conectar2(d)) reloj = TimeZoneInfo.ConvertTimeFromUtc(r2.HoraUtc(), tz);
                break;
        }
        return (reloj, (int)Math.Round((reloj - ahora).TotalSeconds));
    }

    /// <summary>Pone el reloj a la hora de esta PC (en la zona horaria del reloj). Regresa la hora anterior.</summary>
    public DateTime PonerHora(long deviceId)
    {
        var d = Uno(deviceId);
        var tz = TimeZoneInfo.FindSystemTimeZoneById(d.ZonaHoraria);
        DateTime antes;
        switch (d.Sdk)
        {
            case "BS1":
                using (var r1 = Conectar1(d))
                {
                    antes = r1.HoraLocal();
                    r1.PonerHoraLocal(TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz));
                }
                break;
            case "HV":
                antes = RelojHanvon.Hora(d.Ip, d.Puerto) ?? DateTime.MinValue;
                RelojHanvon.PonerHora(d.Ip, d.Puerto, TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz));
                break;
            default:
                using (var r2 = Conectar2(d))
                {
                    antes = TimeZoneInfo.ConvertTimeFromUtc(r2.HoraUtc(), tz);
                    r2.PonerHoraUtc(DateTime.UtcNow);
                }
                break;
        }
        return antes;
    }

    /// <summary>
    /// Borra las checadas del reloj, pero ANTES las descarga todas y las guarda en RelojEvento
    /// (y las procesa a dbo.checadas). Si la descarga falla, no se borra nada.
    /// Regresa cuántos eventos se guardaron en la descarga previa.
    /// </summary>
    public int Borrar(long deviceId)
    {
        var d = Uno(deviceId);
        DateTime inicio = HoraServidor();
        int guardados = d.Sdk switch
        {
            "BS1" => BorrarBS1(d),
            "HV"  => BorrarHV(d),
            _     => BorrarBS2(d),
        };
        ActualizarEstado(d.DeviceId, null, inicio);
        Procesar(d.DeviceId);
        return guardados;
    }

    int BorrarBS2(Dispositivo d)
    {
        var tz = TimeZoneInfo.FindSystemTimeZoneById(d.ZonaHoraria);
        using var reloj = Conectar2(d);

        // Descarga lo pendiente en la misma conexión hasta que ya no llegue nada nuevo,
        // y borra inmediatamente para dejar la ventana más chica posible.
        long cursor = d.UltimoEventoId;
        int guardados = 0;
        for (int vuelta = 1; ; vuelta++)
        {
            int leidos = 0;
            foreach (var lote in reloj.LeerEventos((uint)cursor))
            {
                guardados += GuardarLoteBS2(d, lote, tz);
                cursor = lote[^1].id;
                leidos += lote.Count;
            }
            if (leidos == 0) break;
            if (vuelta >= 5)
                throw new InvalidOperationException("El reloj sigue recibiendo checadas; no se borró nada. Intenta en un momento.");
        }

        reloj.BorrarLog();

        // A partir de aquí el reloj puede reiniciar la numeración: se mueve la base por encima
        // de todo lo guardado y se reinicia el cursor.
        using var cn = Abrir();
        using var cmd = new SqlCommand(@"
            UPDATE dbo.RelojDispositivo
            SET eventoIdBase = (SELECT ISNULL(MAX(eventoId), 0) + 1 FROM dbo.RelojEvento WHERE deviceId = @d),
                ultimoEventoId = 0
            WHERE deviceId = @d;", cn);
        cmd.Parameters.Add("@d", SqlDbType.BigInt).Value = d.DeviceId;
        cmd.ExecuteNonQuery();
        return guardados;
    }

    int BorrarBS1(Dispositivo d)
    {
        using var reloj = Conectar1(d);
        int guardados = 0;
        int cuenta = -1;
        for (int vuelta = 1; ; vuelta++)
        {
            var eventos = reloj.LeerEventos();
            guardados += GuardarLocales(d, eventos);        // todo el log; la PK descarta lo ya guardado
            cuenta = eventos.Count;
            if (reloj.ContarEventos() <= cuenta) break;      // no entró nada mientras se guardaba
            if (vuelta >= 5)
                throw new InvalidOperationException("El reloj sigue recibiendo checadas; no se borró nada. Intenta en un momento.");
        }
        reloj.BorrarLog(cuenta);
        return guardados;
    }

    int BorrarHV(Dispositivo d)
    {
        // todas las checadas que tenga el reloj (la PK descarta lo ya guardado)
        var (devId, eventos) = RelojHanvon.LeerEventos(d.Ip, d.Puerto, new DateTime(2000, 1, 1));
        if (devId != "" && devId != d.DeviceId.ToString())
            throw new InvalidOperationException($"En {d.Ip} respondió el reloj {devId}, se esperaba {d.DeviceId}.");
        int guardados = GuardarLocales(d, eventos);
        RelojHanvon.BorrarRegistros(d.Ip, d.Puerto);
        return guardados;
    }

    // ------------------------------------------------------------------ usuarios del reloj

    const string SIN_ALTAS_BS1 = "El FaceStation de primera generación requiere enrolar al empleado con su rostro directamente en el reloj.";

    public List<UsuarioReloj> Usuarios(long deviceId)
    {
        var d = Uno(deviceId);
        switch (d.Sdk)
        {
            case "HV": return RelojHanvon.Usuarios(d.Ip, d.Puerto);
            case "BS1":
                using (var r = Conectar1(d)) return r.Usuarios();
            default:
                using (var r = Conectar2(d)) return UsuariosBS2.Listar(r);
        }
    }

    public ResultadoUsuarios Alta(long deviceId, IList<(string id, string nombre)> lista)
    {
        var d = Uno(deviceId);
        switch (d.Sdk)
        {
            case "HV": return RelojHanvon.Alta(d.Ip, d.Puerto, lista);
            case "BS1": throw new InvalidOperationException(SIN_ALTAS_BS1);
            default:
                using (var r = Conectar2(d)) return UsuariosBS2.Alta(r, lista);
        }
    }

    public ResultadoUsuarios Baja(long deviceId, IList<string> ids)
    {
        var d = Uno(deviceId);
        switch (d.Sdk)
        {
            case "HV": return RelojHanvon.Baja(d.Ip, d.Puerto, ids);
            case "BS1":
                using (var r = Conectar1(d)) return r.Baja(ids);
            default:
                using (var r = Conectar2(d)) return UsuariosBS2.Baja(r, ids);
        }
    }

    /// <summary>Copia usuarios con rostro entre dos relojes BioStar 2 (mismo formato de rostro).</summary>
    /// <param name="progresoPath">Si se indica, se va escribiendo el avance (JSON) en ese archivo para que
    /// la web lo muestre en una barra. La operación corre en UNA sola conexión por reloj (mínimo churn) y
    /// reintenta/reconecta ante caídas (-907), que es lo que la hacía fallar en lotes grandes.</param>
    public ResultadoUsuarios CopiarRostros(long origenId, long destinoId, IList<string>? ids, bool reemplazar, string? progresoPath = null, bool incluirSinRostro = false, bool marcarFin = true, string? lote = null, bool borrarDestino = false)
    {
        var o = Uno(origenId);
        var d = Uno(destinoId);
        if (o.Sdk != "BS2" || d.Sdk != "BS2")
            throw new InvalidOperationException("Los rostros solo se pueden copiar entre relojes BioStar 2 (cada marca guarda el rostro en otro formato).");
        if (o.DeviceId == d.DeviceId) throw new InvalidOperationException("El origen y el destino son el mismo reloj.");

        void Escribir(bool activo, int hechos, int yaTenian, int sinRostro, int errores, int total, string fase)
        {
            if (progresoPath == null) return;
            int pct = total > 0 ? (int)Math.Round(100.0 * hechos / total) : 0;
            string Esc(string s) => s.Replace("\\", "\\\\").Replace("\"", "\\\"");
            string fin = activo ? "null" : $"\"{DateTime.Now:yyyy-MM-dd HH:mm:ss}\"";
            string loteJson = lote == null ? "null" : $"\"{Esc(lote)}\"";
            string json = "{" +
                $"\"activo\":{(activo ? "true" : "false")}," +
                $"\"origen\":\"{Esc(o.Descripcion)}\",\"destino\":\"{Esc(d.Descripcion)}\",\"lote\":{loteJson}," +
                $"\"total\":{total},\"copiados\":{hechos},\"yaTenian\":{yaTenian},\"sinRostro\":{sinRostro},\"errores\":{errores}," +
                $"\"pct\":{pct},\"fase\":\"{fase}\",\"actualizado\":\"{DateTime.Now:yyyy-MM-dd HH:mm:ss}\",\"fin\":{fin}" +
                "}";
            try { File.WriteAllText(progresoPath, json); } catch { /* el progreso es informativo */ }
        }

        Escribir(true, 0, 0, 0, 0, 0, "leyendo");
        // Últimos conteos reportados: cuando se cae la señal (reconectando) se re-escriben con
        // la fase "reconectando" para que la barra no se quite y muestre que sigue intentando.
        int lastH = 0, lastY = 0, lastS = 0, lastE = 0, lastT = 0;
        UsuariosBS2.OnReconectando = _ => Escribir(true, lastH, lastY, lastS, lastE, lastT, "reconectando");
        try
        {
            using var ro = Conectar2(o);
            using var rd = Conectar2(d);
            var res = UsuariosBS2.CopiarRostros(ro, rd, ids, reemplazar,
                (hechos, ya, sin, err, total, fase) =>
                {
                    lastH = hechos; lastY = ya; lastS = sin; lastE = err; lastT = total;
                    Escribir(true, hechos, ya, sin, err, total, fase);
                },
                incluirSinRostro, borrarDestino);
            // En un lote de varios relojes, solo el último marca "terminado"; los demás quedan "activo"
            // para que la barra no parpadee como si ya hubiera acabado todo.
            if (marcarFin)
                Escribir(false, res.Hechos.Count, res.YaEstaban.Count, res.SinRostro.Count, res.Errores.Count,
                         res.Hechos.Count + res.Errores.Count, "terminado");
            else
                Escribir(true, res.Hechos.Count, res.YaEstaban.Count, res.SinRostro.Count, res.Errores.Count,
                         res.Hechos.Count + res.Errores.Count, "copiando");
            return res;
        }
        catch
        {
            if (marcarFin) Escribir(false, 0, 0, 0, 0, 0, "error");
            throw;
        }
        finally
        {
            UsuariosBS2.OnReconectando = null;
        }
    }

    /// <summary>Copia (todos los usuarios con rostro, y opcionalmente los que no, como alta) desde un
    /// reloj principal hacia VARIOS relojes BioStar 2, uno por uno. Escribe el progreso en el camino.</summary>
    public void CopiarVarios(long origenId, IList<long> destinos, bool reemplazar, bool incluirSinRostro, string? progresoPath, bool borrarDestino = false)
    {
        int n = destinos.Count;
        for (int i = 0; i < n; i++)
        {
            bool ultimo = i == n - 1;
            string lote = $"Reloj {i + 1} de {n}";
            try
            {
                CopiarRostros(origenId, destinos[i], null, reemplazar, progresoPath, incluirSinRostro, marcarFin: ultimo, lote: lote, borrarDestino: borrarDestino);
            }
            catch (Exception ex)
            {
                Console.Error.WriteLine($"ERROR en destino {destinos[i]}: {ex.Message}");
                // seguimos con los demás relojes
            }
        }
    }

    static Reloj Conectar2(Dispositivo d)
    {
        // a veces el reloj rechaza la primera conexión (está atendiendo a otro): se reintenta
        Reloj r;
        for (int intento = 1; ; intento++)
        {
            try { r = new Reloj(d.Ip, (ushort)d.Puerto); break; }
            catch (InvalidOperationException) when (intento < 3) { Thread.Sleep(3000); }
        }
        if (r.DeviceId != d.DeviceId)
        {
            r.Dispose();
            throw new InvalidOperationException($"En {d.Ip} respondió el reloj {r.DeviceId}, se esperaba {d.DeviceId}.");
        }
        return r;
    }

    static Reloj1 Conectar1(Dispositivo d)
    {
        var r = new Reloj1(d.Ip, d.Puerto);
        if (r.DeviceId != d.DeviceId)
        {
            r.Dispose();
            throw new InvalidOperationException($"En {d.Ip} respondió el reloj {r.DeviceId}, se esperaba {d.DeviceId}.");
        }
        return r;
    }

    int Descargar(Dispositivo d) => d.Sdk switch
    {
        "BS1" => DescargarBS1(d),
        "HV"  => DescargarHV(d),
        _     => DescargarBS2(d),
    };

    int DescargarBS2(Dispositivo d)
    {
        var tz = TimeZoneInfo.FindSystemTimeZoneById(d.ZonaHoraria);
        using var reloj = Conectar2(d);

        // Al borrar el registro desde el sistema (Borrar) se mueve eventoIdBase y el cursor
        // vuelve a 0, así que no importa si el reloj reinicia su numeración.
        int total = 0;
        foreach (var lote in reloj.LeerEventos((uint)d.UltimoEventoId))
            total += GuardarLoteBS2(d, lote, tz);
        return total;
    }

    /// <summary>Guarda un lote de eventos BS2 (id + eventoIdBase) y avanza el cursor al último id del reloj.</summary>
    int GuardarLoteBS2(Dispositivo d, List<Sdk.BS2Event> lote, TimeZoneInfo tz)
    {
        var tabla = NuevaTabla();
        foreach (var e in lote)
        {
            var utc = Sdk.Fecha(e);
            tabla.Rows.Add(d.DeviceId, d.EventoIdBase + e.id, utc,
                TimeZoneInfo.ConvertTimeFromUtc(DateTime.SpecifyKind(utc, DateTimeKind.Utc), tz),
                Sdk.UserId(e), (int)e.code, Sdk.EsChecadaValida(e.code));
        }
        return Guardar(tabla, d.DeviceId, lote[^1].id);
    }

    /// <summary>
    /// FaceStation (BioStar 1): no hay id de evento, se lee todo el log y se guardan
    /// los eventos desde el segundo del último descargado (la PK descarta los repetidos).
    /// </summary>
    int DescargarBS1(Dispositivo d)
    {
        using var reloj = Conectar1(d);

        uint desde = Evento1.FechaDeId(d.UltimoEventoId);
        return GuardarLocales(d, reloj.LeerEventos().Where(e => e.Fecha >= desde).ToList());
    }

    /// <summary>FaceGo / Hanvon: se piden las checadas desde el segundo de la última descargada.</summary>
    int DescargarHV(Dispositivo d)
    {
        var desde = DateTime.UnixEpoch.AddSeconds(Evento1.FechaDeId(d.UltimoEventoId));
        if (d.UltimoEventoId == 0) desde = new DateTime(2000, 1, 1);
        var (devId, eventos) = RelojHanvon.LeerEventos(d.Ip, d.Puerto, desde);
        if (devId != "" && devId != d.DeviceId.ToString())
            throw new InvalidOperationException($"En {d.Ip} respondió el reloj {devId}, se esperaba {d.DeviceId}.");
        return GuardarLocales(d, eventos);
    }

    /// <summary>Guarda eventos cuya fecha ya viene en hora local (BioStar 1 y Hanvon).</summary>
    int GuardarLocales(Dispositivo d, List<Evento1> nuevos)
    {
        if (nuevos.Count == 0) return 0;
        var tz = TimeZoneInfo.FindSystemTimeZoneById(d.ZonaHoraria);

        var tabla = NuevaTabla();
        foreach (var e in nuevos)
        {
            var local = e.FechaLocal;
            tabla.Rows.Add(d.DeviceId, e.Id, TimeZoneInfo.ConvertTimeToUtc(DateTime.SpecifyKind(local, DateTimeKind.Unspecified), tz),
                local, e.Usuario == 0 ? "" : e.Usuario.ToString(), (int)e.Evento, Sdk1.EsChecadaValida(e.Evento) && e.Usuario != 0);
        }
        return Guardar(tabla, d.DeviceId, nuevos.Max(e => e.Id));
    }

    static DataTable NuevaTabla()
    {
        var tabla = new DataTable();
        tabla.Columns.Add("deviceId", typeof(long));
        tabla.Columns.Add("eventoId", typeof(long));
        tabla.Columns.Add("fechahoraUtc", typeof(DateTime));
        tabla.Columns.Add("fechahora", typeof(DateTime));
        tabla.Columns.Add("usuarioid", typeof(string));
        tabla.Columns.Add("code", typeof(int));
        tabla.Columns.Add("esChecada", typeof(bool));
        return tabla;
    }

    /// <summary>Inserta el lote (sin duplicar) y avanza ultimoEventoId en la misma transacción.</summary>
    int Guardar(DataTable tabla, long deviceId, long ultimoId)
    {
        using var cn = Abrir();
        using var tx = cn.BeginTransaction();

        new SqlCommand(
            "CREATE TABLE #Lote (deviceId BIGINT, eventoId BIGINT, fechahoraUtc DATETIME2(0), fechahora DATETIME2(0), " +
            "usuarioid VARCHAR(32), code INT, esChecada BIT)", cn, tx).ExecuteNonQuery();

        using (var bulk = new SqlBulkCopy(cn, SqlBulkCopyOptions.Default, tx) { DestinationTableName = "#Lote" })
            bulk.WriteToServer(tabla);

        using var cmd = new SqlCommand(@"
            INSERT INTO dbo.RelojEvento (deviceId, eventoId, fechahoraUtc, fechahora, usuarioid, code, esChecada)
            SELECT DISTINCT l.deviceId, l.eventoId, l.fechahoraUtc, l.fechahora, l.usuarioid, l.code, l.esChecada
            FROM #Lote l
            WHERE NOT EXISTS (SELECT 1 FROM dbo.RelojEvento e WHERE e.deviceId = l.deviceId AND e.eventoId = l.eventoId);
            DECLARE @n INT = @@ROWCOUNT;
            UPDATE dbo.RelojDispositivo SET ultimoEventoId = @ultimo WHERE deviceId = @deviceId;
            SELECT @n;", cn, tx);
        cmd.Parameters.Add("@ultimo", SqlDbType.BigInt).Value = (long)ultimoId;
        cmd.Parameters.Add("@deviceId", SqlDbType.BigInt).Value = deviceId;
        int n = (int)cmd.ExecuteScalar()!;
        tx.Commit();
        return n;
    }

    DateTime HoraServidor()
    {
        using var cn = Abrir();
        return (DateTime)new SqlCommand("SELECT GETDATE()", cn).ExecuteScalar()!;
    }

    /// <summary>
    /// Guarda el resultado de la descarga. Si fue exitosa, cuenta las checadas que
    /// entraron desde <paramref name="inicio"/> (hora del servidor) y las deja en ultimasChecadas.
    /// </summary>
    int ActualizarEstado(long deviceId, string? error, DateTime? inicio)
    {
        using var cn = Abrir();
        using var cmd = new SqlCommand(@"
            DECLARE @checadas INT = NULL;
            IF @inicio IS NOT NULL
                SELECT @checadas = COUNT(*) FROM dbo.RelojEvento
                WHERE deviceId = @deviceId AND esChecada = 1 AND fechaDescarga >= @inicio;
            UPDATE dbo.RelojDispositivo
            SET ultimaDescarga  = CASE WHEN @error IS NULL THEN GETDATE() ELSE ultimaDescarga END,
                ultimasChecadas = CASE WHEN @error IS NULL THEN @checadas ELSE ultimasChecadas END,
                ultimaDescargaInicio = CASE WHEN @error IS NULL THEN @inicio ELSE ultimaDescargaInicio END,
                ultimoError     = @error
            WHERE deviceId = @deviceId;
            SELECT ISNULL(@checadas, 0);", cn);
        cmd.Parameters.Add("@error", SqlDbType.VarChar, -1).Value = (object?)error ?? DBNull.Value;
        cmd.Parameters.Add("@deviceId", SqlDbType.BigInt).Value = deviceId;
        cmd.Parameters.Add("@inicio", SqlDbType.DateTime).Value = (object?)inicio ?? DBNull.Value;
        return (int)cmd.ExecuteScalar()!;
    }

    (int insertadas, int yaExistian, int repetidos) Procesar(long? deviceId)
    {
        using var cn = Abrir();
        using var cmd = new SqlCommand("dbo.ProcesaChecadasReloj", cn) { CommandType = CommandType.StoredProcedure, CommandTimeout = 600 };
        cmd.Parameters.Add("@deviceId", SqlDbType.BigInt).Value = (object?)deviceId ?? DBNull.Value;
        using var rd = cmd.ExecuteReader();
        // trg_checadas regresa un SELECT por cada INSERT; el resumen es el último result set
        (int, int, int) r = default;
        do
        {
            if (rd.FieldCount == 3 && rd.GetName(0) == "insertadas" && rd.Read())
                r = (rd.GetInt32(0), rd.GetInt32(1), rd.GetInt32(2));
        } while (rd.NextResult());
        return r;
    }

    SqlConnection Abrir()
    {
        var cn = new SqlConnection(conexion);
        cn.Open();
        return cn;
    }

    static void Log(string msg) => Console.WriteLine($"{DateTime.Now:yyyy-MM-dd HH:mm:ss} {msg}");
}
