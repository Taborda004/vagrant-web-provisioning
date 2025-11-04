#!/usr/bin/env bash

# Actualiza los paquetes del sistema
sudo apt-get update -y

# Instala PostgreSQL
sudo apt-get install -y postgresql postgresql-contrib

# Inicia el servicio
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Crea un usuario y base de datos
sudo -u postgres psql -c "CREATE USER vagrant WITH PASSWORD 'vagrant';"
sudo -u postgres psql -c "CREATE DATABASE taller;"
sudo -u postgres psql -d taller -c "CREATE TABLE personas (id SERIAL PRIMARY KEY, nombre VARCHAR(50));"
sudo -u postgres psql -d taller -c "INSERT INTO personas (nombre) VALUES ('Maurico'), ('Laura'), ('Sara');"