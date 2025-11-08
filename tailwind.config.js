/** @type {import('tailwindcss').Config} */
module.exports = {
	mode: 'jit', // włączony JIT
	content: [
		'./*.php', // wszystkie PHP w głównym folderze
		'./src/**/*.{js,ts}', // TS/JS w src
		'./src/css/**/*.css', // CSS w src
	],
	theme: { extend: {} },
	plugins: [],
};
