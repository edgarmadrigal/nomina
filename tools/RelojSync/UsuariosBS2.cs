using System.Runtime.InteropServices;
using System.Text;

namespace RelojSync;

/// <summary>
/// Usuarios de relojes BioStar 2 (BS_SDK_V2.dll). Estructuras tomadas del ejemplo oficial
/// de C# del SDK v2.9.9 (SFStruct.cs / SFApi.cs), Pack = 1.
/// </summary>
internal static class SdkU
{
    const string DLL = "BS_SDK_V2.dll";
    public const int ID = 32, NOMBRE = 48 * 4, FOTO = 16 * 1024, PIN = 32, GRUPOS = 16;
    public const int ROSTRO_IMAGEN = 16 * 1024, ROSTRO_TEMPLATES = 30, ROSTRO_TEMPLATE = 3008;

    // BS2UserMaskEnum
    public const uint MASK_DATA = 0x0001, MASK_SETTING = 0x0002, MASK_NAME = 0x0004, MASK_FACE = 0x0080, MASK_ACCESS_GROUP = 0x0100;

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2User
    {
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = ID)] public byte[] userID;
        public byte formatVersion;
        public byte flag;
        public ushort version;
        public byte numCards;
        public byte numFingers;
        public byte numFaces;
        public byte infoMask;
        public uint authGroupID;
        public uint faceChecksum;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2UserSetting
    {
        public uint startTime;
        public uint endTime;
        public byte fingerAuthMode;
        public byte cardAuthMode;
        public byte idAuthMode;
        public byte securityLevel;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2UserPhoto
    {
        public uint size;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = FOTO)] public byte[] data;
    }

    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2UserBlob
    {
        public BS2User user;
        public BS2UserSetting setting;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = NOMBRE)] public byte[] name;
        public BS2UserPhoto photo;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = PIN)] public byte[] pin;
        public IntPtr cardObjs;
        public IntPtr fingerObjs;
        public IntPtr faceObjs;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = GRUPOS)] public uint[] accessGroupId;
    }

    /// <summary>Rostro (FaceStation 2 / FaceLite): imagen + 30 plantillas.</summary>
    [StructLayout(LayoutKind.Sequential, Pack = 1)]
    public struct BS2Face
    {
        public byte faceIndex;
        public byte numOfTemplate;
        public byte flag;
        public byte reserved;
        public ushort imageLen;
        public ushort reserved2;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = ROSTRO_IMAGEN)] public byte[] imageData;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst = ROSTRO_TEMPLATES * ROSTRO_TEMPLATE)] public byte[] templateData;
    }

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_SetDefaultResponseTimeout(IntPtr context, int ms);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_GetUserList(IntPtr context, uint deviceId, out IntPtr outUidObjs, out uint outNumUids, IntPtr cbIsAcceptableUserID);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_GetUserDatas(IntPtr context, uint deviceId, IntPtr uids, uint uidCount, [In, Out] BS2UserBlob[] userBlobs, uint userMask);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_EnrolUser(IntPtr context, uint deviceId, [In, Out] BS2UserBlob[] userBlobs, uint uidCount, byte overwrite);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_RemoveUser(IntPtr context, uint deviceId, IntPtr uids, uint uidCount);

    [DllImport(DLL, CallingConvention = CallingConvention.Cdecl)]
    public static extern int BS2_RemoveAllUser(IntPtr context, uint deviceId);

    public static byte[] Bytes(string s, int largo)
    {
        var b = new byte[largo];
        var raw = Encoding.UTF8.GetBytes(s);
        Array.Copy(raw, b, Math.Min(raw.Length, largo - 1));   // siempre termina en 0
        return b;
    }

    public static string Texto(byte[] b)
    {
        int n = Array.IndexOf(b, (byte)0);
        return Encoding.UTF8.GetString(b, 0, n < 0 ? b.Length : n).Trim();
    }

    public static BS2UserBlob BlobVacio() => new()
    {
        user = new BS2User { userID = new byte[ID] },
        name = new byte[NOMBRE],
        photo = new BS2UserPhoto { data = new byte[FOTO] },
        pin = new byte[PIN],
        accessGroupId = new uint[GRUPOS],
    };
}

