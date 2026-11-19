<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asistente IA del chat (nóminas y RH) con Groq (API compatible con OpenAI, plan gratis).
 * Tabla dbo.ChatIAMensaje (application/sql/chat_ia.sql); clave y modelo en dbo.ConfigSistema.
 * No consulta datos del sistema: solo responde dudas con lo que sabe y con la guía de abajo.
 */
class ChatIAModel extends CI_Model {

    const CLAVE = 'groq_api_key';
    const MODELO = 'groq_modelo';
    const MODELO_INICIAL = 'llama-3.3-70b-versatile';
    const URL = 'https://api.groq.com/openai/v1/';
    const POR_HORA = 40;        // mensajes por usuario por hora (cuida la cuota gratis)
    const HISTORIAL = 16;       // mensajes anteriores que se le mandan como contexto

    public function clave(){
        return $this->config_(self::CLAVE);
    }

    public function modelo(){
        $m = $this->config_(self::MODELO);
        return $m !== '' ? $m : self::MODELO_INICIAL;
    }

    public function guardarConfig($clave, $valor, $quien){
        $this->db->query(
            "MERGE dbo.ConfigSistema AS t
             USING (SELECT ? AS clave) AS s ON t.clave = s.clave
             WHEN MATCHED THEN UPDATE SET valor = ?, fechaModifico = GETDATE(), usuarioModifico = ?
             WHEN NOT MATCHED THEN INSERT (clave, valor, usuarioModifico) VALUES (?, ?, ?);",
            array($clave, $valor, $quien, $clave, $valor, $quien));
    }

    public function mensajes($idUsuario){
        return $this->db->query(
            "SELECT id, rol, texto, CONVERT(VARCHAR(19), fecha, 120) AS fecha
             FROM dbo.ChatIAMensaje WHERE idUsuario = ? AND borrado = 0 ORDER BY id",
            array($idUsuario))->result_array();
    }

    public function guardar($idUsuario, $rol, $texto){
        $this->db->query("INSERT INTO dbo.ChatIAMensaje (idUsuario, rol, texto) VALUES (?, ?, ?)", array($idUsuario, $rol, $texto));
    }

    public function nueva($idUsuario){
        $this->db->query("UPDATE dbo.ChatIAMensaje SET borrado = 1 WHERE idUsuario = ? AND borrado = 0", array($idUsuario));
    }

    public function enviadosUltimaHora($idUsuario){
        $f = $this->db->query(
            "SELECT COUNT(*) AS n FROM dbo.ChatIAMensaje WHERE idUsuario = ? AND rol = 'user' AND fecha > DATEADD(HOUR, -1, GETDATE())",
            array($idUsuario))->row_array();
        return (int) $f['n'];
    }

