import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// Project site on GitHub Pages: https://albertoarena.github.io/filament-event-sourcing
export default defineConfig({
  site: 'https://albertoarena.github.io',
  base: '/filament-event-sourcing',
  integrations: [
    starlight({
      title: 'Filament Event Sourcing',
      description: 'Integrate spatie/laravel-event-sourcing with Filament admin panels.',
      social: {
        github: 'https://github.com/albertoarena/filament-event-sourcing',
      },
      editLink: {
        baseUrl: 'https://github.com/albertoarena/filament-event-sourcing/edit/main/website/',
      },
      customCss: ['./src/styles/custom.css'],
      sidebar: [
        {
          label: 'Introduction',
          items: [{ label: 'Overview', link: '/' }],
        },
        {
          label: 'Getting Started',
          items: [{ label: 'Installation', link: '/installation/' }],
        },
        {
          label: 'Guide',
          items: [
            { label: 'Write bridge', link: '/write-bridge/' },
            { label: 'Audit tooling', link: '/audit-tooling/' },
            { label: 'Replay page', link: '/replay-page/' },
          ],
        },
        {
          label: 'Reference',
          items: [{ label: 'Configuration', link: '/configuration/' }],
        },
      ],
    }),
  ],
});
