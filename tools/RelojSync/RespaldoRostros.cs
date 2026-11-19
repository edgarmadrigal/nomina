using System.Runtime.InteropServices;
using System.Text;

namespace RelojSync;

/// <summary>
/// Respaldo de los usuarios de un reloj BioStar 2 con su rostro (y foto, nombre, vigencia y modos),
/// en un archivo propio compacto, para poder volver a cargarlos si un reloj se borra o se cambia.
///
/// Formato (.bin, little endian):
///   "RSROST01" | deviceId (u64) | usuarios (i32) | tamaño BS2User (i32) | tamaño BS2UserSetting (i32) | tamaño BS2Face (i32)
///   por usuario: BS2User | BS2UserSetting | nombre[192] | pin[32] | grupos[16 × u32] | foto (u32 tamaño + bytes)
///                | rostros (u8) y por rostro: encabezado[8] (índice, plantillas, flag, res, imageLen u16, res u16)
///                  + imagen[imageLen] + plantillas[numOfTemplate × 3008]
/// Junto se escribe &lt;archivo&gt;.csv con número, nombre y rostros (para consultarlo a mano).
/// </summary>
internal static class RespaldoRostros
{
    const string MAGIA = "RSROST01";
    // Lo mismo que lleva una copia de rostros entre relojes
    const uint MASK = SdkU.MASK_DATA | SdkU.MASK_SETTING | SdkU.MASK_NAME | SdkU.MASK_PHOTO | SdkU.MASK_FACE | SdkU.MASK_ACCESS_GROUP;
    static readonly int TAM_USER = Marshal.SizeOf<SdkU.BS2User>();
    static readonly int TAM_SETTING = Marshal.SizeOf<SdkU.BS2UserSetting>();
    static readonly int TAM_ROSTRO = Marshal.SizeOf<SdkU.BS2Face>();
    const int ENC_ROSTRO = 8;

    internal sealed record Resumen(int Usuarios, int ConRostro, int Rostros, long Bytes, bool ConFoto);

    /// <summary>
    /// Lee los blobs con la máscara del respaldo. Hay relojes que no entregan la foto del usuario
    /// (el 152 Dirección contesta -600 = no soportado): entonces se pide todo lo demás sin la foto.
    /// </summary>
    static SdkU.BS2UserBlob[] DatosRespaldo(Reloj r, IList<string> ids, ref uint mask)
    {
        try { return UsuariosBS2.Datos(r, ids, mask); }
        catch (InvalidOperationException ex) when ((mask & SdkU.MASK_PHOTO) != 0 && ex.Message.Contains("código -600"))
        {
            mask &= ~SdkU.MASK_PHOTO;
            return UsuariosBS2.Datos(r, ids, mask);
        }
    }

    static byte[] ABytes<T>(T s) where T : struct
    {
        int n = Marshal.SizeOf<T>();
        IntPtr p = Marshal.AllocHGlobal(n);
        try { Marshal.StructureToPtr(s, p, false); var b = new byte[n]; Marshal.Copy(p, b, 0, n); return b; }
        finally { Marshal.FreeHGlobal(p); }
    }

    static T DeBytes<T>(byte[] b) where T : struct
    {
        IntPtr p = Marshal.AllocHGlobal(b.Length);
        try { Marshal.Copy(b, 0, p, b.Length); return Marshal.PtrToStructure<T>(p); }
        finally { Marshal.FreeHGlobal(p); }
    }