/// <summary>Usuario leído de un reloj (cualquier marca).</summary>
internal sealed record UsuarioReloj(string Id, string Nombre, int Rostros, int Huellas, int Tarjetas);

internal sealed record ErrorUsuario(string Id, string Mensaje);

/// <summary>Resultado de alta / baja / copia de usuarios (se regresa a PHP como JSON).</summary>
internal sealed class ResultadoUsuarios
{
    public List<string> Hechos { get; } = new();       // dados de alta / quitados / copiados
    public List<string> YaEstaban { get; } = new();    // alta: ya existían; baja: no estaban; copia: ya tenían rostro
    public List<string> SinRostro { get; } = new();    // copia: no tienen rostro en el origen
    public List<ErrorUsuario> Errores { get; } = new();
}

/// <summary>Operaciones de usuarios sobre una conexión BS2 abierta.</summary>
internal static class UsuariosBS2
{
    const int PAGINA = 100;

    const int TIMEOUT = -601;

    // Códigos que valen la pena reintentar. -601 = no contestó a tiempo (mismo socket).
    // -907/-102/-103/-104/-105 = se cayó/ocupó la conexión: hay que RECONECTAR antes de reintentar.
    static readonly int[] RECONECTAR = { -907, -102, -103, -104, -105 };

    /// Se avisa al progreso (barra) que estamos reconectando: recibe el número de intento.
    /// Lo pone el Sincronizador para escribir fase "reconectando" y que la barra NO se oculte.
    public static Action<int>? OnReconectando;

    /// Minutos máximos reintentando una operación ante timeouts/caídas antes de rendirse.
    /// Los relojes están por WiFi y a veces pierden señal varios minutos; se insiste mucho.
    const double MAX_MINUTOS_REINTENTO = 15;

    /// <summary>
    /// Los relojes tienen miles de usuarios y a veces no contestan a tiempo (-601) o se cae la
    /// conexión a media operación (-907/-102, típico de WiFi inestable). Se da más tiempo de
    /// respuesta y se REINTENTA (reconectando cuando la conexión se cayó) HASTA QUE EL RELOJ REGRESE,
    /// con un tope de MAX_MINUTOS_REINTENTO. En cada reintento avisa OnReconectando para que la barra
    /// muestre "Reconectando…" y no se quite. Las esperas crecen: 2 s, luego 5 s, luego 10 s.
    /// </summary>
    static int ConReintentos(Reloj r, Func<int> llamada)
    {
        SdkU.BS2_SetDefaultResponseTimeout(r.Contexto, 30000);
        int res = 0;
        var inicio = DateTime.Now;
        int intento = 0;
        while (true)
        {
            intento++;
            res = llamada();
            // Éxito o error que no vale la pena reintentar -> devolver tal cual.
            if (res != TIMEOUT && Array.IndexOf(RECONECTAR, res) < 0) return res;
            // Se agotó el presupuesto de reintentos (el reloj lleva mucho sin volver).
            if ((DateTime.Now - inicio).TotalMinutes >= MAX_MINUTOS_REINTENTO) return res;
            try { OnReconectando?.Invoke(intento); } catch { }
            Thread.Sleep(intento <= 3 ? 2000 : (intento <= 8 ? 5000 : 10000));
            if (Array.IndexOf(RECONECTAR, res) >= 0)
            {
                try { r.Reconectar(); } catch { /* el siguiente intento volverá a fallar y se reporta el código */ }
            }
        }
    }

    /// <summary>Ids de todos los usuarios del reloj (bloques de 32 bytes).</summary>
    static List<string> Ids(Reloj r)
    {
        IntPtr objs = IntPtr.Zero;
        uint n = 0;
        int res = ConReintentos(r, () => SdkU.BS2_GetUserList(r.Contexto, r.DeviceId, out objs, out n, IntPtr.Zero));
        if (res != Sdk.BS_SDK_SUCCESS) throw new InvalidOperationException($"No se pudo leer la lista de usuarios (código {res}{(res == TIMEOUT ? ", el reloj no contestó a tiempo" : "")}).");
        var ids = new List<string>((int)n);
        try
        {
            var buf = new byte[SdkU.ID];
            for (int i = 0; i < n; i++)
            {
                Marshal.Copy(objs + i * SdkU.ID, buf, 0, SdkU.ID);
                ids.Add(SdkU.Texto(buf));
            }
        }
        finally { if (objs != IntPtr.Zero) Sdk.BS2_ReleaseObject(objs); }
        return ids;
    }

