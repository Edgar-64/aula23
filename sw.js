const CACHE_NAME = 'acesso-app-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/index.html',
    '/style.css',
    'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'
];

// INSTALAÇÃO: Salva os arquivos estáticos no cache
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

// ATIVAÇÃO: Limpa caches antigos quando houver atualização
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});

// INTERCEPTAÇÃO: Retorna o cache para a interface, mas busca a API na rede
self.addEventListener('fetch', (event) => {
    // Se for a chamada da API (gerar_token.php), não usa o cache
    if (event.request.url.includes('gerar_token.php')) {
        return; // Deixa a requisição seguir normalmente pela rede
    }

    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});