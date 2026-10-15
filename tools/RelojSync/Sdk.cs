using System.Runtime.InteropServices;
using System.Text;

namespace RelojSync;

/// <summary>
/// Envoltorio mínimo del BioStar 2 Device SDK (BS_SDK_V2.dll).
/// Las firmas se tomaron del ejemplo oficial de C# del SDK v2.9.9 (SFApi.cs / SFStruct.cs).
/// </summary>
internal static class Sdk
{
    public const int BS_SDK_SUCCESS = 1;
    const string DLL = "BS_SDK_V2.dll";

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern IntPtr BS2_AllocateContext();

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_Initialize(IntPtr context);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern void BS2_ReleaseContext(IntPtr context);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern void BS2_ReleaseObject(IntPtr obj);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_ConnectDeviceViaIP(IntPtr context, IntPtr deviceAddress, ushort devicePort, out uint deviceId);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_DisconnectDevice(IntPtr context, uint deviceId);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_GetLog(IntPtr context, uint deviceId, uint eventId, uint amount, out IntPtr logObjs, out uint numLog);

    /// <summary>Hora del reloj en segundos UTC desde 1970.</summary>
    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_GetDeviceTime(IntPtr context, uint deviceId, out uint gmtTime);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_SetDeviceTime(IntPtr context, uint deviceId, uint gmtTime);

    /// <summary>Borra TODO el registro de eventos (checadas) del reloj.</summary>
    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_ClearLog(IntPtr context, uint deviceId);

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2Event
    {
        public uint id;
        public uint dateTime;      // segundos desde 1970 en la hora local del reloj
        public uint deviceID;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = 32)]
        public byte[] userID;
        public ushort code;
        public byte param;
        public byte image;
    }

    public static string UserId(BS2Event e)
    {
        int len = Array.IndexOf(e.userID, (byte)0);
        if (len < 0) len = e.userID.Length;
        return Encoding.ASCII.GetString(e.userID, 0, len).Trim();
    }

    public static DateTime Fecha(BS2Event e) =>
        DateTime.UnixEpoch.AddSeconds(e.dateTime);

    /// <summary>Autenticación exitosa: VERIFY_SUCCESS (0x10xx), IDENTIFY_SUCCESS (0x13xx), DUAL_AUTH_SUCCESS (0x16xx).</summary>
    public static bool EsChecadaValida(ushort code)
    {
        int grupo = code & 0xFF00;
        return grupo == 0x1000 || grupo == 0x1300 || grupo == 0x1600;
    }
}

/// <summary>Conexión a un reloj. Libera contexto y conexión al hacer Dispose.</summary>
internal sealed class Reloj : IDisposable
{
    readonly IntPtr _ctx;
    readonly string _ip;
    readonly ushort _puerto;
    public uint DeviceId { get; private set; }
    internal IntPtr Contexto => _ctx;

    public Reloj(string ip, ushort puerto)
    {
        _ip = ip;
        _puerto = puerto;
        _ctx = Sdk.BS2_AllocateContext();
        if (_ctx == IntPtr.Zero) throw new InvalidOperationException("No se pudo crear el contexto del SDK.");
        int r = Sdk.BS2_Initialize(_ctx);
        if (r != Sdk.BS_SDK_SUCCESS)
        {
            Sdk.BS2_ReleaseContext(_ctx);
            throw new InvalidOperationException($"BS2_Initialize falló ({r}).");
        }
        Conectar();
    }

    void Conectar()
    {
        IntPtr pIp = Marshal.StringToHGlobalAnsi(_ip);
        try
        {
            int r = Sdk.BS2_ConnectDeviceViaIP(_ctx, pIp, _puerto, out uint id);
            if (r != Sdk.BS_SDK_SUCCESS)
                throw new InvalidOperationException($"No se pudo conectar a {_ip}:{_puerto} (código {r}).");
            DeviceId = id;
        }
        finally { Marshal.FreeHGlobal(pIp); }
    }

    /// <summary>
    /// Reconecta sobre el mismo contexto (para cuando la conexión se cayó a media operación, -907/-102).
    /// Primero desconecta por si quedó una sesión colgada, luego vuelve a conectar.
    /// </summary>
    public void Reconectar()
    {
        try { Sdk.BS2_DisconnectDevice(_ctx, DeviceId); } catch { }
        Thread.Sleep(1500);
        Conectar();
    }

    /// <summary>Eventos con id mayor a <paramref name="desdeId"/>, en páginas de <paramref name="pagina"/>.</summary>
    public IEnumerable<List<Sdk.BS2Event>> LeerEventos(uint desdeId, uint pagina = 1000)
    {
        int size = Marshal.SizeOf<Sdk.BS2Event>();
        uint ultimo = desdeId;
        while (true)
        {
            int r = Sdk.BS2_GetLog(_ctx, DeviceId, ultimo, pagina, out IntPtr objs, out uint n);
            if (r != Sdk.BS_SDK_SUCCESS)
                throw new InvalidOperationException($"BS2_GetLog falló (código {r}) en el evento {ultimo}.");

            var lote = new List<Sdk.BS2Event>((int)n);
            try
            {
                for (int i = 0; i < n; i++)
                    lote.Add(Marshal.PtrToStructure<Sdk.BS2Event>(objs + i * size));
            }
            finally { if (objs != IntPtr.Zero) Sdk.BS2_ReleaseObject(objs); }

            if (lote.Count == 0) yield break;
            yield return lote;
            ultimo = lote[^1].id;
            if (n < pagina) yield break;
        }
    }

    public DateTime HoraUtc()
    {
        int r = Sdk.BS2_GetDeviceTime(_ctx, DeviceId, out uint t);
        if (r != Sdk.BS_SDK_SUCCESS) throw new InvalidOperationException($"No se pudo leer la hora del reloj (código {r}).");
        return DateTime.UnixEpoch.AddSeconds(t);
    }

    public void PonerHoraUtc(DateTime utc)
    {
        int r = Sdk.BS2_SetDeviceTime(_ctx, DeviceId, (uint)(utc - DateTime.UnixEpoch).TotalSeconds);
        if (r != Sdk.BS_SDK_SUCCESS) throw new InvalidOperationException($"No se pudo poner la hora al reloj (código {r}).");
    }

    public void BorrarLog()
    {
        int r = Sdk.BS2_ClearLog(_ctx, DeviceId);
        if (r != Sdk.BS_SDK_SUCCESS) throw new InvalidOperationException($"No se pudo borrar el registro del reloj (código {r}).");
    }

    public void Dispose()
    {
        Sdk.BS2_DisconnectDevice(_ctx, DeviceId);
        Sdk.BS2_ReleaseContext(_ctx);
    }
}
