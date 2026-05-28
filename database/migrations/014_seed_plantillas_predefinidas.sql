-- Seed 014: Plantillas predefinidas conforme a LAASSP (Ley de Adquisiciones,
-- Arrendamientos y Servicios del Sector Público) y mejores prácticas
-- internacionales (UNCITRAL Model Law, OCDS).
--
-- Idempotente: usa INSERT ... WHERE NOT EXISTS para evitar duplicados.
-- Las plantillas predefinidas se marcan con es_predefinida=1 (no editables por usuarios)
-- y se asignan al primer usuario ADMINISTRADOR disponible como creador.

SET @id_admin = (SELECT id_usuario FROM usuario WHERE rol='ADMINISTRADOR' AND activo=1 ORDER BY id_usuario LIMIT 1);

-- 1) Acta de Junta de Aclaraciones (LAASSP art. 33 Bis)
INSERT INTO plantilla_reporte
    (nombre, descripcion, tipo, contenido_html, variables_esperadas, id_usuario_creador, activa, es_predefinida)
SELECT
    'Acta de Junta de Aclaraciones (LAASSP)',
    'Acta del acto público de junta de aclaraciones conforme al artículo 33 Bis de la LAASSP. Incluye datos de la convocante, descripción del procedimiento, asistentes y resolución de preguntas.',
    'ACTA_ACLARACIONES',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#111;margin:30px}
