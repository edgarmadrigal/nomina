using System.Runtime.InteropServices;

namespace RelojSync;

/// <summary>
/// BioStar 1 SDK (BS_SDK.dll, x64, el mismo que usa Nomiplus) para relojes
/// FaceStation de primera generación (Windows CE, puerto 1470).
/// </summary>
internal static class Sdk1
{
    public const int BS_SUCCESS = 0;
    const string DLL = "BS_SDK.dll";

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_InitSDK();

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl, CharSet = CharSet.Ansi)]
    public static extern int BS_OpenSocket(string ipAddr, int port, out int handle);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_CloseSocket(int handle);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_GetDeviceID(int handle, out uint deviceId, out int deviceType);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_SetDeviceID(int handle, uint deviceId, int deviceType);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_GetLogCount(int handle, out int numOfLog);

    // time_t: se pasa como 64 bits (en x64 cabe igual si la DLL lo lee como 32)
    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_ReadLogEx(int handle, long startTime, long endTime, out int numOfLog, IntPtr logRecord);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_ReadNextLogEx(int handle, long startTime, long endTime, out int numOfLog, IntPtr logRecord);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_ReadLog(int handle, long startTime, long endTime, out int numOfLog, IntPtr logRecord);

    // time_t en hora local del reloj. En x64 se pasa en un registro de 64 bits: sirve
    // igual si la DLL lo lee como 32 o 64 bits (fechas antes de 2038).
    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_GetTime(int handle, ref long timeVal);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_SetTime(int handle, long timeVal);

    /// <summary>Borra los registros del reloj (numOfLog = cuántos; se pasa el total).</summary>
    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_DeleteAllLog(int handle, int numOfLog, out int numOfDeletedLog);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_GetUserDBInfo(int handle, out int numOfUser, out int numOfTemplate);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_GetAllUserInfoFStation(int handle, IntPtr userHdr, out int numOfUser);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS_DeleteUser(int handle, uint userId);

    /// <summary>Identificación / verificación exitosa (BE_EVENT_IDENTIFY_SUCCESS 0x37, BE_EVENT_VERIFY_SUCCESS 0x27).</summary>
    public static bool EsChecadaValida(byte evento) => evento == 0x37 || evento == 0x27;
}

/// <summary>Evento leído de un reloj BioStar 1. La fecha ya viene en hora local.</summary>
internal readonly record struct Evento1(uint Fecha, uint Usuario, byte Evento, byte SubEvento)
{
    public DateTime FechaLocal => DateTime.UnixEpoch.AddSeconds(Fecha);

    /// <summary>
    /// El SDK 1 no da id de evento: se arma uno ordenado por fecha
    /// (segundos &lt;&lt; 31) + hash de usuario/evento, para la PK de RelojEvento.
    /// </summary>
    public long Id
    {
        get
        {
            uint h = 2166136261;
            foreach (uint v in new[] { Usuario, Evento, (uint)SubEvento })
                h = (h ^ v) * 16777619;
            return ((long)Fecha << 31) | (h & 0x7FFFFFFF);
        }
    }

    public static uint FechaDeId(long id) => (uint)(id >> 31);
}

/// <summary>Conexión a un reloj BioStar 1 (FaceStation, puerto 1470).</summary>
internal sealed class Reloj1 : IDisposable
{
    // BSLogRecordEx: event(1) subEvent(1) tnaKey(2) eventTime(4) userID(4) deviceID(4) imageSlot(2) reserved(2) reserved(4)
    const int TAM_REGISTRO = 24;
    static bool _iniciado;
    readonly int _h;
    public uint DeviceId { get; }

