# 🚀 SIMCOMP en AWS — Guía Definitiva para Principiantes (Parte 1)

> Esta guía asume que **nunca has usado AWS**. Cada paso incluye exactamente dónde hacer clic.

---

## 🧠 ¿Qué vamos a hacer?

Vamos a replicar tu entorno local de Vagrant en la nube de Amazon (AWS). En lugar de 3 máquinas virtuales en tu computadora, tendrás 3 servidores reales en internet:

| Tu PC (Local) | AWS (Nube) |
|---|---|
| `managerDocker` (VM Vagrant) | Servidor EC2 "simcomp-manager" |
| `workerDocker1` (VM Vagrant) | Servidor EC2 "simcomp-worker1" |
| `workerDocker2` (VM Vagrant) | Servidor EC2 "simcomp-worker2" |
| Accedes por `192.168.100.2` | Accedes por una IP pública de internet |

**No se cambia ni un solo archivo del proyecto.** Usaremos el mismo `stack.yml`, las mismas imágenes de Docker Hub y los mismos secretos.

---

## PASO 1: Crear tu Cuenta de AWS

1. Ve a [https://aws.amazon.com](https://aws.amazon.com)
2. Clic en **"Crear una cuenta de AWS"** (botón naranja arriba a la derecha)
3. Llena tus datos:
   - Correo electrónico
   - Contraseña
   - Nombre de la cuenta (pon: `simcomp-proyecto`)
4. **Te pedirá una tarjeta de crédito/débito** — es obligatorio pero NO te cobran nada hasta que uses servicios de pago
5. Verificación telefónica — te llaman o envían SMS con un código
6. Selecciona el plan **"Basic Support - Free"**
7. Clic en **"Completar registro"**

> [!NOTE]
> **¿Qué es AWS?** Es como alquilar computadoras en internet. Amazon tiene miles de servidores y tú alquilas 3 de ellos para correr tu proyecto. Solo pagas por el tiempo que los tengas encendidos.

---

## PASO 2: Entrar a la Consola de AWS

1. Ve a [https://console.aws.amazon.com](https://console.aws.amazon.com)
2. Inicia sesión con tu correo y contraseña
3. **Arriba a la derecha**, verás algo como "N. Virginia" o "Ohio". Haz clic ahí y selecciona la región más cercana a ti:
   - Si estás en Colombia → **US East (N. Virginia)** `us-east-1`
   - Si estás en México → **US East (N. Virginia)** `us-east-1`
4. En la **barra de búsqueda** de arriba, escribe **EC2** y haz clic en el primer resultado

> [!IMPORTANT]
> **¿Qué es EC2?** Son las máquinas virtuales de Amazon. Cada "instancia EC2" es como una computadora completa con su propio sistema operativo, RAM y CPU. Nosotros vamos a crear 3.

---

## PASO 3: Crear la Llave SSH (Key Pair)

Antes de crear los servidores, necesitas una "llave" para poder conectarte a ellos (como una contraseña especial en forma de archivo).

1. En el panel izquierdo de EC2, busca **"Network & Security"** y haz clic en **"Key Pairs"**
2. Clic en el botón **"Create key pair"** (naranja, arriba a la derecha)
3. Llena así:
   - **Name:** `simcomp-key`
   - **Key pair type:** RSA
   - **Private key file format:** `.pem`
4. Clic en **"Create key pair"**
5. **Se descargará automáticamente** un archivo llamado `simcomp-key.pem`. **GUÁRDALO EN UN LUGAR SEGURO** (ej: `C:\Users\mauri\.ssh\simcomp-key.pem`). Si lo pierdes, no podrás entrar a tus servidores.

---

## PASO 4: Crear el Grupo de Seguridad (Security Group)

El Security Group es el **cortafuegos** de tus servidores. Define qué puertos están abiertos y quién puede conectarse.

1. En el panel izquierdo de EC2, clic en **"Security Groups"** (dentro de "Network & Security")
2. Clic en **"Create security group"**
3. **Datos básicos:**
   - **Security group name:** `simcomp-swarm-sg`
   - **Description:** `Puertos para Docker Swarm y SIMCOMP`
   - **VPC:** Deja la que viene por defecto
4. **Inbound rules** (Reglas de entrada) — Haz clic en **"Add rule"** para agregar cada fila:

| # | Type | Port range | Source | Descripción |
|---|---|---|---|---|
| 1 | SSH | 22 | My IP | Para conectarte por terminal |
| 2 | HTTP | 80 | Anywhere-IPv4 (0.0.0.0/0) | App SIMCOMP |
| 3 | Custom TCP | 2377 | Custom → escribe el nombre `simcomp-swarm-sg` y selecciónalo | Swarm management |
| 4 | Custom TCP | 7946 | Custom → `simcomp-swarm-sg` | Swarm nodos TCP |
| 5 | Custom UDP | 7946 | Custom → `simcomp-swarm-sg` | Swarm nodos UDP |
| 6 | Custom UDP | 4789 | Custom → `simcomp-swarm-sg` | Red overlay Swarm |
| 7 | Custom TCP | 3000 | Anywhere-IPv4 | Grafana |
| 8 | Custom TCP | 8404 | Anywhere-IPv4 | HAProxy Stats |
| 9 | Custom TCP | 8010 | Anywhere-IPv4 | Spark Analytics |
| 10 | Custom TCP | 4040 | Anywhere-IPv4 | Spark UI |
| 11 | Custom TCP | 9090 | Anywhere-IPv4 | Prometheus |
| 12 | Custom TCP | 61208 | Anywhere-IPv4 | Glances |

5. **Outbound rules:** Déjalo como está (permite todo el tráfico de salida)
6. Clic en **"Create security group"**

> [!NOTE]
> **¿Por qué "My IP" en SSH?** Para que solo TÚ puedas entrar al servidor por terminal. Si alguien más necesita acceso, agrega su IP también.

---

## PASO 5: Crear los 3 Servidores (Instancias EC2)

### 5.1 Crear el primero: simcomp-manager

1. En EC2, clic en **"Instances"** (panel izquierdo) → **"Launch instances"** (botón naranja)
2. Llena así:

| Campo | Qué poner |
|---|---|
| **Name and tags** | `simcomp-manager` |
| **Application and OS Images (AMI)** | Clic en **Ubuntu**. Selecciona **Ubuntu Server 24.04 LTS (HVM), SSD Volume Type**. Arquitectura: **64-bit (x86)** |
| **Instance type** | Escribe `t3.large` en el buscador y selecciónalo. Dice: 2 vCPU, 8 GiB Memory |
| **Key pair** | Selecciona `simcomp-key` (el que creaste en el Paso 3) |

3. En **"Network settings"**, clic en **"Edit"** (a la derecha):
   - **Firewall (security groups):** Selecciona **"Select existing security group"**
   - Busca y selecciona **`simcomp-swarm-sg`**

4. En **"Configure storage":**
   - Cambia el tamaño a **30 GiB** (o 40 GiB si quieres más espacio)
   - Tipo: **gp3**

5. Clic en **"Launch instance"**

### 5.2 Repetir para Worker 1 y Worker 2

Repite exactamente los mismos pasos dos veces más, pero cambiando solo el nombre:
- Segunda vez: Name = `simcomp-worker1`
- Tercera vez: Name = `simcomp-worker2`

> Todo lo demás es **idéntico** (mismo tipo `t3.large`, misma key pair, mismo security group, mismo storage).

### 5.3 Anotar las IPs

1. Ve a **EC2 → Instances**
2. Espera a que las 3 digan **"Running"** en la columna "Instance state" (toma 1-2 minutos)
3. **Para cada instancia, anota DOS IPs** (haz clic en cada una para ver sus detalles):
   - **Public IPv4 address** — para conectarte desde tu PC (ej: `54.123.45.67`)
   - **Private IPv4 address** — para que se comuniquen entre ellas (ej: `172.31.20.10`)

Anótalas así:
```
simcomp-manager:  Pública=________  Privada=________
simcomp-worker1:  Pública=________  Privada=________
simcomp-worker2:  Pública=________  Privada=________
```
