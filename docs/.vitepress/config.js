import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Converse',
  description: 'A Laravel package for storing and managing AI conversation history',
  
  head: [
    ['link', { rel: 'icon', href: '/converse-icon.png' }],
    ['meta', { name: 'theme-color', content: '#3eaf7c' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { property: 'og:title', content: 'Converse' }],
    ['meta', { property: 'og:description', content: 'A Laravel package for storing and managing AI conversation history with any LLM provider' }],
  ],

  themeConfig: {
    logo: '/converse-icon.png',
    
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/conversations' }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is Converse?', link: '/guide/what-is-converse' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Setup', link: '/guide/getting-started' }
          ]
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Conversations', link: '/guide/conversations' },
            { text: 'Messages', link: '/guide/messages' },
            { text: 'Streaming', link: '/guide/streaming' },
            { text: 'Events', link: '/guide/events' }
          ]
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Conditional Logic', link: '/guide/conditional-logic' },
            { text: 'View Support', link: '/guide/view-support' },
            { text: 'Bulk Operations', link: '/guide/bulk-operations' },
            { text: 'Advanced Usage', link: '/guide/advanced' },
            { text: 'Soft Deletes', link: '/guide/soft-deletes' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Conversations', link: '/api/conversations' },
            { text: 'Messages', link: '/api/messages' },
            { text: 'Message Chunks', link: '/api/message-chunks' },
            { text: 'Traits', link: '/api/traits' },
            { text: 'Events', link: '/api/events' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/elliottlawson/converse' }
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024 Elliott Lawson'
    },

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/elliottlawson/converse/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    }
  }
}) 