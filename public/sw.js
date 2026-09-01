/* ==========================================================================
   Service Worker — Importadora RYM PWA
   Estrategia pensada para que módulos actuales y futuros funcionen igual:
   - Navegación (páginas): network-first con fallback offline.
   - Assets estáticos: stale-while-revalidate.
   - Nunca cachea peticiones que no sean GET ni rutas privadas (portal/admin/formularios).
   Sube el número de versión para forzar la actualización del caché.
   ========================================================================== */

const VERSION  = 'rym-v52';
const SCOPE    = self.registration.scope;          // .../importadorarym/public/
const PRECACHE = 'precache-' + VERSION;
const RUNTIME  = 'runtime-'  + VERSION;

// App shell: solo recursos de URL FIJA (nunca cambian de nombre ni llevan
// querystring). CSS/JS/manifest/logo se sirven con asset() -> "?v=<mtime>",
// y en producción el CSS además es un bundle ("site.bundle.min.css", no las
// hojas sueltas) — precachearlos aquí con un nombre fijo nunca coincidiría
// con la URL real que pide el navegador, dejaría el precache instalando
// basura que nadie pide, y si algún día los archivos sueltos dejan de
// existir en el servidor (solo queda el bundle), cache.addAll() fallaría
// completo y el Service Worker jamás se activaría. Esos assets versionados
// se cachean solos, con su URL real, en la primera visita (ver el handler
// 'fetch' de más abajo: stale-while-revalidate ya los guarda en RUNTIME).
const SHELL = [
  '',                       // start_url (raíz del scope)
  'offline.html',
  // Tipografía autoalojada: referenciada por ruta relativa fija dentro de
  // fonts.css (url(), sin ?v=) — sin esto, sin red el sitio caería a la
  // fuente del sistema.
  'assets/fonts/inter-variable-latin.woff2',
  'assets/fonts/poppins-600-latin.woff2',
  'assets/fonts/poppins-700-latin.woff2',
  // Iconos: referenciados por ruta relativa fija dentro de manifest.webmanifest
  // (JSON estático), no vía asset().
  'assets/img/icons/icon-192.png',
  'assets/img/icons/icon-512.png'
].map((p) => new URL(p, SCOPE).toString());

const OFFLINE_URL = new URL('offline.html', SCOPE).toString();

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(PRECACHE)
      .then((cache) => cache.addAll(SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => k !== PRECACHE && k !== RUNTIME).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// Rutas que NO deben cachearse (contenido privado o sensible a la sesión).
function isPrivate(url) {
  const p = url.pathname;
  return p.includes('/portal') || p.includes('/admin') || p.includes('/cotizar') || p.includes('/reparto');
}

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Solo GET y mismo origen; el resto pasa directo a la red.
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Navegaciones (documentos HTML): network-first, fallback a caché y a offline.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (!isPrivate(url)) {
            const copy = res.clone();
            caches.open(RUNTIME).then((c) => c.put(req, copy));
          }
          return res;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match(OFFLINE_URL)))
    );
    return;
  }

  // No guardar assets de rutas privadas.
  if (isPrivate(url)) return;

  // Assets estáticos: stale-while-revalidate.
  event.respondWith(
    caches.match(req).then((cached) => {
      const network = fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => cached);
      return cached || network;
    })
  );
});
