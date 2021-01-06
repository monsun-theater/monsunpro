//--------------------------------------------------------------------------
// Tailwind Typography configuration
//--------------------------------------------------------------------------
//
// Here you may overwrite the default Tailwind Typography (or prosé) styles.
// Some defaults are provided.
// More info: https://github.com/tailwindlabs/tailwindcss-typography.
//

const plugin = require('tailwindcss/plugin')

module.exports = {
  theme: {
    extend: {
      typography: {
        DEFAULT: {
          css: {
            h1: {
              'font-weight': 'normal',
            },
            h2: {
              'font-weight': 'normal',
            },
            h3: {
              'font-weight': 'normal',
            },
          }
        },
      },
      typography: (theme) => ({
        DEFAULT: {
          css: {
            color: theme('colors.black'),
            '[class~="lead"]': {
              color: theme('colors.black'),
            },
            a: {
              color: theme('colors.black'),
              '&:hover': {
                color: theme('colors.monsun'),
              },
            },
            'a.no-underline': {
              textDecoration: 'none',
            },
            'h1, h2, h3, h4': {
              fontWeight: 'normal',
              scrollMarginTop: theme('spacing.36'),
              color: theme('colors.black'),
            },
            blockquote: {
              borderColor: theme('colors.black'),
            },
            hr: {
              borderColor: theme('colors.black'),
            },
            'ul > li::before': {
              backgroundColor: theme('colors.neutral.500'),
            },
            'ol > li::before': {
              color: theme('colors.neutral.500'),
            },
            pre: {
              whiteSpace: 'pre-wrap',
            },
          }
        }
      }),
    }
  },
  plugins: [
    require('@tailwindcss/typography'),
  ]
}
