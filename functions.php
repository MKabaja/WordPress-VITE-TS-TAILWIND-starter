<?php
/**
 * functions.php
 * Kompletny snippet ładowania assetów Vite (dev + prod) dla WordPress.
 *
 * Instrukcja:
 *  - W trybie deweloperskim (WP_DEBUG = true) pliki ładowane są z Vite dev servera.
 *  - W trybie produkcyjnym ładuje skompilowane pliki z katalogu dist.
 *
 * Ważne: ustaw $dev_host na adres, który pokazał Vite w logach (Network: ...),
 * np. 'http://192.168.55.7:5173' albo 'http://localhost:5173' zależnie od Twojego środowiska.
 */

/* -------------------------------------------
   Konfiguracja: ADRES VITE DEV SERVERA
   -------------------------------------------
   Ustaw tutaj host/port dev servera Vite. Po uruchomieniu `npm run dev`
   w terminalu Vite wypisze "Network: http://<IP>:5173" — użyj dokładnie
   tego URL-a, aby WordPress mógł pobierać pliki podczas developu.
*/
$dev_host = 'http://192.168.55.7:5173'; // <- Zmień na swój adres z `npm run dev`

/* -------------------------------------------
   Pomocnicza globalna tablica do oznaczania skryptów
   które w dev muszą mieć type="module".
   Dlaczego: WordPress domyślnie generuje <script src="..."></script>
   — Vite dev wymaga natomiast modułów ES (type="module") dla plików .ts/.js importowanych jako moduły.
   -------------------------------------------*/
global $vite_module_handles;
if ( ! isset( $vite_module_handles ) ) {
    $vite_module_handles = array();
}

/* -------------------------------------------
   Filtr zamieniający tag <script> na type="module"
   dla uchwytów zapisanych w $vite_module_handles.
   Dlaczego jedna funkcja: unikasz tworzenia wielu zapętleń/dup filtrowania.
   -------------------------------------------*/
function vite_script_loader_tag( $tag, $handle, $src ) {
    global $vite_module_handles;

    // Jeśli dany handle jest oznaczony jako modułowy — zwróć tag z type="module"
    if ( in_array( $handle, $vite_module_handles, true ) ) {
        // esc_url sanitizuje src
        return '<script type="module" src="' . esc_url( $src ) . '"></script>' . "\n";
    }

    // W przeciwnym razie zwracamy standardowy wygenerowany tag
    return $tag;
}
// Rejestrujemy filtr globalnie. Funkcja nic nie zmienia jeśli lista uchwytów pusta.
add_filter( 'script_loader_tag', 'vite_script_loader_tag', 10, 3 );

/* -------------------------------------------
   enqueue_vite_asset
   - $handle: handle WP dla enqueue
   - $path: ścieżka względna DO DEV HOSTA (np. '/src/ts/main.ts' albo '/src/css/input.css')
   - $is_js: czy plik to JS (wtedy potrzebujemy type="module")
   Funkcja automatyzuje rejestrację dla trybu dev (Vite) i zostawia miejsce na prod.
   -------------------------------------------*/
function enqueue_vite_asset( $handle, $path, $is_js = true ) {
    global $dev_host, $vite_module_handles;

    // Upewniamy się, że flagi debugowe są dostępne
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // DEV: ładujemy bezpośrednio z dev-servera Vite
        if ( $is_js ) {
            // Rejestrujemy/enkjujemy skrypt z dev servera
            // Zwróć uwagę: ładujemy URL typu http://192.168.x.y:5173/src/ts/main.ts
            wp_enqueue_script( $handle, $dev_host . $path, array(), null, true );

            // Dodaj handle do listy modułów — filtr wyżej zamieni <script> na type="module"
            if ( ! in_array( $handle, $vite_module_handles, true ) ) {
                $vite_module_handles[] = $handle;
            }
        } else {
            // CSS: w dev Vite serwuje CSS z plików źródłowych (postcss/tailwind)
            wp_enqueue_style( $handle, $dev_host . $path, array(), null );
        }
    } else {
        // PROD: w produkcji powinniśmy ładować skompilowane pliki z katalogu dist.
        // Tutaj nie robimy nic — produkcyjne ładowanie realizujemy centralnie w theme_assets().
        // (Funkcja pozostawiona, by łatwo dodać assety dev jeśli trzeba.)
    }
}

