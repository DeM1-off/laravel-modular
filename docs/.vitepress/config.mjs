import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Laravel Modular',
  description: 'DDD modules for Laravel that promote to standalone packages with zero code churn.',

  // GitHub Pages serves the site under /<repo>/. Change if you deploy elsewhere
  // (root domain → set base to '/').
  base: '/laravel-modular/',

  lastUpdated: true,
  cleanUrls: true,

  // The href must include `base` above. Update both together if you rename the repo.
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/laravel-modular/favicon.svg' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/getting-started/introduction', activeMatch: '/' },
      {
        // Version dropdown — add older versions here as the package evolves.
        text: 'v1.3',
        items: [
          { text: '1.3 (current)', link: '/getting-started/introduction' },
          { text: 'Changelog', link: '/getting-started/changelog' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Introduction', link: '/getting-started/introduction' },
          { text: 'Installation', link: '/getting-started/installation' },
          { text: 'Configuration', link: '/getting-started/configuration' },
          { text: 'Upgrade guide', link: '/getting-started/upgrade' },
          { text: 'Changelog', link: '/getting-started/changelog' },
        ],
      },
      {
        text: 'Basic Usage',
        items: [
          { text: 'Creating a module', link: '/basic-usage/creating-a-module' },
          { text: 'Configuring a module', link: '/basic-usage/attributes' },
          { text: 'Contract modules', link: '/basic-usage/contract-modules' },
        ],
      },
      {
        text: 'Recipes',
        items: [
          { text: 'Common tasks', link: '/recipes' },
          { text: 'FAQ & Troubleshooting', link: '/troubleshooting' },
        ],
      },
      {
        text: 'Advanced',
        items: [
          { text: 'How it works', link: '/advanced/how-it-works' },
          { text: 'Performance & caching', link: '/advanced/performance' },
          { text: 'Artisan commands', link: '/advanced/commands' },
          { text: 'Runtime API', link: '/advanced/runtime-api' },
          { text: 'Customising behaviour', link: '/advanced/extending-the-core' },
        ],
      },
      {
        text: 'Promotion',
        items: [
          { text: 'Promote to a package', link: '/promotion/promote-to-package' },
          { text: 'Private distribution', link: '/promotion/private-distribution' },
        ],
      },
      {
        text: 'Examples',
        items: [
          { text: 'A Blog module', link: '/examples/blog-module' },
        ],
      },
    ],

    search: {
      provider: 'local',
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/dem1-off/laravel-modular' },
      { icon: 'linkedin', link: 'https://www.linkedin.com/in/dmytro-dohryk-04532a187/' },
    ],

    editLink: {
      pattern: 'https://github.com/dem1-off/laravel-modular/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Built by <a href="https://www.linkedin.com/in/dmytro-dohryk-04532a187/">Dmytro Dohryk</a>',
    },
  },
})