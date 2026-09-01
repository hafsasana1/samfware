/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./app/Views/**/*.php",
    "./public/assets/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        canvas: '#F8FAF9',
        surface: '#FFFFFF',
        ink: {
          DEFAULT: '#1A1D21',
          muted: '#5A5D63',
          faint: '#9A9DA3',
        },
        navy: {
          900: '#101C2C',
          800: '#1B2A3D',
          700: '#28384D',
        },
        accent: {
          DEFAULT: '#1FBF8F',
          hover: '#0E9670',
          soft: '#E3F9F1',
        },
        highlight: {
          DEFAULT: '#FF8A1F',
          soft: '#FFF1E2',
        },
        danger: {
          DEFAULT: '#DC2626',
          soft: '#FDE8E8',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Roboto', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
      },
      animation: {
        'slide-in': 'slideIn 0.3s ease-out',
        'fade-in': 'fadeIn 0.3s ease-out',
        'spin-slow': 'spin 2s linear infinite',
      },
      keyframes: {
        slideIn: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
