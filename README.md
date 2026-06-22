# 🏢 YA CONSULTING — Project Manager

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-v1.x-9553E8?style=flat-square)](https://inertiajs.com)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D_8.2-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=flat-square&logo=sqlite&logoColor=white)](https://sqlite.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

> **Solution interne de suivi de projet, de gestion des dépenses et d'analyse financière de rentabilité.**

Ce dépôt contient l'ensemble des livrables et le code source de l'application de gestion de projet développée pour **YA CONSULTING** 

---

## 🌟 Fonctionnalités Clés

*   📊 **Tableau de Bord Dynamique** : Indicateurs de performance (KPI) financiers, graphiques interactifs (ApexCharts) montrant la répartition des coûts et les tendances mensuelles.
*   🗂️ **Gestion Métier des Projets** : Suivi rigoureux du cycle de vie des projets (statuts, budgets en FCFA, dates clés, chefs de projets et clients associés).
*   💰 **Gestion des Dépenses & Rentabilité** : Saisie structurée des dépenses avec archivage des pièces justificatives (PDF/Images) et calcul automatique du gain brut et du taux de rentabilité.
*   📄 **Génération de Rapports Professionnels** : Exportation de rapports mensuels de performance en PDF (via DomPDF) et CSV, incluant des analyses comparatives (N vs N-1).
*   🔑 **Sécurité & Rôles Fins** : Gestion des accès basée sur les rôles (`admin`, `chef_projet`, `collaborateur`) via Spatie Permission, garantissant la confidentialité des projets.
*   📋 **Journal d'Audit & Traçabilité** : Journalisation automatique de toutes les actions sensibles (créations, modifications, suppressions) via Spatie Activitylog.

---

## 📂 Structure du dépôt

```text
Logiciel-de-gestion-de-projet-YA-CONSULTING/
├── ya-consulting-app/             # Application Web principale (Laravel 11 + Vue 3)
│   ├── app/                       # Logique Backend (Modèles, Contrôleurs, Policies)
│   ├── resources/js/              # Logique Frontend (Composants Vue 3, Pages, Layouts)
│   └── tests/                     # Tests unitaires et fonctionnels
├── _scaffold/                     # Modèles et ressources de squelette technique
├── documentation_technique.html   # Documentation technique interactive (UML, MCD, Architecture...)
├── Cahier De Charges...pdf        # Spécifications fonctionnelles d'origine
└── README.md                      # Guide d'accueil (ce fichier)
```

### 🔗 Liens Rapides
*   📄 **Spécifications d'origine** : [Cahier Des Charges (PDF)](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/Cahier%20De%20Charges%20gestion%20projets%20Ya%20Consulting.pdf)
*   📄 **Documentation technique complète** : Ouvrez [documentation_technique.html](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/documentation_technique.html) dans votre navigateur.
*   📂 **Code Source principal** : Dossier [ya-consulting-app/](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/ya-consulting-app)

---

## 👥 Matrice des Rôles & Accès

| Rôle | Description | Droits Clés |
| :--- | :--- | :--- |
| **Administrateur (`admin`)** | Accès total à la plateforme. | Gestion des utilisateurs, des clients, configuration globale, édition de tous les projets et dépenses. |
| **Chef de Projet (`chef_projet`)** | Responsable de portefeuilles de projets. | Création et modification de ses propres projets et dépenses uniquement. Lecture seule pour le reste. |
| **Collaborateur (`collaborateur`)** | Rôle consultatif. | Consultation générale (lecture seule) des projets et du tableau de bord. Aucune action d'écriture. |

---

## ⚡ Lancement Rapide (Mode Développement)

### 📋 Prérequis
*   **PHP** ≥ 8.2 (avec extensions SQLite, GD, XML, etc.)
*   **Composer**
*   **Node.js** ≥ 18 & **npm**

### 🛠️ Étapes d'installation

1. **Se positionner dans le répertoire de l'application :**
   ```bash
   cd ya-consulting-app
   ```

2. **Installer les dépendances PHP et JavaScript :**
   ```bash
   composer install
   npm install
   ```

3. **Créer le fichier d'environnement et générer la clé de sécurité :**
   * **Windows (CMD) :**
     ```cmd
     copy .env.example .env
     php artisan key:generate
     ```
   * **Linux / macOS / PowerShell :**
     ```bash
     cp .env.example .env
     php artisan key:generate
     ```

4. **Configurer et initialiser la base de données SQLite :**
   * **Windows (CMD) :**
     ```cmd
     copy NUL database\database.sqlite
     php artisan migrate --seed
     ```
   * **Windows (PowerShell) :**
     ```powershell
     New-Item database/database.sqlite -Force
     php artisan migrate --seed
     ```
   * **Linux / macOS :**
     ```bash
     touch database/database.sqlite
     php artisan migrate --seed
     ```

5. **Lier le dossier de stockage (pour les justificatifs et PDFs) :**
   ```bash
   php artisan storage:link
   ```

6. **Lancer le serveur de développement (Laravel + Vite unifiés) :**
   ```bash
   composer run dev
   ```

7. **Accéder à l'application :**
   Rendez-vous sur [**http://localhost:8000**](http://localhost:8000)

---


> [!TIP]
> Une fois connecté en tant qu'**Administrateur**, vous pouvez créer de nouveaux profils d'utilisateurs et leur affecter les rôles de **Chef de Projet** ou de **Collaborateur** directement depuis l'interface d'administration.

---

## 🧪 Tests de Qualité & Stabilité

Pour lancer la suite de tests unitaires et fonctionnels :
```bash
php artisan test
```

---

## 💼 Contexte du Projet & Stage

Ce logiciel a été conçu et réalisé dans le cadre du projet de fin de stage au sein du cabinet **YA CONSULTING** (Cote D'ivoire). 

**YA CONSULTING** est un cabinet spécialisé dans :
*   Le conseil en stratégie et organisation opérationnelle
*   La formation professionnelle continue et le coaching de dirigeants
*   L'assistance technique et la gestion opérationnelle de projets d'envergure nationale et régionale.

---
*Développé avec ❤️ pour YA CONSULTING.*
