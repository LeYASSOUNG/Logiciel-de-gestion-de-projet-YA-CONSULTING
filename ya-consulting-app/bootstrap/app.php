<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

/*
|--------------------------------------------------------------------------
| Configuration et Initialisation de l'Application Laravel (Bootstrap)
|--------------------------------------------------------------------------
| Ce fichier configure le cœur de l'application : les chemins de routage, 
| l'injection des middlewares, la gestion des exceptions et les tâches 
| planifiées (cron jobs).
*/
return Application::configure(basePath: dirname(__DIR__))
    // 1. Configuration du Routage (Web, Console, Santé)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',       // Fichier contenant toutes les routes web (navigateur)
        commands: __DIR__.'/../routes/console.php', // Commandes artisan personnalisées
        health: '/up',                           // Route technique pour vérifier si l'app fonctionne
    )
    
    // 2. Configuration des Tâches Planifiées (Cron)
    ->withSchedule(function (Schedule $schedule) {
        // Exécute la commande de sauvegarde de la base de données automatiquement chaque jour
        $schedule->command('db:backup')->daily();
    })
    
    // 3. Configuration des Middlewares (Intercepteurs de requêtes HTTP)
    ->withMiddleware(function (Middleware $middleware) {
        // Middlewares appliqués à TOUTES les routes du groupe "web"
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class, // Bridge entre Laravel et Vue.js (Inertia)
            \App\Http\Middleware\SecurityHeaders::class,       // Ajoute les en-têtes de protection contre le piratage
        ]);
        
        // Exempter certaines routes de la vérification de jeton CSRF (ex: logout parfois nécessaire sans token exact)
        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);
        
        // Enregistrement des alias de middlewares pour la gestion des Rôles et Permissions (Package Spatie)
        // Permet d'utiliser middleware('role:admin') dans les routes.
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    
    // 4. Configuration de la gestion globale des Erreurs et Exceptions
    ->withExceptions(function (Exceptions $exceptions) {
        // C'est ici que l'on pourrait intercepter certaines erreurs (ex: 404, 500)
        // pour renvoyer une vue Inertia spécifique à l'utilisateur.
    })->create();
