/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./**/*.html'],
  theme: {
    extend: {
      fontFamily: { sans: ['Ravi', 'sans-serif'] },
      colors: {
        brand: '#0068ff',
        'brand-dark': '#0052cc',
        'brand-tint': '#e8f1ff',
      }
    }
  },
  plugins: [],
}
