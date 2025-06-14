module.exports = {
    plugins: {
        '@tailwindcss/postcss': {},
        "postcss-prefix-selector": {
            prefix: '.custom-fields-component',
        },
        ...(process.env.NODE_ENV === 'production' ? { cssnano: {} } : {}),
    },
}
