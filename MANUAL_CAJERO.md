# 📘 Manual del Cajero - Sistema de Empeño

## Guía Paso a Paso para un Día de Trabajo

---

## 🔐 1. INICIO DEL DÍA

### 1.1 Ingreso al Sistema
1. Abrir el navegador web
2. Ir a la dirección del sistema (ej: `http://sistema-empeno.local/admin`)
3. Ingresar tu **correo electrónico** y **contraseña**
4. Hacer clic en **Iniciar Sesión**

**Nota**: Tu sesión quedará registrada con la hora de ingreso, IP y dispositivo.

---

## 👥 2. REGISTRO DE NUEVO CLIENTE

### Cuándo: Al llegar un cliente nuevo que nunca ha usado el servicio

### Pasos:
1. En el menú lateral, ir a **Gestión → Clientes**
2. Hacer clic en el botón **+ Nuevo Cliente** (esquina superior derecha)
3. Completar el formulario:

#### **Información Personal** (obligatorio)
- **Nombre**: Primer nombre del cliente
- **Apellido**: Apellido del cliente
- **Correo Electrónico**: cliente@ejemplo.com (opcional)
- **Teléfono**: Número de contacto (10 dígitos)
- **Dirección**: Domicilio completo

#### **Identificación** (obligatorio)
- **Tipo de ID**: DPI (Documento Personal de Identificación)
- **Número de DPI**: Número del documento

#### **Notas** (opcional)
- Cualquier información adicional relevante

4. Hacer clic en **Crear**

** El cliente ahora aparecerá en la lista y podrás usarlo para préstamos y ventas**

---

## 📦 3. REGISTRO DE ARTÍCULO (PRENDA)

### Cuándo: Cuando un cliente trae un artículo para empeñar

### Pasos:
1. En el menú lateral, ir a **Inventario → Artículos**
2. Hacer clic en **+ Nuevo Artículo**
3. Completar el formulario:

#### **Información del Artículo** (obligatorio)
- **Nombre**: Descripción breve (ej: "Anillo de Oro")
- **Categoría**: Seleccionar
  - Joyería
  - Electrónica
  - Herramientas
  - Otros
- **Condición**: Estado del artículo
  - Excelente
  - Bueno
  - Regular
  - Malo

#### **Detalles**
- **Marca**: Fabricante del artículo (opcional)
- **Modelo**: Modelo específico (opcional)
- **Número de Serie**: Si aplica (opcional)
- **Descripción**: Detalles adicionales

#### **Valoración** (importante)
- **Valor Tasado**: Precio estimado del artículo ($)
- **Valor de Mercado**: Precio de venta en el mercado ($)
- **Ubicación**: Dónde se guardará el artículo

#### **Sucursal**
- **Sucursal**: Tu sucursal (se selecciona automáticamente)

4. Hacer clic en **Crear**

** El artículo queda registrado con estado "Disponible" y listo para préstamo**

---

## 💰 4. CREAR UN PRÉSTAMO

### Cuándo: Cliente solicita dinero dejando un artículo en garantía

### Pasos:
1. En el menú lateral, ir a **Operaciones → Préstamos**
2. Hacer clic en **+ Nuevo Préstamo**

### 4.1 Información del Préstamo
- **Número de Préstamo**: Se genera automáticamente (ej: L-20251004-0001)
- **Cliente**: Buscar y seleccionar el cliente (usa el buscador)
- **Artículo**: Seleccionar el artículo que deja en garantía
  -  Solo aparecen artículos con estado "Disponible"
- **Sucursal**: Tu sucursal (automático)

### 4.2 Montos
- **Monto del Préstamo**: Cantidad a prestar ($)
- **Tasa de Interés (%)**: Porcentaje a cobrar (ej: 10%)
- **Plazo (días)**: Días para pagar (ej: 30)
- **Fecha de Inicio**: Hoy (automático)
- **Fecha de Vencimiento**: Se calcula automáticamente

