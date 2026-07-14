// ─── Configuration Globale Axios ────────────────────────────────────
// Importation de la bibliothèque HTTP Axios
import axios from 'axios';
window.axios = axios;

// Ajout de l'en-tête 'X-Requested-With' à toutes les requêtes Axios.
// Cela permet au backend Laravel de reconnaître automatiquement 
// que la requête entrante est une requête AJAX (XMLHttpRequest).
const headers = axios.defaults.headers.common;
headers['X-Requested-With'] = 'XMLHttpRequest';

// ─── Protection CSRF (Cross-Site Request Forgery) ───────────────────
// Récupération du jeton de sécurité (token CSRF) généré par Laravel
// et stocké dans une balise <meta> de l'en-tête HTML de la page.
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    // Si le token est trouvé, on l'injecte automatiquement dans les en-têtes
    // de chaque future requête Axios. Ainsi, chaque soumission de formulaire
    // ou appel API AJAX est sécurisé.
    headers['X-CSRF-TOKEN'] = token.content;
} else {
    // Avertissement dans la console si le token est introuvable (généralement un oubli de la balise meta).
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