    /// <summary>Encabezado, imagen y plantillas de un rostro (solo los bytes usados). Revisa que tenga sentido.</summary>
    static byte[] RostroCompacto(IntPtr p, string id)
    {
        var enc = new byte[ENC_ROSTRO];
        Marshal.Copy(p, enc, 0, ENC_ROSTRO);
        int plantillas = enc[1];
        int imagen = BitConverter.ToUInt16(enc, 4);
        if (plantillas > SdkU.ROSTRO_TEMPLATES || imagen > SdkU.ROSTRO_IMAGEN)
            throw new InvalidOperationException($"El rostro del usuario {id} no tiene el formato esperado (plantillas {plantillas}, imagen {imagen}); no se respalda para no guardar basura.");
        var b = new byte[ENC_ROSTRO + imagen + plantillas * SdkU.ROSTRO_TEMPLATE];
        Array.Copy(enc, b, ENC_ROSTRO);
        if (imagen > 0) Marshal.Copy(p + ENC_ROSTRO, b, ENC_ROSTRO, imagen);
        if (plantillas > 0) Marshal.Copy(p + ENC_ROSTRO + SdkU.ROSTRO_IMAGEN, b, ENC_ROSTRO + imagen, plantillas * SdkU.ROSTRO_TEMPLATE);
        return b;
    }

    static List<byte[]> Rostros(SdkU.BS2UserBlob b)
    {
        var l = new List<byte[]>();
        if (b.faceObjs == IntPtr.Zero) return l;
        string id = SdkU.Texto(b.user.userID);
        for (int i = 0; i < b.user.numFaces; i++) l.Add(RostroCompacto(b.faceObjs + i * TAM_ROSTRO, id));
        return l;
    }

    /// <summary>Lee todos los usuarios del reloj y los escribe en el archivo. avance(hechos, total).</summary>
    public static Resumen Exportar(Reloj r, string archivo, Action<int, int>? avance = null)
    {
        var ids = UsuariosBS2.Ids(r);
        int conRostro = 0, rostros = 0;
        uint mask = MASK;
        string tmp = archivo + ".part";
        var csv = new StringBuilder("numero,nombre,rostros\r\n");
        using (var fs = new FileStream(tmp, FileMode.Create, FileAccess.Write))
        using (var w = new BinaryWriter(fs))
        {
            w.Write(Encoding.ASCII.GetBytes(MAGIA));
            w.Write((ulong)r.DeviceId);
            w.Write(ids.Count);
            w.Write(TAM_USER); w.Write(TAM_SETTING); w.Write(TAM_ROSTRO);
            for (int ini = 0; ini < ids.Count; ini += 20)
            {
                var pagina = ids.Skip(ini).Take(20).ToList();
                var blobs = DatosRespaldo(r, pagina, ref mask);
                try
                {
                    foreach (var b in blobs)
                    {
                        var caras = Rostros(b);
                        var u = b.user; u.numFaces = (byte)caras.Count;
                        w.Write(ABytes(u));
                        w.Write(ABytes(b.setting));
                        w.Write(b.name);
                        w.Write(b.pin);
                        foreach (var g in b.accessGroupId) w.Write(g);
                        uint foto = Math.Min(b.photo.size, (uint)SdkU.FOTO);
                        w.Write(foto);
                        w.Write(b.photo.data, 0, (int)foto);
                        w.Write((byte)caras.Count);
                        foreach (var c in caras) w.Write(c);
                        if (caras.Count > 0) { conRostro++; rostros += caras.Count; }
                        csv.Append(SdkU.Texto(b.user.userID)).Append(",\"").Append(SdkU.Texto(b.name).Replace("\"", "\"\""))
                           .Append("\",").Append(caras.Count).Append("\r\n");
                    }
                }
                finally { UsuariosBS2.Liberar(blobs); }
                avance?.Invoke(Math.Min(ini + 20, ids.Count), ids.Count);
            }
        }
        File.Move(tmp, archivo, true);
        File.WriteAllText(archivo + ".csv", csv.ToString(), new UTF8Encoding(true));
        return new Resumen(ids.Count, conRostro, rostros, new FileInfo(archivo).Length, (mask & SdkU.MASK_PHOTO) != 0);
    }