/* -------------------------------------------
   GŁÓWNA FUNKCJA: theme_assets
   - rejestruje assety zarówno dla dev jak i prod
   - wywoływana na akcję wp_enqueue_scripts
   -------------------------------------------*/
function theme_assets() {
    global $dev_host;

    // URI motywu do użycia w produkcji (dist)
    $dir = get_template_directory_uri();

    // Dev mode: WP_DEBUG true
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // 1) Vite HMR client - konieczne, żeby HMR (hot module reload) i konsola działały.
        //    Endpoint: http://<dev_host>/@vite/client (to nie jest fizyczny plik na dysku,
        //    to wirtualny endpoint udostępniany przez Vite dev server).
        wp_enqueue_script( 'vite-client', $dev_host . '/@vite/client', array(), null, false );

        // 2) Główny moduł aplikacji (TypeScript / JS) - ładowany jako module
        //    Zauważ: path zaczyna się od '/src/...' — Vite obsłuży to jako moduł ES.
        enqueue_vite_asset( 'theme-script', '/src/ts/main.ts', true );

        // 3) CSS źródłowy (Tailwind/PostCSS) - w dev jest serwowany przez Vite
        enqueue_vite_asset( 'theme-style', '/src/css/input.css', false );

        // Jeżeli masz dodatkowe entrypoints w dev — zarejestruj je analogicznie:
        // enqueue_vite_asset('admin-js', '/src/ts/admin.ts', true);
        // enqueue_vite_asset('editor-style', '/src/css/editor.css', false);
        add_action('wp_footer', function() {
        echo '<script src="http://localhost:35729/livereload.js"></script>';
    });
    } else {
        // PRODUKCJA:
        // Po `npm run build` Vite zapisze skompilowane pliki w katalogu dist/assets/
        // (w tej konfiguracji zakładamy nazwy bez hash: assets/main.js i assets/style.css).
        // Jeśli używasz hashów plików (np. main.abcdef.js) -> rozważ generowanie manifestu
        // i mapowanie nazw z pliku manifest.json (to krok dalej).
        wp_enqueue_style( 'theme-style', $dir . '/dist/assets/style.css', array(), null );
        wp_enqueue_script( 'theme-script', $dir . '/dist/assets/main.js', array(), null, true );
    }
}
// Rejestrujemy funkcję do ładowania assetów
add_action( 'wp_enqueue_scripts', 'theme_assets' );

/* -------------------------------------------
   Opcjonalne: funkcja pomocnicza do automatycznego wykrywania hosta dev
   (przydatne jeśli zmieniasz sieci / IP często)
   -------------------------------------------
   Jeżeli chcesz, możesz zastąpić ręczne ustawienie $dev_host prostym wykrywaniem:
   - jeśli WP_HOST (lub inna zmienna środowiskowa) jest ustawiona, użyj jej
   - albo spróbuj użyć current host z przeglądarki (nie zawsze działa jeśli WP na VM)
   Przykład (odkomentuj i dopasuj jeśli chcesz automatyzować):
*/

// Example (do NOT execute by default):
// if ( defined('WP_DEBUG') && WP_DEBUG ) {
//     // próba automatycznego ustawienia hosta: jeśli Twoja przeglądarka
//     // odwiedza fictional-university.local i dev server także wystawia pod tą nazwą,
//     // to można użyć:
//     // $dev_host = 'http://' . $_SERVER['HTTP_HOST'] . ':5173';
//     //
//     // Jednak często dev server używa innego IP niż hostname WP (VM vs host) —
//     // dlatego manualne podanie $dev_host jest bardziej niezawodne.
// }

?>
