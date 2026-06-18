# YA CONSULTING — Project Manager

> Solution interne de suivi de projet, de gestion des dépenses et d'analyse financière de rentabilité.

Ce dépôt contient l'ensemble des livrables et le code source de l'application de gestion de projet développée pour **YA CONSULTING .

---

## 📂 Structure du dépôt

Le projet est structuré comme suit :

*   📂 [**`ya-consulting-app/`**](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/ya-consulting-app) : Répertoire principal contenant l'application web (Laravel 11 + Vue.js 3 via Inertia.js).
*   📄 [**`documentation_technique.html`**](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/documentation_technique.html) : Documentation technique interactive complète (UML, MCD, Architecture, Installation, Policies).
*   📄 [**`Cahier De Charges gestion projets Ya Consulting.pdf`**](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/Cahier%20De%20Charges%20gestion%20projets%20Ya%20Consulting.pdf) : Spécifications fonctionnelles et contraintes métier d'origine du client.
*   📂 [**`_scaffold/`**](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/_scaffold) : Modèles et ressources de squelette technique.

---

## 🗺️ Cartographie & Liens rapides

Pour naviguer facilement dans le projet, utilisez les ressources suivantes :

*   **Spécifications d'origine** : [Cahier Des Charges (PDF)](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/Cahier%20De%20Charges%20gestion%20projets%20Ya%20Consulting.pdf)
*   **Documentation technique complète** : Ouvrez simplement [documentation_technique.html](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/documentation_technique.html) dans n'importe quel navigateur web. Elle contient :
    *   Le diagramme d'architecture globale (Inertia SPA / MVC).
    *   Les diagrammes UML (Cas d'utilisation, Classes, Séquences).
    *   Le MCD / diagramme relationnel de base de données (ERD).
    *   Le cycle de vie d'un projet (Diagramme d'état-transition).
    *   La matrice complète des rôles et des autorisations fines (Policies).

---

## ⚡ Lancement Rapide (Mode Développement)

Pour lancer l'application en local, assurez-vous d'avoir installé **PHP ≥ 8.2**, **Composer** et **Node.js ≥ 18**.

1.  Rendez-vous dans le dossier de l'application :
    ```bash
    cd ya-consulting-app
    ```
2.  Installez les dépendances backend et frontend :
    ```bash
    composer install
    npm install
    ```
3.  Initialisez l'environnement et configurez la base de données SQLite :
    ```bash
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate --seed
    php artisan storage:link
    ```
4.  Lancez le serveur de développement unifié :
    ```bash
    composer run dev
    ```
5.  Ouvrez votre navigateur à l'adresse suivante : [**http://localhost:8000**](http://localhost:8000)

*(Voir le [README.md de l'application](file:///c:/Users/diarr/Desktop/projet%20de%20stage/Logiciel-de-gestion-de-projet-YA-CONSULTING/ya-consulting-app/README.md) pour obtenir les identifiants de test des différents rôles).*

---

## 💼 Contexte de Stage
Ce logiciel a été réalisé dans le cadre du projet de fin de stage pour **YA CONSULTING S.U.A.R.L**, cabinet spécialisé dans le conseil, la formation et la gestion opérationnelle de projets au Sénégal.
