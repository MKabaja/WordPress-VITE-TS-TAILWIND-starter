import { defineConfig } from 'vite';
import liveReload from 'vite-plugin-live-reload';

export default defineConfig({
	plugins: [
		liveReload(['./*.php']), // obserwuje wszystkie pliki PHP
	],
	server: {
		host: '0.0.0.0', // nasłuch na wszystkich interfejsach (potrzebne dla VM/domen lokalnych)
		port: 5173,
		strictPort: true,
		cors: true,
		hmr: {
			// jeśli fictional-university.local rozwiązuje się do Twojej maszyny -> użyj tej nazwy,
			// inaczej użyj lokalnego IPv4 (np. 192.168.1.25)
			host: 'fictional-university.local',
			protocol: 'ws',
		},
		watch: {
			usePolling: true,
		},
	},
	build: {
		outDir: 'dist',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				main: 'src/ts/main.ts',
				style: 'src/css/input.css',
			},
			output: {
				entryFileNames: `assets/main.js`,
				chunkFileNames: `assets/[name].js`,
				assetFileNames: `assets/[name][extname]`,
			},
		},
	},
});
