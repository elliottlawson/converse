import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Laravel Converse',
  description: 'A Laravel package for storing and managing AI conversation history',
  
  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#3eaf7c' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { property: 'og:title', content: 'Laravel Converse' }],
    ['meta', { property: 'og:description', content: 'A Laravel package for storing and managing AI conversation history with any LLM provider' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/conversations' },
      { text: 'Examples', link: '/examples/basic-chat' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/elliottlawson/converse' },
          { text: 'Packagist', link: 'https://packagist.org/packages/elliottlawson/converse' }
        ]
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is Laravel Converse?', link: '/guide/what-is-converse' },
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Installation', link: '/guide/installation' }
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
            { text: 'Soft Deletes', link: '/guide/soft-deletes' },
            { text: 'Broadcasting', link: '/guide/broadcasting' }
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
      ],
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Basic Chat', link: '/examples/basic-chat' },
            { text: 'Streaming Responses', link: '/examples/streaming' },
            { text: 'Function Calling', link: '/examples/function-calling' },
            { text: 'Multi-Provider Setup', link: '/examples/multi-provider' },
            { text: 'Real-time Updates', link: '/examples/real-time' }
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