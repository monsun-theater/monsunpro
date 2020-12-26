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
      typography: (theme) => ({
        DEFAULT: {
          css: {
            color: theme('colors.black'),
            '[class~="lead"]': {
              color: theme('colors.neutral.800'),
            },
            a: {
              color: theme('colors.primary.600'),
              '&:hover': {
                color: theme('colors.monsun'),
              },
            },
            'a.no-underline': {
              textDecoration: 'none',
            },
            'h1, h2, h3, h4': {
              scrollMarginTop: theme('spacing.36'),
              color: theme('colors.black'),
            },
            blockquote: {
              borderColor: theme('colors.black'),
            },
            hr: {
              borderColor: theme('colors.neutral.100'),
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