    /// <summary>Lee los blobs de los ids dados (con la máscara indicada). El llamador libera faceObjs con Liberar.</summary>
    public static SdkU.BS2UserBlob[] Datos(Reloj r, IList<string> ids, uint mask)
    {
        var todos = new SdkU.BS2UserBlob[ids.Count];
        for (int ini = 0; ini < ids.Count; ini += PAGINA)
        {
            int n = Math.Min(PAGINA, ids.Count - ini);
            IntPtr uids = Marshal.AllocHGlobal(n * SdkU.ID);
            try
            {
                for (int i = 0; i < n; i++)
                    Marshal.Copy(SdkU.Bytes(ids[ini + i], SdkU.ID), 0, uids + i * SdkU.ID, SdkU.ID);
                var blobs = new SdkU.BS2UserBlob[n];
                for (int i = 0; i < n; i++) blobs[i] = SdkU.BlobVacio();
                int res = ConReintentos(r, () => SdkU.BS2_GetUserDatas(r.Contexto, r.DeviceId, uids, (uint)n, blobs, mask));
                if (res != Sdk.BS_SDK_SUCCESS) throw new InvalidOperationException($"No se pudieron leer los usuarios (código {res}).");
                Array.Copy(blobs, 0, todos, ini, n);
            }
            finally { Marshal.FreeHGlobal(uids); }
        }
        return todos;
    }

    public static void Liberar(SdkU.BS2UserBlob[] blobs)
    {
        foreach (var b in blobs)
        {
            if (b.cardObjs != IntPtr.Zero) Sdk.BS2_ReleaseObject(b.cardObjs);
            if (b.fingerObjs != IntPtr.Zero) Sdk.BS2_ReleaseObject(b.fingerObjs);
            if (b.faceObjs != IntPtr.Zero) Sdk.BS2_ReleaseObject(b.faceObjs);
        }
    }

    public static List<UsuarioReloj> Listar(Reloj r)
    {
        var ids = Ids(r);
        var blobs = Datos(r, ids, SdkU.MASK_DATA | SdkU.MASK_NAME);
        try
        {
            return blobs.Select(b => new UsuarioReloj(SdkU.Texto(b.user.userID), SdkU.Texto(b.name),
                                                      b.user.numFaces, b.user.numFingers, b.user.numCards)).ToList();
        }
        finally { Liberar(blobs); }
    }

    // Igual que los usuarios que ya existen en los relojes: vigencia 2001-01-01 a 2030-12-31,
    // modos de autenticación 255 (los del reloj), nivel de seguridad 0, sin grupos de acceso.
    const uint INICIO = 978307200, FIN = 1924991999;

    /// <summary>
    /// Da de alta usuarios SIN biometría (número y nombre) para que en el reloj solo se registre el rostro.
    /// Nunca toca a uno que ya exista (se filtra antes y además overwrite = 0), para no borrar su rostro.
    /// </summary>
    public static ResultadoUsuarios Alta(Reloj r, IList<(string id, string nombre)> lista)
    {
        var res = new ResultadoUsuarios();
        var existentes = new HashSet<string>(Ids(r));
        foreach (var (id, nombre) in lista)
        {
            if (existentes.Contains(id)) { res.YaEstaban.Add(id); continue; }
            var blob = SdkU.BlobVacio();
            blob.user.userID = SdkU.Bytes(id, SdkU.ID);
            blob.setting = new SdkU.BS2UserSetting
            {
                startTime = INICIO, endTime = FIN,
                fingerAuthMode = 255, cardAuthMode = 255, idAuthMode = 255, securityLevel = 0,
            };
            blob.name = SdkU.Bytes(nombre, SdkU.NOMBRE);
            int c = ConReintentos(r, () => SdkU.BS2_EnrolUser(r.Contexto, r.DeviceId, new[] { blob }, 1, 0));
            if (c == Sdk.BS_SDK_SUCCESS) { res.Hechos.Add(id); existentes.Add(id); }
            else res.Errores.Add(new ErrorUsuario(id, $"código {c}"));
        }
        return res;
    }

