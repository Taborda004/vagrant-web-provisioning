# 🚀 SIMCOMP — Guía Definitiva de Migración a AWS (Desde Cero)

> **Requisitos previos:** Solo necesitas un navegador web, una tarjeta de crédito/débito y tu proyecto SIMCOMP subido a GitHub.

---

## 📋 RESUMEN: ¿Qué tiene tu proyecto?

Analicé absolutamente todos los archivos de tu carpeta `simcomp`. Tu proyecto tiene:

- **`provisioning_docker/stack.yml`** → Archivo de despliegue para Docker Swarm (el que vamos a usar)
- **15 imágenes Docker** ya publicadas en Docker Hub bajo `deytonro/simcomp-*:v1.0.0`
- **5 bases de datos** PostgreSQL (auth, personas, automotores, infracciones, comparendos)
- **6 microservicios** Node.js (auth, personas, automotores, infracciones, comparendos, reportes)
- **1 servicio de analíticas** con Apache Spark + 1 Spark Master + 2 Spark Workers
- **2 réplicas de frontend** (React/Vite servido con Nginx)
- **1 balanceador HAProxy** con configuración Swarm (`haproxy.swarm.cfg`)
- **Monitoreo completo:** Prometheus, Grafana (con dashboard JSON), Node-Exporter, cAdvisor, Glances
- **6 archivos de secretos** generados por `scripts/build-secrets.sh`
- **1 dataset CSV** de 3.7 MB para Spark en `analytics-spark-service/data/`

**No se modifica ni un solo archivo.** Todo se despliega usando tu `stack.yml` tal cual.

---

## PASO 1 · Crear tu Cuenta de AWS

1. Abre tu navegador y ve a **https://aws.amazon.com**
2. Clic en **"Crear una cuenta de AWS"** (botón naranja, arriba a la derecha)
3. Ingresa tu correo electrónico y una contraseña
4. Pon un nombre de cuenta: `simcomp-proyecto`
5. Te pedirá datos de contacto y una **tarjeta de crédito/débito** (no cobran nada aún)
6. Verificación: te enviarán un código por SMS o llamada
7. Selecciona el plan **"Basic Support - Gratuito"**
8. Clic en **"Completar registro"**

---

## PASO 2 · Entrar a la Consola de EC2

1. Ve a **https://console.aws.amazon.com** e inicia sesión
2. **Arriba a la derecha**, verás el nombre de una región (ej: "Ohio"). Haz clic ahí y selecciona **"EE.UU. Este (Norte de Virginia) us-east-1"**
3. En la **barra de búsqueda** de arriba, escribe **EC2** y haz clic en el primer resultado
4. Ya estás en el panel de EC2

---

## PASO 3 · Crear la Llave SSH (Key Pair)

1. En el **menú izquierdo**, busca la sección **"Red y seguridad"** y haz clic en **"Pares de claves"**
2. Clic en el botón naranja **"Crear par de claves"**
3. Llena:
   - **Nombre:** `simcomp-key`
   - **Tipo de par de claves:** RSA
   - **Formato de archivo de clave privada:** `.pem`
4. Clic en **"Crear par de claves"**
5. Se descargará un archivo `simcomp-key.pem`. **Muévelo a una carpeta segura**, por ejemplo: `C:\Users\mauri\.ssh\simcomp-key.pem`

---

## PASO 4 · Crear el Grupo de Seguridad (Cortafuegos)

1. En el **menú izquierdo**, sección **"Red y seguridad"** → clic en **"Grupos de seguridad"**
2. Clic en **"Crear grupo de seguridad"**
3. **Detalles básicos:**
   - Nombre del grupo de seguridad: `simcomp-swarm-sg`
   - Descripción: `Puertos para Docker Swarm y SIMCOMP`
   - VPC: **déjala como está** (la que viene por defecto)

4. **Reglas de entrada** — Haz clic en **"Agregar regla"** 12 veces (una por fila):

