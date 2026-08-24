import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Fixa o servidor de HMR em `localhost` — sem isto o Vite escolhe o
        // loopback IPv6 e escreve `http://[::1]:5173` no ficheiro `public/hot`.
        //
        // A gramática da Content Security Policy não sabe exprimir um literal
        // IPv6: `http://[::1]:5173` é rejeitado pelo browser como fonte
        // inválida, e o `<link>` do CSS e o WebSocket da recarga a quente
        // passam a ser bloqueados. Não há forma de contornar isso do lado da
        // política — só de escolher um endereço que ela consiga nomear.
        //
        // (O nonce não salva o CSS: `style-src` não declara nonce nenhum, de
        // propósito. Declará-lo desligaria o `'unsafe-inline'` de que as
        // vistas dependem — ver app/Http/Middleware/SecurityHeaders.php.)
        host: 'localhost',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
