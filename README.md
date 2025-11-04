#  Taller de Provisionamiento con Vagrant y Shell

Este proyecto despliega dos máquinas virtuales Ubuntu 20.04 usando **Vagrant + VirtualBox**:
- **web**: Servidor Apache + PHP
- **db**: Servidor PostgreSQL  
Todo se configura automáticamente mediante **scripts de provisionamiento en Shell**.

---

##  Objetivo

Aprender a usar **Vagrant** para:
- Crear entornos reproducibles.
- Automatizar la instalación de servicios con **Shell scripts**.
- Implementar una aplicación web con **Apache, PHP y PostgreSQL**.

---

##  Requisitos (Windows)

1. **Instalar**
   - [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
   - [Vagrant](https://www.vagrantup.com/downloads)
   - [Git](https://git-scm.com/downloads) (opcional)

2. **Desactivar Hyper-V y WSL2** si usas VirtualBox:
   ```powershell
   DISM /Online /Disable-Feature /FeatureName:VirtualMachinePlatform /NoRestart
   DISM /Online /Disable-Feature /FeatureName:Microsoft-Hyper-V-All /NoRestart
   DISM /Online /Disable-Feature /FeatureName:Windows-Hypervisor-Platform /NoRestart
   bcdedit /set hypervisorlaunchtype off