| # | Tipo | Puerto | Tipo de origen | Origen | Descripción |
|---|---|---|---|---|---|
| 1 | SSH | 22 | Mi IP | *(se llena solo)* | SSH admin |
| 2 | HTTP | 80 | Anywhere-IPv4 | 0.0.0.0/0 | App SIMCOMP |
| 3 | TCP personalizado | 2377 | Personalizada | `172.31.0.0/16` | Swarm management |
| 4 | TCP personalizado | 7946 | Personalizada | `172.31.0.0/16` | Swarm nodos TCP |
| 5 | UDP personalizado | 7946 | Personalizada | `172.31.0.0/16` | Swarm nodos UDP |
| 6 | UDP personalizado | 4789 | Personalizada | `172.31.0.0/16` | Red overlay |
| 7 | TCP personalizado | 3000 | Anywhere-IPv4 | 0.0.0.0/0 | Grafana |
| 8 | TCP personalizado | 8404 | Anywhere-IPv4 | 0.0.0.0/0 | HAProxy Stats |
| 9 | TCP personalizado | 8010 | Anywhere-IPv4 | 0.0.0.0/0 | Spark Analytics |
| 10 | TCP personalizado | 4040 | Anywhere-IPv4 | 0.0.0.0/0 | Spark UI |
| 11 | TCP personalizado | 9090 | Anywhere-IPv4 | 0.0.0.0/0 | Prometheus |
| 12 | TCP personalizado | 61208 | Anywhere-IPv4 | 0.0.0.0/0 | Glances |

5. **Reglas de salida:** NO TOQUES NADA. Deja la regla por defecto (Todo el tráfico → 0.0.0.0/0)
6. Clic en **"Crear grupo de seguridad"**

---

## PASO 5 · Crear los 3 Servidores

### Servidor 1: simcomp-manager

1. En el menú izquierdo clic en **"Instancias"** → botón naranja **"Lanzar instancias"**
2. **Nombre:** `simcomp-manager`
3. **Imágenes de aplicaciones y SO (AMI):** Haz clic en **Ubuntu**. Selecciona **Ubuntu Server 24.04 LTS**. Arquitectura: **64 bits (x86)**
4. **Tipo de instancia:** En el buscador escribe `t3.large` y selecciónalo (2 vCPU, 8 GiB)
5. **Par de claves:** Selecciona `simcomp-key`
6. **Configuración de red:** Clic en **"Editar"**
   - En "Firewall (grupos de seguridad)" selecciona: **"Seleccionar grupo de seguridad existente"**
   - Busca y marca: **simcomp-swarm-sg**
7. **Configurar almacenamiento:** Cambia a **30 GiB**, tipo **gp3**
8. Clic en **"Lanzar instancia"**

### Servidor 2: simcomp-worker1

Repite los pasos del 1 al 8 pero con nombre: **`simcomp-worker1`**. Todo lo demás es idéntico.

### Servidor 3: simcomp-worker2

Repite los pasos del 1 al 8 pero con nombre: **`simcomp-worker2`**. Todo lo demás es idéntico.

### Anotar las IPs

1. Ve a **Instancias** en el menú izquierdo
2. Espera a que las 3 digan **"En ejecución"**
3. Haz clic en cada instancia y anota sus IPs:

```
simcomp-manager:   IP Pública = ________   IP Privada = ________
simcomp-worker1:   IP Pública = ________   IP Privada = ________
simcomp-worker2:   IP Pública = ________   IP Privada = ________
```

---

## PASO 6 · Conectarte a los Servidores

Abre **3 ventanas de PowerShell** en tu computadora (Tecla Windows → escribe PowerShell → clic).

### 6.1 Arreglar permisos de la llave (solo la primera vez, en 1 terminal)

```powershell
icacls "C:\Users\mauri\.ssh\simcomp-key.pem" /inheritance:r /grant:r "$($env:USERNAME):(R)"
```

### 6.2 Conectarte (una terminal por servidor)

**Terminal 1 (Manager):**
```powershell
ssh -i "C:\Users\mauri\.ssh\simcomp-key.pem" ubuntu@PEGAR_IP_PUBLICA_MANAGER
```

**Terminal 2 (Worker 1):**
```powershell
ssh -i "C:\Users\mauri\.ssh\simcomp-key.pem" ubuntu@PEGAR_IP_PUBLICA_WORKER1
```

**Terminal 3 (Worker 2):**
```powershell
ssh -i "C:\Users\mauri\.ssh\simcomp-key.pem" ubuntu@PEGAR_IP_PUBLICA_WORKER2
```

Cuando pregunte `Are you sure...?` escribe **yes** y Enter.

Si ves `ubuntu@ip-172-31-xx-xx:~$` → **¡estás dentro!** ✅

---

## PASO 7 · Instalar Docker (en las 3 terminales)

Copia y pega este bloque completo **en cada una de las 3 terminales**:

