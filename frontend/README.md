# Summit v2 — Frontend (Web)

Aplikasi Web Multi-Role untuk platform Summit (Marketplace Tiket Pendakian Gunung) yang mencakup:
- **Pendaki (Web Version)** (`/pendaki`)
- **Mitra Basecamp Portal** (`/mitra`)
- **Admin Central Portal** (`/admin`)

## Tech Stack
- **Framework**: [Vue 3](https://vuejs.org/) + [Vite](https://vite.dev/) + TypeScript
- **Component Library**: [shadcn-vue](https://www.shadcn-vue.com/) (Reka UI / Lucide Icons)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com/)
- **Routing & State**: Vue Router 4 + Pinia
- **AI / MCP Integration**: MCP server `shadcn-vue` configured via `opencode.json`

## Development Scripts

```bash
# Workdir: frontend/

# Install dependencies
npm install

# Start development server
npm run dev

# Type check & production build
npm run build

# Preview build output
npm run preview
```

## Adding Components via shadcn-vue CLI

```bash
# Workdir: frontend/
npx shadcn-vue@latest add <component-name>
```

## MCP Configuration

Konfigurasi MCP Server shadcn-vue tersimpan di [opencode.json](file:///D:/laragon/www/summit-v2/frontend/opencode.json):
```json
{
  "$schema": "https://opencode.ai/config.json",
  "mcp": {
    "shadcnVue": {
      "type": "local",
      "enabled": true,
      "command": [
        "npx",
        "shadcn-vue@latest",
        "mcp"
      ]
    }
  }
}
```
