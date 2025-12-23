# 📌 README -- Système de Gestion du Personnel
<img width="1748" height="1124" alt="image" src="https://github.com/user-attachments/assets/b2f13262-d57f-4377-83a0-ab8a6123b36a" />


<img width="1862" height="1324" alt="image" src="https://github.com/user-attachments/assets/d0cdb7d3-2517-43c2-a6c5-a028a8165327" />

<img width="1931" height="1084" alt="image" src="https://github.com/user-attachments/assets/8549921c-50ce-438b-92b4-1e673749a763" />
<img width="1892" height="1038" alt="image" src="https://github.com/user-attachments/assets/1c9af607-17b4-4c63-9eba-3cc09e0f0203" />
<img width="1115" height="384" alt="Screenshot 2025-12-23 213939" src="https://github.com/user-attachments/assets/5e13d096-939b-448d-94e5-be049d51932c" />




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