```bash
sudo apt update && sudo apt upgrade -y && \
sudo apt install -y ca-certificates curl gnupg lsb-release apt-transport-https git && \
sudo install -m 0755 -d /etc/apt/keyrings && \
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo tee /etc/apt/keyrings/docker.asc > /dev/null && \
sudo chmod a+r /etc/apt/keyrings/docker.asc && \
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
sudo tee /etc/apt/sources.list.d/docker.list > /dev/null && \
sudo apt update && \
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin && \
sudo usermod -aG docker ubuntu
```

Espera a que termine (2-3 minutos por máquina). Luego **en las 3 terminales** escribe:

```bash
exit
```

Y vuelve a conectarte con los mismos comandos SSH del Paso 6.2.

Verifica en las 3:
```bash
docker --version
```

---

## PASO 8 · Clonar el Proyecto (en las 3 terminales)

**En cada una de las 3 terminales** ejecuta:

```bash
git clone https://github.com/TU_USUARIO/simcomp.git
cd simcomp
```

---

## PASO 9 · Crear el Clúster Swarm

### 9.1 Inicializar (SOLO en la Terminal del Manager)

```bash
docker swarm init --advertise-addr $(hostname -I | awk '{print $1}')
```

Te mostrará un comando como este:
```
docker swarm join --token SWMTKN-1-xxxxxxx 172.31.20.10:2377
```
**CÓPIALO COMPLETO.**

### 9.2 Unir Workers (en las Terminales de Worker1 y Worker2)

Pega el comando copiado **en la Terminal del Worker 1** y luego **en la Terminal del Worker 2**. Ambas deben decir: `This node joined a swarm as a worker.`

### 9.3 Verificar (en la Terminal del Manager)

```bash
docker node ls
```

Debes ver 3 nodos con estado **Ready**. Si los ves → **¡clúster listo!** ✅

---

## PASO 10 · Generar Secretos y Desplegar (SOLO en el Manager)

### 10.1 Generar secretos

```bash
cd ~/simcomp
chmod +x scripts/build-secrets.sh
bash scripts/build-secrets.sh
```

Verifica:
```bash
ls provisioning_docker/secrets/
```
Debe mostrar 6 archivos `.txt`.

### 10.2 ¡DESPLEGAR! 🚀

```bash
cd ~/simcomp/provisioning_docker
docker stack deploy -c stack.yml simcomp
```

Espera **5-10 minutos** (se descargan las imágenes de Docker Hub).

### 10.3 Verificar

```bash
docker stack services simcomp
```

Espera hasta que todas las réplicas estén completas (ej: `4/4`, `3/3`, `2/2`, `1/1`).

Para ver en qué servidor cayó cada contenedor:
```bash
docker stack ps simcomp
```

---

## PASO 11 · ¡Tu App está en Internet! 🎉

Abre tu navegador y escribe la **IP Pública del Manager**:

| Servicio | URL | Credenciales |
|---|---|---|
| **App SIMCOMP** | `http://IP_PUBLICA_MANAGER` | — |
| **HAProxy Stats** | `http://IP_PUBLICA_MANAGER:8404/stats` | admin / Admin123* |
| **Grafana** | `http://IP_PUBLICA_MANAGER:3000` | admin / admin |
| **Spark Analytics** | `http://IP_PUBLICA_MANAGER:8010` | — |
| **Spark UI** | `http://IP_PUBLICA_MANAGER:4040` | — |
| **Prometheus** | `http://IP_PUBLICA_MANAGER:9090` | — |
| **Glances** | `http://IP_PUBLICA_MANAGER:61208` | — |

---

## 🛑 APAGAR LOS SERVIDORES (Para no gastar dinero)

Cada hora encendida cuesta ~$0.10 por servidor ($0.30/hora las 3 juntas).

### Apagar temporalmente (puedes encenderlos después):
1. Ve a **EC2 → Instancias**
2. Selecciona las 3 (marca las casillas)
3. Clic en **"Estado de la instancia" → "Detener instancia"**

### Encender de nuevo:
1. Selecciona las 3
2. **"Estado de la instancia" → "Iniciar instancia"**
3. Las IPs Públicas cambiarán. Anota las nuevas.

### Eliminar definitivamente (dejas de pagar):
1. **"Estado de la instancia" → "Terminar instancia"**

---

## 🔧 Comandos Útiles (en el Manager)

```bash
# Ver estado de todos los servicios
docker stack services simcomp

# Ver logs de un servicio
docker service logs -f simcomp_ms-auth-service

# Apagar todo el proyecto
docker stack rm simcomp

# Volver a desplegar
cd ~/simcomp/provisioning_docker
docker stack deploy -c stack.yml simcomp
```
