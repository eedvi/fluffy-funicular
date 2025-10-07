# 📋 Usuarios y Permisos del Sistema

## 👥 Usuarios Creados

### 1. Administrador Principal
- **Email:** `admin@pawnshop.com`
- **Contraseña:** `password`
- **Rol:** Admin
- **Sucursal:** Sucursal Principal (MAIN)
- **Estado:** Activo
- **Acceso:** Super Admin - Sin restricciones

---

### 2. Carlos Méndez
- **Email:** `carlos.mendez@pawnshop.com`
- **Contraseña:** `password`
- **Rol:** Gerente
- **Sucursal:** Sucursal Principal (MAIN)
- **Estado:** Activo

---

### 3. María Fernández
- **Email:** `maria.fernandez@pawnshop.com`
- **Contraseña:** `password`
- **Rol:** Gerente
- **Sucursal:** Sucursal Norte (NORTE)
- **Estado:** Activo

---

### 4. Juan Rodríguez
- **Email:** `juan.rodriguez@pawnshop.com`
- **Contraseña:** `password`
- **Rol:** Cajero
- **Sucursal:** Sucursal Norte (NORTE)
- **Estado:** Activo

---

### 5. Ana López
- **Email:** `ana.lopez@pawnshop.com`
- **Contraseña:** `password`
- **Rol:** Cajero
- **Sucursal:** Sucursal Sur (SUR)
- **Estado:** Activo

---

## 🎭 Roles y Permisos

###  ADMIN (Super Administrador)
**Acceso:** TOTAL - Sin restricciones

**Descripción:**
El rol Admin tiene acceso completo a todas las funcionalidades del sistema sin ninguna restricción de permisos.

**Puede:**
-  Ver, crear, editar y eliminar todos los recursos
-  Gestionar usuarios y roles
-  Acceder a todas las páginas y reportes
-  Ver todos los widgets del dashboard
-  Gestionar sucursales
-  Configurar el sistema

---

### 🎩 GERENTE (Manager)
**Acceso:** Gestión completa excepto configuración de usuarios/roles

#### 📋 Recursos - CRUD Completo
- **Clientes** - view, view_any, create, update
- **Artículos** - view, view_any, create, update
- **Préstamos** - view, view_any, create, update
- **Pagos** - view, view_any, create, update
- **Ventas** - view, view_any, create, update
- **Sucursales** - view, view_any (solo lectura)

#### 📄 Páginas Disponibles
-  Reports (Reportes)
-  AppraisalCalculator (Calculadora de Avalúos)

#### 📊 Widgets del Dashboard
-  LoanStatsWidget (Estadísticas de Préstamos)
-  LoansChartWidget (Gráfico de Préstamos)
-  RevenueChartWidget (Gráfico de Ingresos)

**Puede:**
-  Gestionar clientes (crear, editar, ver)
-  Gestionar artículos (crear, editar, ver)
-  Crear y gestionar préstamos
-  Registrar pagos
-  Realizar ventas
-  Ver todas las sucursales
-  Acceder a reportes y análisis
-  Usar calculadora de avalúos

**No puede:**
-  Eliminar registros
-  Gestionar usuarios
-  Crear o modificar sucursales
-  Acceder a configuración de roles

---

### 💼 CAJERO (Cashier)
**Acceso:** Solo operaciones transaccionales

#### 📋 Recursos
**Solo Lectura:**
- **Clientes** - view, view_any
- **Artículos** - view, view_any
- **Sucursales** - view, view_any

**Crear y Ver:**
- **Préstamos** - view, view_any, create
- **Pagos** - view, view_any, create
- **Ventas** - view, view_any, create

#### 📄 Páginas Disponibles
-  AppraisalCalculator (Calculadora de Avalúos)

#### 📊 Widgets del Dashboard
-  LoanStatsWidget (Estadísticas de Préstamos)

**Puede:**
-  Ver clientes y artículos
-  Crear nuevos préstamos
-  Registrar pagos
-  Realizar ventas
-  Ver su sucursal
-  Usar calculadora de avalúos
-  Ver estadísticas básicas

**No puede:**
-  Editar clientes o artículos
-  Modificar préstamos existentes
-  Editar pagos o ventas
-  Acceder a reportes completos
-  Ver gráficos de ingresos
-  Eliminar ningún registro
-  Gestionar usuarios
-  Acceder a configuración

---

## 🏢 Sucursales

1. **Sucursal Principal** (MAIN)
   - Código: MAIN
   - Marca principal: 
   - Estado: Activo

2. **Sucursal Norte** (NORTE)
   - Código: NORTE
   - Estado: Activo

3. **Sucursal Sur** (SUR)
   - Código: SUR
   - Estado: Activo

---

## 🔑 Credenciales de Acceso

**URL:** `http://localhost:8000/admin`

| Email | Contraseña | Rol | Sucursal |
|-------|-----------|-----|----------|
| admin@pawnshop.com | password | Admin | Principal |
| carlos.mendez@pawnshop.com | password | Gerente | Principal |
| maria.fernandez@pawnshop.com | password | Gerente | Norte |
| juan.rodriguez@pawnshop.com | password | Cajero | Norte |
| ana.lopez@pawnshop.com | password | Cajero | Sur |

---

## 📊 Resumen de Permisos por Recurso

### 🔐 Matriz de Permisos

| Recurso | Admin | Gerente | Cajero |
|---------|-------|---------|--------|
| **Clientes** |  CRUD Completo |  Ver/Crear/Editar | 👁️ Solo Ver |
| **Artículos** |  CRUD Completo |  Ver/Crear/Editar | 👁️ Solo Ver |
| **Préstamos** |  CRUD Completo |  Ver/Crear/Editar |  Ver/Crear |
| **Pagos** |  CRUD Completo |  Ver/Crear/Editar |  Ver/Crear |
| **Ventas** |  CRUD Completo |  Ver/Crear/Editar |  Ver/Crear |
| **Sucursales** |  CRUD Completo | 👁️ Solo Ver | 👁️ Solo Ver |
| **Usuarios** |  CRUD Completo |  Sin acceso |  Sin acceso |
| **Roles** |  CRUD Completo |  Sin acceso |  Sin acceso |
| **Reportes** |  Acceso Total |  Acceso Total |  Sin acceso |
| **Dashboard** |  Todos Widgets |  Widgets Completos | 📊 Widget Básico |

---

## 🎯 Casos de Uso Típicos

### Admin
- Configuración inicial del sistema
- Gestión de usuarios y roles
- Administración de sucursales
- Acceso a todos los reportes
- Auditoría completa del sistema

### Gerente
- Operación diaria de la sucursal
- Supervisión de transacciones
- Gestión de inventario
- Generación de reportes
- Análisis de rendimiento

### Cajero
- Atención al cliente
- Registro de préstamos
- Procesamiento de pagos
- Realización de ventas
- Consulta de información básica

---

**Generado:** 2025-10-02
**Sistema:** Casa de Empeño - Multi-Branch
