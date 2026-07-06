# YA CONSULTING Project Manager — Application Web

Cette application web est la solution interne de gestion opérationnelle et financière de **YA CONSULTING**. Elle permet d'enregistrer les projets, d'associer des clients, de saisir les dépenses par catégorie (avec justificatifs) et de générer des rapports de rentabilité.

---

## 🛠️ Stack Technique

*   **Backend** : Laravel 11.x (PHP 8.2+)
*   **Frontend** : Vue.js 3 (Composition API)
*   **Lien SPA** : Inertia.js (Pas d'API REST séparée)
*   **Base de Données** : SQLite (idéal pour le développement local)
*   **Styles CSS** : Tailwind CSS
*   **Rapports PDF** : Barryvdh/laravel-dompdf
*   **Rôles & Permissions** : Spatie Laravel-Permission
*   **Journal d'Audit** : Spatie Laravel-Activitylog
*   **Serveur de dev** : Vite.js

---

## ✨ Fonctionnalités Majeures

1.  **Gestion des Rôles & Autorisations** :
    *   `admin` (accès total)
    *   `chef_projet` (ne peut voir/modifier que ses propres projets)
    *   `collaborateur` (lecture seule globale)
2.  **Suivi de Projets** : Budget initial, statut (en cours, en pause, terminé), date de fin prévisionnelle et réelle, contact fournisseur.
    *   🚨 **Système d'alerte proactive** : Notification et mise en évidence visuelle (cloche) dès qu'un projet dépasse **80% de son budget alloué**.
3.  **Gestion Financière (Dépenses)** : Saisie catégorisée des dépenses, calcul de rentabilité automatique (Gain Brut, Taux de rentabilité %), stockage de justificatifs de paiement (PDF, images).
4.  **Rapports Statistiques & Graphiques** :
    *   Dashboard réactif (ApexCharts) avec :
        *   Graphique comparatif : Budget alloué vs Dépenses réelles pour le top 10 des projets.
        *   Répartition des coûts par catégorie (Donut).
        *   Tendances mensuelles des dépenses (Barres).
    *   Génération de rapports mensuels PDF / CSV avec comparatifs N vs N-1.
5.  **Audit Complet** : Suivi rigoureux de toutes les actions sensibles (créations, éditions, suppressions) via un journal d'activité.

---

## 🚀 Guide d'Installation

### 1. Prérequis
Assurez-vous d'avoir installé :
*   **PHP** ≥ 8.2
*   **Composer** (gestionnaire PHP)
*   **Node.js** ≥ 18 & **npm**

### 2. Cloner et configurer le projet
```bash
# Entrer dans le répertoire de l'application
cd ya-consulting-app

# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application Laravel
php artisan key:generate
```

### 3. Configurer la base de données
L'application utilise par défaut une base de données locale SQLite.
```bash
# Créer le fichier de base de données SQLite vide
touch database/database.sqlite

# Lancer les migrations de table et injecter les rôles/catégories
php artisan migrate --seed
```

### 4. Configurer le stockage local
Pour permettre l'accès public aux pièces jointes et rapports générés :
```bash
php artisan storage:link
```

### 5. Démarrer le serveur de développement
Une commande unifiée est disponible pour lancer à la fois le serveur Laravel et le bundler de développement Vite :
```bash
composer run dev
```
Accédez à l'application sur : [**http://localhost:8000**](http://localhost:8000)

---

## 🔑 Identifiants de Connexion par Défaut (Seeders)

Lors du `db:seed`, un compte administrateur par défaut est généré :

*   **Email** : `courriel@ya-consulting.com`
*   **Mot de passe** : `Admin@2024`

### Création d'autres rôles pour test :
Vous pouvez créer de nouveaux utilisateurs directement depuis l'interface d'administration (connecté en `admin`) et leur attribuer les rôles suivants :
1.  **Chef de Projet** (limité à la gestion de ses propres projets et dépenses)
2.  **Collaborateur** (consultation en lecture seule, pas d'écriture)

---

## 🧪 Tests Unitaires & Fonctionnels

Pour lancer la suite de tests et s'assurer de la stabilité du projet :
```bash
php artisan test
```
