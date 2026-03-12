/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{php,html}",
    "./includes/*.php",
    "./assets/js/*.js"
  ],
  theme: {
    extend: {
      colors: {
        // Paleta oceânica customizada
        ocean: {
          50: '#e0f7fa',   // Superfície clara
          100: '#b2ebf2',  // Águas rasas
          200: '#80deea',  // Azul claro
          300: '#4dd0e1',  // Azul médio
          400: '#26c6da',  // Azul vibrante
          500: '#00acc1',  // Azul oceânico
          600: '#0097a7',  // Azul profundo
          700: '#00838f',  // Azul escuro
          800: '#006064',  // Azul muito escuro
          900: '#004d56',  // Abissal
          950: '#001a1f',  // Profundidades
        },
        // Cores de destaque bioluminescentes
        biolume: {
          cyan: '#00ffff',      // Ciano neon
          blue: '#00d4ff',      // Azul brilhante
          teal: '#00fff2',      // Turquesa luminoso
          aqua: '#6ff5ff',      // Água-marinha clara
          ice: '#c8ffff',       // Gelo luminoso
        },
        // Cores de microplásticos
        plastic: {
          light: '#4dd0e1',
          medium: '#00acc1',
          dark: '#006064',
        }
      },
      fontFamily: {
        sans: ['Outfit', 'Inter', '-apple-system', 'sans-serif'],
        display: ['Orbitron', 'sans-serif'],
        body: ['Inter', 'sans-serif'],
      },
      backgroundImage: {
        'ocean-gradient': 'linear-gradient(to bottom, #0d47a1 0%, #01579b 8%, #004d7a 16%, #003d5c 24%, #00334d 32%, #002840 40%, #001f33 48%, #001629 56%, #000d1f 64%, #000715 72%, #000410 80%, #00020a 88%, #000105 94%, #000000 100%)',
        'ocean-gradient-radial': 'radial-gradient(ellipse at top, #0d47a1 0%, #000000 100%)',
      },
      boxShadow: {
        'biolume-sm': '0 0 15px rgba(0, 255, 255, 0.3)',
        'biolume': '0 0 30px rgba(0, 255, 255, 0.5)',
        'biolume-lg': '0 0 50px rgba(0, 255, 255, 0.6)',
        'biolume-xl': '0 0 80px rgba(0, 255, 255, 0.7)',
        'depth': '0 20px 60px rgba(0, 0, 0, 0.9)',
        'depth-lg': '0 30px 80px rgba(0, 0, 0, 0.95)',
      },
      animation: {
        'float': 'float 20s ease-in-out infinite',
        'float-slow': 'float 30s ease-in-out infinite',
        'pulse-biolume': 'pulseBiolume 3s ease-in-out infinite',
        'shimmer': 'shimmer 8s linear infinite',
        'wave': 'wave 15s ease-in-out infinite',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0px) translateX(0px)' },
          '25%': { transform: 'translateY(-30px) translateX(15px)' },
          '50%': { transform: 'translateY(-50px) translateX(-10px)' },
          '75%': { transform: 'translateY(-20px) translateX(-20px)' },
        },
        pulseBiolume: {
          '0%, 100%': {
            boxShadow: '0 0 30px rgba(0, 255, 255, 0.4)',
            opacity: '1'
          },
          '50%': {
            boxShadow: '0 0 60px rgba(0, 255, 255, 0.8)',
            opacity: '0.9'
          },
        },
        shimmer: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(100%)' },
        },
        wave: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-20px)' },
        },
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '100': '25rem',
        '112': '28rem',
        '128': '32rem',
      },
    },
  },
  plugins: [],
}
