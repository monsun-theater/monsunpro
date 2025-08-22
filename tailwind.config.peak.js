//--------------------------------------------------------------------------
// Tailwind custom Peak configuration
//--------------------------------------------------------------------------
//
// Here we define base styles, components and utilities used by Peak.
//

const _ = require("lodash");
const plugin = require("tailwindcss/plugin");

module.exports = {
    theme: {
        extend: {
            keyframes: (theme) => ({
                // Add a highlight animation for the highlight utility.
                highlight: {
                    from: {
                        boxShadow:
                            "inset 4px 4px " +
                            theme("colors.error.600") +
                            ", inset -4px -4px " +
                            theme("colors.error.600"),
                    },
                    to: {
                        boxShadow:
                            "inset 8px 8px " +
                            theme("colors.error.600") +
                            ", inset -8px -8px " +
                            theme("colors.error.600"),
                    },
                },
            }),
            spacing: {
                // Used for the mobile navigation toggle.
                safe: "calc(env(safe-area-inset-bottom, 0rem) + 2rem)",
            },
            zIndex: {
                // Z-index stuff behind it's parent.
                behind: "-1",
            },
        },
    },
    plugins: [
        // Use Tailwinds aspect-ratio plugin for embedded media: https://github.com/tailwindlabs/tailwindcss-aspect-ratio.
        require("@tailwindcss/aspect-ratio"),
        // Use Tailwinds forms plugin for form styling: https://github.com/tailwindlabs/tailwindcss-forms
        require("@tailwindcss/forms"),
        plugin(function ({ addBase, theme }) {
            addBase({
                ":root": {
                    // Fluid typography from 1 rem to 1.15 rem with fallback to 16px.
                    //1vw = 1% viewport width
                    fontSize: "16px",
                    "font-size": "clamp(1.0rem, 1.4vw, 1.4rem)",
                    // Safari resize fix.
                    minHeight: "0vw",
                },
                // Used to hide alpine elements before being rendered.
                "[x-cloak]": {
                    display: "none !important",
                },
                // Implement the focus-visible polyfill: https://github.com/WICG/focus-visible
                ".js-focus-visible :focus:not(.focus-visible)": {
                    outline: "none",
                },
                // Display screen breakpoints in debug environment.
                "body.debug::before": {
                    display: "block",
                    position: "fixed",
                    zIndex: "99",
                    top: theme("spacing.1"),
                    right: theme("spacing.1"),
                    padding: theme("spacing.1"),
                    border: "1px",
                    borderStyle: "solid",
                    borderColor: theme("colors.notice.300"),
                    borderRadius: theme("borderRadius.full"),
                    backgroundColor: theme("colors.notice.200"),
                    fontSize: ".5rem",
                    color: theme("colors.notice.900"),
                    textTransform: "uppercase",
                    fontWeight: theme("fontWeight.bold"),
                    content: '"-"',
                    pointerEvents: "none",
                },
            });
        }),

        // Render screen names in the breakpoint display.
        plugin(function ({ addBase, theme }) {
            const breakpoints = _.map(theme("screens"), (value, key) => {
                return {
                    [`@media (min-width: ${value})`]: {
                        "body.debug::before": {
                            content: `"${key}"`,
                        },
                    },
                };
            });
            addBase(breakpoints);
        }),

        plugin(function ({ addComponents, theme }) {
            const components = {
                // The main wrapper for all sections on our website. Has a max width and is centered.
                ".fluid-container": {
                    width: "100%",
                    maxWidth: theme("screens.2xl"),
                    marginLeft: "auto",
                    marginRight: "auto",
                    // Use safe-area-inset together with default padding for Apple devices with a notch.
                    paddingLeft:
                        "calc(env(safe-area-inset-left, 0rem) + " +
                        theme("padding.4") +
                        ")",
                    paddingRight:
                        "calc(env(safe-area-inset-right, 0rem) + " +
                        theme("padding.4") +
                        ")",
                },
                // Disable scroll e.g. when a modal is open. Should be used on the <body>
                ".no-scroll": {
                    height: "100%",
                    overflow: "hidden",
                },
                // Utility class to center an absolute positioned element:
                ".inset-center": {
                    position: "absolute",
                    top: "50%",
                    left: "50%",
                    transform: "translate(-50%, -50%)",
                },
                // The outer grid where all block builder blocks are a child of. Spreads out all blocks
                // vertically with a uniform space between them.
                ".outer-grid": {
                    width: "100%",
                    display: "grid",
                    gridAutoColumns: "minmax(0, 1fr)",
                    rowGap: theme("spacing.3"),
                    paddingTop: theme("spacing.3"),
                    paddingBottom: theme("spacing.3"),
                    // If the last child of the outer grid is full width (e.g. when it has a full width
                    // colored background), give it negative margin bottom to get it flush to your
                    // sites footer.
                    "& > *:last-child:has(.w-full)": {
                        marginBottom: theme("spacing.3") * -1,
                    },
                },
                [`@media (min-width: ${theme("screens.md")})`]: {
                    // Larger vertical spacing between blocks on larger screens.
                    ".outer-grid": {
                        rowGap: theme("spacing.3"),
                        paddingTop: theme("spacing.3"),
                        paddingBottom: theme("spacing.3"),
                        "& > *:last-child:has(.w-full)": {
                            marginBottom: theme("spacing.3") * -1,
                        },
                    },
                },
                [`@media (min-width: ${theme("screens.lg")})`]: {
                    // Larger horizontal padding on larger screens.
                    ".fluid-container": {
                        // Use safe-area-inset together with default padding for Apple devices with a notch.
                        paddingLeft:
                            "calc(env(safe-area-inset-left, 0rem) + " +
                            theme("padding.12") +
                            ")",
                        paddingRight:
                            "calc(env(safe-area-inset-right, 0rem) + " +
                            theme("padding.12") +
                            ")",
                    },
                    // Larger vertical spacing between blocks on larger screens.
                    ".outer-grid": {
                        rowGap: theme("spacing.5"),
                        paddingTop: theme("spacing.5"),
                        paddingBottom: theme("spacing.5"),
                        "& > *:last-child:has(.w-full)": {
                            marginBottom: theme("spacing.5") * -1,
                        },
                    },
                },
            };
            addComponents(components);
        }),

        plugin(function ({ addUtilities, theme, variants }) {
            const newUtilities = {
                // Add a ? utility to quickly highlight an element.
                ".?": {
                    animation: "highlight 0.5s ease-in-out alternate infinite",
                },
                // Break words only when needed.
                ".break-decent": {
                    wordBreak: "break-word",
                },
                // Sizing utilities for sets in our bard (long form content).
                // On small devices they're full width.
                ".size-sm, .size-md, .size-lg, .size-xl": {
                    gridColumn: "span 12 / span 12",
                },
                ".size-xsm": {
                    gridColumn: "span 4 / span 3",
                },
                [`@media (min-width: ${theme("screens.md")})`]: {
                    // Sizing utilities for sets in our bard (long form content).
                    // On larger devices they go from small to extra large.
                    // (E.g. an image wider then text in an article.)
                    ".size-sm": {
                        gridColumn: "span 4 / span 4",
                        gridColumnStart: "3",
                    },
                    ".size-md": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                    ".size-lg": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                    ".size-xl": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                },
                [`@media (min-width: ${theme("screens.lg")})`]: {
                    // Sizing utilities for sets in our bard (long form content).
                    // On larger devices they go from small to extra large.
                    ".size-sm": {
                        gridColumn: "span 4 / span 4",
                        gridColumnStart: "4",
                    },
                    ".size-md": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                    ".size-lg": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                    ".size-xl": {
                        gridColumn: "span 12 / span 12",
                        gridColumnStart: "5",
                    },
                },
            };
            addUtilities(newUtilities);
        }),
    ],
};
