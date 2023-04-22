const defaultTheme = require("tailwindcss/defaultTheme");
const plugin = require("tailwindcss/plugin");

module.exports = {
    presets: [
        require("tailwindcss/defaultConfig"),
        require("./tailwind.config.typography.js"),
        require("./tailwind.config.peak.js"),
        require("./tailwind.config.site.js"),
    ],
    darkMode: "media", // Change this line from 'class' to 'media'
    content: {
        files: ["./resources/views/**/*.html", "./resources/js/**/*.js"],
    },
    options: {
        blocklist: ["?"],
        keyframes: true,
        safelist: [
            "size-sm",
            "size-md",
            "size-lg",
            "size-xl",
            "js-focus-visible",
            "bg-neutral-100",
            "bg-neutral-200",
            "bg-neutral-300",
            "bg-neutral-400",
            "bg-neutral-500",
            "bg-neutral-600",
            "bg-neutral-700",
        ],
    },
    variants: {
        extend: {
            animation: ["motion-safe"],
            margin: ["last"],
            ringWidth: ["focus-visible"],
            rotate: ["group-hover", "motion-safe"],
            scale: ["group-hover", "motion-safe"],
            skew: ["group-hover", "motion-safe"],
            transitionDuration: ["motion-safe"],
            transitionProperty: ["motion-safe"],
            translate: ["group-hover", "motion-safe"],
            typography: ["dark"],
        },
    },
};