.header{text-align:center;border-bottom:2px solid #1d4ed8;padding-bottom:10px;margin-bottom:18px}
.header h1{margin:0;font-size:16pt;color:#1d4ed8}
.header h2{margin:4px 0 0;font-size:12pt;color:#444;font-weight:normal}
.section{margin-top:14px}
.section h3{font-size:12pt;color:#1d4ed8;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
table{width:100%;border-collapse:collapse;margin-top:6px}
th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;font-size:10pt}
th{background:#f1f5f9;font-weight:bold}
.firmas{margin-top:32px;display:table;width:100%}
.firma{display:table-cell;text-align:center;padding-top:50px;border-top:1px solid #333;width:33%}
.foot{margin-top:24px;font-size:9pt;color:#666;text-align:center}
.fundamento{font-size:9pt;color:#444;font-style:italic;margin-top:6px}
</style></head><body>
<div class="header">
  <h1>{{convocante_nombre}}</h1>
  <h2>Acta de Junta de Aclaraciones</h2>
</div>
<div class="section">
  <p><strong>Procedimiento:</strong> {{tipo_procedimiento}} N&uacute;m. <strong>{{numero_licitacion}}</strong></p>
  <p><strong>Objeto:</strong> {{descripcion_proyecto}}</p>
  <p><strong>Fecha y hora del acto:</strong> {{fecha_acto}}</p>
  <p><strong>Lugar:</strong> {{lugar_acto}}</p>
</div>
<div class="section">
  <h3>1. Convocante</h3>
  <p>{{dependencia_nombre}}, con domicilio en {{dependencia_domicilio}}, representada por {{responsable_nombre}}.</p>
</div>
<div class="section">
  <h3>2. Asistentes</h3>
  <p>{{asistentes}}</p>
</div>
<div class="section">
  <h3>3. Aclaraciones y respuestas</h3>
  <p>{{aclaraciones_contenido}}</p>
</div>
<div class="section">
  <h3>4. Cierre del acto</h3>
  <p>No habiendo m&aacute;s asuntos que tratar, se cierra la presente junta de aclaraciones siendo las {{hora_cierre}} del d&iacute;a {{fecha_cierre}}.</p>
  <p class="fundamento">Fundamento: art&iacute;culos 33 Bis de la LAASSP y 45 de su Reglamento.</p>
</div>
<div class="firmas">
  <div class="firma">{{firmante_convocante}}<br>Convocante</div>
  <div class="firma">{{firmante_area_requirente}}<br>&Aacute;rea requirente</div>
  <div class="firma">{{firmante_testigo}}<br>Testigo</div>
</div>
<div class="foot">Documento generado por SGPLOPyPC &middot; {{fecha_emision}} &middot; ID Licitaci&oacute;n {{id_licitacion}}</div>
</body></html>',
    'convocante_nombre,tipo_procedimiento,numero_licitacion,descripcion_proyecto,fecha_acto,lugar_acto,dependencia_nombre,dependencia_domicilio,responsable_nombre,asistentes,aclaraciones_contenido,hora_cierre,fecha_cierre,firmante_convocante,firmante_area_requirente,firmante_testigo,fecha_emision,id_licitacion',
    @id_admin, 1, 1
WHERE @id_admin IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plantilla_reporte WHERE nombre='Acta de Junta de Aclaraciones (LAASSP)' AND es_predefinida=1);

-- 2) Acta de Apertura de Proposiciones (LAASSP art. 35)
INSERT INTO plantilla_reporte
    (nombre, descripcion, tipo, contenido_html, variables_esperadas, id_usuario_creador, activa, es_predefinida)
SELECT
    'Acta de Apertura de Proposiciones (LAASSP)',
    'Acta del acto público de presentación y apertura de proposiciones, conforme al artículo 35 de la LAASSP. Lista licitantes, monto de cada proposición y observaciones.',
    'ACTA_APERTURA',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#111;margin:30px}
.header{text-align:center;border-bottom:2px solid #047857;padding-bottom:10px;margin-bottom:18px}
.header h1{margin:0;font-size:16pt;color:#047857}
.header h2{margin:4px 0 0;font-size:12pt;color:#444;font-weight:normal}
.section{margin-top:14px}
.section h3{font-size:12pt;color:#047857;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
table{width:100%;border-collapse:collapse;margin-top:6px}
th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;font-size:10pt}
th{background:#ecfdf5;font-weight:bold}
.firmas{margin-top:32px;display:table;width:100%}
.firma{display:table-cell;text-align:center;padding-top:50px;border-top:1px solid #333;width:50%}
.foot{margin-top:24px;font-size:9pt;color:#666;text-align:center}
.fundamento{font-size:9pt;color:#444;font-style:italic;margin-top:6px}
</style></head><body>
<div class="header">
  <h1>{{convocante_nombre}}</h1>
  <h2>Acta de Presentaci&oacute;n y Apertura de Proposiciones</h2>
</div>
<div class="section">
  <p><strong>Procedimiento:</strong> {{tipo_procedimiento}} N&uacute;m. <strong>{{numero_licitacion}}</strong></p>
  <p><strong>Objeto:</strong> {{descripcion_proyecto}}</p>
  <p><strong>Presupuesto estimado:</strong> ${{presupuesto_estimado}} MXN</p>
  <p><strong>Fecha y hora del acto:</strong> {{fecha_acto}}</p>
</div>
<div class="section">
  <h3>1. Licitantes que presentaron proposici&oacute;n</h3>
  <table>
    <thead><tr><th>#</th><th>Licitante / RFC</th><th>Monto propuesto (MXN)</th><th>Documentaci&oacute;n</th></tr></thead>
    <tbody>{{licitantes_filas}}</tbody>
  </table>
</div>
<div class="section">
  <h3>2. Observaciones</h3>
  <p>{{observaciones}}</p>
</div>
<div class="section">
  <h3>3. Cierre del acto</h3>
  <p>El acto concluy&oacute; siendo las {{hora_cierre}} del d&iacute;a {{fecha_cierre}}, sin haberse desechado proposici&oacute;n alguna en este acto.</p>
  <p class="fundamento">Fundamento: art&iacute;culo 35 de la LAASSP y 47 de su Reglamento.</p>
</div>
<div class="firmas">
  <div class="firma">{{firmante_convocante}}<br>Por la convocante</div>
  <div class="firma">{{firmante_testigo}}<br>Testigo de honor</div>
</div>
<div class="foot">Documento generado por SGPLOPyPC &middot; {{fecha_emision}}</div>
</body></html>',
    'convocante_nombre,tipo_procedimiento,numero_licitacion,descripcion_proyecto,presupuesto_estimado,fecha_acto,licitantes_filas,observaciones,hora_cierre,fecha_cierre,firmante_convocante,firmante_testigo,fecha_emision',
    @id_admin, 1, 1
WHERE @id_admin IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plantilla_reporte WHERE nombre='Acta de Apertura de Proposiciones (LAASSP)' AND es_predefinida=1);

-- 3) Acta de Fallo (LAASSP art. 37)
INSERT INTO plantilla_reporte
    (nombre, descripcion, tipo, contenido_html, variables_esperadas, id_usuario_creador, activa, es_predefinida)
SELECT
    'Acta de Fallo (LAASSP)',
    'Acta de notificación del fallo del procedimiento de contratación. Identifica al licitante adjudicado, monto del contrato y razones del fallo. Conforme al artículo 37 de la LAASSP.',
    'ACTA_FALLO',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#111;margin:30px}
.header{text-align:center;border-bottom:2px solid #b45309;padding-bottom:10px;margin-bottom:18px}
.header h1{margin:0;font-size:16pt;color:#b45309}
.header h2{margin:4px 0 0;font-size:12pt;color:#444;font-weight:normal}
.section{margin-top:14px}
.section h3{font-size:12pt;color:#b45309;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
.box{background:#fef3c7;border-left:4px solid #b45309;padding:10px 14px;margin-top:8px}
.firmas{margin-top:32px;display:table;width:100%}
.firma{display:table-cell;text-align:center;padding-top:50px;border-top:1px solid #333;width:50%}
.foot{margin-top:24px;font-size:9pt;color:#666;text-align:center}
.fundamento{font-size:9pt;color:#444;font-style:italic;margin-top:6px}
</style></head><body>
<div class="header">
  <h1>{{convocante_nombre}}</h1>
  <h2>Acta de Fallo</h2>
</div>
<div class="section">
  <p><strong>Procedimiento:</strong> {{tipo_procedimiento}} N&uacute;m. <strong>{{numero_licitacion}}</strong></p>
  <p><strong>Objeto:</strong> {{descripcion_proyecto}}</p>
  <p><strong>Fecha de emisi&oacute;n:</strong> {{fecha_emision}}</p>
</div>
<div class="section">
  <h3>1. Resultado</h3>
  <div class="box">
    <p><strong>Licitante adjudicado:</strong> {{licitante_ganador}}</p>
    <p><strong>RFC:</strong> {{licitante_rfc}}</p>
    <p><strong>Monto del contrato:</strong> ${{monto_adjudicado}} MXN (con IVA)</p>
    <p><strong>Plazo de ejecuci&oacute;n:</strong> {{plazo_ejecucion}}</p>
  </div>
</div>
<div class="section">
  <h3>2. Motivaci&oacute;n del fallo</h3>
  <p>{{motivacion}}</p>
</div>
<div class="section">
  <h3>3. Licitantes desechados (en su caso)</h3>
  <p>{{licitantes_desechados}}</p>
</div>
<div class="section">
  <h3>4. Notificaci&oacute;n</h3>
  <p>El presente fallo se notifica a los licitantes en t&eacute;rminos del art&iacute;culo 37 de la LAASSP, surtiendo efectos a partir de su publicaci&oacute;n.</p>
  <p class="fundamento">Fundamento: art&iacute;culos 36, 36 Bis y 37 de la LAASSP.</p>
</div>
<div class="firmas">
  <div class="firma">{{firmante_titular}}<br>{{cargo_firmante}}</div>
  <div class="firma">{{firmante_juridico}}<br>&Aacute;rea jur&iacute;dica</div>
</div>
<div class="foot">Documento generado por SGPLOPyPC &middot; {{fecha_emision}}</div>
</body></html>',
    'convocante_nombre,tipo_procedimiento,numero_licitacion,descripcion_proyecto,fecha_emision,licitante_ganador,licitante_rfc,monto_adjudicado,plazo_ejecucion,motivacion,licitantes_desechados,firmante_titular,cargo_firmante,firmante_juridico',
    @id_admin, 1, 1
WHERE @id_admin IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plantilla_reporte WHERE nombre='Acta de Fallo (LAASSP)' AND es_predefinida=1);

-- 4) Dictamen Técnico-Económico (LAASSP art. 36)
INSERT INTO plantilla_reporte
    (nombre, descripcion, tipo, contenido_html, variables_esperadas, id_usuario_creador, activa, es_predefinida)
SELECT
    'Dictamen Técnico-Económico (LAASSP)',
    'Dictamen que sirve de base al fallo, con análisis de cumplimiento técnico y comparativa económica. Conforme al artículo 36 de la LAASSP.',
    'DICTAMEN',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#111;margin:30px}
.header{text-align:center;border-bottom:2px solid #6d28d9;padding-bottom:10px;margin-bottom:18px}
.header h1{margin:0;font-size:16pt;color:#6d28d9}
.header h2{margin:4px 0 0;font-size:12pt;color:#444;font-weight:normal}
.section{margin-top:14px}
.section h3{font-size:12pt;color:#6d28d9;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
table{width:100%;border-collapse:collapse;margin-top:6px}
th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;font-size:10pt}
th{background:#f5f3ff;font-weight:bold}
.foot{margin-top:24px;font-size:9pt;color:#666;text-align:center}
.fundamento{font-size:9pt;color:#444;font-style:italic;margin-top:6px}
</style></head><body>
<div class="header">
  <h1>{{convocante_nombre}}</h1>
  <h2>Dictamen T&eacute;cnico-Econ&oacute;mico</h2>
</div>
<div class="section">
  <p><strong>Procedimiento:</strong> {{tipo_procedimiento}} N&uacute;m. <strong>{{numero_licitacion}}</strong></p>
  <p><strong>Objeto:</strong> {{descripcion_proyecto}}</p>
  <p><strong>Fecha:</strong> {{fecha_emision}}</p>
</div>
<div class="section">
  <h3>1. Evaluaci&oacute;n t&eacute;cnica</h3>
  <table>
    <thead><tr><th>Licitante</th><th>Cumple t&eacute;cnico</th><th>Calificaci&oacute;n</th><th>Observaciones</th></tr></thead>
    <tbody>{{evaluacion_tecnica_filas}}</tbody>
  </table>
</div>
<div class="section">
  <h3>2. Evaluaci&oacute;n econ&oacute;mica</h3>
  <table>
    <thead><tr><th>Licitante</th><th>Monto propuesto</th><th>% sobre presupuesto</th><th>Observaciones</th></tr></thead>
    <tbody>{{evaluacion_economica_filas}}</tbody>
  </table>
</div>
<div class="section">
  <h3>3. Conclusi&oacute;n y recomendaci&oacute;n</h3>
  <p>{{conclusion}}</p>
  <p class="fundamento">Fundamento: art&iacute;culo 36 de la LAASSP.</p>
</div>
<div class="foot">Documento generado por SGPLOPyPC &middot; {{fecha_emision}}</div>
</body></html>',
    'convocante_nombre,tipo_procedimiento,numero_licitacion,descripcion_proyecto,fecha_emision,evaluacion_tecnica_filas,evaluacion_economica_filas,conclusion',
    @id_admin, 1, 1
WHERE @id_admin IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plantilla_reporte WHERE nombre='Dictamen Técnico-Económico (LAASSP)' AND es_predefinida=1);

-- 5) Resumen de Licitación (formato datos abiertos / OCDS-friendly)
INSERT INTO plantilla_reporte
    (nombre, descripcion, tipo, contenido_html, variables_esperadas, id_usuario_creador, activa, es_predefinida)
SELECT
    'Resumen de Licitación (OCDS / Datos Abiertos)',
    'Ficha resumen de una licitación con datos clave para publicación en transparencia. Compatible con campos del Open Contracting Data Standard.',
    'RESUMEN_LICITACION',
    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;font-size:11pt;color:#111;margin:30px}
.header{text-align:center;border-bottom:2px solid #0e7490;padding-bottom:10px;margin-bottom:18px}
.header h1{margin:0;font-size:16pt;color:#0e7490}
.header h2{margin:4px 0 0;font-size:12pt;color:#444;font-weight:normal}
.kv{display:table;width:100%;margin-top:14px}
.kv .row{display:table-row}
.kv .k,.kv .v{display:table-cell;border-bottom:1px solid #e2e8f0;padding:6px 8px;font-size:10.5pt}
.kv .k{font-weight:bold;color:#334155;width:40%;background:#f8fafc}
.section{margin-top:14px}
.section h3{font-size:12pt;color:#0e7490;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
.foot{margin-top:24px;font-size:9pt;color:#666;text-align:center}
.tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:9pt;background:#cffafe;color:#0e7490;font-weight:bold}
</style></head><body>
<div class="header">
  <h1>{{convocante_nombre}}</h1>
  <h2>Resumen de Licitaci&oacute;n &middot; Datos Abiertos</h2>
</div>
<div class="kv">
  <div class="row"><div class="k">N&uacute;mero (OCID)</div><div class="v">{{numero_licitacion}}</div></div>
  <div class="row"><div class="k">Tipo de procedimiento</div><div class="v">{{tipo_procedimiento}}</div></div>
  <div class="row"><div class="k">Estado</div><div class="v"><span class="tag">{{estado_proceso}}</span></div></div>
  <div class="row"><div class="k">Dependencia (buyer)</div><div class="v">{{dependencia_nombre}}</div></div>
  <div class="row"><div class="k">Descripci&oacute;n del objeto</div><div class="v">{{descripcion_proyecto}}</div></div>
  <div class="row"><div class="k">Presupuesto estimado</div><div class="v">${{presupuesto_estimado}} MXN</div></div>
  <div class="row"><div class="k">Ubicaci&oacute;n</div><div class="v">{{ubicacion_proyecto}}</div></div>
  <div class="row"><div class="k">Fecha de creaci&oacute;n</div><div class="v">{{fecha_creacion}}</div></div>
  <div class="row"><div class="k">&Uacute;ltima actualizaci&oacute;n</div><div class="v">{{fecha_actualizacion}}</div></div>
  <div class="row"><div class="k">Responsable</div><div class="v">{{responsable_nombre}}</div></div>
</div>
<div class="section">
  <h3>Hitos del proceso</h3>
  <p>{{hitos}}</p>
</div>
<div class="section">
  <h3>Adjudicaci&oacute;n (en su caso)</h3>
  <p>{{adjudicacion_resumen}}</p>
</div>
<div class="foot">Generado por SGPLOPyPC &middot; {{fecha_emision}} &middot; Estructura compatible con OCDS 1.1 &middot; LGTAIP</div>
</body></html>',
    'convocante_nombre,numero_licitacion,tipo_procedimiento,estado_proceso,dependencia_nombre,descripcion_proyecto,presupuesto_estimado,ubicacion_proyecto,fecha_creacion,fecha_actualizacion,responsable_nombre,hitos,adjudicacion_resumen,fecha_emision',
    @id_admin, 1, 1
WHERE @id_admin IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM plantilla_reporte WHERE nombre='Resumen de Licitación (OCDS / Datos Abiertos)' AND es_predefinida=1);