**💡 El sistema calcula automáticamente:**
- Monto de Interés = Préstamo × (Tasa ÷ 100)
- Total a Pagar = Préstamo + Interés
- Saldo Pendiente = Total (al inicio)

### 4.3 Estado Inicial
- **Estado**: Seleccionar "Activo"
- **Notas**: Cualquier observación especial

3. Hacer clic en **Crear**

### 📋 Qué sucede después:
-  El artículo cambia a estado "En Préstamo"
-  Se genera un número de préstamo único
-  Puedes **Imprimir el Recibo** haciendo clic en el botón 🖨️
-  El cliente recibe una copia del recibo

---

## 💵 5. REGISTRAR UN PAGO

### Cuándo: Cliente viene a pagar (abono o liquidación total)

### Pasos:
1. En el menú lateral, ir a **Operaciones → Pagos**
2. Hacer clic en **+ Nuevo Pago**

### 5.1 Información del Pago
- **Número de Pago**: Se genera automáticamente (ej: P-20251004-0001)
- **Préstamo**: Buscar por número de préstamo o nombre de cliente
- **Sucursal**: Tu sucursal (automático)

### 5.2 Detalles del Pago
- **Monto**: Cantidad que paga el cliente ($)
  -  **IMPORTANTE**: El sistema muestra "Saldo pendiente: $XXX"
  -  **No puedes ingresar más del saldo pendiente**
  -  Si ingresa el saldo exacto, el préstamo se marcará como PAGADO

- **Fecha de Pago**: Hoy (por defecto)
- **Método de Pago**: Seleccionar
  - Efectivo
  - Transferencia
  - Tarjeta de Débito
  - Tarjeta de Crédito
  - Cheque
  - Otro

### 5.3 Estado y Referencias
- **Número de Referencia**: Número de transacción/cheque (opcional)
- **Estado**: "Completado" (por defecto)
- **Notas**: Observaciones adicionales

3. Hacer clic en **Crear**

### 📋 Qué sucede después:
-  El saldo del préstamo se actualiza automáticamente
-  Si el pago cubre el total:
  - El préstamo cambia a estado "Pagado"
  - El artículo vuelve a estado "Disponible"
  - Cliente puede retirar su prenda
-  Puedes **Imprimir el Recibo de Pago** 🖨️

---

## 🏷️ 6. REALIZAR UNA VENTA

### Cuándo: Cliente compra un artículo (confiscado o disponible)

### Pasos:
1. En el menú lateral, ir a **Operaciones → Ventas**
2. Hacer clic en **+ Nueva Venta**

### 6.1 Información de la Venta
- **Número de Venta**: Se genera automáticamente (ej: S-20251004-0001)
- **Artículo**: Seleccionar el artículo a vender
  - Pueden ser artículos "Disponibles" o "Confiscados"
- **Cliente**: Seleccionar comprador (opcional si es venta al público)
- **Sucursal**: Tu sucursal (automático)

### 6.2 Precios
- **Precio de Venta**: Precio regular del artículo ($)
- **Descuento**: Si aplica descuento ($)
- **Precio Final**: Se calcula automáticamente (Venta - Descuento)

### 6.3 Detalles de la Venta
- **Fecha de Venta**: Hoy (por defecto)
- **Método de Pago**: Efectivo, Transferencia, Tarjeta, etc.
- **Número de Factura**: Si se genera factura (opcional)
- **Estado**: "Completado" (por defecto)
- **Fecha de Entrega**: Si la entrega es posterior (opcional)
- **Notas**: Observaciones adicionales

3. Hacer clic en **Crear**

### 📋 Qué sucede después:
-  El artículo cambia a estado "Vendido"
-  Ya no aparece en inventario disponible
-  Puedes **Imprimir el Recibo de Venta** 🖨️

---

## 🔍 7. CONSULTAS COMUNES

