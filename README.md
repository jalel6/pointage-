# 📌 README -- Système de Gestion du Personnel

## 📖 Description du projet

Ce projet est une application web permettant la **gestion du personnel**
au sein d'une organisation.\
Il inclut des fonctionnalités avancées pour les différents rôles :
**Administrateur**, **Employé**, et **Secrétaire**.

------------------------------------------------------------------------

## 🚀 Fonctionnalités principales

### 🔹 Administrateur

-   Gestion complète des employés (ajout, modification, suppression)
-   Consultation des détails d'un employé
-   Gestion des notifications
-   Visualisation des statistiques
-   Consultation des retards et absences
-   Gestion des congés et des jours fériés
-   Accès au tableau de bord administrateur

### 🔹 Employés

-   Faire une demande de congé
-   Consulter les demandes en cours ou traitées
-   Modifier leur profil utilisateur
-   Accéder à leur tableau de bord

### 🔹 Secrétaire

-   Gestion des demandes de congé (validation, refus)
-   Accès rapide aux notifications
-   Consultation des détails des employés
-   Gestion des absences et retards

------------------------------------------------------------------------

## 🗂️ Structure du projet

Principaux fichiers : - `admin_dashboard.php` -
`secretary_dashboard.php` - `profil_employe.php` - `demande_conge.php` -
`ajouter_absents.php` - `db.php` - `docker-compose.yml` -
`apache.conf` - `uploads/`

------------------------------------------------------------------------

## 🛠️ Technologies utilisées

-   PHP\
-   MySQL / MariaDB\
-   HTML / CSS\
-   JavaScript\
-   Docker & Apache

------------------------------------------------------------------------

## ⚙️ Installation

### 1️⃣ Cloner le projet

``` bash
git clone <url_du_projet>
cd System
```

### 2️⃣ Lancer avec Docker

``` bash
docker-compose up --build
```

### 3️⃣ Accéder au site

    http://localhost:8080

------------------------------------------------------------------------

## 🗄️ Base de données

Importer le fichier SQL dans `/sql` :

``` sql
SOURCE sql/base.sql;
```

------------------------------------------------------------------------

## 📌 Auteur

Projet réalisé par **Jalel bouazizi**.
