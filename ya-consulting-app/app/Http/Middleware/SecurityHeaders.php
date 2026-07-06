<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Ajoute des en-têtes HTTP de sécurité à chaque réponse de l'application.
     *
     * - X-Frame-Options        : Empêche le clickjacking (chargement en iframe)
     * - X-Content-Type-Options : Empêche le MIME type sniffing par le navigateur
     * - Referrer-Policy        : Contrôle les informations de provenance inter-domaines
     * - X-XSS-Protection       : Active le filtre XSS des anciens navigateurs
     * - Permissions-Policy     : Désactive l'accès aux APIs sensibles (caméra, micro, géoloc)
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. On laisse la requête traverser l'application et on récupère la réponse
        $response = $next($request);

        // 2. On injecte les en-têtes de sécurité dans l'objet réponse avant de l'envoyer au navigateur
        
        // Empêche le site d'être intégré dans une balise <iframe /> ou <frame /> depuis un autre domaine (Protection Clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Interdit au navigateur de "deviner" (sniffing) le type MIME d'un fichier, le forçant à respecter l'en-tête Content-Type
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Contrôle la quantité d'informations envoyées dans l'en-tête "Referer" lors d'un clic sortant
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Active le filtre anti-XSS intégré des navigateurs plus anciens (bloque la page si une attaque XSS est détectée)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Restreint l'accès aux fonctionnalités sensibles de l'appareil (caméra, micro, etc.)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 3. On retourne la réponse modifiée et sécurisée
        return $response;
    }
}