### 7.1 Buscar un Préstamo Activo
1. Ir a **Operaciones → Préstamos**
2. Usar el buscador (🔍) para buscar por:
   - Número de préstamo
   - Nombre del cliente
   - Número de artículo
3. O usar los filtros:
   - Estado: "Activo"
   - Sucursal: Tu sucursal

### 7.2 Ver Historial de un Cliente
1. Ir a **Gestión → Clientes**
2. Buscar al cliente
3. Hacer clic en el ícono de **Ver** (👁️)
4. Ver toda la información y historial

### 7.3 Verificar Artículos Disponibles
1. Ir a **Inventario → Artículos**
2. Usar filtro de "Estado" → "Disponible"
3. Ver qué artículos están listos para préstamo

### 7.4 Revisar Préstamos por Vencer
1. Ir a **Operaciones → Préstamos**
2. Ordenar por "Vencimiento" (hacer clic en la columna)
3. Los más próximos aparecerán primero

---

##  8. CORRECCIONES Y EDICIONES

###  IMPORTANTE: Solo puedes editar registros del día actual

### 8.1 Editar un Préstamo (mismo día)
1. Ir a **Operaciones → Préstamos**
2. Buscar el préstamo creado HOY
3. Hacer clic en el ícono de **Editar** ()
4. Modificar los datos necesarios
5. Guardar cambios

** No podrás editar:**
- Préstamos de días anteriores
- Registros creados por otros usuarios (si no eres Admin/Gerente)

### 8.2 Editar un Pago (mismo día)
- Mismo proceso que préstamos
- Solo pagos registrados HOY

### 8.3 Editar Cliente o Artículo
-  **SÍ puedes editar** en cualquier momento
- Útil para actualizar teléfonos, direcciones, etc.

---

## 🧮 9. HERRAMIENTAS ÚTILES

### 9.1 Calculadora de Tasación
1. En el menú lateral, ir a **Calculadora de Tasación**
2. Usar para estimar valores antes de crear un préstamo
3. Te ayuda a calcular:
   - Valor de préstamo recomendado
   - Intereses
   - Totales

---

## 🚫 10. LO QUE NO PUEDES HACER COMO CAJERO

 **No tienes acceso a:**
- Usuarios (crear/editar usuarios del sistema)
- Registro de Actividades (auditoría del sistema)
- Sesiones Activas (gestión de sesiones)
- Reportes avanzados (solo Admin/Gerente)
- Configuración de Sucursales
- Eliminar registros (préstamos, pagos, ventas)

 **Pero SÍ puedes:**
- Ver toda la información de tu sucursal
- Crear clientes, artículos, préstamos, pagos, ventas
- Editar registros del día actual
- Imprimir todos los recibos necesarios
- Consultar historial y estados

---

## 🔚 11. CIERRE DEL DÍA

### Al finalizar tu turno:

1. **Verifica tus operaciones del día:**
   - Ir a **Operaciones → Préstamos**
   - Filtrar por fecha de hoy
   - Revisar que todo esté correcto

2. **Revisa pagos recibidos:**
   - Ir a **Operaciones → Pagos**
   - Verificar métodos de pago
   - Contar efectivo recibido

3. **Cerrar sesión:**
   - Hacer clic en tu nombre (esquina superior derecha)
   - Seleccionar **Cerrar Sesión**

**💡 Tu sesión de salida quedará registrada automáticamente**

---

## 📞 12. SITUACIONES ESPECIALES

### 12.1 Cliente Quiere Renovar un Préstamo
1. Buscar el préstamo en **Operaciones → Préstamos**
2. Hacer clic en el préstamo
3. Hacer clic en **Renovar Préstamo** 🔄
4. Ingresar:
   - Días de extensión (ej: 30 días más)
   - Nueva tasa de interés (si aplica)
   - Cargo por renovación
5. Confirmar

