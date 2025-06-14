# Laravel Converse Documentation

This directory contains the VitePress documentation for Laravel Converse.

## Local Development

1. Install dependencies:
```bash
npm install
```

2. Start the development server:
```bash
npm run docs:dev
```

3. Visit `http://localhost:5173` to view the documentation.

## Building for Production

Build the static documentation:
```bash
npm run docs:build
```

The built files will be in `.vitepress/dist/`.

## Deployment to Netlify

### Manual Deployment

1. Create a new site on [Netlify](https://app.netlify.com)
2. Connect your GitHub repository
3. Configure build settings:
   - **Base directory**: `docs`
   - **Build command**: `npm install && npm run docs:build`
   - **Publish directory**: `docs/.vitepress/dist`
   - **Node version**: 18 (set in Environment variables)

### Automatic Deployment

The repository includes a `netlify.toml` configuration file that automatically configures the build settings. Simply connect your repository to Netlify and deployments will happen automatically on every push to the main branch.

### Environment Variables

No environment variables are required for the documentation build.

## Documentation Structure

```
docs/
├── .vitepress/          # VitePress configuration
│   └── config.js        # Main config file
├── guide/               # User guide
│   ├── getting-started.md
│   ├── installation.md
│   └── ...
├── api/                 # API reference
│   ├── conversations.md
│   ├── messages.md
│   └── ...
├── examples/            # Code examples
│   ├── basic-chat.md
│   ├── streaming.md
│   └── ...
└── index.md            # Homepage
```

## Adding New Pages

1. Create a new `.md` file in the appropriate directory
2. Add the page to the sidebar in `.vitepress/config.js`
3. Link to the page from other relevant documentation

## Writing Guidelines

- Use clear, concise language
- Include code examples for all features
- Add TypeScript types where applicable
- Test all code examples
- Keep the documentation up to date with package changes

## Useful Commands

From the root directory:
- `npm run docs:dev` - Start development server
- `npm run docs:build` - Build for production
- `npm run docs:preview` - Preview production build locally 