    /**
     * Pregunta al asistente con el historial de la conversación.
     * Regresa array('texto' => ...) o array('error' => ...).
     */
    public function preguntar($idUsuario, $nombreUsuario){
        $previos = $this->db->query(
            "SELECT TOP " . self::HISTORIAL . " rol, texto FROM dbo.ChatIAMensaje
             WHERE idUsuario = ? AND borrado = 0 ORDER BY id DESC", array($idUsuario))->result_array();
        $mensajes = array(array('role' => 'system', 'content' => $this->instrucciones($nombreUsuario)));
        foreach (array_reverse($previos) as $p) {
            $mensajes[] = array('role' => $p['rol'], 'content' => $p['texto']);
        }
        return $this->completar($this->clave(), $mensajes, true);
    }

    /** Prueba una clave antes de guardarla. */
    public function probar($clave){
        return $this->completar($clave, array(array('role' => 'user', 'content' => 'Responde solo: ok')), true);
    }

    // ------------------------------------------------------------------

    private function completar($clave, $mensajes, $reintentar){
        $modelo = $this->modelo();
        $r = $this->llamar($clave, 'chat/completions', array(
            'model' => $modelo, 'messages' => $mensajes, 'temperature' => 0.3, 'max_tokens' => 1200,
        ));
        if (isset($r['error'])) {
            // Groq retira modelos de vez en cuando: se elige otro disponible y se reintenta una vez
            if ($reintentar && !empty($r['modelo'])) {
                $nuevo = $this->elegirModelo($clave, $modelo);
                if ($nuevo) {
                    $this->guardarConfig(self::MODELO, $nuevo, 'automatico');
                    return $this->completar($clave, $mensajes, false);
                }
            }
            return array('error' => $r['error']);
        }
        $texto = isset($r['datos']['choices'][0]['message']['content']) ? trim($r['datos']['choices'][0]['message']['content']) : '';
        if ($texto === '') return array('error' => 'El asistente no respondió nada; intenta de nuevo.');
        return array('texto' => $texto);
    }

    private function elegirModelo($clave, $actual){
        $r = $this->llamar($clave, 'models', null);
        if (isset($r['error']) || empty($r['datos']['data'])) return null;
        $ids = array();
        foreach ($r['datos']['data'] as $m) {
            if (!empty($m['id']) && (!isset($m['active']) || $m['active'])) $ids[] = $m['id'];
        }
        // modelos de texto grandes primero; nunca los de audio ni los filtros "guard"
        foreach (array('llama-3.3-70b', 'gpt-oss-120b', 'llama-4', '70b', 'gpt-oss', 'qwen', 'llama') as $pref) {
            foreach ($ids as $id) {
                if ($id !== $actual && stripos($id, $pref) !== false && !preg_match('/whisper|guard|tts|playai|distil/i', $id)) return $id;
            }
        }
        return null;
    }

    private function llamar($clave, $ruta, $cuerpo){
        $ch = curl_init(self::URL . $ruta);
        $opciones = array(
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45, CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $clave, 'Content-Type: application/json'),
        );
        if ($cuerpo !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = json_encode($cuerpo);
        }
        curl_setopt_array($ch, $opciones);
        $resp = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return array('error' => 'No hay conexión con el servicio de IA (' . $err . ').');
        $d = json_decode($resp, true);
        if ($codigo == 200 && is_array($d)) return array('datos' => $d);

        $msg = isset($d['error']['message']) ? $d['error']['message'] : '';
        $code = isset($d['error']['code']) ? $d['error']['code'] : '';
        if ($codigo == 401) return array('error' => 'La clave de Groq no es válida.');
        if ($codigo == 429) return array('error' => 'Se alcanzó el límite gratis de la IA por ahora. Espera un momento e intenta de nuevo.');
        if ($code === 'model_not_found' || $code === 'model_decommissioned' || $codigo == 404 || stripos($msg, 'decommissioned') !== false || stripos($msg, 'model') !== false && $codigo == 400) {
            return array('error' => 'El modelo de IA ya no está disponible.', 'modelo' => true);
        }
        return array('error' => 'El servicio de IA respondió con error ' . $codigo . ($msg !== '' ? ': ' . $msg : '.'));
    }

    private function config_($clave){
        $f = $this->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = ?", array($clave))->row_array();
        return $f && $f['valor'] !== null ? trim((string) $f['valor']) : '';
    }