    /// <summary>Un usuario leído del archivo: blob listo para BS2_EnrolUser (faceObjs en memoria propia) y sus rostros compactos.</summary>
    internal sealed class Registro : IDisposable
    {
        public SdkU.BS2UserBlob Blob;
        public string Id = "";
        public List<byte[]> Caras = new();
        public byte[] Foto = Array.Empty<byte>();
        public void Dispose()
        {
            if (Blob.faceObjs != IntPtr.Zero) { Marshal.FreeHGlobal(Blob.faceObjs); Blob.faceObjs = IntPtr.Zero; }
        }
    }

    /// <summary>Lee los usuarios del archivo uno por uno (el llamador hace Dispose de cada uno).</summary>
    public static IEnumerable<Registro> Leer(string archivo)
    {
        using var fs = new FileStream(archivo, FileMode.Open, FileAccess.Read);
        using var rd = new BinaryReader(fs);
        if (Encoding.ASCII.GetString(rd.ReadBytes(8)) != MAGIA) throw new InvalidOperationException("El archivo no es un respaldo de rostros.");
        rd.ReadUInt64();
        int n = rd.ReadInt32();
        int tu = rd.ReadInt32(), ts = rd.ReadInt32(), tr = rd.ReadInt32();
        if (tu != TAM_USER || ts != TAM_SETTING || tr != TAM_ROSTRO) throw new InvalidOperationException("El respaldo se hizo con otra versión de las estructuras del SDK.");
        for (int i = 0; i < n; i++)
        {
            var reg = new Registro();
            var b = SdkU.BlobVacio();
            b.user = DeBytes<SdkU.BS2User>(rd.ReadBytes(TAM_USER));
            b.setting = DeBytes<SdkU.BS2UserSetting>(rd.ReadBytes(TAM_SETTING));
            b.name = rd.ReadBytes(SdkU.NOMBRE);
            b.pin = rd.ReadBytes(SdkU.PIN);
            for (int g = 0; g < SdkU.GRUPOS; g++) b.accessGroupId[g] = rd.ReadUInt32();
            uint foto = rd.ReadUInt32();
            reg.Foto = rd.ReadBytes((int)foto);
            b.photo = new SdkU.BS2UserPhoto { size = foto, data = new byte[SdkU.FOTO] };
            Array.Copy(reg.Foto, b.photo.data, reg.Foto.Length);
            int caras = rd.ReadByte();
            for (int c = 0; c < caras; c++)
            {
                var enc = rd.ReadBytes(ENC_ROSTRO);
                int imagen = BitConverter.ToUInt16(enc, 4), plantillas = enc[1];
                var cuerpo = rd.ReadBytes(imagen + plantillas * SdkU.ROSTRO_TEMPLATE);
                var cara = new byte[ENC_ROSTRO + cuerpo.Length];
                Array.Copy(enc, cara, ENC_ROSTRO);
                Array.Copy(cuerpo, 0, cara, ENC_ROSTRO, cuerpo.Length);
                reg.Caras.Add(cara);
            }
            if (caras > 0)
            {
                // estructuras BS2Face completas (imagen y plantillas en su lugar, el resto en ceros)
                IntPtr p = Marshal.AllocHGlobal(caras * TAM_ROSTRO);
                var cero = new byte[TAM_ROSTRO];
                for (int c = 0; c < caras; c++)
                {
                    var cara = reg.Caras[c];
                    int imagen = BitConverter.ToUInt16(cara, 4), plantillas = cara[1];
                    IntPtr q = p + c * TAM_ROSTRO;
                    Marshal.Copy(cero, 0, q, TAM_ROSTRO);
                    Marshal.Copy(cara, 0, q, ENC_ROSTRO);
                    if (imagen > 0) Marshal.Copy(cara, ENC_ROSTRO, q + ENC_ROSTRO, imagen);
                    if (plantillas > 0) Marshal.Copy(cara, ENC_ROSTRO + imagen, q + ENC_ROSTRO + SdkU.ROSTRO_IMAGEN, plantillas * SdkU.ROSTRO_TEMPLATE);
                }
                b.faceObjs = p;
            }
            b.user.numFaces = (byte)caras;
            reg.Blob = b;
            reg.Id = SdkU.Texto(b.user.userID);
            yield return reg;
        }
    }