**Qué sucede:**
- Se extiende la fecha de vencimiento
- Se genera un cargo adicional
- El préstamo sigue activo

### 12.2 Artículo Confiscado (Cliente No Pagó)
 **Solo Admin o Gerente puede marcar como confiscado**
- Informar a tu supervisor
- El artículo pasará a estado "Confiscado"
- Luego puede venderse

### 12.3 Error al Crear un Registro
- **Mismo día**: Editar el registro directamente
- **Días anteriores**: Informar a Admin/Gerente

### 12.4 Sistema Lento o Error
1. Refrescar la página (F5)
2. Si persiste, cerrar sesión y volver a entrar
3. Si continúa, reportar a soporte técnico

---

##  13. CHECKLIST DIARIO

### Al Iniciar:
- [ ] Ingresar al sistema
- [ ] Verificar que estás en tu sucursal correcta
- [ ] Revisar préstamos que vencen hoy

### Durante el Día:
- [ ] Registrar cada operación inmediatamente
- [ ] Imprimir recibos para clientes
- [ ] Verificar saldos antes de aceptar pagos
- [ ] Confirmar datos de clientes nuevos

### Al Cerrar:
- [ ] Revisar operaciones del día
- [ ] Verificar efectivo recibido vs registrado
- [ ] Cerrar sesión correctamente

---

## 🎯 14. CONSEJOS Y BUENAS PRÁCTICAS

###  Hacer:
-  Verificar SIEMPRE la identificación del cliente
-  Revisar bien el artículo antes de registrarlo
-  Explicar al cliente el total a pagar (préstamo + interés)
-  Guardar las prenadas en orden y etiquetadas
-  Imprimir y entregar recibos al cliente
-  Ser cortés y profesional en todo momento

###  Evitar:
-  Crear préstamos sin verificar el artículo
-  Aceptar pagos mayores al saldo sin confirmar
-  Dejar sesión abierta sin supervisión
-  Compartir tu contraseña con otros
-  Entregar artículos sin verificar pago completo
-  Modificar registros de otros días sin autorización

---

## 📱 15. ATAJOS Y FUNCIONES RÁPIDAS

### Navegación Rápida:
- **Dashboard**: Clic en el logo del sistema
- **Buscar**: Usar la barra de búsqueda (🔍) en cada tabla
- **Filtros**: Clic en el ícono de embudo (🔽) en las tablas
- **Refrescar**: Botón de actualizar en cada página

### Impresiones:
- **Recibo de Préstamo**: Botón 🖨️ en la fila del préstamo
- **Recibo de Pago**: Botón 🖨️ en la fila del pago
- **Recibo de Venta**: Botón 🖨️ en la fila de la venta

---

## 🆘 16. PREGUNTAS FRECUENTES

**P: ¿Puedo crear un préstamo sin registrar primero el artículo?**
R: No, primero debes registrar el artículo, luego crear el préstamo.

**P: ¿Qué hago si me equivoqué en el monto de un pago de ayer?**
R: Contacta a tu supervisor (Admin/Gerente), ellos pueden editar registros anteriores.

**P: ¿Puedo eliminar un préstamo?**
R: No, los cajeros no pueden eliminar registros. Solo Admin/Gerente.

**P: ¿El cliente puede pagar más del saldo pendiente?**
R: No, el sistema lo bloqueará y mostrará un error.

**P: ¿Cómo sé si un artículo está disponible para préstamo?**
R: Al crear un préstamo, solo aparecen artículos con estado "Disponible".

**P: ¿Puedo ver préstamos de otras sucursales?**
R: No, solo ves los de tu sucursal asignada.

---

## 📞 SOPORTE

**Para dudas o problemas técnicos:**
- Contactar a tu supervisor inmediato
- Reportar errores del sistema a IT
- No compartir información de acceso

---

**Versión del Manual**: 1.0
**Fecha**: Octubre 2025
**Sistema**: Laravel Pawnshop Management System
