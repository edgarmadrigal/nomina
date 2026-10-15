using System.Globalization;
using System.Net.Sockets;
using System.Text;
using System.Text.RegularExpressions;

namespace RelojSync;

/// <summary>
/// Relojes FaceGo / Hanvon (puerto 9922). Protocolo de texto sin cifrar:
///   GetRecord(start_time="yyyy-MM-dd HH:mm:ss" end_time="...")
///   -> Return(result="success" dev_id="..." total="n"
///        time="..." id="48004" name="..." workcode="0" status="0" authority="0X11" card_src="from_check" ...)
/// La hora viene en hora local. No requiere DLL.
/// </summary>
internal static partial class RelojHanvon
{
    [GeneratedRegex("total=\"(\\d+)\"")]
    private static partial Regex RxTotal();

    [GeneratedRegex("time=\"([^\"]+)\" id=\"(\\d+)\"")]
    private static partial Regex RxRegistro();

    [GeneratedRegex("dev_id=\"(\\d+)\"")]
    private static partial Regex RxDevId();

    /// <summary>dev_id del reloj (GetDeviceInfo).</summary>
    public static string ObtenerId(string ip, int puerto)
    {
        string resp = Enviar(ip, puerto, "GetDeviceInfo()", _ => true);
        var m = RxDevId().Match(resp);
        if (!m.Success) throw new InvalidOperationException($"Respuesta inesperada de {ip}: {resp[..Math.Min(resp.Length, 200)]}");
        return m.Groups[1].Value;
    }

    [GeneratedRegex("time=\"([^\"]+)\"")]
    private static partial Regex RxTime();

    [GeneratedRegex("\\bid=\"(\\d+)\"")]
    private static partial Regex RxId();

    [GeneratedRegex("\\bname=\"([^\"]*)\"")]
    private static partial Regex RxName();

    /// <summary>Ids de los empleados dados de alta en el reloj.</summary>
    public static List<string> IdsEmpleados(string ip, int puerto)
    {
        string resp = Enviar(ip, puerto, "GetEmployeeID()", t =>
        {
            var total = RxTotal().Match(t);
            return !total.Success || RxId().Count(t) >= int.Parse(total.Groups[1].Value);
        });
        if (!resp.Contains("result=\"success\""))
        {
            if (resp.StartsWith("Return(", StringComparison.Ordinal)) return [];   // reloj sin empleados
            throw new InvalidOperationException($"Respuesta inesperada de {ip}: {resp[..Math.Min(resp.Length, 200)]}");
        }
        return RxId().Matches(resp).Select(m => m.Groups[1].Value).ToList();
    }

    /// <summary>Empleados con su nombre (un GetEmployee por cada uno; el FaceGo tiene pocos).</summary>
    public static List<UsuarioReloj> Usuarios(string ip, int puerto)
    {
        var lista = new List<UsuarioReloj>();
        foreach (var id in IdsEmpleados(ip, puerto))
        {
            string resp = Enviar(ip, puerto, $"GetEmployee(id=\"{id}\")", _ => true);
            var n = RxName().Match(resp);
            // check_type="face": el rostro se valida en el reloj; no hay forma barata de saber si ya lo registró
            lista.Add(new UsuarioReloj(id, n.Success ? n.Groups[1].Value : "", resp.Contains("check_type=\"face\"") ? 1 : 0, 0, 0));
        }
        return lista;
    }

    /// <summary>
    /// El FaceGo NO acepta empleados nuevos sin su plantilla de rostro (SetEmployee regresa "fail" con
    /// cualquier combinación de campos; probado 2026-10-12). En este reloj el empleado se crea al registrar
    /// su rostro directamente en el reloj.
    /// </summary>
    public static ResultadoUsuarios Alta(string ip, int puerto, IList<(string id, string nombre)> lista) =>
        throw new InvalidOperationException("El FaceGo no permite dar de alta empleados sin su rostro: en este reloj el empleado se crea al registrar su rostro directamente en el reloj.");

    public static ResultadoUsuarios Baja(string ip, int puerto, IList<string> ids)
    {
        var res = new ResultadoUsuarios();
        var existentes = new HashSet<string>(IdsEmpleados(ip, puerto));
        foreach (var id in ids)
        {
            if (!existentes.Contains(id)) { res.YaEstaban.Add(id); continue; }
            string resp = Enviar(ip, puerto, $"DeleteEmployee(id=\"{id}\")", _ => true);
            if (resp.Contains("result=\"success\"")) res.Hechos.Add(id);
            else res.Errores.Add(new ErrorUsuario(id, resp[..Math.Min(resp.Length, 150)]));
        }
        return res;
    }