    /// <summary>Borra TODOS los usuarios del reloj (deja la lista en cero). Destructivo.</summary>
    public static void BorrarTodos(Reloj r)
    {
        int c = ConReintentos(r, () => SdkU.BS2_RemoveAllUser(r.Contexto, r.DeviceId));
        if (c != Sdk.BS_SDK_SUCCESS)
            throw new InvalidOperationException($"No se pudieron borrar todos los usuarios (código {c}).");
    }

    public static ResultadoUsuarios Baja(Reloj r, IList<string> ids)
    {
        var res = new ResultadoUsuarios();
        var existentes = new HashSet<string>(Ids(r));
        foreach (var id in ids)
        {
            if (!existentes.Contains(id)) { res.YaEstaban.Add(id); continue; }   // aquí: "no estaba"
            IntPtr uid = Marshal.AllocHGlobal(SdkU.ID);
            try
            {
                Marshal.Copy(SdkU.Bytes(id, SdkU.ID), 0, uid, SdkU.ID);
                int c = ConReintentos(r, () => SdkU.BS2_RemoveUser(r.Contexto, r.DeviceId, uid, 1));
                if (c == Sdk.BS_SDK_SUCCESS) res.Hechos.Add(id);
                else res.Errores.Add(new ErrorUsuario(id, $"código {c}"));
            }
            finally { Marshal.FreeHGlobal(uid); }
        }
        return res;
    }

