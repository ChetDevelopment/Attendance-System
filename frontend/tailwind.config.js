/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        primary: '#135bec',
        'background-light': '#f6f6f8',
        'pnc-blue': '#3b82f6',
        'pnc-dark': '#0f172a',
<<<<<<< HEAD
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
=======
>>>>>>> e384938ff91a3908f609e488e191c6c7006d523d
      },
    },
  },
  plugins: [],
}