    /// <summary>Envía un comando y regresa la respuesta cruda.</summary>
    public static string Comando(string ip, int puerto, string cmd) => Enviar(ip, puerto, cmd, _ => true);

    /// <summary>Respuesta cruda de GetDeviceInfo() (diagnóstico).</summary>
    public static string Info(string ip, int puerto) => Enviar(ip, puerto, "GetDeviceInfo()", _ => true);

    /// <summary>Hora local del reloj, si GetDeviceInfo la informa (si no, null).</summary>
    public static DateTime? Hora(string ip, int puerto)
    {
        var m = RxTime().Match(Info(ip, puerto));
        return m.Success && DateTime.TryParseExact(m.Groups[1].Value, "yyyy-MM-dd HH:mm:ss", CultureInfo.InvariantCulture, DateTimeStyles.None, out var t) ? t : null;
    }

    /// <summary>Pone la hora local (mismo comando que usa Nomiplus).</summary>
    public static void PonerHora(string ip, int puerto, DateTime local) =>
        Exigir(Enviar(ip, puerto, $"SetDeviceInfo(time=\"{local:yyyy-MM-dd HH:mm:ss}\")", _ => true), ip, "poner la hora");

    /// <summary>Borra todas las checadas del reloj (mismo comando que usa Nomiplus).</summary>
    public static void BorrarRegistros(string ip, int puerto) =>
        Exigir(Enviar(ip, puerto, "DeleteAllRecord()", _ => true), ip, "borrar las checadas");

    static void Exigir(string resp, string ip, string que)
    {
        if (!resp.Contains("result=\"success\""))
            throw new InvalidOperationException($"El reloj {ip} no pudo {que}: {resp[..Math.Min(resp.Length, 200)]}");
    }

    /// <summary>Envía un comando y lee la respuesta hasta que <paramref name="completa"/> se cumpla o el reloj deje de mandar.</summary>
    static string Enviar(string ip, int puerto, string cmd, Func<string, bool> completa)
    {
        using var cliente = new TcpClient { ReceiveTimeout = 15000, SendTimeout = 15000 };
        if (!cliente.ConnectAsync(ip, puerto).Wait(10000))
            throw new InvalidOperationException($"No se pudo conectar a {ip}:{puerto}.");
        using var red = cliente.GetStream();
        byte[] b = Encoding.ASCII.GetBytes(cmd);
        red.Write(b, 0, b.Length);

        var texto = new StringBuilder();
        var buf = new byte[65536];
        while (true)
        {
            int n;
            try { n = red.Read(buf, 0, buf.Length); }
            catch (IOException) { break; }               // timeout: ya no llegó más
            if (n == 0) break;
            texto.Append(Encoding.UTF8.GetString(buf, 0, n));
            string t = texto.ToString();
            if (t.StartsWith("Return(", StringComparison.Ordinal) && t.TrimEnd().EndsWith(')') && completa(t)) break;
        }
        return texto.ToString();
    }

    /// <summary>Checadas desde <paramref name="desde"/> (hora local). Regresa también el dev_id del reloj.</summary>
    public static (string devId, List<Evento1> eventos) LeerEventos(string ip, int puerto, DateTime desde)
    {
        string cmd = $"GetRecord(start_time=\"{desde:yyyy-MM-dd HH:mm:ss}\" end_time=\"2099-12-31 23:59:59\")";
        string resp = Enviar(ip, puerto, cmd, t =>
        {
            var total = RxTotal().Match(t);
            return !total.Success || RxRegistro().Count(t) >= int.Parse(total.Groups[1].Value);
        });
        if (!resp.Contains("result=\"success\""))
        {
            // sin registros en el rango el reloj responde result="fail"
            if (resp.StartsWith("Return(", StringComparison.Ordinal)) return ("", []);
            throw new InvalidOperationException($"Respuesta inesperada de {ip}: {resp[..Math.Min(resp.Length, 200)]}");
        }

        var eventos = new List<Evento1>();
        foreach (Match m in RxRegistro().Matches(resp))
        {
            var fecha = DateTime.ParseExact(m.Groups[1].Value, "yyyy-MM-dd HH:mm:ss", CultureInfo.InvariantCulture);
            uint segundos = (uint)(fecha - DateTime.UnixEpoch).TotalSeconds;
            // se marca como identificación exitosa (0x37) para reutilizar el flujo de BioStar 1
            eventos.Add(new Evento1(segundos, uint.Parse(m.Groups[2].Value), 0x37, 0));
        }
        return (RxDevId().Match(resp).Groups[1].Value, eventos);
    }
}