    /// <summary>
    /// Compara contra el reloj los primeros <paramref name="cuantos"/> usuarios con rostro del archivo
    /// (rostros, foto y nombre byte por byte). Solo lee; sirve para confirmar que el respaldo sirve.
    /// </summary>
    public static (int revisados, int iguales, List<string> distintos) Verificar(Reloj r, string archivo, int cuantos)
    {
        int revisados = 0, iguales = 0;
        var distintos = new List<string>();
        var enReloj = new HashSet<string>(UsuariosBS2.Ids(r));
        uint mask = MASK;
        foreach (var reg in Leer(archivo))
        {
            using (reg)
            {
                if (reg.Caras.Count == 0 || !enReloj.Contains(reg.Id)) continue;
                var blobs = DatosRespaldo(r, new[] { reg.Id }, ref mask);
                try
                {
                    var b = blobs[0];
                    var caras = Rostros(b);
                    uint foto = Math.Min(b.photo.size, (uint)SdkU.FOTO);
                    bool ok = caras.Count == reg.Caras.Count
                              && caras.Zip(reg.Caras).All(x => x.First.AsSpan().SequenceEqual(x.Second))
                              && ((mask & SdkU.MASK_PHOTO) == 0 || b.photo.data.AsSpan(0, (int)foto).SequenceEqual(reg.Foto))
                              && SdkU.Texto(b.name) == SdkU.Texto(reg.Blob.name);
                    if (ok) iguales++; else distintos.Add(reg.Id);
                }
                finally { UsuariosBS2.Liberar(blobs); }
                if (++revisados >= cuantos) break;
            }
        }
        return (revisados, iguales, distintos);
    }

    /// <summary>
    /// Carga al reloj los usuarios del respaldo que tienen rostro. A quien ya tiene rostro en el reloj no se
    /// le toca, salvo con <paramref name="reemplazar"/>. <paramref name="ids"/> = null: todos los del archivo.
    /// </summary>
    public static ResultadoUsuarios Restaurar(Reloj r, string archivo, IList<string>? ids, bool reemplazar, Action<int, int>? avance = null)
    {
        var res = new ResultadoUsuarios();
        var filtro = ids == null ? null : new HashSet<string>(ids);
        var enReloj = UsuariosBS2.Ids(r);
        var conRostro = new HashSet<string>();
        var cab = UsuariosBS2.Datos(r, enReloj, SdkU.MASK_DATA);
        try { foreach (var b in cab) if (b.user.numFaces > 0) conRostro.Add(SdkU.Texto(b.user.userID)); }
        finally { UsuariosBS2.Liberar(cab); }

        int vistos = 0;
        foreach (var reg in Leer(archivo))
        {
            using (reg)
            {
                if (filtro != null && !filtro.Contains(reg.Id)) continue;
                vistos++;
                if (reg.Caras.Count == 0) { res.SinRostro.Add(reg.Id); continue; }
                if (conRostro.Contains(reg.Id) && !reemplazar) { res.YaEstaban.Add(reg.Id); continue; }
                var blob = reg.Blob;
                int c = UsuariosBS2.ConReintentos(r, () => SdkU.BS2_EnrolUser(r.Contexto, r.DeviceId, new[] { blob }, 1, 1));
                if (c == Sdk.BS_SDK_SUCCESS) res.Hechos.Add(reg.Id);
                else res.Errores.Add(new ErrorUsuario(reg.Id, $"código {c}"));
                if (vistos % 20 == 0) avance?.Invoke(res.Hechos.Count, vistos);
            }
        }
        if (filtro != null)
        {
            var leidos = new HashSet<string>(res.Hechos.Concat(res.YaEstaban).Concat(res.SinRostro).Concat(res.Errores.Select(e => e.Id)));
            foreach (var i in filtro.Where(i => !leidos.Contains(i))) res.Errores.Add(new ErrorUsuario(i, "no está en el respaldo"));
        }
        return res;
    }
}