    /// <summary>
    /// Copia usuarios CON su rostro de un reloj a otro (mismo formato de rostro, FaceStation 2).
    /// Solo copia a quien tiene rostro en el origen. En el destino: si no existe, se crea; si existe sin
    /// rostro, se completa; si ya tiene rostro, se deja como está salvo <paramref name="reemplazar"/>.
    /// <paramref name="ids"/> null = todos los que tengan rostro en el origen.
    /// </summary>
    public static ResultadoUsuarios CopiarRostros(Reloj origen, Reloj destino, IList<string>? ids, bool reemplazar,
                                                  Action<int, int, int, int, int, string>? reportar = null,
                                                  bool incluirSinRostro = false, bool borrarDestino = false)
    {
        var res = new ResultadoUsuarios();
        // IMPORTANTE: leer PRIMERO el origen. Nunca borrar el destino antes de tener la lista del
        // origen en mano; si el origen no se puede leer (o está vacío) y ya borramos, el destino
        // quedaría vacío sin nada que restaurar. Por eso Ids(origen) va ANTES de BorrarTodos.
        var enOrigen = Ids(origen);
        if (borrarDestino)   // dejar el reloj destino en cero antes de volver a dar de alta
        {
            if (enOrigen.Count == 0)
                throw new InvalidOperationException(
                    "El reloj de origen no tiene usuarios o no se pudo leer; NO se borra el destino (se evita dejarlo vacío).");
            reportar?.Invoke(0, 0, 0, 0, 0, "borrando");
            BorrarTodos(destino);
        }
        var pedir = ids == null ? enOrigen : ids.Where(i => enOrigen.Contains(i)).ToList();
        if (ids != null) foreach (var i in ids.Where(i => !enOrigen.Contains(i))) res.Errores.Add(new ErrorUsuario(i, "no está en el reloj de origen"));

        // rostros que ya hay en el destino
        var enDestino = Ids(destino);
        var enDestinoSet = new HashSet<string>(enDestino);
        var conRostroDestino = new HashSet<string>();
        var cabeceras = Datos(destino, enDestino.Where(i => pedir.Contains(i)).ToList(), SdkU.MASK_DATA);
        try { foreach (var b in cabeceras) if (b.user.numFaces > 0) conRostroDestino.Add(SdkU.Texto(b.user.userID)); }
        finally { Liberar(cabeceras); }

        // del origen: los que tienen rostro se copian con rostro; los que NO tienen, si se pidió
        // incluirlos, se dan de alta en el destino solo con número y nombre (sin tocar a los que ya existen).
        var cabOrigen = Datos(origen, pedir, SdkU.MASK_DATA | SdkU.MASK_NAME);
        List<string> copiar;
        var altaSinRostro = new List<(string id, string nombre)>();
        try
        {
            copiar = cabOrigen.Where(b => b.user.numFaces > 0).Select(b => SdkU.Texto(b.user.userID)).ToList();
            foreach (var b in cabOrigen.Where(b => b.user.numFaces == 0))
            {
                string id = SdkU.Texto(b.user.userID);
                if (incluirSinRostro && !enDestinoSet.Contains(id)) altaSinRostro.Add((id, SdkU.Texto(b.name)));
                else res.SinRostro.Add(id);
            }
        }
        finally { Liberar(cabOrigen); }
        foreach (var id in copiar.Where(conRostroDestino.Contains).ToList())
            if (!reemplazar) { res.YaEstaban.Add(id); copiar.Remove(id); }

        // Alta (número + nombre, sin rostro) de los que no tienen rostro en el origen.
        foreach (var (id, nombre) in altaSinRostro)
        {
            var blob = SdkU.BlobVacio();
            blob.user.userID = SdkU.Bytes(id, SdkU.ID);
            blob.setting = new SdkU.BS2UserSetting
            {
                startTime = INICIO, endTime = FIN,
                fingerAuthMode = 255, cardAuthMode = 255, idAuthMode = 255, securityLevel = 0,
            };
            blob.name = SdkU.Bytes(nombre, SdkU.NOMBRE);
            var blobAlta = blob;
            int c = ConReintentos(destino, () => SdkU.BS2_EnrolUser(destino.Contexto, destino.DeviceId, new[] { blobAlta }, 1, 0));
            if (c == Sdk.BS_SDK_SUCCESS) { res.Hechos.Add(id); enDestinoSet.Add(id); }
            else res.Errores.Add(new ErrorUsuario(id, $"código {c}"));
        }

        int total = copiar.Count;
        reportar?.Invoke(0, res.YaEstaban.Count, res.SinRostro.Count, res.Errores.Count, total, "copiando");

        const uint MASK = SdkU.MASK_DATA | SdkU.MASK_SETTING | SdkU.MASK_NAME | SdkU.MASK_FACE | SdkU.MASK_ACCESS_GROUP;
        for (int ini = 0; ini < copiar.Count; ini += 20)
        {
            var pagina = copiar.Skip(ini).Take(20).ToList();
            var blobs = Datos(origen, pagina, MASK);
            try
            {
                foreach (var b in blobs)
                {
                    string id = SdkU.Texto(b.user.userID);
                    var blobCopia = b;   // evita capturar la variable del foreach en el lambda
                    int c = ConReintentos(destino, () => SdkU.BS2_EnrolUser(destino.Contexto, destino.DeviceId, new[] { blobCopia }, 1, 1));
                    if (c == Sdk.BS_SDK_SUCCESS) res.Hechos.Add(id);
                    else res.Errores.Add(new ErrorUsuario(id, $"código {c}"));
                }
            }
            finally { Liberar(blobs); }
            reportar?.Invoke(res.Hechos.Count, res.YaEstaban.Count, res.SinRostro.Count, res.Errores.Count, total, "copiando");
        }
        return res;
    }

    /// <summary>Diagnóstico: configuración (setting y grupos de acceso) de algunos usuarios existentes.</summary>
    public static IEnumerable<string> Muestra(Reloj r, int cuantos)
    {
        var ids = Ids(r).Take(cuantos).ToList();
        var blobs = Datos(r, ids, SdkU.MASK_DATA | SdkU.MASK_SETTING | SdkU.MASK_NAME | SdkU.MASK_ACCESS_GROUP);
        try
        {
            foreach (var b in blobs)
                yield return $"id={SdkU.Texto(b.user.userID)} nombre='{SdkU.Texto(b.name)}' rostros={b.user.numFaces} fmt={b.user.formatVersion} flag={b.user.flag} " +
                             $"inicio={b.setting.startTime} fin={b.setting.endTime} finger={b.setting.fingerAuthMode} card={b.setting.cardAuthMode} " +
                             $"idmode={b.setting.idAuthMode} nivel={b.setting.securityLevel} grupos=[{string.Join(",", b.accessGroupId.Where(g => g != 0))}]";
        }
        finally { Liberar(blobs); }
    }
}