    public Reloj1(string ip, int puerto)
    {
        if (!_iniciado) { Sdk1.BS_InitSDK(); _iniciado = true; }
        int r = Sdk1.BS_OpenSocket(ip, puerto, out _h);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"No se pudo conectar a {ip}:{puerto} (código {r}).");
        r = Sdk1.BS_GetDeviceID(_h, out uint id, out int tipo);
        if (r == Sdk1.BS_SUCCESS) r = Sdk1.BS_SetDeviceID(_h, id, tipo);
        if (r != Sdk1.BS_SUCCESS)
        {
            Sdk1.BS_CloseSocket(_h);
            throw new InvalidOperationException($"No se pudo identificar el reloj {ip} (código {r}).");
        }
        DeviceId = id;
        Tipo = tipo;
    }

    public int Tipo { get; }

    /// <summary>Todo el log del reloj (unos miles de registros; se filtra después).</summary>
    public List<Evento1> LeerEventos()
    {
        int r = Sdk1.BS_GetLogCount(_h, out int cuenta);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"BS_GetLogCount falló (código {r}).");

        // margen por si entran checadas entre GetLogCount y ReadLogEx
        int capacidad = cuenta + 1024;
        IntPtr buf = Marshal.AllocHGlobal(capacidad * TAM_REGISTRO);
        try
        {
            r = Sdk1.BS_ReadLogEx(_h, 0, 0, out int n, buf);
            if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"BS_ReadLogEx falló (código {r}).");
            n = Math.Min(n, capacidad);
            var bytes = new byte[n * TAM_REGISTRO];
            Marshal.Copy(buf, bytes, 0, bytes.Length);

            var lista = new List<Evento1>(n);
            for (int i = 0; i < n; i++)
            {
                int o = i * TAM_REGISTRO;
                lista.Add(new Evento1(BitConverter.ToUInt32(bytes, o + 4), BitConverter.ToUInt32(bytes, o + 8), bytes[o], bytes[o + 1]));
            }
            return lista;
        }
        finally { Marshal.FreeHGlobal(buf); }
    }

    public int ContarEventos()
    {
        int r = Sdk1.BS_GetLogCount(_h, out int cuenta);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"BS_GetLogCount falló (código {r}).");
        return cuenta;
    }

    /// <summary>Hora del reloj (ya viene en hora local).</summary>
    public DateTime HoraLocal()
    {
        long t = 0;
        int r = Sdk1.BS_GetTime(_h, ref t);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"No se pudo leer la hora del reloj (código {r}).");
        return DateTime.UnixEpoch.AddSeconds((uint)t);
    }

    public void PonerHoraLocal(DateTime local)
    {
        int r = Sdk1.BS_SetTime(_h, (long)(local - DateTime.UnixEpoch).TotalSeconds);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"No se pudo poner la hora al reloj (código {r}).");
    }

    /// <summary>Borra el registro del reloj. Regresa cuántos borró (si la DLL lo informa).</summary>
    public int BorrarLog(int cuantos)
    {
        int r = Sdk1.BS_DeleteAllLog(_h, cuantos, out int borrados);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"No se pudo borrar el registro del reloj (código {r}).");
        return borrados;
    }

    public void Dispose() => Sdk1.BS_CloseSocket(_h);

    /// <summary>Lista de usuarios dados de alta en el reloj FaceStation.</summary>
    public List<UsuarioReloj> Usuarios()
    {
        int r = Sdk1.BS_GetUserDBInfo(_h, out int count, out _);
        if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"BS_GetUserDBInfo falló (código {r}).");
        if (count <= 0) return new List<UsuarioReloj>();

        const int STRIDE = 1512;
        int capacidad = Math.Max(count + 64, 128);
        IntPtr buf = Marshal.AllocHGlobal(capacidad * STRIDE);
        try
        {
            r = Sdk1.BS_GetAllUserInfoFStation(_h, buf, out int n);
            if (r != Sdk1.BS_SUCCESS) throw new InvalidOperationException($"BS_GetAllUserInfoFStation falló (código {r}).");

            var raw = new byte[n * STRIDE];
            Marshal.Copy(buf, raw, 0, raw.Length);

            var lista = new List<UsuarioReloj>();
            for (int i = 0; i < n; i++)
            {
                int off = i * STRIDE;
                if (off + STRIDE > raw.Length) break;
                uint id = BitConverter.ToUInt32(raw, off);
                if (id == 0) continue;

                // El nombre viene en UTF-16LE a partir del byte 16 (hasta 48 caracteres / 96 bytes)
                string nombre = System.Text.Encoding.Unicode.GetString(raw, off + 16, 96).TrimEnd('\0').Trim();

                // En el byte 242 se encuentra numOfFace (número de poses/plantillas de rostro enroladas)
                short faces = BitConverter.ToInt16(raw, off + 242);

                lista.Add(new UsuarioReloj(id.ToString(), nombre, faces > 0 ? 1 : 0, 0, 0));
            }
            return lista;
        }
        finally
        {
            Marshal.FreeHGlobal(buf);
        }
    }

    /// <summary>Elimina usuarios del reloj FaceStation por su ID numérico.</summary>
    public ResultadoUsuarios Baja(IList<string> ids)
    {
        var res = new ResultadoUsuarios();
        foreach (var idStr in ids)
        {
            if (!uint.TryParse(idStr, out uint uid))
            {
                res.Errores.Add(new ErrorUsuario(idStr, "ID no numérico"));
                continue;
            }
            int r = Sdk1.BS_DeleteUser(_h, uid);
            if (r == Sdk1.BS_SUCCESS)
            {
                res.Hechos.Add(idStr);
            }
            else
            {
                res.Errores.Add(new ErrorUsuario(idStr, $"Código error {r}"));
            }
        }
        return res;
    }
}