    /** Lo que sabe el asistente: nómina y RH en México y cómo se usa este sistema. */
    private function instrucciones($nombreUsuario){
        $hoy = date('Y-m-d');
        return <<<TXT
Eres el "Asistente de Nóminas", un experto en nómina y recursos humanos en México que ayuda al equipo de nóminas de la empresa dentro de su sistema interno "Nóminas". Hoy es {$hoy}. Estás hablando con {$nombreUsuario}.

Cómo responder:
- Siempre en español, claro y directo, como un compañero experto. Respuestas cortas; usa listas o pasos cuando ayuden. Puedes usar **negritas**.
- Si haces cálculos (horas extra, vacaciones, aguinaldo, prima vacacional, finiquito, PTU, faltas, retardos), muestra la fórmula y los pasos.
- Cifras que cambian cada año (salario mínimo, UMA, tablas de ISR, cuotas IMSS, tope de PTU): da la regla y di que el valor exacto hay que confirmarlo en la fuente oficial (DOF, SAT, IMSS, CONASAMI) para el año en curso. No inventes cifras.
- Si algo depende del contrato colectivo, del reglamento interno o de la política de la empresa, dilo.
- NO tienes acceso a la base de datos ni a datos de los empleados. Si te piden datos de una persona, explica en qué pantalla del sistema se consultan.
- No des asesoría legal definitiva en conflictos (demandas, despidos controvertidos): orienta y recomienda consultar al área jurídica.
- Si no sabes algo del sistema, dilo y sugiere preguntar al administrador en el chat (botón "Reportar falla").

Conocimiento de nómina y RH (México):
- Ley Federal del Trabajo (LFT): jornada diurna 8 h, nocturna 7 h, mixta 7.5 h; horas extra: las primeras 9 a la semana al doble y las que excedan al triple; descanso semanal y prima dominical (25 % mínimo sobre el salario del día si se trabaja en domingo); día de descanso trabajado se paga doble adicional al salario; días de descanso obligatorio (art. 74) se pagan triple si se trabajan.
- Vacaciones (reforma 2023, art. 76): 12 días el primer año, +2 por año hasta 20 días al quinto año, y a partir del sexto +2 días cada 5 años. Prima vacacional mínima 25 %.
- Aguinaldo: mínimo 15 días de salario, antes del 20 de diciembre, proporcional si no completó el año.
- PTU, finiquito y liquidación (indemnización de 3 meses, 20 días por año en sus casos, prima de antigüedad de 12 días por año con tope de 2 salarios mínimos), incapacidades del IMSS (enfermedad general, riesgo de trabajo, maternidad), permisos con y sin goce, faltas injustificadas, retardos, premios de puntualidad y asistencia, IMSS/INFONAVIT, ISR y subsidio, CFDI de nómina, salario diario integrado.

Cómo funciona el sistema "Nóminas" de la empresa:
- Las checadas vienen de los relojes checadores (Suprema/BioStar). "Checadas Directo de Checadores y Biostar" muestra por semana las 4 checadas de cada día (Entrada, Entrada comedor, Salida comedor, Salida), faltas, retardos y tiempo extra; el botón Procesar recalcula la semana. "Reporte Checadas Nomiplus" es el reporte del sistema anterior.
- Incidencias: se capturan por empleado (Incidencias) o para toda una empresa/planta (Incidencias x Empresa). Cada concepto tiene clave corta: VAC vacaciones, INCA incapacidad, PER permisos, "tran" = Retardo ruta transporte (justifica el retardo de ese día por el transporte; solo aparece en la columna de Entrada y no quita el premio de puntualidad). Las vacaciones e incapacidades de Microsip se traen solas cada hora (etiqueta "Microsip"); si se cambian en Microsip, se actualizan aquí.
- Conceptos con goce de sueldo cuentan como día pagado; sin goce cuentan como falta.
- Horarios: catálogo de horarios, Asignación de horarios (por empleado) y Asignación de horarios x Empresa (pregunta si es a todos o solo a algunos y separa los de turno nocturno).
- Reporte de asistencia TXT: genera el archivo para la nómina con días trabajados, faltas, tiempo extra y conceptos (premio de puntualidad, premio de asistencia, prima dominical, descanso trabajado, día festivo). En las plantas de Torreón las claves son 008 puntualidad, 009 asistencia, 090 prima dominical, 078 descanso trabajado y 073 día festivo.
- Inicio muestra indicadores de la semana (asistencias, faltas, retardos, vacaciones, incapacidades/permisos) por planta.
- Chat: canal de todos, mensajes directos, grupos y "Reportar falla" para avisar errores del sistema a los administradores.
TXT;
    }
}
