# WP + Tailwind + Vite Starter

Minimalny starter motywu WordPress z Tailwind CSS, Vite, TypeScript i LiveReload dla szybkiego developmentu.

---

## Setup

1. Skopiuj folder motywu do `wp-content/themes/` jako nowy motyw.
2. W katalogu motywu zainstaluj zależności:

```bash
npm install
```

## Uruchom workflow developerski:

```bash
npm run start
```

Terminal 1: Vite dev → obserwuje TS/CSS, HMR dla Tailwind.

Terminal 2: Livereload → obserwuje PHP, automatyczne odświeżanie przeglądarki.

Dzięki npm run start uruchamiasz oba procesy równocześnie (wymaga npm-run-all).

## Nazwa-THEME/

![UKŁad](/dist/assets/układ.png)

## Notes

Wszystkie nowe klasy Tailwind w PHP są automatycznie generowane w CSS dzięki Tailwind JIT.

LiveReload dla PHP działa dzięki wtyczce w functions.php i livereload.

Do produkcji używaj:

```bash
npm run build
```

To wygeneruje minifikowany CSS/JS w dist_assets/.
